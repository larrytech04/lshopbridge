<?php

namespace App\Notifications;

use App\Enums\KycDecisionType;
use App\Models\KycVerification;
use App\Notifications\Concerns\ChecksNotificationPreferences;
use App\Notifications\Concerns\DerivesWebPushFromMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class KycReviewed extends Notification
{
    use ChecksNotificationPreferences, DerivesWebPushFromMail;

    public function __construct(
        public KycVerification $kyc,
        public bool $approved,
        public ?string $reason = null,
        public ?KycDecisionType $type = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->wantsMail($notifiable)) {
            $channels[] = 'mail';
        }

        if ($this->wantsPush($notifiable)) {
            $channels[] = WebPushChannel::class;
        }

        if ($this->wantsBroadcast($notifiable)) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting("Hi {$notifiable->name},");

        return match ($this->type) {
            KycDecisionType::ApproveLimited => $mail->subject('Your identity is verified (limited access)')
                ->line('Your KYC verification was approved with a limitation on your account.')
                ->when($this->reason, fn ($m) => $m->line($this->reason))
                ->action('Go to dashboard', route('dashboard')),
            KycDecisionType::RequestMoreInfo => $mail->subject('We need more information')
                ->line('Our compliance team needs additional information to complete your verification.')
                ->line($this->reason ?: 'Please check your dashboard for details.')
                ->action('Go to verification', route('verification.index')),
            KycDecisionType::ReturnForCorrection => $mail->subject('Please correct your verification documents')
                ->line('Some information you submitted needs correction before we can continue.')
                ->line($this->reason ?: 'Please re-submit clearer documents.')
                ->action('Re-submit', route('verification.index')),
            default => $this->approved
                ? $mail->subject('Your identity is verified')
                    ->line('Your KYC verification was approved. Higher limits are now unlocked.')
                    ->action('Go to dashboard', route('dashboard'))
                : $mail->subject('Your verification needs attention')
                    ->line('Your KYC verification was not approved.')
                    ->line('Reason: '.($this->reason ?: 'Please re-submit clearer documents.'))
                    ->action('Re-submit', route('verification.index')),
        };
    }

    public function toArray(object $notifiable): array
    {
        $title = match ($this->type) {
            KycDecisionType::ApproveLimited => 'Identity verified (limited)',
            KycDecisionType::RequestMoreInfo => 'More information needed',
            KycDecisionType::ReturnForCorrection => 'Correction needed',
            default => $this->approved ? 'Identity verified' : 'Verification rejected',
        };

        return [
            'type' => 'kyc.reviewed',
            'title' => $title,
            'message' => $this->reason ?: ($this->approved ? 'Your KYC was approved, higher limits unlocked.' : 'Please check your dashboard for details.'),
            'url' => route('verification.index'),
            'icon' => $this->approved ? 'check' : 'alert',
        ];
    }
}
