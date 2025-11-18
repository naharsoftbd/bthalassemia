<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id', 'vendor_id',
        'product_name', 'variant_name', 'sku', 'attributes',
        'unit_price', 'discount_amount', 'tax_amount', 'total_price',
        'quantity', 'fulfillment_status', 'fulfilled_at'
    ];

    protected $casts = [
        'attributes' => 'array',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_price' => 'decimal:2',
        'fulfilled_at' => 'datetime',
    ];

    // Relationships
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class);
    }

    // Scopes
    public function scopeForVendor($query, $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }

    public function scopePendingFulfillment($query)
    {
        return $query->where('fulfillment_status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('fulfillment_status', 'processing');
    }

    // Methods
    public function markAsProcessing()
    {
        $this->update(['fulfillment_status' => 'processing']);
    }

    public function markAsShipped($trackingNumber = null)
    {
        $this->update([
            'fulfillment_status' => 'shipped',
            'fulfilled_at' => now(),
        ]);
    }

    public function markAsDelivered()
    {
        $this->update(['fulfillment_status' => 'delivered']);
    }
}