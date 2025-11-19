<?php

namespace App\Http\Resources\V1\Orders;

use Illuminate\Http\Resources\Json\JsonResource;

class VendorOrderResource extends JsonResource
{
    public function toArray($request)
    {
        $user = auth()->user();

        return [
            'id' => $this->id,
            'order_number' => $this->order_number,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'total' => $this->total,
            'vendor_total' => $this->getVendorTotal($user),
            'customer_email' => $this->customer_email,
            'customer_phone' => $this->customer_phone,
            'shipping_address' => $this->shipping_address,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // Only show vendor's items
            'items' => VendorOrderItemResource::collection(
                $this->items->where('vendor_id', $user->vendor->id)
            ),

            // Order statistics for this vendor
            'vendor_statistics' => [
                'item_count' => $this->items->where('vendor_id', $user->vendor->id)->count(),
                'total_quantity' => $this->items->where('vendor_id', $user->vendor->id)->sum('quantity'),
                'subtotal' => $this->items->where('vendor_id', $user->vendor->id)->sum('total_price'),
            ],

            // Customer info (basic, no sensitive data)
            'customer' => [
                'name' => $this->user->name,
                'email' => $this->customer_email,
            ],
        ];
    }

    protected function getVendorTotal($user)
    {
        return $this->items
            ->where('vendor_id', $user->vendor->id)
            ->sum('total_price');
    }
}
