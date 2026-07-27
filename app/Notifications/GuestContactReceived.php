<?php

namespace App\Notifications;

use App\Models\GuestSupportTicket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent on-demand to the configured support inbox (no admin User to attach a
 * database notification to). Queued explicitly — admin email must never be
 * sent synchronously inside the visitor's HTTP request.
 */
class GuestContactReceived extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public GuestSupportTicket $ticket) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New contact message: '.$this->ticket->subject)
            ->greeting('New message received')
            ->line("From: {$this->ticket->name} ({$this->ticket->email})")
            ->line("Subject: {$this->ticket->subject}")
            ->line($this->ticket->description)
            ->action('Open in admin', route('admin.support-tickets.show', $this->ticket))
            ->line('Reference: '.$this->ticket->reference);
    }
}
