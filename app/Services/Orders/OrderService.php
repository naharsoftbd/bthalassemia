<?php

namespace App\Services\Order;

use App\Interfaces\Order\OrderRepositoryInterface;
use App\Models\Order;

class OrderService
{
    protected $orderRepo;

    public function __construct(OrderRepositoryInterface $orderRepo)
    {
        $this->orderRepo = $orderRepo;
    }

    public function getAllOrders()
    {
        return $this->orderRepo->all();
    }

    public function getOrder(int $id)
    {
        return $this->orderRepo->find($id);
    }

    public function createOrder(array $data): Order
    {
        return $this->orderRepo->create($data);
    }

    public function updateOrderStatus(Order $order, string $status): Order
    {
        return $this->orderRepo->updateStatus($order, $status);
    }
}
