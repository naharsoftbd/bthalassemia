<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    public $previousStatus;

    public $newStatus;

    public function __construct(Order $order, string $previousStatus, string $newStatus)
    {
        $this->order = $order;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $statusMessages = [
            'Confirmed' => 'Your order has been confirmed and is now being processed.',
            'Processing' => 'Your order is now being processed and prepared for shipment.',
            'Shipped' => 'Your order has been shipped! Track your package below.',
            'Delivered' => 'Your order has been delivered. Thank you for your purchase!',
            'Cancelled' => 'Your order has been cancelled.',
        ];

        return (new MailMessage)
            ->subject('Order Status Updated - '.$this->order->order_number)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your order status has been updated:')
            ->line('**From:** '.ucfirst($this->previousStatus))
            ->line('**To:** '.ucfirst($this->newStatus))
            ->line($statusMessages[$this->newStatus] ?? 'Your order status has been updated.')
            ->line('**Order Details:**')
            ->line('Order Number: '.$this->order->order_number)
            ->line('Order Total: $'.number_format($this->order->total, 2))
            ->action('View Order', url('/orders/'.$this->order->id))
            ->line('If you have any questions, please contact our support team.');
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'message' => 'Order status updated from '.$this->previousStatus.' to '.$this->newStatus,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'type' => 'order_status_updated',
        ];
    }
}
