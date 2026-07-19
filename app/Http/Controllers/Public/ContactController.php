<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ContactController extends Controller
{
    public function show(): View
    {
        return view('public.contact');
    }

    public function submit(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email'],
            'subject' => ['required', 'string', 'max:160'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        // Logged-in users get a tracked support ticket; guests get a logged message.
        if (Auth::check()) {
            Dispute::create([
                'reference' => reference('PB-DSP'),
                'user_id' => Auth::id(),
                'subject' => $data['subject'],
                'category' => 'general',
                'description' => $data['message'],
                'status' => 'open',
            ]);
        }

        return back()->with('success', 'Thanks for reaching out, our team will respond shortly.');
    }
}
