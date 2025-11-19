<?php

namespace App\Notifications;

use App\Models\OrderItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorOrderItemStatusNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $orderItem;

    public $previousStatus;

    public $newStatus;

    public function __construct(OrderItem $orderItem, string $previousStatus, string $newStatus)
    {
        $this->orderItem = $orderItem;
        $this->previousStatus = $previousStatus;
        $this->newStatus = $newStatus;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $order = $this->orderItem->order;
        $customer = $order->user;

        return (new MailMessage)
            ->subject('Order Item Status Updated - '.$order->order_number)
            ->greeting('Hello '.$customer->name.'!')
            ->line('The status of an item in your order has been updated:')
            ->line('**Product:** '.$this->orderItem->product_name)
            ->line('**Vendor:** '.$this->orderItem->vendor->business_name)
            ->line('**Status Changed:** '.ucfirst($this->previousStatus).' → '.ucfirst($this->newStatus))
            ->line('**Order Number:** '.$order->order_number)
            ->action('View Order Details', url('/orders/'.$order->id))
            ->line('Thank you for your patience!');
    }
}
