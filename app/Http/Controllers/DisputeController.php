<?php

namespace App\Http\Controllers;

use App\Models\Dispute;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisputeController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard.disputes.index', [
            'disputes' => $user->disputes()->latest()->paginate(10),
            'related' => [
                'deposit' => $user->deposits()->latest()->take(8)->get(['id', 'reference', 'net_amount', 'currency']),
                'funding' => $user->fundingRequests()->latest()->take(8)->get(['id', 'reference', 'target_amount', 'target_currency']),
                'order' => $user->shopOrders()->latest()->take(8)->get(['id', 'reference', 'total', 'currency']),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'category' => ['required', 'in:deposit,funding,agent,general'],
            'priority' => ['required', 'in:low,normal,high'],
            'related' => ['nullable', 'string'],
            'description' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $dispute = Dispute::create([
            'reference' => reference('PB-DSP'),
            'user_id' => $request->user()->id,
            'subject' => $data['subject'],
            'category' => $data['category'],
            'priority' => $data['priority'],
            'description' => $data['description'],
            'status' => 'open',
        ]);

        // Optional "which transaction is this about" link, the ticket detail page
        // can then show the related deposit/funding/order inline for support to see.
        if (! empty($data['related']) && str_contains($data['related'], ':')) {
            [$type, $id] = explode(':', $data['related'], 2);
            $model = match ($type) {
                'deposit' => \App\Models\Deposit::class,
                'funding' => \App\Models\FundingRequest::class,
                'order' => \App\Models\ShopOrder::class,
                default => null,
            };
            if ($model) {
                $relatedRecord = $model::where('id', $id)->where('user_id', $request->user()->id)->first();
                if ($relatedRecord) {
                    $dispute->subjectRef()->associate($relatedRecord);
                    $dispute->save();
                }
            }
        }

        if ($request->hasFile('attachment')) {
            $dispute->messages()->create([
                'user_id' => $request->user()->id,
                'is_staff' => false,
                'message' => __('Attachment for this ticket.'),
                'attachment_path' => $request->file('attachment')->store('disputes', 'private'),
            ]);
        }

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
