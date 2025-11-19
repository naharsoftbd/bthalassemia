<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'order_number', 'status', 'subtotal', 'tax_amount',
        'shipping_cost', 'discount_amount', 'total', 'customer_email',
        'customer_phone', 'shipping_address', 'billing_address',
        'payment_status', 'payment_method', 'transaction_id',
        'shipping_method', 'tracking_number',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'confirmed_at' => 'datetime',
        'processing_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'shipping_address' => 'array',
        'billing_address' => 'array',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function vendorItems($vendorId)
    {
        return $this->items()->where('vendor_id', $vendorId);
    }

    // Scopes
    public function scopeForVendor($query, $vendorId)
    {
        return $query->whereHas('items', function ($q) use ($vendorId) {
            $q->where('vendor_id', $vendorId);
        });
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
