<?php

namespace App\Notifications;

use App\Models\FundingRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FundingCompleted extends Notification
{
    public function __construct(public FundingRequest $funding) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Your China wallet funding is complete')
            ->greeting("Hi {$notifiable->name},")
            ->line("We've delivered ".money($this->funding->target_amount, $this->funding->target_currency)." to {$this->funding->recipient_account}.")
            ->line("Reference: {$this->funding->reference}")
            ->action('View transaction', route('funding.show', $this->funding));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'funding.completed',
            'title' => 'Funding completed',
            'message' => money($this->funding->target_amount, $this->funding->target_currency)." delivered to {$this->funding->recipient_account}.",
            'url' => route('funding.show', $this->funding),
            'icon' => 'check',
        ];
    }
}
