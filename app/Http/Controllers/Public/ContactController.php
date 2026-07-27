<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Jobs\DeliverAcceptedContactMessage;
use App\Models\Dispute;
use App\Models\GuestSupportTicket;
use App\Services\Security\FormProtectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('public.contact');
    }

    public function submit(Request $request, FormProtectionService $formProtection)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $guard = $formProtection->guard($request, 'contact', $data, [
            'protection_setting_key' => 'contact_form_protection',
            'turnstile_action' => 'contact',
            'allow_authenticated_bypass' => true,
        ]);

        if ($guard->outcome === 'rate_limited') {
            return back()->withInput()->with('error', 'Please wait a moment before submitting again.');
        }

        if ($guard->outcome === 'challenge_required') {
            return back()->withInput()->with('error', 'Please complete the verification below and try again.');
        }

        if ($guard->needsFakeSuccessResponse()) {
            // Same response a real submission gets — a confirmed or held-for-review
            // bot submission must never look any different to the sender.
            return back()->with('success', 'Thanks for reaching out, our team will respond shortly.');
        }

        // Idempotency: a legitimate double-click on "Send" must not create two
        // tickets. Keyed by content + IP, not the session, so it still works
        // for visitors whose session cookie doesn't round-trip reliably.
        $idempotencyKey = 'contact-submit:'.sha1($request->ip().$data['email'].$data['subject'].$data['message']);
        if (! Cache::add($idempotencyKey, true, 30)) {
            return back()->with('success', 'Thanks for reaching out, our team will respond shortly.');
        }

        if (Auth::check()) {
            Dispute::create([
                'reference' => reference('PB-DSP'),
                'user_id' => Auth::id(),
                'subject' => $data['subject'],
                'category' => 'general',
                'description' => $data['message'],
                'status' => 'open',
            ]);
        } else {
            $ticket = GuestSupportTicket::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'subject' => $data['subject'],
                'category' => 'general',
                'description' => $data['message'],
                'status' => 'open',
            ]);

            DeliverAcceptedContactMessage::dispatch($ticket);
        }

        return back()->with('success', 'Thanks for reaching out, our team will respond shortly.');
    }
}
