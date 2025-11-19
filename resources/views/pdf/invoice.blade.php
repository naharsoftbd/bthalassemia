<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $order->order_number }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #eee; }
        .header { display: flex; justify-content: space-between; margin-bottom: 30px; }
        .company-info { text-align: right; }
        .invoice-title { font-size: 24px; font-weight: bold; color: #333; }
        .details { margin: 20px 0; }
        .table { width: 100%; border-collapse: collapse; }
        .table th { background: #f8f9fa; text-align: left; padding: 10px; border: 1px solid #dee2e6; }
        .table td { padding: 10px; border: 1px solid #dee2e6; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .total-section { margin-top: 20px; float: right; width: 300px; }
        .total-row { display: flex; justify-content: space-between; margin: 5px 0; }
        .grand-total { font-weight: bold; font-size: 16px; border-top: 2px solid #333; padding-top: 5px; }
        .footer { margin-top: 50px; text-align: center; color: #666; font-size: 10px; }
    </style>
</head>
<body>
    <div class="invoice-box">
        <!-- Header -->
        <div class="header">
            <div>
                <div class="invoice-title">INVOICE</div>
                <div><strong>Invoice #:</strong> {{ $invoice_number }}</div>
                <div><strong>Date:</strong> {{ $invoice_date }}</div>
                <div><strong>Due Date:</strong> {{ $due_date }}</div>
            </div>
            <div class="company-info">
                <strong>{{ $company['name'] }}</strong><br>
                {{ $company['address'] }}<br>
                Phone: {{ $company['phone'] }}<br>
                Email: {{ $company['email'] }}<br>
                Tax ID: {{ $company['tax_id'] }}
            </div>
        </div>

        <!-- Bill To -->
        <div class="details">
            <div style="display: flex; justify-content: space-between;">
                <div>
                    <strong>Bill To:</strong><br>
                    {{ $order->user->name }}<br>
                    {{ $order->customer_email }}<br>
                    {{ $order->customer_phone ?? 'N/A' }}
                </div>
                <div>
                    <strong>Order Details:</strong><br>
                    Order #: {{ $order->order_number }}<br>
                    Order Date: {{ $order->created_at->format('F j, Y') }}<br>
                    Status: {{ ucfirst($order->status) }}
                </div>
            </div>
        </div>

        <!-- Shipping Address -->
        @if($order->shipping_address)
        <div class="details">
            <strong>Shipping Address:</strong><br>
            @if(is_array($order->shipping_address))
                {{ $order->shipping_address['street'] ?? '' }}<br>
                {{ $order->shipping_address['city'] ?? '' }}, 
                {{ $order->shipping_address['state'] ?? '' }} 
                {{ $order->shipping_address['zip_code'] ?? '' }}<br>
                {{ $order->shipping_address['country'] ?? '' }}
            @else
                {{ $order->shipping_address }}
            @endif
        </div>
        @endif

        <!-- Items Table -->
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Product</th>
                    <th>Vendor</th>
                    <th>SKU</th>
                    <th class="text-right">Price</th>
                    <th class="text-center">Qty</th>
                    <th class="text-right">Total</th>
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
                    <td>{{ $item->vendor->business_name ?? 'N/A' }}</td>
                    <td>{{ $item->sku }}</td>
                    <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-center">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->total_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="total-section">
            <div class="total-row">
                <span>Subtotal:</span>
                <span>${{ number_format($order->subtotal, 2) }}</span>
            </div>
            @if($order->tax_amount > 0)
            <div class="total-row">
                <span>Tax:</span>
                <span>${{ number_format($order->tax_amount, 2) }}</span>
            </div>
            @endif
            @if($order->shipping_cost > 0)
            <div class="total-row">
                <span>Shipping:</span>
                <span>${{ number_format($order->shipping_cost, 2) }}</span>
            </div>
            @endif
            @if($order->discount_amount > 0)
            <div class="total-row">
                <span>Discount:</span>
                <span>-${{ number_format($order->discount_amount, 2) }}</span>
            </div>
            @endif
            <div class="total-row grand-total">
                <span>Grand Total:</span>
                <span>${{ number_format($order->total, 2) }}</span>
            </div>
        </div>

        <div style="clear: both;"></div>

        <!-- Payment Information -->
        @if($order->payment_method)
        <div class="details">
            <strong>Payment Method:</strong> {{ ucfirst($order->payment_method) }}<br>
            <strong>Payment Status:</strong> {{ ucfirst($order->payment_status) }}
            @if($order->transaction_id)
                <br><strong>Transaction ID:</strong> {{ $order->transaction_id }}
            @endif
        </div>
        @endif

        <!-- Notes -->
        @if($order->customer_notes)
        <div class="details">
            <strong>Customer Notes:</strong><br>
            {{ $order->customer_notes }}
        </div>
        @endif

        <!-- Footer -->
        <div class="footer">
            Thank you for your business!<br>
            {{ $company['name'] }} | {{ $company['website'] }}<br>
            This is a computer-generated invoice. No signature required.
        </div>
    </div>
</body>
</html>