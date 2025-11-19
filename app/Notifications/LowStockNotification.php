<?php

namespace App\Notifications;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $productVariant;

    public function __construct(ProductVariant $productVariant)
    {
        $this->productVariant = $productVariant;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Low Stock Alert - '.$this->productVariant->product->name)
            ->greeting('Low Stock Alert!')
            ->line('Your product is running low on stock:')
            ->line('**Product:** '.$this->productVariant->product->name)
            ->line('**Variant:** '.$this->productVariant->name)
            ->line('**SKU:** '.$this->productVariant->sku)
            ->line('**Current Stock:** '.$this->productVariant->stock)
            ->line('**Low Stock Threshold:** '.$this->productVariant->low_stock_threshold)
            ->action('Manage Inventory', url('/vendor/products/'.$this->productVariant->product_id))
            ->line('Please restock soon to avoid missing sales.');
    }

    public function toArray($notifiable): array
    {
        return [
            'product_variant_id' => $this->productVariant->id,
            'product_name' => $this->productVariant->product->name,
            'variant_name' => $this->productVariant->name,
            'current_stock' => $this->productVariant->stock,
            'threshold' => $this->productVariant->low_stock_threshold,
            'message' => 'Low stock alert for '.$this->productVariant->product->name,
            'type' => 'low_stock_alert',
        ];
    }
}
