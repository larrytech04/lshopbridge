<?php

namespace App\Notifications;

use App\Models\KycVerification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KycReviewed extends Notification
{
    public function __construct(public KycVerification $kyc, public bool $approved, public ?string $reason = null) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting("Hi {$notifiable->name},");

        return $this->approved
            ? $mail->subject('Your identity is verified')
                ->line('Your KYC verification was approved. Higher limits are now unlocked.')
                ->action('Go to dashboard', route('dashboard'))
            : $mail->subject('Your verification needs attention')
                ->line('Your KYC verification was not approved.')
                ->line('Reason: '.($this->reason ?: 'Please re-submit clearer documents.'))
                ->action('Re-submit', route('verification.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'kyc.reviewed',
            'title' => $this->approved ? 'Identity verified' : 'Verification rejected',
            'message' => $this->approved
                ? 'Your KYC was approved — higher limits unlocked.'
                : 'Your KYC was rejected: '.($this->reason ?: 'please re-submit.'),
            'url' => route('verification.index'),
            'icon' => $this->approved ? 'check' : 'alert',
        ];
    }
}
