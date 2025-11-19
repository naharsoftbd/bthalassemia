<?php

namespace App\Http\Resources\V1\Orders;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorOrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_name' => $this->product_name,
            'variant_name' => $this->variant_name,
            'sku' => $this->sku,
            'attributes' => $this->attributes,
            'unit_price' => $this->unit_price,
            'quantity' => $this->quantity,
            'total_price' => $this->total_price,
            'fulfillment_status' => $this->fulfillment_status,
            'fulfilled_at' => $this->fulfilled_at,
            'product' => [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'slug' => $this->product->slug,
            ],
            'variant' => $this->variant ? [
                'id' => $this->variant->id,
                'sku' => $this->variant->sku,
            ] : null,
        ];
    }
}
