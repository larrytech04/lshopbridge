<?php

namespace App\Notifications;

use App\Models\Agent;
use App\Notifications\Concerns\ChecksNotificationPreferences;
use App\Notifications\Concerns\DerivesWebPushFromMail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;

class AgentVerified extends Notification
{
    use ChecksNotificationPreferences, DerivesWebPushFromMail;

    public function __construct(public Agent $agent, public bool $approved, public ?string $reason = null) {}

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

        return $this->approved
            ? $mail->subject('Your agent profile is verified')
                ->line("{$this->agent->business_name} is now a verified shipping agent and listed in the marketplace.")
                ->action('Open agent dashboard', route('agent.dashboard'))
            : $mail->subject('Agent verification update')
                ->line('Your agent verification was not approved.')
                ->line('Reason: '.($this->reason ?: 'Please re-submit your documents.'))
                ->action('Update profile', route('agent.verification'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'agent.verified',
            'title' => $this->approved ? 'Agent verified' : 'Agent verification rejected',
            'message' => $this->approved
                ? "{$this->agent->business_name} is now verified and listed."
                : 'Agent verification rejected: '.($this->reason ?: 'please re-submit.'),
            'url' => $this->approved ? route('agent.dashboard') : route('agent.verification'),
            'icon' => $this->approved ? 'check' : 'alert',
        ];
    }
}
