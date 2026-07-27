<?php

namespace App\Jobs;

use App\Models\GuestSupportTicket;
use App\Notifications\GuestContactReceived;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeliverAcceptedContactMessage implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public GuestSupportTicket $ticket) {}

    public function handle(): void
    {
        $supportEmail = setting('support_email', config('platform.support_email'));
        if (! $supportEmail) {
            return;
        }

        Notification::route('mail', $supportEmail)->notify(new GuestContactReceived($this->ticket));
    }
}
