<?php

namespace App\Interfaces\Orders;

use App\Models\Order;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface OrderRepositoryInterface
{
    /**
     * Get orders based on user role with filters
     */
    public function getOrdersForUser(User $user, array $filters = []): LengthAwarePaginator;

    /**
     * Get order details with proper authorization
     */
    public function getOrderForUser(User $user, $orderId): ?Order;

    /**
     * Create a new order
     */
    public function createOrder(User $user, array $data): Order;

    /**
     * Update order status
     */
    public function updateOrderStatus(User $user, $orderId, string $status, ?string $notes = null): bool;

    /**
     * Confirm an order
     */
    public function confirmOrder(User $user, $orderId, ?string $notes = null): array;

    /**
     * Cancel an order Vendor Items
     */
    public function cancelVendorOrderItems(User $user, $orderId, ?string $reason = null): array;

    /**
     * Cancel an order
     */
    public function cancelOrder(User $user, $orderId, ?string $reason = null): array;

    /**
     * Get orders by vendor ID
     */
    public function getOrdersByVendor(User $user, $vendorId, array $filters = []): LengthAwarePaginator;

    /**
     * Get order items for vendor
     */
    public function getVendorOrderItems(User $user, array $filters = []): LengthAwarePaginator;
}
