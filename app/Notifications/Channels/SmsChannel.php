<?php

namespace App\Notifications\Channels;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Sends SMS via Twilio's REST API directly (no SDK — it's a single simple
 * POST). Only "twilio" is implemented; any other/blank SMS_PROVIDER value is
 * a documented no-op, matching this app's existing "unconfigured means
 * nothing happens, never fake it" pattern (see Turnstile, Google sign-in).
 *
 * A notification opts in by implementing toSms(): ?string.
 */
class SmsChannel
{
    public function send(mixed $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        if (! $this->isConfigured()) {
            return;
        }

        if (! $notifiable instanceof User || ! $notifiable->isPhoneVerified() || ! $notifiable->phone) {
            return;
        }

        $text = $notification->toSms($notifiable);
        if (! $text) {
            return;
        }

        try {
            $sid = config('services.sms.account_sid');
            $response = Http::asForm()
                ->withBasicAuth($sid, config('services.sms.api_key'))
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'To' => $notifiable->phone,
                    'From' => config('services.sms.sender'),
                    'Body' => $text,
                ]);

            if ($response->failed()) {
                Log::warning('SMS send failed', ['status' => $response->status(), 'body' => $response->body()]);
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public function isConfigured(): bool
    {
        return config('services.sms.provider') === 'twilio'
            && config('services.sms.account_sid')
            && config('services.sms.api_key')
            && config('services.sms.sender');
    }
}
