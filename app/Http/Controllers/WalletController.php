<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $wallet = $user->primaryWallet();

        // Base-currency wallet first, then any others the user holds (see
        // DashboardController for the same pattern) — lets the balance card
        // become a swipeable carousel when there's more than one.
        $baseCurrency = config('platform.base_currency', 'XAF');
        $wallets = $user->wallets()->get()->sortByDesc(fn ($w) => $w->currency === $baseCurrency)->values();

        return view('dashboard.wallet', [
            'wallet' => $wallet,
            'wallets' => $wallets,
            'transactions' => $wallet->transactions()->paginate(15),
            'inflow' => $user->walletTransactions()->where('type', 'credit')->sum('amount'),
            'outflow' => $user->walletTransactions()->where('type', 'debit')->sum('amount'),
        ]);
    }

    /** The wallet ledger is authoritative — this streams exactly what's stored, nothing recalculated. */
    public function statement(Request $request): StreamedResponse
    {
        $rows = $request->user()->primaryWallet()->transactions()->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Reference', 'Date', 'Type', 'Category', 'Description', 'Amount', 'Currency', 'Balance after']);
            foreach ($rows as $tx) {
                fputcsv($out, [
                    $tx->reference, $tx->created_at->toDateTimeString(), ucfirst($tx->type), ucfirst($tx->category),
                    $tx->description, $tx->amount, $tx->currency, $tx->balance_after,
                ]);
            }
            fclose($out);
        }, 'wallet-statement-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }
}
