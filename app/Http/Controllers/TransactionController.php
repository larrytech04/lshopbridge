<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $query = $user->walletTransactions()->with('source')->latest();

        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        return view('dashboard.transactions', [
            'transactions' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only('type', 'category'),
        ]);
    }
}
