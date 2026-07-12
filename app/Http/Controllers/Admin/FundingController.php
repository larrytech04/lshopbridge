<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FundingRequest;
use App\Services\Funding\FundingService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FundingController extends Controller
{
    public function index(Request $request): View
    {
        $query = FundingRequest::with('user', 'beneficiary');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->query('q')) {
            $query->where('reference', 'like', "%{$search}%");
        }

        return view('admin.funding.index', [
            'requests' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only('status', 'q'),
        ]);
    }

    public function show(FundingRequest $funding): View
    {
        return view('admin.funding.show', ['funding' => $funding->load('user', 'beneficiary', 'deposit', 'walletTransactions')]);
    }

    public function complete(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
            'receipt' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
        ]);

        $receiptPath = $request->hasFile('receipt')
            ? $request->file('receipt')->store('funding/receipts', 'private')
            : null;

        $service->completeManually($funding, $request->user(), $receiptPath, $data['note'] ?? null);

        return back()->with('success', 'Funding marked complete.');
    }

    public function retry(FundingRequest $funding, FundingService $service)
    {
        $service->retry($funding);

        return back()->with('success', 'Funding retried through the provider.');
    }

    public function refund(Request $request, FundingRequest $funding, FundingService $service)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $service->refund($funding, $request->user(), $data['reason']);

        return back()->with('success', 'Funding refunded to the user wallet.');
    }
}
