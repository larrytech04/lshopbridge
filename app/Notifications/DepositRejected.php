<?php

namespace App\Notifications;

use App\Models\Deposit;
use App\Notifications\Concerns\ChecksNotificationPreferences;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositRejected extends Notification
{
    use ChecksNotificationPreferences;

    public function __construct(public Deposit $deposit, public string $reason) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->wantsMail($notifiable, 'notify_wallet_activity')) {
            $channels[] = 'mail';
        }

        if ($this->wantsBroadcast($notifiable, 'notify_wallet_activity')) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your deposit could not be confirmed')
            ->greeting("Hi {$notifiable->name},")
            ->line("Deposit {$this->deposit->reference} was not confirmed.")
            ->line("Reason: {$this->reason}")
            ->action('Open support', route('disputes.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'deposit.rejected',
            'title' => 'Deposit rejected',
            'message' => "Deposit {$this->deposit->reference} was rejected: {$this->reason}",
            'url' => route('deposit.index'),
            'icon' => 'alert',
        ];
    }
}
