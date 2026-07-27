<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverAcceptedContactMessage;
use App\Models\GuestSupportTicket;
use App\Services\Security\FormProtectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class GuestSupportController extends Controller
{
    public function create(): View
    {
        return view('public.support-guest');
    }

    public function store(Request $request, FormProtectionService $formProtection)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:160'],
            'category' => ['required', 'in:deposit,funding,agent,general'],
            'description' => ['required', 'string', 'max:2000'],
            // Same allowlist as the authenticated support form (DisputeController):
            // extension + MIME both checked by Laravel's `mimes` rule, size-capped,
            // stored on the PRIVATE disk under a random generated name — never the
            // visitor's original filename, never a publicly reachable path.
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);
        $attachment = $request->file('attachment');
        unset($data['attachment']);

        $guard = $formProtection->guard($request, 'guest_support', $data, [
            'protection_setting_key' => 'guest_support_protection',
            'turnstile_action' => 'guest_support',
            'allow_authenticated_bypass' => true,
        ]);

        if ($guard->outcome === 'rate_limited') {
            return back()->withInput()->with('error', 'Please wait a moment before submitting again.');
        }

        if ($guard->outcome === 'challenge_required') {
            return back()->withInput()->with('error', 'Please complete the verification below and try again.');
        }

        if ($guard->needsFakeSuccessResponse()) {
            return back()->with('success', "We've received your request. Our team will follow up by email shortly.");
        }

        $idempotencyKey = 'guest-support-submit:'.sha1($request->ip().$data['email'].$data['subject'].$data['description']);
        if (! Cache::add($idempotencyKey, true, 30)) {
            return back()->with('success', "We've received your request. Our team will follow up by email shortly.");
        }

        // Only stored once we know the submission is genuinely accepted — a
        // held or discarded submission's attachment is simply dropped with
        // the request, never written to disk.
        if ($attachment) {
            $data['attachment_path'] = $attachment->store('guest-support', 'private');
        }

        $ticket = GuestSupportTicket::create($data + ['status' => 'open']);
        DeliverAcceptedContactMessage::dispatch($ticket);

        return back()->with('success', "We've received your request. Our team will follow up by email shortly.");
    }
}
