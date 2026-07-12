<?php

namespace App\Notifications;

use App\Models\ShopOrder;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ShopOrderDelivered extends Notification
{
    public function __construct(public ShopOrder $order) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Your digital order is ready — '.$this->order->reference)
            ->greeting("Hi {$notifiable->name},")
            ->line('Your digital products have been delivered:');

        foreach ($this->order->items as $item) {
            $mail->line('**'.$item->name.'**');
            foreach (($item->delivered ?? []) as $code) {
                $mail->line('`'.$code.'`');
            }
        }

        return $mail->action('View order', route('shop.orders.show', $this->order))
            ->line('Thank you for shopping with '.config('platform.name').'.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'shop.delivered',
            'title' => 'Digital order delivered',
            'message' => "Order {$this->order->reference} is ready — your codes are available.",
            'url' => route('shop.orders.show', $this->order),
            'icon' => 'bag',
        ];
    }
}
