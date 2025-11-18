<?php

namespace App\Notifications;

use App\Models\ProductVariant;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    protected $variant;

    /**
     * Create a new notification instance.
     */
    public function __construct(ProductVariant $variant)
    {
        $this->variant = $variant;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $product = $this->variant->product;

        return (new MailMessage)
            ->subject("Low stock alert: {$product->name} — {$this->variant->sku}")
            ->line("Variant: {$this->variant->name} ({$this->variant->sku})")
            ->line("Current stock: {$this->variant->stock}")
            ->line("Low stock threshold: {$this->variant->low_stock_threshold}")
            ->action('View product', url("/products/{$product->id}/variants/{$this->variant->id}"))
            ->line('Please replenish stock as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->variant->product_id,
            'variant_id' => $this->variant->id,
            'sku' => $this->variant->sku,
            'stock' => $this->variant->stock,
            'threshold' => $this->variant->low_stock_threshold,
        ];
    }
}
