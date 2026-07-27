<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Country;
use App\Models\Deposit;
use App\Models\Dispute;
use App\Models\FundingRequest;
use App\Models\RiskFlag;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\AdminMessage;
use App\Services\Audit\AuditLogger;
use App\Services\Wallet\WalletService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $query = User::query()
            ->with('country', 'agent', 'wallets')
            ->withCount([
                'deposits', 'fundingRequests', 'shopOrders', 'beneficiaryAccounts', 'referrals', 'disputes',
                'riskFlags as open_risk_flags_count' => fn ($q) => $q->where('status', 'open'),
            ]);

        $this->applyFilters($query, $request);

        $sort = in_array($request->query('sort'), ['name', 'created_at', 'last_login_at', 'kyc_level', 'points'], true) ? $request->query('sort') : 'created_at';
        $dir = $request->query('dir') === 'asc' ? 'asc' : 'desc';

        $today = now()->startOfDay();
        $stats = [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'online' => User::where('last_seen_at', '>', now()->subMinutes(5))->count(),
            'new_today' => User::where('created_at', '>=', $today)->count(),
            'pending_kyc' => User::where('kyc_status', 'pending')->count(),
            'verified' => User::where('kyc_status', 'approved')->count(),
            'suspended' => User::where('status', 'suspended')->count(),
            'frozen_wallets' => Wallet::where('status', 'frozen')->distinct('user_id')->count('user_id'),
            'blocked' => User::where('status', 'blocked')->count(),
            'agents' => User::where('role', 'agent')->count(),
            'admins' => User::whereIn('role', ['admin', 'super_admin'])->count(),
            'deposits_today' => (float) Deposit::whereDate('created_at', $today)->where('status', 'confirmed')->sum('net_amount'),
            'funding_today' => (float) FundingRequest::whereDate('created_at', $today)->where('status', 'funding_successful')->sum('target_amount'),
            'open_tickets' => Dispute::whereIn('status', ['open', 'in_progress'])->count(),
            'fraud_alerts' => RiskFlag::where('status', 'open')->count(),
            'total_balance' => (float) Wallet::sum('balance'),
        ];

        $regTrend = collect(range(13, 0))->map(function ($daysAgo) {
            $day = now()->subDays($daysAgo);
            return [
                'label' => $day->format('D'),
                'count' => User::whereDate('created_at', $day->toDateString())->count(),
            ];
        });

        $insights = [
            'highest_depositor' => User::withSum(['deposits as total_deposited' => fn ($q) => $q->where('status', 'confirmed')], 'net_amount')->orderByDesc('total_deposited')->first(),
            'most_active' => User::withCount('walletTransactions')->orderByDesc('wallet_transactions_count')->first(),
            'suspicious' => User::whereHas('riskFlags', fn ($q) => $q->where('status', 'open'), '>=', 2)
                ->withCount(['riskFlags as open_flags_count' => fn ($q) => $q->where('status', 'open')])
                ->take(5)->get(),
            'recent_count' => User::where('created_at', '>=', now()->subDay())->count(),
            'inactive_count' => User::where('status', 'active')->where(fn ($q) => $q->whereNull('last_login_at')->orWhere('last_login_at', '<', now()->subDays(30)))->count(),
            'pending_kyc_count' => $stats['pending_kyc'],
            'pending_agents_count' => Agent::where('status', 'pending')->count(),
        ];

        return view('admin.users.index', [
            'users' => $query->orderBy($sort, $dir)->paginate(20)->withQueryString(),
            'filters' => $request->only([
                'q', 'role', 'status', 'kyc_level', 'kyc_status', 'country_id', 'online', 'email_verified',
                'phone_verified', 'two_factor_enabled', 'balance_min', 'balance_max', 'created_from', 'created_to',
                'tier', 'risk_level', 'china_wallet', 'currency', 'tag', 'agent_status', 'sort', 'dir',
            ]),
            'countries' => Country::active()->orderBy('name')->get(),
            'allTags' => User::whereNotNull('tags')->pluck('tags')->flatten()->unique()->values(),
            'stats' => $stats,
            'insights' => $insights,
            'regTrend' => $regTrend,
        ]);
    }

    protected function applyFilters(Builder $query, Request $request): void
    {
        if ($q = $request->query('q')) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('email', 'like', "%{$q}%")
                    ->orWhere('phone', 'like', "%{$q}%")
                    ->orWhere('referral_code', 'like', "%{$q}%")
                    ->orWhere('city', 'like', "%{$q}%")
                    ->orWhereHas('country', fn ($c) => $c->where('name', 'like', "%{$q}%"))
                    ->orWhereHas('beneficiaryAccounts', fn ($b) => $b->where('account_id', 'like', "%{$q}%")->orWhere('account_name', 'like', "%{$q}%"))
                    ->orWhereHas('agent', fn ($a) => $a->where('business_name', 'like', "%{$q}%"));
                if (is_numeric($q)) {
                    $w->orWhere('id', (int) $q);
                }
            });
        }

        if ($v = $request->query('role')) $query->where('role', $v);
        if ($v = $request->query('status')) $query->where('status', $v);
        if ($request->query('kyc_level') !== null && $request->query('kyc_level') !== '') $query->where('kyc_level', $request->query('kyc_level'));
        if ($v = $request->query('kyc_status')) $query->where('kyc_status', $v);
        if ($v = $request->query('country_id')) $query->where('country_id', $v);
        if ($v = $request->query('agent_status')) $query->whereHas('agent', fn ($a) => $a->where('status', $v));
        if ($v = $request->query('currency')) $query->whereHas('wallets', fn ($w) => $w->where('currency', $v));
        if ($v = $request->query('tag')) $query->whereJsonContains('tags', $v);

        if ($request->query('online') === '1') $query->where('last_seen_at', '>', now()->subMinutes(5));
        if ($request->query('online') === '0') $query->where(fn ($w) => $w->whereNull('last_seen_at')->orWhere('last_seen_at', '<=', now()->subMinutes(5)));

        if ($request->query('email_verified') === '1') $query->whereNotNull('email_verified_at');
        if ($request->query('email_verified') === '0') $query->whereNull('email_verified_at');
        if ($request->query('phone_verified') === '1') $query->whereNotNull('phone_verified_at');
        if ($request->query('phone_verified') === '0') $query->whereNull('phone_verified_at');
        if ($request->query('two_factor_enabled') === '1') $query->where('two_factor_enabled', true);
        if ($request->query('two_factor_enabled') === '0') $query->where('two_factor_enabled', false);

        if ($request->query('china_wallet') === '1') $query->whereHas('beneficiaryAccounts');
        if ($request->query('china_wallet') === '0') $query->whereDoesntHave('beneficiaryAccounts');

        if ($v = $request->query('balance_min')) $query->whereHas('wallets', fn ($w) => $w->where('balance', '>=', (float) $v));
        if ($v = $request->query('balance_max')) $query->whereHas('wallets', fn ($w) => $w->where('balance', '<=', (float) $v));

        if ($v = $request->query('created_from')) $query->whereDate('created_at', '>=', $v);
        if ($v = $request->query('created_to')) $query->whereDate('created_at', '<=', $v);

        if ($v = $request->query('tier')) {
            match ($v) {
                'gold' => $query->where('points', '>=', 1000),
                'silver' => $query->whereBetween('points', [250, 999]),
                'bronze' => $query->where('points', '<', 250),
                default => null,
            };
        }

        if ($v = $request->query('risk_level')) {
            $openFlags = fn ($q) => $q->where('status', 'open');
            match ($v) {
                'none' => $query->whereDoesntHave('riskFlags', $openFlags),
                'low' => $query->whereHas('riskFlags', $openFlags, '=', 1),
                'medium' => $query->whereHas('riskFlags', $openFlags, '>=', 2)->whereHas('riskFlags', $openFlags, '<=', 3),
                'high' => $query->whereHas('riskFlags', $openFlags, '>=', 4),
                default => null,
            };
        }
    }

    public function exportCsv(Request $request)
    {
        $query = User::query()->with('country');
        $this->applyFilters($query, $request);
        $rows = $query->latest()->get();

        $headers = ['Content-Type' => 'text/csv', 'Content-Disposition' => 'attachment; filename="users-export.csv"'];

        return response()->stream(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Country', 'Status', 'KYC status', 'KYC level', 'Points', 'Created']);
            foreach ($rows as $u) {
                fputcsv($out, [$u->id, $u->name, $u->email, $u->phone, $u->role->value, $u->country->name ?? '', $u->status, $u->kyc_status->value, $u->kyc_level, $u->points, $u->created_at]);
            }
            fclose($out);
        }, 200, $headers);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:user,agent,admin,super_admin'],
            'phone' => ['nullable', 'string', 'max:30'],
            'country_id' => ['nullable', 'exists:countries,id'],
        ]);

        if (in_array($data['role'], ['admin', 'super_admin'], true) && ! $request->user()->isSuperAdmin()) {
            return back()->withErrors(['role' => 'Only a super admin can create admin accounts.'])->withInput();
        }

        $user = User::create([
            ...$data,
            'password' => Hash::make($data['password']),
            'status' => 'active',
            'kyc_status' => 'pending',
        ]);

        $audit->log('admin.user.created', "Created user {$user->email}", $user);

        return back()->with('success', "User {$user->name} created.");
    }

    public function destroy(Request $request, User $user, AuditLogger $audit)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['delete' => "You can't delete your own account."]);
        }
        if ($user->isAdmin() && ! $request->user()->isSuperAdmin()) {
            return back()->withErrors(['delete' => 'Only a super admin can delete admin accounts.']);
        }

        $audit->log('admin.user.deleted', "Deleted user {$user->email}", $user);
        $user->delete();

        return back()->with('success', 'User deleted (recoverable).');
    }

    public function assignTags(Request $request, User $user, AuditLogger $audit)
    {
        $data = $request->validate(['tags' => ['nullable', 'string', 'max:500']]);
        $tags = collect(explode(',', $data['tags'] ?? ''))->map(fn ($t) => trim($t))->filter()->values()->all();

        $user->update(['tags' => $tags]);
        $audit->log('admin.user.tags_updated', "Updated tags for {$user->email}", $user, ['tags' => $tags]);

        return back()->with('success', 'Tags updated.');
    }

    public function bulkAction(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:users,id'],
            'action' => ['required', 'in:verify,suspend,activate,delete,notify,credit,debit,tags'],
            'amount' => ['nullable', 'numeric', 'min:0.01'],
            'reason' => ['nullable', 'string', 'max:255'],
            'subject' => ['nullable', 'string', 'max:150'],
            'body' => ['nullable', 'string', 'max:2000'],
            'send_mail' => ['nullable', 'boolean'],
            'tags' => ['nullable', 'string', 'max:500'],
        ]);

        $users = User::whereIn('id', $data['ids'])->get();
        $wallet = app(WalletService::class);
        $count = 0;

        foreach ($users as $user) {
            match ($data['action']) {
                'verify' => $user->update(['kyc_status' => 'approved']),
                'suspend' => $user->update(['status' => 'suspended']),
                'activate' => $user->update(['status' => 'active']),
                'delete' => $user->is($request->user()) ? null : $user->delete(),
                'notify' => $user->notify(new AdminMessage($data['subject'] ?? 'Notice', $data['body'] ?? '', (bool) ($data['send_mail'] ?? false))),
                'credit' => $wallet->credit($user->primaryWallet(), (float) ($data['amount'] ?? 0), 'adjustment', null, $data['reason'] ?? 'Bulk credit'),
                'debit' => $wallet->debit($user->primaryWallet(), (float) ($data['amount'] ?? 0), 'adjustment', null, $data['reason'] ?? 'Bulk debit'),
                'tags' => $user->update(['tags' => collect(explode(',', $data['tags'] ?? ''))->map(fn ($t) => trim($t))->filter()->values()->all()]),
                default => null,
            };
            $count++;
        }

        $audit->log('admin.user.bulk_'.$data['action'], "Bulk {$data['action']} applied to {$count} users", null, ['ids' => $data['ids']]);

        return back()->with('success', "Bulk action applied to {$count} users.");
    }

    public function show(User $user): View
    {
        $deposits = $user->deposits()->latest()->take(25)->get();
        $funding = $user->fundingRequests()->latest()->take(25)->get();
        $orders = $user->shopOrders()->latest()->take(25)->get();
        $disputes = $user->disputes()->latest()->take(25)->get();

        $stats = [
            'lifetime_deposits' => (float) $user->deposits()->where('status', 'confirmed')->sum('net_amount'),
            'lifetime_funding_sent' => (float) $user->fundingRequests()->where('status', 'funding_successful')->sum('target_amount'),
            'lifetime_spending' => (float) $user->shopOrders()->whereIn('status', ['paid', 'fulfilled'])->sum('total'),
            'lifetime_credits' => (float) $user->walletTransactions()->where('type', 'credit')->where('category', '!=', 'deposit')->sum('amount'),
            'fees_paid' => (float) $user->deposits()->sum('fee') + (float) $user->fundingRequests()->sum('fee'),
            'pending_count' => $user->deposits()->whereIn('status', ['pending', 'under_review'])->count()
                + $user->fundingRequests()->whereIn('status', ['manual_review', 'funding_processing'])->count(),
        ];

        $kycVerifications = $user->kycVerifications()->latest()->take(25)->get();

        $timeline = collect()
            ->concat($deposits->map(fn ($d) => ['type' => 'deposit', 'icon' => 'deposit', 'color' => '#10B981', 'title' => 'Deposit '.money($d->net_amount, $d->currency), 'subtitle' => ucfirst(str_replace('_', ' ', is_object($d->status) ? $d->status->value : $d->status)), 'at' => $d->created_at, 'url' => route('admin.deposits.show', $d)]))
            ->concat($funding->map(fn ($f) => ['type' => 'funding', 'icon' => 'fund', 'color' => '#F97316', 'title' => 'Funding '.money($f->target_amount, $f->target_currency), 'subtitle' => ucfirst(str_replace('_', ' ', is_object($f->status) ? $f->status->value : $f->status)), 'at' => $f->created_at, 'url' => route('admin.funding.show', $f)]))
            ->concat($orders->map(fn ($o) => ['type' => 'order', 'icon' => 'bag', 'color' => '#EC4899', 'title' => 'Order '.money($o->total, $o->currency), 'subtitle' => $o->status->label(), 'at' => $o->created_at, 'url' => route('admin.shop.orders.show', $o)]))
            ->concat($disputes->map(fn ($d) => ['type' => 'dispute', 'icon' => 'alert', 'color' => '#EF4444', 'title' => 'Ticket: '.$d->subject, 'subtitle' => $d->status->label(), 'at' => $d->created_at, 'url' => route('admin.disputes.show', $d)]))
            ->concat($kycVerifications->map(fn ($k) => ['type' => 'kyc', 'icon' => 'shield', 'color' => '#0EA5E9', 'title' => 'KYC submission (L'.$k->target_level.')', 'subtitle' => ucfirst(str_replace('_', ' ', is_object($k->status) ? $k->status->value : $k->status)), 'at' => $k->created_at, 'url' => route('admin.kyc.show', $k)]))
            ->sortByDesc('at')
            ->take(40)
            ->values();

        return view('admin.users.show', [
            'user' => $user->load('country', 'wallets', 'beneficiaryAccounts', 'agent'),
            'deposits' => $deposits,
            'funding' => $funding,
            'transactions' => $user->walletTransactions()->latest()->take(50)->get(),
            'flags' => $user->riskFlags()->latest()->take(25)->get(),
            'orders' => $orders,
            'disputes' => $disputes,
            'kycVerifications' => $kycVerifications,
            'reviews' => $user->reviews()->latest()->take(25)->get(),
            'referrals' => $user->referrals()->latest()->take(25)->get(),
            'activity' => AuditLog::where('user_id', $user->id)->latest()->take(50)->get(),
            'adminLog' => AuditLog::where('auditable_type', User::class)->where('auditable_id', $user->id)->latest()->take(50)->get(),
            'sessions' => DB::table('sessions')->where('user_id', $user->id)->orderByDesc('last_activity')->get(),
            'notifications' => $user->notifications()->latest()->take(25)->get(),
            'countries' => Country::active()->orderBy('name')->get(),
            'stats' => $stats,
            'timeline' => $timeline,
        ]);
    }

    public function rowDetail(User $user)
    {
        $timeline = collect()
            ->concat($user->deposits()->latest()->take(3)->get()->map(fn ($d) => ['icon' => 'deposit', 'color' => '#10B981', 'title' => 'Deposit '.money($d->net_amount, $d->currency), 'at' => $d->created_at->diffForHumans()]))
            ->concat($user->fundingRequests()->latest()->take(3)->get()->map(fn ($f) => ['icon' => 'fund', 'color' => '#F97316', 'title' => 'Funding '.money($f->target_amount, $f->target_currency), 'at' => $f->created_at->diffForHumans()]))
            ->concat($user->shopOrders()->latest()->take(3)->get()->map(fn ($o) => ['icon' => 'bag', 'color' => '#EC4899', 'title' => 'Order '.money($o->total, $o->currency), 'at' => $o->created_at->diffForHumans()]))
            ->sortByDesc('at')->take(6)->values();

        return response()->json([
            'wallet_balance' => money(optional($user->wallets->first())->balance ?? 0, config('platform.base_currency')),
            'timeline' => $timeline,
            'tickets' => $user->disputes()->latest()->take(3)->get(['subject', 'status', 'created_at'])->map(fn ($d) => ['subject' => $d->subject, 'status' => $d->status->label(), 'at' => $d->created_at->diffForHumans()]),
            'china_wallets' => $user->beneficiaryAccounts()->get()->map(fn ($b) => ['name' => $b->account_name, 'type' => $b->app_type->label(), 'status' => $b->status]),
            'notes' => $user->admin_notes,
            'risk_flags' => $user->riskFlags()->where('status', 'open')->get(['rule_code', 'severity'])->map(fn ($f) => ['code' => $f->rule_code, 'severity' => $f->severity]),
        ]);
    }

    public function update(Request $request, User $user, AuditLogger $audit)
    {
        $data = $request->validate([
            'role' => ['required', 'in:user,agent,admin,super_admin'],
            'status' => ['required', 'in:active,suspended,blocked'],
            'kyc_level' => ['required', 'integer', 'between:0,3'],
            'status_reason' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:30'],
            'phone_country' => ['nullable', 'string', 'max:5'],
            'country_id' => ['nullable', 'exists:countries,id'],
            'city' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'in:male,female,other'],
            'points' => ['nullable', 'integer', 'min:0'],
            'email_verified' => ['nullable', 'boolean'],
            'phone_verified' => ['nullable', 'boolean'],
        ]);

        // Only a super admin may grant admin/super_admin roles.
        if (in_array($data['role'], ['admin', 'super_admin'], true) && ! $request->user()->isSuperAdmin()) {
            return back()->withErrors(['role' => 'Only a super admin can assign admin roles.']);
        }

        $data['email_verified_at'] = $request->boolean('email_verified') ? ($user->email_verified_at ?? now()) : null;
        $data['phone_verified_at'] = $request->boolean('phone_verified') ? ($user->phone_verified_at ?? now()) : null;
        unset($data['email_verified'], $data['phone_verified']);
        // 2FA is deliberately not editable here: it's real TOTP MFA the user
        // enrolls themselves (a secret only they possess), an admin can only
        // reset it via resetTwoFactor() below, never directly turn it "on".

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

    public function toggleWalletFreeze(User $user, AuditLogger $audit)
    {
        $w = $user->primaryWallet();
        $w->status = $w->status === 'frozen' ? 'active' : 'frozen';
        $w->save();

        $audit->log('admin.wallet.'.($w->status === 'frozen' ? 'frozen' : 'unfrozen'), "Wallet {$w->status} for {$user->email}", $user);

        return back()->with('success', "Wallet {$w->status}.");
    }

    public function updateNotes(Request $request, User $user, AuditLogger $audit)
    {
        $data = $request->validate(['admin_notes' => ['nullable', 'string', 'max:5000']]);

        $user->update($data);
        $audit->log('admin.user.notes_updated', "Updated internal notes for {$user->email}", $user);

        return back()->with('success', 'Notes saved.');
    }

    public function quickStatus(Request $request, User $user, AuditLogger $audit)
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,suspended,blocked'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $user->update(['status' => $data['status'], 'status_reason' => $data['reason'] ?? $user->status_reason]);
        $audit->log('admin.user.status_changed', "Set {$user->email} to {$data['status']}", $user, $data);

        return back()->with('success', "User marked {$data['status']}.");
    }

    public function resetPassword(User $user, AuditLogger $audit)
    {
        Password::sendResetLink(['email' => $user->email]);
        $audit->log('admin.user.password_reset_sent', "Password reset link sent to {$user->email}", $user);

        return back()->with('success', 'Password reset link sent.');
    }

    public function resetTwoFactor(User $user, AuditLogger $audit)
    {
        $user->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
            'two_factor_disabled_at' => now(),
        ]);
        $audit->log('admin.user.2fa_reset', "2FA reset for {$user->email}", $user);

        $user->notify(new \App\Notifications\SecurityAlert(
            title: 'Two-factor authentication was reset',
            message: "An administrator turned off two-factor authentication on your account. If you didn't request this, contact support and re-enable it as soon as possible.",
            actionLabel: 'Review account security',
            actionUrl: route('security.index'),
        ));

        return back()->with('success', '2FA has been reset.');
    }

    public function impersonate(Request $request, User $user, AuditLogger $audit)
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['impersonate' => "You can't impersonate yourself."]);
        }

        $audit->log('admin.user.impersonate_start', "{$request->user()->email} started impersonating {$user->email}", $user);

        session(['impersonator_id' => $request->user()->id]);
        Auth::login($user);

        return redirect()->route('dashboard')->with('success', "You are now viewing the site as {$user->name}.");
    }

    public function notify(Request $request, User $user)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
            'send_mail' => ['nullable', 'boolean'],
        ]);

        $user->notify(new AdminMessage($data['subject'], $data['body'], $request->boolean('send_mail')));

        return back()->with('success', 'Notification sent.');
    }

    public function revokeSession(Request $request, User $user, string $session, AuditLogger $audit)
    {
        DB::table('sessions')->where('id', $session)->where('user_id', $user->id)->delete();
        $audit->log('admin.user.session_revoked', "Revoked a session for {$user->email}", $user);

        return back()->with('success', 'Session revoked.');
    }

    public function stopImpersonating(Request $request, AuditLogger $audit)
    {
        $adminId = session('impersonator_id');
        $impersonatedUser = $request->user();

        if (! $adminId || ! ($admin = User::find($adminId))) {
            return redirect()->route('dashboard');
        }

        $audit->log('admin.user.impersonate_end', "{$admin->email} stopped impersonating {$impersonatedUser->email}", $impersonatedUser, [], $admin->id);

        session()->forget('impersonator_id');
        Auth::login($admin);

        return redirect()->route('admin.users.show', $impersonatedUser)->with('success', 'Returned to your admin account.');
    }

    public function exportActivity(User $user)
    {
        $rows = AuditLog::where('user_id', $user->id)->latest()->get();

        $filename = "user-{$user->id}-activity.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Date', 'Action', 'Description', 'IP', 'User agent']);
            foreach ($rows as $r) {
                fputcsv($out, [$r->created_at, $r->action, $r->description, $r->ip, $r->user_agent]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
