<?php

namespace App\Repositories\Orders;

use App\Interfaces\Orders\OrderRepositoryInterface;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Get orders based on user role
     */
    public function getOrdersForUser(User $user, array $filters = []): LengthAwarePaginator
    {
        $query = Order::with([
            'items' => function ($query) use ($user) {
                // For vendors, only load their items
                if ($user->hasRole('vendor') && $user->vendor) {
                    $query->where('vendor_id', $user->vendor->id);
                }
                $query->with(['vendor', 'product']);
            },
            'user'
        ]);
       
        // Role-based filtering
        if ($user->hasRole('vendor')) {
            $query->whereHas('items', function ($q) use ($user) {
                $q->where('vendor_id', $user->vendor->id);
            });
        } elseif ($user->hasRole('customer')) {
            $query->where('user_id', $user->id);
        }
        // Admin can see all orders (no additional filter)

        // Apply filters
        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get order details with proper authorization
     */
    public function getOrderForUser(User $user, $orderId): ?Order
    {
        $order = Order::with([
            'items.vendor', 
            'items.product', 
            'items.variant',
            'user'
        ])->find($orderId);

        if (!$order) {
            return null;
        }

        // Authorization checks
        if ($user->hasRole('vendor')) {
            if (!$order->items->where('vendor_id', $user->vendor->id)->count()) {
                return null;
            }
        } elseif ($user->hasRole('customer')) {
            if ($order->user_id !== $user->id) {
                return null;
            }
        }

        return $order;
    }

    /**
     * Create a new order
     */
    public function createOrder(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(uniqid());
            
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => $orderNumber,
                'status' => 'pending',
                'subtotal' => $data['subtotal'],
                'tax_amount' => $data['tax_amount'] ?? 0,
                'shipping_cost' => $data['shipping_cost'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'total' => $data['total'],
                'customer_email' => $user->email,
                'customer_phone' => $data['customer_phone'] ?? null,
                'shipping_address' => $data['shipping_address'],
                'billing_address' => $data['billing_address'] ?? $data['shipping_address'],
                'payment_method' => $data['payment_method'] ?? null,
                'customer_notes' => $data['customer_notes'] ?? null,
            ]);

            // Create order items
            foreach ($data['items'] as $item) {
                $order->items()->create([
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'vendor_id' => $item['vendor_id'],
                    'product_name' => $item['product_name'],
                    'variant_name' => $item['variant_name'] ?? null,
                    'sku' => $item['sku'],
                    'attributes' => $item['attributes'] ?? null,
                    'unit_price' => $item['unit_price'],
                    'compare_at_price' => $item['compare_at_price'] ?? null,
                    'quantity' => $item['quantity'],
                    'total_price' => $item['unit_price'] * $item['quantity'],
                ]);
            }

            return $order->load('items');
        });
    }

    /**
     * Update order status (Admin/Vendor)
     */
    public function updateOrderStatus(User $user, $orderId, string $status, ?string $notes = null): bool
    {
        $order = $this->getOrderForUser($user, $orderId);
        
        if (!$order) {
            return false;
        }

        $updateData = ['status' => $status];
        
        // Set timestamp based on status
        $timestampField = $status . '_at';
        if (in_array($timestampField, ['processing_at', 'shipped_at', 'delivered_at', 'cancelled_at'])) {
            $updateData[$timestampField] = now();
        }

        if ($notes && $user->hasRole('admin')) {
            $updateData['admin_notes'] = $notes;
        }

        return $order->update($updateData);
    }

    /**
     * Confirm order and deduct inventory
     */
    public function confirmOrder(User $user, $orderId, ?string $notes = null): array
    {
        return DB::transaction(function () use ($user, $orderId, $notes) {
            $order = $this->getOrderForUser($user, $orderId);
            
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Order not found or unauthorized.'
                ];
            }

            // Check if order can be confirmed
            if ($order->status !== 'Pending') {
                return [
                    'success' => false,
                    'message' => 'Only pending orders can be confirmed.'
                ];
            }

            // Validate stock before confirmation
            $stockValidation = $this->validateStockAvailability($order->items);
            if (!$stockValidation['success']) {
                return $stockValidation;
            }

            // Update order status
            $order->update([
                'status' => 'Confirmed',
                'confirmed_at' => now(),
                'admin_notes' => $notes ? "Confirmed: {$notes}" : null,
            ]);

            // Deduct inventory
            $this->updateInventory($order, 'deduct');

            return [
                'success' => true,
                'data' => $order->fresh(),
                'message' => 'Order confirmed and inventory updated successfully.'
            ];
        });
    }

    /**
     * Cancel order and restore inventory
     */
    public function cancelOrder(User $user, $orderId, ?string $reason = null): array
    {
        return DB::transaction(function () use ($user, $orderId, $reason) {
            $order = $this->orderRepository->getOrderForUser($user, $orderId);
            
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Order not found or unauthorized.'
                ];
            }

            // Check if order can be cancelled
            if (!in_array($order->status, ['pending', 'confirmed', 'processing'])) {
                return [
                    'success' => false,
                    'message' => 'Order cannot be cancelled in its current status.'
                ];
            }

            // Store previous status for logging
            $previousStatus = $order->status;

            // Update order status
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'admin_notes' => $reason ? "Cancelled from {$previousStatus}: {$reason}" : null,
            ]);

            // Restore inventory only if order was confirmed/processing
            if (in_array($previousStatus, ['confirmed', 'processing'])) {
                $this->updateInventory($order, 'restore');
            }

            return [
                'success' => true,
                'data' => $order->fresh(),
                'message' => 'Order cancelled and inventory restored successfully.'
            ];
        });
    }

    /**
     * Get orders by vendor ID (Admin only)
     */
    public function getOrdersByVendor(User $user, $vendorId, array $filters = []): LengthAwarePaginator
    {
        if (!$user->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $query = Order::whereHas('items', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        });

        $this->applyFilters($query, $filters);

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Get order items for vendor
     */
    public function getVendorOrderItems(User $user, array $filters = []): LengthAwarePaginator
    {
        if (!$user->hasRole('vendor') || !$user->vendor) {
            abort(403, 'Unauthorized action.');
        }

        $query = OrderItem::with(['order.user', 'product', 'variant'])
            ->where('vendor_id', $user->vendor->id);

        // Apply item-level filters
        if (!empty($filters['fulfillment_status'])) {
            $query->where('fulfillment_status', $filters['fulfillment_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    /**
     * Apply filters to query
     */
    protected function applyFilters($query, array $filters): void
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('order_number', 'like', "%{$filters['search']}%")
                  ->orWhere('customer_email', 'like', "%{$filters['search']}%")
                  ->orWhereHas('user', function($q) use ($filters) {
                      $q->where('name', 'like', "%{$filters['search']}%");
                  });
            });
        }
    }

    // Inventory Update 

    /**
     * Update inventory based on action
     */
    public function updateInventory(Order $order, string $action): void
    {
        foreach ($order->items as $item) {
            if ($item->product_variant_id) {
                $this->updateVariantInventory($item, $action);
            } else {
                $this->updateProductInventory($item, $action);
            }

            // Log inventory change
            $this->logInventoryChange($item, $action, $order);
        }
    }

    /**
     * Update variant inventory
     */
    protected function updateVariantInventory(OrderItem $item, string $action): void
    {
        $variant = \App\Models\ProductVariant::find($item->product_variant_id);
        
        if (!$variant) {
            return;
        }

        if ($action === 'deduct') {
            $variant->decrement('stock', $item->quantity);
            
            // Check low stock threshold
            if ($variant->stock <= $variant->low_stock_threshold) {
                $this->notifyLowStock($variant);
            }
        } elseif ($action === 'restore') {
            $variant->increment('stock', $item->quantity);
        }

        $variant->touch(); // Update timestamp
    }

    /**
     * Update product inventory (if no variants)
     */
    protected function updateProductInventory(OrderItem $item, string $action): void
    {
        $product = \App\Models\Product::find($item->product_id);
        
        if (!$product) {
            return;
        }

        // For products without variants, you might want to track stock at product level
        // This depends on your business logic
        if ($action === 'deduct') {
            // Implement product-level stock deduction if needed
        } elseif ($action === 'restore') {
            // Implement product-level stock restoration if needed
        }
    }

    /**
     * Validate stock availability before order confirmation
     */
    protected function validateStockAvailability($items): array
    {
        $outOfStockItems = [];
        
        foreach ($items as $item) {
            if ($item->product_variant_id) {
                $variant = \App\Models\ProductVariant::find($item->product_variant_id);
                
                if (!$variant) {
                    $outOfStockItems[] = "Variant not found for: {$item->product_name}";
                    continue;
                }

                if ($variant->stock < $item->quantity) {
                    $outOfStockItems[] = "{$item->product_name}: Requested {$item->quantity}, Available {$variant->stock}";
                }
            }
        }

        if (!empty($outOfStockItems)) {
            return [
                'success' => false,
                'message' => 'Insufficient stock for some items.',
                'out_of_stock_items' => $outOfStockItems
            ];
        }

        return ['success' => true];
    }

     /**
     * Log inventory changes
     */
    protected function logInventoryChange(OrderItem $item, string $action, Order $order): void
    {
        \App\Models\InventoryLog::create([
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'order_id' => $order->id,
            'quantity' => $action === 'deduct' ? -$item->quantity : $item->quantity,
            'action' => $action,
            'reason' => $action === 'deduct' ? 'order_confirmation' : 'order_cancellation',
            'notes' => "Order {$order->order_number} - {$action}",
            'previous_stock' => $this->getPreviousStock($item),
            'new_stock' => $this->getCurrentStock($item),
        ]);
    }

    /**
     * Get previous stock level
     */
    protected function getPreviousStock(OrderItem $item): ?int
    {
        if ($item->product_variant_id) {
            $variant = \App\Models\ProductVariant::find($item->product_variant_id);
            return $variant ? $variant->stock + ($item->quantity) : null;
        }
        return null;
    }

    /**
     * Get current stock level
     */
    protected function getCurrentStock(OrderItem $item): ?int
    {
        if ($item->product_variant_id) {
            $variant = \App\Models\ProductVariant::find($item->product_variant_id);
            return $variant ? $variant->stock : null;
        }
        return null;
    }

    /**
     * Notify about low stock
     */
    protected function notifyLowStock(\App\Models\ProductVariant $variant): void
    {
        // Implement low stock notification
        // This could be: email to admin, notification in dashboard, etc.
        
        \Log::warning("Low stock alert for variant: {$variant->sku}, Current stock: {$variant->stock}");
        
        // Example: Send notification to admin
        // Notification::send($adminUsers, new LowStockNotification($variant));
    }

    /**
     * Force cancel order (admin only) - cancels regardless of status
     */
    public function forceCancelOrder(User $user, $orderId, ?string $reason = null): array
    {
        if (!$user->hasRole('admin')) {
            return [
                'success' => false,
                'message' => 'Unauthorized action.'
            ];
        }

        return DB::transaction(function () use ($user, $orderId, $reason) {
            $order = $this->orderRepository->getOrderForUser($user, $orderId);
            
            if (!$order) {
                return [
                    'success' => false,
                    'message' => 'Order not found.'
                ];
            }

            $previousStatus = $order->status;

            // Update order status
            $order->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'admin_notes' => $reason ? "Force cancelled from {$previousStatus}: {$reason}" : null,
            ]);

            // Restore inventory if order was confirmed/processing/shipped
            if (in_array($previousStatus, ['confirmed', 'processing', 'shipped'])) {
                $this->updateInventory($order, 'restore');
            }

            return [
                'success' => true,
                'data' => $order->fresh(),
                'message' => 'Order force cancelled successfully.'
            ];
        });
    }
}
