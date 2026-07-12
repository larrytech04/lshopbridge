<?php

namespace App\Notifications;

use App\Models\Deposit;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DepositConfirmed extends Notification
{
    public function __construct(public Deposit $deposit) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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
