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
 * The forgot-PIN code (see PinResetService). Always mailed (no preference
 * gate — a live, time-boxed credential the user needs in the next few
 * minutes, not something to browse later) and, for anyone with push
 * enabled, also pushed as a banner notification.
 */
class PinResetCodeMail extends Notification implements ShouldQueue
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
            ->subject('Your PIN reset code: '.$this->code)
            ->line('You asked to reset your transaction PIN, the code you\'ll enter to authorize transfers and withdrawals from your wallet.')
            ->line("## {$this->code}")
            ->line("This code expires in {$this->ttlMinutes} minutes.")
            ->line('Keep this code and your PIN to yourself. We will never call, email, or message you asking for either one, and you should never share them with anyone, including someone claiming to be our support team.')
            ->line("If you didn't request this, you can ignore this email, your current PIN stays exactly as it is until this code is entered.");
    }
}
