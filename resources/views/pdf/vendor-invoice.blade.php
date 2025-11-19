<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Vendor Invoice - {{ $order->order_number }}</title>
    <style>
        /* Same styles as regular invoice, with vendor-specific modifications */
        .vendor-info { background: #f8f9fa; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <!-- Header with Vendor Info -->
        <div class="header">
            <div>
                <div class="invoice-title">VENDOR INVOICE</div>
                <div><strong>Invoice #:</strong> {{ $invoice_number }}</div>
                <div><strong>Date:</strong> {{ $invoice_date }}</div>
            </div>
            <div class="company-info">
                <strong>{{ $company['name'] }}</strong><br>
                {{ $company['address'] }}<br>
                Phone: {{ $company['phone'] }}
            </div>
        </div>

        <!-- Vendor Information -->
        @if($vendor)
        <div class="vendor-info">
            <strong>Vendor:</strong> {{ $vendor->business_name }}<br>
            <strong>Contact:</strong> {{ $vendor->contact_person }}<br>
            <strong>Phone:</strong> {{ $vendor->phone ?? 'N/A' }}
        </div>
        @endif

        <!-- Order and Customer Info -->
        <div class="details">
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <strong>Customer:</strong><br>
                    {{ $order->user->name }}<br>
                    {{ $order->customer_email }}
                </div>
                <div>
                    <strong>Order Details:</strong><br>
                    Order #: {{ $order->order_number }}<br>
                    Order Date: {{ $order->created_at->format('F j, Y') }}
                </div>
            </div>
        </div>

        <!-- Vendor Items Only -->
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>SKU</th>
                    <th class="text-right">Price</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        {{ $item->product_name }}
                        @if($item->variant_name)
                            <br><small>{{ $item->variant_name }}</small>
                        @endif
                    </td>
                    <td>{{ $item->sku }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->total_price, 2) }}</td>
                    <td>{{ ucfirst($item->fulfillment_status) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Vendor Totals -->
        @php
            $vendorSubtotal = $order->items->sum('total_price');
        @endphp
        <div class="total-section">
            <div class="total-row grand-total">
                <span>Vendor Total:</span>
                <span>${{ number_format($vendorSubtotal, 2) }}</span>
            </div>
        </div>

        <div class="footer">
            Vendor Invoice - {{ $vendor->business_name ?? 'Vendor' }}<br>
            Generated on {{ date('F j, Y \a\t g:i A') }}
        </div>
    </div>
</body>
</html>