<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdminMessage extends Notification
{
    public function __construct(public string $subject, public string $body, public bool $sendMail = false) {}

    public function via(object $notifiable): array
    {
        return $this->sendMail ? ['database', 'mail'] : ['database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->greeting("Hi {$notifiable->name},")
            ->subject($this->subject)
            ->line($this->body);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'admin.message',
            'title' => $this->subject,
            'message' => $this->body,
            'url' => route('notifications.index'),
            'icon' => 'bell',
        ];
    }
}
