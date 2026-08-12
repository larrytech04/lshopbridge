<?php

namespace App\Notifications;

use App\Models\FundingRequest;
use App\Notifications\Concerns\ChecksNotificationPreferences;
use App\Notifications\Concerns\DerivesWebPushFromMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class FundingNeedsAttention extends Notification
{
    use ChecksNotificationPreferences, DerivesWebPushFromMail;

    public function __construct(public FundingRequest $funding, public string $message) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->wantsMail($notifiable, 'notify_wallet_activity')) {
            $channels[] = 'mail';
        }

        if ($this->wantsPush($notifiable, 'notify_wallet_activity')) {
            $channels[] = WebPushChannel::class;
        }

        if ($this->wantsBroadcast($notifiable, 'notify_wallet_activity')) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Update on your funding request')
            ->greeting("Hi {$notifiable->name},")
            ->line($this->message)
            ->line("Reference: {$this->funding->reference}")
            ->action('View transaction', route('funding.show', $this->funding));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'funding.attention',
            'title' => 'Funding update',
            'message' => $this->message,
            'url' => route('funding.show', $this->funding),
            'icon' => 'info',
        ];
    }
}
