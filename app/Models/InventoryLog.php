<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id', 'product_variant_id', 'order_id', 'quantity',
        'action', 'reason', 'notes', 'previous_stock', 'new_stock'
    ];

    protected $casts = [
        'previous_stock' => 'integer',
        'new_stock' => 'integer',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Scopes
    public function scopeDeductions($query)
    {
        return $query->where('quantity', '<', 0);
    }

    public function scopeAdditions($query)
    {
        return $query->where('quantity', '>', 0);
    }

    public function scopeForProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeForVariant($query, $variantId)
    {
        return $query->where('product_variant_id', $variantId);
    }
}