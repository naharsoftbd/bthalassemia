<?php 

namespace App\Interfaces\Orders;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function all();
    public function find(int $id): ?Order;
    public function create(array $data): Order;
    public function updateStatus(Order $order, string $status): Order;
}
