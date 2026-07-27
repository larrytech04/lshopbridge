<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use App\Models\GuestSupportTicket;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function index(Request $request): View
    {
        $query = GuestSupportTicket::with('assignee');
        $status = $request->query('status', 'open');
        if ($status) {
            $query->where('status', $status);
        }

        return view('admin.support-tickets.index', [
            'tickets' => $query->latest()->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(GuestSupportTicket $supportTicket): View
    {
        return view('admin.support-tickets.show', ['ticket' => $supportTicket->load('assignee', 'convertedToDispute')]);
    }

    public function resolve(Request $request, GuestSupportTicket $supportTicket)
    {
        $data = $request->validate(['resolution' => ['required', 'string', 'max:2000']]);
        $supportTicket->update(['status' => 'resolved', 'resolution' => $data['resolution'], 'resolved_at' => now()]);

        return back()->with('success', 'Ticket resolved.');
    }

    public function assign(Request $request, GuestSupportTicket $supportTicket)
    {
        $supportTicket->update(['assigned_to' => auth()->id(), 'status' => 'in_progress']);

        return back()->with('success', 'Ticket assigned to you.');
    }

    /** Once the guest has (or creates) a real account, staff can migrate the conversation into the normal Dispute system. */
    public function convertToDispute(GuestSupportTicket $supportTicket)
    {
        $user = \App\Models\User::where('email', $supportTicket->email)->first();
        if (! $user) {
            return back()->with('error', 'No account exists yet for this email — ask the guest to register first.');
        }

        $dispute = Dispute::create([
            'reference' => reference('PB-DSP'),
            'user_id' => $user->id,
            'subject' => $supportTicket->subject,
            'category' => $supportTicket->category,
            'description' => $supportTicket->description,
            'status' => 'open',
        ]);

        $supportTicket->update(['converted_to_dispute_id' => $dispute->id, 'status' => 'closed']);

        return redirect()->route('admin.disputes.show', $dispute)->with('success', 'Converted to a tracked dispute.');
    }
}
