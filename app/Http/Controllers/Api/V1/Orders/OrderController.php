<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\Orders\OrderService;
use App\Models\Order;

class OrderController extends Controller
{
    protected $service;

    public function __construct(OrderService $service)
    {
        $this->service = $service;
        $this->middleware('auth:api');
    }

    public function index()
    {
        return response()->json($this->service->getAllOrders());
    }

    public function show($id)
    {
        return response()->json($this->service->getOrder($id));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0'
        ]);

        $data['user_id'] = auth('api')->id();

        $order = $this->service->createOrder($data);

        return response()->json($order, 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,processing,shipped,delivered,cancelled'
        ]);

        $order = Order::findOrFail($id);

        $order = $this->service->updateOrderStatus($order, $request->status);

        return response()->json($order);
    }
}
