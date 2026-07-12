<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Wallet\WalletService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()->with('country')->withCount('deposits', 'fundingRequests');

        if ($search = $request->query('q')) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }
        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }
        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        return view('admin.users.index', [
            'users' => $query->latest()->paginate(20)->withQueryString(),
            'filters' => $request->only('q', 'role', 'status'),
        ]);
    }

    public function show(User $user): View
    {
        return view('admin.users.show', [
            'user' => $user->load('country', 'wallets', 'beneficiaryAccounts'),
            'deposits' => $user->deposits()->latest()->take(10)->get(),
            'funding' => $user->fundingRequests()->latest()->take(10)->get(),
            'transactions' => $user->walletTransactions()->latest()->take(15)->get(),
            'flags' => $user->riskFlags()->latest()->take(10)->get(),
        ]);
    }

    public function update(Request $request, User $user, AuditLogger $audit)
    {
        $data = $request->validate([
            'role' => ['required', 'in:user,agent,admin,super_admin'],
            'status' => ['required', 'in:active,suspended,blocked'],
            'kyc_level' => ['required', 'integer', 'between:0,3'],
            'status_reason' => ['nullable', 'string', 'max:255'],
        ]);

        // Only a super admin may grant admin/super_admin roles.
        if (in_array($data['role'], ['admin', 'super_admin'], true) && ! $request->user()->isSuperAdmin()) {
            return back()->withErrors(['role' => 'Only a super admin can assign admin roles.']);
        }

        $user->update($data);
        $audit->log('admin.user.updated', "Updated user {$user->email}", $user, $data);

        return back()->with('success', 'User updated.');
    }

    public function adjustWallet(Request $request, User $user, WalletService $wallet, AuditLogger $audit)
    {
        $data = $request->validate([
            'type' => ['required', 'in:credit,debit'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'reason' => ['required', 'string', 'max:255'],
        ]);

        $w = $user->primaryWallet();

        $data['type'] === 'credit'
            ? $wallet->credit($w, (float) $data['amount'], 'adjustment', null, $data['reason'])
            : $wallet->debit($w, (float) $data['amount'], 'adjustment', null, $data['reason']);

        $audit->log('admin.wallet.adjusted', "Manual {$data['type']} for {$user->email}", $user, $data);

        return back()->with('success', 'Wallet adjusted.');
    }
}
