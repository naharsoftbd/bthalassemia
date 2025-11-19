<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Order Confirmation - '.$this->order->order_number)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Thank you for your order. Your order has been received and is being processed.')
            ->line('**Order Details:**')
            ->line('Order Number: '.$this->order->order_number)
            ->line('Order Total: $'.number_format($this->order->total, 2))
            ->line('Order Date: '.$this->order->created_at->format('F j, Y'))
            ->action('View Order', url('/orders/'.$this->order->id))
            ->line('We will notify you when your order status changes.')
            ->line('Thank you for shopping with us!');
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'message' => 'Your order has been created successfully.',
            'type' => 'order_created',
            'amount' => $this->order->total,
        ];
    }
}
