<?php

namespace App\Notifications;

use App\Models\ShopOrderItem;
use App\Notifications\Concerns\ChecksNotificationPreferences;
use Illuminate\Notifications\Notification;

/** Ops alert: a paid eSIM order has no live provider to auto-fulfil it and needs staff action. */
class EsimPendingProvisioning extends Notification
{
    use ChecksNotificationPreferences;

    public function __construct(public ShopOrderItem $item) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->wantsBroadcast($notifiable)) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'esim.pending_provisioning',
            'title' => 'eSIM order needs manual fulfilment',
            'message' => "Order item #{$this->item->id} ({$this->item->name}) is paid and waiting for a real activation code.",
            'url' => route('admin.esim.provisioning.index'),
            'icon' => 'sim',
        ];
    }
}
