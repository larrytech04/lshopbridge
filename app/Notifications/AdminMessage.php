<?php

namespace App\Notifications;

use App\Notifications\Concerns\ChecksNotificationPreferences;
use App\Notifications\Concerns\DerivesWebPushFromMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class AdminMessage extends Notification
{
    use ChecksNotificationPreferences, DerivesWebPushFromMail;

    public function __construct(public string $subject, public string $body, public bool $sendMail = false) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->sendMail && $this->wantsMail($notifiable)) {
            $channels[] = 'mail';
        }

        if ($this->sendMail && $this->wantsPush($notifiable)) {
            $channels[] = WebPushChannel::class;
        }

        if ($this->wantsBroadcast($notifiable)) {
            $channels[] = 'broadcast';
        }

        return $channels;
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
