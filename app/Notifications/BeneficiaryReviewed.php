<?php

namespace App\Notifications;

use App\Models\BeneficiaryAccount;
use App\Notifications\Concerns\ChecksNotificationPreferences;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BeneficiaryReviewed extends Notification
{
    use ChecksNotificationPreferences;

    public function __construct(public BeneficiaryAccount $account, public bool $approved, public ?string $reason = null) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if ($this->wantsMail($notifiable)) {
            $channels[] = 'mail';
        }

        if ($this->wantsBroadcast($notifiable)) {
            $channels[] = 'broadcast';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)->greeting("Hi {$notifiable->name},");

        return $this->approved
            ? $mail->subject('Your China wallet was approved')
                ->line("{$this->account->app_type->label()} account {$this->account->account_id} is approved for funding.")
                ->action('Fund now', route('funding.create'))
            : $mail->subject('Your China wallet was rejected')
                ->line('Reason: '.($this->reason ?: 'Please check the details and try again.'))
                ->action('Manage accounts', route('beneficiaries.index'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'beneficiary.reviewed',
            'title' => $this->approved ? 'China wallet approved' : 'China wallet rejected',
            'message' => $this->approved
                ? "{$this->account->app_type->label()} account approved."
                : 'China wallet rejected: '.($this->reason ?: 'please try again.'),
            'url' => route('beneficiaries.index'),
            'icon' => $this->approved ? 'check' : 'alert',
        ];
    }
}
