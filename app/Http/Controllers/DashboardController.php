<?php

namespace App\Http\Controllers;

use App\Models\KycLevel;
use App\Models\ShopCategory;
use App\Models\ShopProduct;
use App\Services\Navigation\NavigationBadgeService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $wallet = $user->primaryWallet();

        // Most users only ever hold their base-currency wallet, but anyone who's
        // funded or been paid in another currency has a second Wallet row that
        // never surfaced anywhere before — list every wallet, base currency first,
        // so the balance card can be swiped to reveal the rest.
        $baseCurrency = config('platform.base_currency', 'XAF');
        $wallets = $user->wallets()->get()->sortByDesc(fn ($w) => $w->currency === $baseCurrency)->values();

        // 7-day inflow vs outflow vs order-spend series for the transactions graph.
        $txSeries = collect(range(6, 0))->map(function ($i) use ($user) {
            $day = now()->subDays($i);
            return [
                'label' => $day->format('D'),
                'date' => $day->format('M j'),
                'credit' => (float) $user->walletTransactions()->where('type', 'credit')->whereDate('created_at', $day->toDateString())->sum('amount'),
                'debit' => (float) $user->walletTransactions()->where('type', 'debit')->whereDate('created_at', $day->toDateString())->sum('amount'),
                'orders' => (float) $user->shopOrders()->whereIn('status', ['paid', 'fulfilled'])->whereDate('created_at', $day->toDateString())->sum('total'),
            ];
        });

        return view('dashboard.index', [
            'navBadges' => app(NavigationBadgeService::class)->forUser($user),
            'txSeries' => $txSeries,
            'txInflow' => (float) $txSeries->sum('credit'),
            'txOutflow' => (float) $txSeries->sum('debit'),
            'txOrders' => (float) $txSeries->sum('orders'),
            'user' => $user,
            'wallet' => $wallet,
            'wallets' => $wallets,
            'recentDeposits' => $user->deposits()->latest()->take(5)->get(),
            'recentFunding' => $user->fundingRequests()->latest()->take(5)->get(),
            'transactions' => $wallet->transactions()->take(6)->get(),
            'beneficiaries' => $user->beneficiaryAccounts()->get(),
            'recentOrders' => $user->shopOrders()->with('items')->latest()->take(3)->get(),
            'popular' => ShopProduct::active()->where('is_featured', true)->orderBy('sort')->with('variants')->take(6)->get(),
            'shopCategories' => ShopCategory::active()->whereNull('parent_id')->take(8)->get(),
            'esimProducts' => ShopProduct::active()->where('type', 'esim')
                ->whereHas('variants', fn ($q) => $q->where('is_active', true))
                ->with('variants')->orderByDesc('is_featured')->orderByDesc('sales_count')->take(6)->get(),
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
