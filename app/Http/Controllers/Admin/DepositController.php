<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Services\Deposit\DepositService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepositController extends Controller
{
    public function index(Request $request): View
    {
        $query = Deposit::with('user', 'paymentMethod');
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->query('q')) {
            $query->where('reference', 'like', "%{$search}%");
        }

        return view('admin.deposits.index', [
            'deposits' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only('status', 'q'),
        ]);
    }

    public function show(Deposit $deposit): View
    {
        return view('admin.deposits.show', ['deposit' => $deposit->load('user', 'paymentMethod', 'walletTransactions')]);
    }

    public function confirm(Deposit $deposit, DepositService $deposits)
    {
        $deposits->confirm($deposit, auth()->user());

        return back()->with('success', 'Deposit confirmed and wallet credited.');
    }

    public function reject(Request $request, Deposit $deposit, DepositService $deposits)
    {
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $deposits->reject($deposit, $data['reason'], auth()->user());

        return back()->with('success', 'Deposit rejected.');
    }
}
