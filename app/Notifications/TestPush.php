<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Sent on demand from the "Send a test notification" button in
 * Settings > Notifications, so a user can confirm the whole push pipeline
 * (permission granted, subscribed, service worker receiving) actually works
 * right now, rather than guessing from a real event that may have fired
 * before they turned Web Push on.
 */
class TestPush extends Notification
{
    public function via(object $notifiable): array
    {
        return [WebPushChannel::class];
    }

    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        return (new WebPushMessage)
            ->title('Push notifications are working')
            ->icon(site_favicon())
            ->badge(site_favicon())
            ->body("This is a test from ".config('app.name').". If you can see this, push is set up correctly on this device.")
            ->data(['url' => route('profile.edit')])
            ->options(['TTL' => 60]);
    }
}
