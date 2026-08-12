<?php

namespace App\Notifications;

use App\Notifications\Concerns\ChecksNotificationPreferences;
use App\Notifications\Concerns\DerivesWebPushFromMail;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

/**
 * The idle-session re-authentication code (see ReauthService). Always
 * mailed (no preference gate — it's a live, time-boxed credential the user
 * needs in the next few minutes, not something to browse later in the
 * notification bell) and, for anyone with push enabled, also pushed as a
 * banner notification so it doesn't just sit silently in the inbox.
 */
class ReauthCodeMail extends Notification implements ShouldQueue
{
    use ChecksNotificationPreferences, DerivesWebPushFromMail, Queueable;

    public function __construct(public string $code, public int $ttlMinutes) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if ($this->wantsPush($notifiable)) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting("Hi {$notifiable->name},")
            ->subject('Your sign-in code: '.$this->code)
            ->line("You stepped away for a while, so we need to confirm it's still you.")
            ->line("## {$this->code}")
            ->line("This code expires in {$this->ttlMinutes} minutes.")
            ->line("If you didn't request this, you can ignore this email, your account stays locked until this code is entered.");
    }
}
