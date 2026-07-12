<?php

namespace App\Notifications;

use App\Models\FundingRequest;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class FundingNeedsAttention extends Notification
{
    public function __construct(public FundingRequest $funding, public string $message) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
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
