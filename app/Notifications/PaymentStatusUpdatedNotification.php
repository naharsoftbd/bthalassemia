<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PaymentStatusUpdatedNotification extends Notification implements ShouldQueue
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
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $messages = [
            'paid' => 'Your payment has been successfully processed.',
            'failed' => 'Your payment has failed. Please update your payment method.',
            'refunded' => 'Your payment has been refunded.',
        ];

        return (new MailMessage)
            ->subject('Payment Status Updated - '.$this->order->order_number)
            ->greeting('Hello '.$notifiable->name.'!')
            ->line('Your payment status has been updated:')
            ->line('**Order:** '.$this->order->order_number)
            ->line('**Amount:** $'.number_format($this->order->total, 2))
            ->line('**New Status:** '.ucfirst($this->newStatus))
            ->line($messages[$this->newStatus] ?? 'Your payment status has been updated.')
            ->action('View Order', url('/orders/'.$this->order->id))
            ->line('If you have any questions, please contact our support team.');
    }
}
