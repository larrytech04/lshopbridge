<?php

namespace App\Notifications;

use App\Notifications\Channels\SmsChannel;
use App\Notifications\Concerns\ChecksNotificationPreferences;
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
    use ChecksNotificationPreferences;

    public function __construct(
        public string $title,
        public string $message,
        public ?string $actionLabel = null,
        public ?string $actionUrl = null,
        // Only true for events the account owner may not have caused themselves
        // (an unrecognised device/country logging in) — this is what drives the
        // dashboard's "needs attention" banner. A confirmation of something the
        // user just did in an authenticated session (changed their own password,
        // toggled 2FA, added a passkey) still lands in the notification bell,
        // but shouldn't keep re-lighting that banner every time they touch their
        // own security settings.
        public bool $requiresReview = false,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];
        $prefersMail = $this->wantsMail($notifiable, 'notify_security_alerts');

        // Unusual-activity alerts (unrecognised device/country) always email
        // the account owner, even if they've turned off the general
        // security-alert mail preference — this is the one class of notice
        // too important to let a settings toggle silence entirely. SMS stays
        // preference-gated either way.
        if ($prefersMail || $this->requiresReview) {
            $channels[] = 'mail';
        }

        if ($prefersMail) {
            $channels[] = SmsChannel::class;
        }

        if ($this->wantsBroadcast($notifiable, 'notify_security_alerts')) {
            $channels[] = 'broadcast';
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
            'requires_review' => $this->requiresReview,
        ];
    }
}
