<?php

namespace App\Http\Controllers;

use App\Models\KycLevel;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $wallet = $user->primaryWallet();

        // 7-day inflow vs outflow series for the transactions graph.
        $txSeries = collect(range(6, 0))->map(function ($i) use ($user) {
            $day = now()->subDays($i);
            return [
                'label' => $day->format('D'),
                'date' => $day->format('M j'),
                'credit' => (float) $user->walletTransactions()->where('type', 'credit')->whereDate('created_at', $day->toDateString())->sum('amount'),
                'debit' => (float) $user->walletTransactions()->where('type', 'debit')->whereDate('created_at', $day->toDateString())->sum('amount'),
            ];
        });

        return view('dashboard.index', [
            'txSeries' => $txSeries,
            'txInflow' => (float) $txSeries->sum('credit'),
            'txOutflow' => (float) $txSeries->sum('debit'),
            'user' => $user,
            'wallet' => $wallet,
            'recentDeposits' => $user->deposits()->latest()->take(5)->get(),
            'recentFunding' => $user->fundingRequests()->latest()->take(5)->get(),
            'transactions' => $wallet->transactions()->take(6)->get(),
            'beneficiaries' => $user->beneficiaryAccounts()->get(),
            'recentOrders' => $user->shopOrders()->with('items')->latest()->take(3)->get(),
            'popular' => ShopProduct::active()->where('is_featured', true)->orderBy('sort')->with('variants')->take(6)->get(),
            'shopCategories' => ShopCategory::active()->whereNull('parent_id')->take(8)->get(),
            'level' => KycLevel::where('level', $user->kyc_level)->first(),
            'nextLevel' => KycLevel::where('level', $user->kyc_level + 1)->first(),
            'stats' => [
                'funded' => $user->fundingRequests()->where('status', 'funding_successful')->sum('target_amount'),
                'pending' => $user->fundingRequests()->whereIn('status', ['payment_pending', 'funding_processing', 'manual_review'])->count(),
                'deposited' => $user->deposits()->where('status', 'confirmed')->sum('net_amount'),
            ],
        ]);
    }
}
