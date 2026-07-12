<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class WalletController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $wallet = $user->primaryWallet();

        return view('dashboard.wallet', [
            'wallet' => $wallet,
            'transactions' => $wallet->transactions()->paginate(15),
            'inflow' => $user->walletTransactions()->where('type', 'credit')->sum('amount'),
            'outflow' => $user->walletTransactions()->where('type', 'debit')->sum('amount'),
        ]);
    }
}
