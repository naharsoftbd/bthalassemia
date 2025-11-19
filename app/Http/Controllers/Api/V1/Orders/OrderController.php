<?php

namespace App\Http\Controllers\Api\V1\Orders;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Orders\StoreOrderRequest;
use App\Http\Requests\Api\V1\Orders\UpdateOrderRequest;
use App\Services\Orders\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->getOrders(auth()->user(), $request->all());

        return response()->json([
            'success' => true,
            'data' => $orders,
            'message' => 'Orders retrieved successfully.',
        ]);
    }

    public function show($id): JsonResponse
    {
        $order = $this->orderService->getOrder(auth()->user(), $id);

        if (! $order) {
            return response()->json([
                'success' => false,
                'message' => 'Order not found or unauthorized.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $order,
            'message' => 'Order retrieved successfully.',
        ]);
    }

    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $order = $this->orderService->createOrder(auth()->user(), $request->validated());

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order created successfully.',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function update(UpdateOrderRequest $request, $id): JsonResponse
    {
        try {
            $order = $this->orderService->updateOrder(auth()->user(), $id, $request->validated());

            return response()->json([
                'success' => true,
                'data' => $order,
                'message' => 'Order updated successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function updateStatus(Request $request, $id): JsonResponse
    {
        $result = $this->orderService->updateOrderStatus(
            auth()->user(),
            $id,
            $request->status,
            $request->notes
        );

        if (! $result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    /**
     * Confirm order and deduct inventory
     */
    public function confirm($id, Request $request): JsonResponse
    {
        try {
            $result = $this->orderService->confirmOrder(
                auth()->user(),
                $id,
                $request->notes
            );

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'out_of_stock_items' => $result['out_of_stock_items'] ?? null,
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => $result['message'],
            ]);
        } catch (\Exception $e) {

            \Log::error('Error confirming order', [
                'order_id' => $id,
                'user_id' => auth()->user()->id,
                'error_message' => $e->getMessage(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to confirm order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Cancel vendor's specific order items (not entire order)
     */
    public function cancelVendorItems($id, Request $request): JsonResponse
    {
        try {
            $result = $this->orderService->cancelVendorOrderItems(
                auth()->user(),
                $id,
                $request->reason
            );

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => $result['message'],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel vendor order items.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Cancel order and restore inventory
     */
    public function cancel($id, Request $request): JsonResponse
    {
        try {
            $result = $this->orderService->cancelOrder(
                auth()->user(),
                $id,
                $request->reason
            );

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => $result['message'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to cancel order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Force cancel order (admin only)
     */
    public function forceCancel($id, Request $request): JsonResponse
    {
        try {
            $result = $this->orderService->forceCancelOrder(
                auth()->user(),
                $id,
                $request->reason
            );

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                ], 400);
            }

            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'message' => $result['message'],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to force cancel order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $this->orderService->deleteOrder(auth()->user(), $id);

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete order.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
