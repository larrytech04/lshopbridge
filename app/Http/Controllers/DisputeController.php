<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function index(Request $request): View
    {
        return view('dashboard.disputes.index', [
            'disputes' => $request->user()->disputes()->latest()->paginate(10),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'category' => ['required', 'in:deposit,funding,agent,general'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $dispute = Dispute::create([
            'reference' => reference('PB-DSP'),
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'category' => $data['category'],
            'description' => $data['description'],
            'status' => 'open',
        ]);

        return redirect()->route('disputes.show', $dispute)->with('success', 'Your support ticket has been created.');
    }

    public function show(Dispute $dispute): View
    {
        $this->authorize('view', $dispute);

        return view('dashboard.disputes.show', ['dispute' => $dispute->load('messages.user')]);
    }

    public function reply(Request $request, Dispute $dispute)
    {
        $this->authorize('reply', $dispute);

        $data = $request->validate([
            'message' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'max:5120'],
        ]);

        $dispute->messages()->create([
            'user_id' => $request->user()->id,
            'is_staff' => false,
            'message' => $data['message'],
            'attachment_path' => $request->hasFile('attachment')
                ? $request->file('attachment')->store('disputes', 'private')
                : null,
        ]);

        if ($dispute->status->value === 'resolved') {
            $dispute->update(['status' => 'open']);
        }

        return back()->with('success', 'Reply sent.');
    }
}
