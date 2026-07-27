<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Generic customer-facing security notification (new device login, password
 * changed, MFA enabled/disabled, sessions revoked, passkey added/removed).
 * Always sent in-app (the notification bell is never gated behind a
 * preference — a user should always be able to see what happened to their
 * own account). Mail and SMS are gated on the existing `notify_security_alerts`
 * preference, which previously existed in Settings but was never actually
 * enforced anywhere — wiring it here rather than leaving it as a fake toggle.
 * SMS itself is a further no-op unless SMS_PROVIDER=twilio is configured
 * (see SmsChannel) and the recipient has a verified phone number.
 */
class SecurityAlert extends Notification
{
    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($notifiable->preferences['notify_security_alerts'] ?? true) {
            $channels[] = 'mail';
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function toSms(object $notifiable): ?string
    {
        return "{$this->title}: {$this->message}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->greeting("Hi {$notifiable->name},")
            ->subject($this->title)
            ->line($this->message);

        if ($this->actionLabel && $this->actionUrl) {
            $mail->action($this->actionLabel, $this->actionUrl);
        }

        return $mail;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'security.alert',
            'title' => $this->title,
            'message' => $this->message,
            'url' => $this->actionUrl ?? route('security.index'),
            'icon' => 'shield',
        ];
    }
}
