<?php

namespace App\Notifications;

use App\Models\Order;
use App\Models\Vendor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class VendorOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $order;

    public $vendor;

    public $vendorItems;

    public function __construct(Order $order, Vendor $vendor)
    {
        $this->order = $order;
        $this->vendor = $vendor;
        $this->vendorItems = $order->items->where('vendor_id', $vendor->id);
    }

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        $vendorTotal = $this->vendorItems->sum('total_price');
        $itemCount = $this->vendorItems->count();

        return (new MailMessage)
            ->subject('New Order Received - '.$this->order->order_number)
            ->greeting('Hello '.$this->vendor->business_name.'!')
            ->line('You have received a new order with the following details:')
            ->line('**Order Information:**')
            ->line('Order Number: '.$this->order->order_number)
            ->line('Customer: '.$this->order->user->name)
            ->line('Customer Email: '.$this->order->customer_email)
            ->line('Order Date: '.$this->order->created_at->format('F j, Y g:i A'))
            ->line('**Your Items in this Order:**')
            ->line('Total Items: '.$itemCount)
            ->line('Your Total: $'.number_format($vendorTotal, 2))
            ->action('View Order Details', url('/vendor/orders/'.$this->order->id))
            ->line('Please process this order within 24-48 hours.')
            ->line('**Shipping Address:**')
            ->line($this->formatAddress($this->order->shipping_address))
            ->line('Thank you for being part of our marketplace!');
    }

    protected function formatAddress($address): string
    {
        if (is_array($address)) {
            return implode(', ', array_filter([
                $address['street'] ?? '',
                $address['city'] ?? '',
                $address['state'] ?? '',
                $address['zip_code'] ?? '',
                $address['country'] ?? '',
            ]));
        }

        return $address;
    }

    public function toArray($notifiable): array
    {
        return [
            'order_id' => $this->order->id,
            'order_number' => $this->order->order_number,
            'vendor_id' => $this->vendor->id,
            'message' => 'New order received with '.$this->vendorItems->count().' items',
            'item_count' => $this->vendorItems->count(),
            'total_amount' => $this->vendorItems->sum('total_price'),
            'type' => 'vendor_new_order',
        ];
    }
}
