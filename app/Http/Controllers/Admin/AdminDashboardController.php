<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\BeneficiaryAccount;
use App\Models\Deposit;
use App\Models\FundingRequest;
use App\Models\KycVerification;
use App\Models\RiskFlag;
use App\Models\ShopOrder;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WebhookEvent;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function index(): View
    {
        // 14-day multi-series platform monitor (volume in base currency).
        $monitor = collect(range(13, 0))->map(function ($daysAgo) {
            $day = Carbon::today()->subDays($daysAgo);

            return [
                'label' => $day->format('d'),
                'date' => $day->format('M j'),
                'deposits' => (float) Deposit::whereDate('created_at', $day)->where('status', 'confirmed')->sum('net_amount'),
                'funding' => (float) FundingRequest::whereDate('created_at', $day)->whereIn('status', ['funding_successful', 'funding_processing'])->sum('total_charged'),
                'shop' => (float) ShopOrder::whereDate('created_at', $day)->whereIn('status', ['paid', 'fulfilled'])->sum('total'),
            ];
        });

        return view('admin.dashboard', [
            'monitor' => $monitor,
            'cards' => [
                'users' => User::count(),
                'deposited' => Deposit::where('status', 'confirmed')->sum('net_amount'),
                'funded' => FundingRequest::where('status', 'funding_successful')->sum('target_amount'),
                'liabilities' => Wallet::sum('balance'),
                'revenue' => FundingRequest::whereIn('status', ['funding_successful'])->sum('fee'),
                'shop' => ShopOrder::whereIn('status', ['paid', 'fulfilled'])->sum('total'),
            ],
            'queues' => [
                'kyc' => KycVerification::where('status', 'pending')->count(),
                'agents' => Agent::where('status', 'pending')->count(),
                'deposits' => Deposit::whereIn('status', ['pending', 'under_review'])->count(),
                'funding' => FundingRequest::whereIn('status', ['manual_review', 'funding_processing'])->count(),
                'beneficiaries' => BeneficiaryAccount::where('status', 'pending')->count(),
                'risk' => RiskFlag::where('status', 'open')->count(),
            ],
            'recentDeposits' => Deposit::with('user')->latest()->take(6)->get(),
            'recentFunding' => FundingRequest::with('user')->latest()->take(6)->get(),
            'recentWebhooks' => WebhookEvent::latest()->take(6)->get(),
        ]);
    }
}
