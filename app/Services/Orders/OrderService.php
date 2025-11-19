<?php

namespace App\Services\Orders;

use App\Interfaces\Orders\OrderRepositoryInterface;
use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    protected $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * Get orders for authenticated user with filters
     */
    public function getOrders(User $user, array $filters = []): LengthAwarePaginator
    {
        return $this->orderRepository->getOrdersForUser($user, $filters);
    }

    /**
     * Get specific order with authorization
     */
    public function getOrder(User $user, $orderId): ?Order
    {
        return $this->orderRepository->getOrderForUser($user, $orderId);
    }

    /**
     * Create a new order
     */
    public function createOrder(User $user, array $orderData): Order
    {
        // Validate stock availability before creating order
        $this->validateStockAvailability($orderData['items']);

        return $this->orderRepository->createOrder($user, $orderData);
    }

    /**
     * Update order
     */
    public function updateOrder(User $user, $orderId, array $data): Order
    {
        $order = $this->orderRepository->getOrderForUser($user, $orderId);
        
        if (!$order) {
            throw new \Exception('Order not found or unauthorized.');
        }

        $order->update($data);
        
        return $order->fresh();
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(User $user, $orderId, string $status, ?string $notes = null): array
    {
        return $this->orderRepository->updateOrderStatus($user, $orderId, $status, $notes);
    }

    /**
     * Cancel an order
     */
    public function confirmOrder(User $user, $orderId, ?string $reason = null): array
    {
        return $this->orderRepository->confirmOrder($user, $orderId, $reason);
    }

    /**
     * Cancel an order
     */
    public function cancelOrder(User $user, $orderId, ?string $reason = null): array
    {
        return $this->orderRepository->cancelOrder($user, $orderId, $reason);
    }


    /**
     * Get vendor's order items
     */
    public function getVendorOrderItems(User $user, array $filters = []): LengthAwarePaginator
    {
        if (!$user->hasRole('vendor')) {
            abort(403, 'Unauthorized action.');
        }

        return $this->orderRepository->getVendorOrderItems($user, $filters);
    }

    /**
     * Process order payment
     */
    public function processPayment(User $user, $orderId, array $paymentData): array
    {
        $order = $this->orderRepository->getOrderForUser($user, $orderId);

        if (!$order) {
            return [
                'success' => false,
                'message' => 'Order not found.'
            ];
        }

        if ($order->payment_status === 'paid') {
            return [
                'success' => false,
                'message' => 'Order is already paid.'
            ];
        }

        // Process payment logic here (integrate with payment gateway)
        $paymentSuccess = $this->processPaymentGateway($paymentData);

        if ($paymentSuccess) {
            $order->update([
                'payment_status' => 'paid',
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'payment_method' => $paymentData['payment_method'] ?? 'card',
            ]);

            // Update order status to confirmed if payment successful
            if ($order->status === 'pending') {
                $order->update([
                    'status' => 'confirmed',
                    'confirmed_at' => now(),
                ]);
            }

            return [
                'success' => true,
                'message' => 'Payment processed successfully.',
                'order' => $order->fresh()
            ];
        }

        return [
            'success' => false,
            'message' => 'Payment processing failed.'
        ];
    }

    /**
     * Validate stock availability for order items
     */
    protected function validateStockAvailability(array $items): void
    {
        foreach ($items as $item) {
            $productVariant = \App\Models\ProductVariant::find($item['product_variant_id']);
            
            if (!$productVariant || $productVariant->stock < $item['quantity']) {
                throw new \Exception("Insufficient stock for product: {$item['product_name']}");
            }
        }
    }

    /**
     * Update stock after order creation/cancellation
     */
    public function updateStock(Order $order, string $action = 'decrement'): void
    {
        foreach ($order->items as $item) {
            if ($item->product_variant_id) {
                $variant = \App\Models\ProductVariant::find($item->product_variant_id);
                
                if ($variant) {
                    if ($action === 'decrement') {
                        $variant->decrement('stock', $item->quantity);
                    } elseif ($action === 'increment') {
                        $variant->increment('stock', $item->quantity);
                    }
                }
            }
        }
    }


    /**
     * Process payment with payment gateway (placeholder)
     */
    protected function processPaymentGateway(array $paymentData): bool
    {
        // Integrate with your payment gateway here
        // This is a placeholder - implement actual payment processing
        return true; // Simulate successful payment
    }


    /**
     * Delete order
     */
    public function deleteOrder(User $user, $orderId): bool
    {
        $order = $this->orderRepository->getOrderForUser($user, $orderId);
        
        if (!$order) {
            throw new \Exception('Order not found or unauthorized.');
        }

        // Only allow deletion of pending orders
        if ($order->status !== 'pending') {
            throw new \Exception('Only pending orders can be deleted.');
        }

        return $order->delete();
    }
}
