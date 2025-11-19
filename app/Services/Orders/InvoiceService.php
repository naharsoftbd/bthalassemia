<?php

namespace App\Services\Orders;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class InvoiceService
{
    /**
     * Generate PDF invoice for order
     */
    public function generateInvoice(Order $order, bool $download = false)
    {
        // Load order with necessary relationships
        $order->load([
            'items.vendor',
            'items.product',
            'items.variant',
            'user',
        ]);

        $data = [
            'order' => $order,
            'invoice_number' => $this->generateInvoiceNumber($order),
            'invoice_date' => now()->format('F j, Y'),
            'due_date' => now()->addDays(30)->format('F j, Y'),
            'company' => $this->getCompanyInfo(),
        ];

        $pdf = PDF::loadView('pdf.invoice', $data);

        if ($download) {
            return $pdf->download("invoice-{$order->order_number}.pdf");
        }

        // Save to storage
        $filename = "invoices/invoice-{$order->order_number}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        return [
            'url' => Storage::url($filename),
            'filename' => $filename,
            'invoice_number' => $data['invoice_number'],
        ];
    }

    /**
     * Generate invoice number
     */
    protected function generateInvoiceNumber(Order $order): string
    {
        return 'INV-'.$order->order_number.'-'.date('Ymd');
    }

    /**
     * Get company information
     */
    protected function getCompanyInfo(): array
    {
        return [
            'name' => config('app.name', 'Your Company Name'),
            'address' => '123 Business Street, City, State 12345',
            'phone' => '+1 (555) 123-4567',
            'email' => 'billing@example.com',
            'website' => 'https://example.com',
            'tax_id' => 'TAX-123456789',
        ];
    }

    /**
     * Generate vendor-specific invoice
     */
    public function generateVendorInvoice(Order $order, int $vendorId, bool $download = false)
    {
        $order->load([
            'items' => function ($query) use ($vendorId) {
                $query->where('vendor_id', $vendorId)
                    ->with(['vendor', 'product', 'variant']);
            },
            'items.vendor',
            'items.product',
            'items.variant',
            'user',
        ]);

        $vendor = $order->items->first()->vendor ?? null;

        $data = [
            'order' => $order,
            'vendor' => $vendor,
            'invoice_number' => $this->generateVendorInvoiceNumber($order, $vendor),
            'invoice_date' => now()->format('F j, Y'),
            'company' => $this->getCompanyInfo(),
            'is_vendor_invoice' => true,
        ];

        $pdf = PDF::loadView('pdf.vendor-invoice', $data);

        if ($download) {
            return $pdf->download("vendor-invoice-{$order->order_number}.pdf");
        }

        $filename = "invoices/vendor-invoice-{$order->order_number}-{$vendorId}.pdf";
        Storage::disk('public')->put($filename, $pdf->output());

        return [
            'url' => Storage::url($filename),
            'filename' => $filename,
            'invoice_number' => $data['invoice_number'],
        ];
    }

    /**
     * Generate vendor invoice number
     */
    protected function generateVendorInvoiceNumber(Order $order, $vendor): string
    {
        $vendorCode = strtoupper(substr($vendor->business_name ?? 'VENDOR', 0, 3));

        return 'VINV-'.$vendorCode.'-'.$order->order_number;
    }

    /**
     * Check if invoice exists
     */
    public function invoiceExists(Order $order): bool
    {
        $filename = "invoices/invoice-{$order->order_number}.pdf";

        return Storage::disk('public')->exists($filename);
    }

    /**
     * Get invoice URL
     */
    public function getInvoiceUrl(Order $order): ?string
    {
        $filename = "invoices/invoice-{$order->order_number}.pdf";

        return Storage::disk('public')->exists($filename)
            ? Storage::url($filename)
            : null;
    }

    /**
     * Delete invoice
     */
    public function deleteInvoice(Order $order): bool
    {
        $filename = "invoices/invoice-{$order->order_number}.pdf";

        return Storage::disk('public')->delete($filename);
    }
}
