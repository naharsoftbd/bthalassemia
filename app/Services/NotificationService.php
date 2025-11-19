<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Notifications\OrderCreatedNotification;
use App\Notifications\OrderStatusUpdatedNotification;
use App\Notifications\VendorOrderItemStatusNotification;
use App\Notifications\VendorOrderNotification;
use Illuminate\Support\Facades\Notification;

class NotificationService
{
    /**
     * Send order created notifications
     */
    public function sendOrderCreatedNotifications(Order $order): void
    {
        // Notify customer
        $order->user->notify(new OrderCreatedNotification($order));

        // Notify admin users
        $adminUsers = User::role('admin')->get();
        Notification::send($adminUsers, new OrderCreatedNotification($order));

        // Notify vendors
        $this->notifyVendorsAboutNewOrder($order);
    }

    /**
     * Send order status update notifications
     */
    public function sendOrderStatusUpdateNotifications(Order $order, string $previousStatus, string $newStatus): void
    {
        // Notify customer
        $order->user->notify(new OrderStatusUpdatedNotification($order, $previousStatus, $newStatus));

        // Notify admin users for significant status changes
        if (in_array($newStatus, ['Cancelled', 'Shipped', 'Delivered'])) {
            $adminUsers = User::role('admin')->get();
            Notification::send($adminUsers, new OrderStatusUpdatedNotification($order, $previousStatus, $newStatus));
        }

        // Notify vendors if order is cancelled
        if ($newStatus === 'Cancelled') {
            $this->notifyVendorsAboutOrderCancellation($order, $previousStatus);
        }
    }

    /**
     * Send vendor order item status update notifications
     */
    public function sendVendorOrderItemStatusUpdate(Order $order, int $vendorId, string $previousStatus, string $newStatus): void
    {
        $vendorItems = $order->items->where('vendor_id', $vendorId);

        foreach ($vendorItems as $item) {
            // Notify customer about vendor-specific updates
            if (in_array($newStatus, ['Shipped', 'Cancelled'])) {
                $order->user->notify(new VendorOrderItemStatusNotification($item, $previousStatus, $newStatus));
            }
        }
    }

    /**
     * Notify vendors about new order
     */
    protected function notifyVendorsAboutNewOrder(Order $order): void
    {
        $vendors = $order->items->pluck('vendor_id')->unique();

        foreach ($vendors as $vendorId) {
            $vendor = Vendor::with('user')->find($vendorId);
            if ($vendor && $vendor->user) {
                $vendor->user->notify(new VendorOrderNotification($order, $vendor));
            }
        }
    }

    /**
     * Notify vendors about order cancellation
     */
    protected function notifyVendorsAboutOrderCancellation(Order $order, string $previousStatus): void
    {
        $vendors = $order->items->pluck('vendor_id')->unique();

        foreach ($vendors as $vendorId) {
            $vendor = Vendor::with('user')->find($vendorId);
            if ($vendor && $vendor->user) {
                $vendor->user->notify(new OrderStatusUpdatedNotification($order, $previousStatus, 'Cancelled'));
            }
        }
    }

    /**
     * Send low stock notifications to vendors
     */
    public function sendLowStockNotification(Vendor $vendor, $productVariant, $currentStock): void
    {
        if ($vendor->user) {
            $vendor->user->notify(new \App\Notifications\LowStockNotification($productVariant, $currentStock));
        }
    }

    /**
     * Send payment status update notifications
     */
    public function sendPaymentStatusUpdateNotifications(Order $order, string $previousStatus, string $newStatus): void
    {
        // Notify customer
        $order->user->notify(new \App\Notifications\PaymentStatusUpdatedNotification($order, $previousStatus, $newStatus));

        // Notify admin for failed payments
        if ($newStatus === 'failed') {
            $adminUsers = User::role('admin')->get();
            Notification::send($adminUsers, new \App\Notifications\PaymentStatusUpdatedNotification($order, $previousStatus, $newStatus));
        }
    }
}
