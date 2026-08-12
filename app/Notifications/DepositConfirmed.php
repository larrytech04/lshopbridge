<?php

namespace App\Notifications;

use App\Models\Deposit;
use App\Notifications\Concerns\ChecksNotificationPreferences;
use App\Notifications\Concerns\DerivesWebPushFromMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class DepositConfirmed extends Notification
{
    use ChecksNotificationPreferences, DerivesWebPushFromMail;

    public function __construct(public Deposit $deposit) {}

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
            ->subject('Your deposit was confirmed')
            ->greeting("Hi {$notifiable->name},")
            ->line("Your deposit {$this->deposit->reference} of ".money($this->deposit->net_amount, $this->deposit->currency).' has been credited to your wallet.')
            ->action('View wallet', route('wallet.index'))
            ->line('Thank you for using '.config('platform.name').'.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'deposit.confirmed',
            'title' => 'Deposit confirmed',
            'message' => "Deposit {$this->deposit->reference} of ".money($this->deposit->net_amount, $this->deposit->currency).' was credited.',
            'url' => route('wallet.index'),
            'icon' => 'wallet',
        ];
    }
}
