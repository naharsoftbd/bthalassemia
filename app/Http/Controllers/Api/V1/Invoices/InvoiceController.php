<?php

namespace App\Http\Controllers\Api\V1\Invoices;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\InvoiceService;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    protected $invoiceService;

    public function __construct(InvoiceService $invoiceService)
    {
        $this->invoiceService = $invoiceService;
    }

    /**
     * Generate and download invoice
     */
    public function downloadInvoice(Order $order): mixed
    {
        try {
            // Authorization check
            if (! auth()->user()->can('view orders') &&
                ! (auth()->user()->hasRole('customer') && $order->user_id === auth()->id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view this invoice.',
                ], 403);
            }

            return $this->invoiceService->generateInvoice($order, true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Generate and get invoice URL
     */
    public function generateInvoice(Order $order): JsonResponse
    {
        try {
            if (! auth()->user()->can('view orders') &&
                ! (auth()->user()->hasRole('customer') && $order->user_id === auth()->id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to generate invoice.',
                ], 403);
            }

            $result = $this->invoiceService->generateInvoice($order);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Invoice generated successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate invoice.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Generate vendor-specific invoice
     */
    public function downloadVendorInvoice(Order $order): mixed
    {
        try {
            if (! auth()->user()->hasRole('vendor') || ! auth()->user()->vendor) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only vendors can download vendor invoices.',
                ], 403);
            }

            $vendorId = auth()->user()->vendor->id;

            // Check if vendor has items in this order
            if (! $order->items->where('vendor_id', $vendorId)->count()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No items found for your vendor account in this order.',
                ], 404);
            }

            return $this->invoiceService->generateVendorInvoice($order, $vendorId, true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate vendor invoice.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Get invoice information
     */
    public function getInvoiceInfo(Order $order): JsonResponse
    {
        try {
            if (! auth()->user()->can('view orders') &&
                ! (auth()->user()->hasRole('customer') && $order->user_id === auth()->id())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized to view invoice information.',
                ], 403);
            }

            $invoiceExists = $this->invoiceService->invoiceExists($order);
            $invoiceUrl = $this->invoiceService->getInvoiceUrl($order);

            return response()->json([
                'success' => true,
                'data' => [
                    'order_number' => $order->order_number,
                    'invoice_exists' => $invoiceExists,
                    'invoice_url' => $invoiceUrl,
                    'can_generate' => in_array($order->status, ['confirmed', 'processing', 'shipped', 'delivered']),
                ],
                'message' => 'Invoice information retrieved successfully.',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get invoice information.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
