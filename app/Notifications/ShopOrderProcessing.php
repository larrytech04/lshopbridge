<?php

namespace App\Notifications;

use App\Models\ShopOrder;
use App\Notifications\Concerns\ChecksNotificationPreferences;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent instead of ShopOrderDelivered when payment succeeded but at least one
 * item (an eSIM awaiting real/manual provisioning) isn't actually fulfilled
 * yet — replaces the old behaviour of always claiming delivery immediately.
 */
class ShopOrderProcessing extends Notification
{
    use ChecksNotificationPreferences;

    public function __construct(public ShopOrder $order) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->wantsMail($notifiable, 'notify_order_updates')) {
            $channels[] = 'mail';
        }

        if ($this->wantsBroadcast($notifiable, 'notify_order_updates')) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your order is being prepared, '.$this->order->reference)
            ->greeting("Hi {$notifiable->name},")
            ->line('Payment was received for order '.$this->order->reference.'.')
            ->line('One or more items are still being prepared. We\'ll email you the moment everything is ready. Please do not place a duplicate order.')
            ->action('View order status', route('shop.orders.show', $this->order))
            ->line('Thank you for your patience.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shop.processing',
            'title' => 'Order is being prepared',
            'message' => "Order {$this->order->reference} is paid and being prepared for delivery.",
            'url' => route('shop.orders.show', $this->order),
            'icon' => 'clock',
        ];
    }
}
