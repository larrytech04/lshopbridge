<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Dispute::with('user', 'assignee');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.disputes.index', [
            'disputes' => $query->latest()->paginate(20)->withQueryString(),
            'status' => $status,
        ]);
    }

    public function show(Dispute $dispute): View
    {
        return view('admin.disputes.show', ['dispute' => $dispute->load('user', 'messages.user')]);
    }

    public function reply(Request $request, Dispute $dispute)
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:2000']]);

        $dispute->messages()->create([
            'user_id' => $request->user()->id,
            'is_staff' => true,
            'message' => $data['message'],
        ]);

        $dispute->update(['status' => 'in_progress', 'assigned_to' => $request->user()->id]);

        return back()->with('success', 'Reply sent.');
    }

    public function resolve(Request $request, Dispute $dispute)
    {
        $data = $request->validate([
            'resolution' => ['required', 'string', 'max:1000'],
            'status' => ['required', 'in:resolved,closed'],
        ]);

        $dispute->update([
            'status' => $data['status'],
            'resolution' => $data['resolution'],
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Dispute '.$data['status'].'.');
    }
}
