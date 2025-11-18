<?php

namespace App\Repositories\Orders;

use App\Interfaces\Orders\OrderRepositoryInterface;
use App\Models\Order;

class OrderRepository implements OrderRepositoryInterface
{
    public function all()
    {
        return Order::with('items.product')->get();
    }

    public function find(int $id): ?Order
    {
        return Order::with('items.product')->find($id);
    }

    public function create(array $data): Order
    {
        $order = Order::create([
            'user_id' => $data['user_id'],
            'total' => 0
        ]);

        $total = 0;
        foreach ($data['items'] as $item) {
            $subtotal = $item['quantity'] * $item['price'];
            $order->items()->create([
                'product_id' => $item['product_id'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'subtotal' => $subtotal
            ]);
            $total += $subtotal;
        }

        $order->update(['total' => $total]);

        return $order->load('items.product');
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        if (!in_array($status, $allowed)) {
            throw new \InvalidArgumentException("Invalid status");
        }

        $order->update(['status' => $status]);
        return $order;
    }
}
