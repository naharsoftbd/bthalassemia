<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'name',
        'attributes',
        'price',
        'stock',
        'low_stock_threshold',
        'low_stock_notified',
        'is_active',
    ];

    protected $casts = [
        'attributes' => 'array',
        'price' => 'decimal:2',
        'stock' => 'integer',
        'low_stock_threshold' => 'integer',
        'low_stock_notified' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function inventoryLogs()
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Adjust stock and create an inventory log.
     * Use positive $amount to increment, negative to decrement.
     *
     * This method dispatches the CheckLowStock job via observer (or you can call CheckLowStockJob directly).
     */
    public function adjustStock(int $amount, ?string $reason = null, $userId = null): void
    {
        // We're updating the model directly to keep DB atomic outside transactions:
        $this->increment('stock', $amount);

        InventoryLog::create([
            'product_variant_id' => $this->id,
            'change' => $amount,
            'reason' => $reason,
            'user_id' => $userId,
        ]);

        // Note: observer will dispatch the CheckLowStock job on saved()
        $this->refresh();
    }
}
