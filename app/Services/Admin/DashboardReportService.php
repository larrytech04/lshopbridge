<?php

namespace App\Services\Admin;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Country;
use App\Models\Deposit;
use App\Models\Dispute;
use App\Models\FundingRequest;
use App\Models\KycVerification;
use App\Models\RiskFlag;
use App\Models\ShopOrder;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WebhookEvent;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * All aggregate reporting for the admin Overview / Command Center. Every
 * figure here comes from a real query against real columns — nothing is
 * invented. Where the schema genuinely can't support a metric (e.g. there is
 * no lat/lng anywhere in the database, no provider API-response-time
 * tracking, no commission ledger), that section is scoped down or omitted
 * rather than faked. See the dashboard view for the honest labels used.
 */
class DashboardReportService
{
    protected string $currency;

    public function __construct(protected ProviderHealthService $health)
    {
        $this->currency = config('platform.base_currency', 'XAF');
    }

    /* -------------------------------------------------- Period resolution */

    public function resolvePeriod(Request $request): array
    {
        $key = $request->query('period', '30d');
        $now = now();

        [$from, $to, $label] = match ($key) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay(), 'Today'],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay(), 'Yesterday'],
            '7d' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay(), 'Last 7 days'],
            '14d' => [$now->copy()->subDays(13)->startOfDay(), $now->copy()->endOfDay(), 'Last 14 days'],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay(), 'This month'],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth(), 'Previous month'],
            'quarter' => [$now->copy()->startOfQuarter(), $now->copy()->endOfDay(), 'This quarter'],
            'year' => [$now->copy()->startOfYear(), $now->copy()->endOfDay(), 'This year'],
            'custom' => [
                $request->query('from') ? Carbon::parse($request->query('from'))->startOfDay() : $now->copy()->subDays(29)->startOfDay(),
                $request->query('to') ? Carbon::parse($request->query('to'))->endOfDay() : $now->copy()->endOfDay(),
                'Custom range',
            ],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay(), 'Last 30 days'],
        };

        $days = max(1, $from->diffInDays($to) + 1);
        $prevTo = $from->copy()->subSecond();
        $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

        return [
            'key' => $key, 'label' => $label, 'from' => $from, 'to' => $to,
            'prevFrom' => $prevFrom, 'prevTo' => $prevTo, 'days' => $days,
            'compare' => $request->boolean('compare', true),
        ];
    }

    /* -------------------------------------------------- Full report */

    public function build(array $p): array
    {
        return [
            'period' => $p,
            'currency' => $this->currency,
            'kpis' => $this->kpis($p),
            'attention' => $this->attention(),
            'geo' => $this->geoBreakdown($p),
            'financialSeries' => $this->financialSeries($p),
            'reconciliation' => $this->reconciliation(),
            'transactions' => $this->transactionFeed(),
            'customer' => $this->customerOps($p),
            'compliance' => $this->compliance($p),
            'marketplace' => $this->marketplace($p),
            'agents' => $this->agentNetwork(),
            'providers' => $this->providerHealth(),
            'system' => $this->systemHealth(),
            'support' => $this->support($p),
            'insights' => $this->insights($p),
            'activity' => AuditLog::with('user')->latest()->take(15)->get(),
        ];
    }

    /* -------------------------------------------------- Executive KPIs */

    protected function delta(float $current, float $previous): ?float
    {
        if ($previous == 0.0) {
            return $current == 0.0 ? 0.0 : null; // null = "new" (no baseline to compare against)
        }

        return round((($current - $previous) / abs($previous)) * 100, 1);
    }

    protected function kpi(string $label, float $current, float $previous, string $img, string $tint, ?string $href = null, bool $money = false, string $hint = ''): array
    {
        return [
            'label' => $label, 'value' => $current, 'previous' => $previous,
            'delta' => $this->delta($current, $previous), 'img' => $img, 'tint' => $tint,
            'href' => $href, 'money' => $money, 'hint' => $hint,
        ];
    }

    public function kpis(array $p): array
    {
        [$from, $to, $pf, $pt] = [$p['from'], $p['to'], $p['prevFrom'], $p['prevTo']];

        $sumIn = fn ($query, $col, $from, $to) => (clone $query)->whereBetween('created_at', [$from, $to])->sum($col);
        $countIn = fn ($query, $from, $to) => (clone $query)->whereBetween('created_at', [$from, $to])->count();

        $depositsConfirmed = Deposit::where('status', 'confirmed');
        $fundingSuccessful = FundingRequest::where('status', 'funding_successful');
        $ordersPaid = ShopOrder::whereIn('status', ['paid', 'fulfilled']);
        $ordersRefunded = ShopOrder::where('status', 'refunded');

        $financial = [
            $this->kpi('Total deposited', $sumIn($depositsConfirmed, 'net_amount', $from, $to), $sumIn($depositsConfirmed, 'net_amount', $pf, $pt), 'Saving-Bank-Cash--Streamline-Ultimate.png', '#10B981', route('admin.deposits.index'), true, 'Confirmed deposits credited to customer wallets in this period.'),
            $this->kpi('Wallet funding sent', $sumIn($fundingSuccessful, 'target_amount', $from, $to), $sumIn($fundingSuccessful, 'target_amount', $pf, $pt), 'Yuan--Streamline-Plump.png', '#F97316', route('admin.funding.index'), false, 'Successful funding delivered to China wallets (CNY), this period.'),
            $this->kpi('Marketplace sales', $sumIn($ordersPaid, 'total', $from, $to), $sumIn($ordersPaid, 'total', $pf, $pt), 'Shop-Sign-Bag--Streamline-Ultimate.png', '#EC4899', route('admin.shop.orders.index'), true, 'Paid + fulfilled shop order value.'),
            $this->kpi('Fee revenue', $sumIn($fundingSuccessful, 'fee', $from, $to) + $sumIn($depositsConfirmed, 'fee', $from, $to), $sumIn($fundingSuccessful, 'fee', $pf, $pt) + $sumIn($depositsConfirmed, 'fee', $pf, $pt), 'Money-Bags--Streamline-Ultimate.png', '#7C5CFC', null, true, 'Fees earned on deposits + successful funding.'),
            $this->kpi('Refunds', $sumIn($ordersRefunded, 'total', $from, $to), $sumIn($ordersRefunded, 'total', $pf, $pt), 'Receipt-Slip-1--Streamline-Ultimate.png', '#EF4444', route('admin.shop.orders.index'), true, 'Refunded shop order value.'),
            $this->kpi('Wallet liabilities', (float) Wallet::sum('balance'), (float) Wallet::sum('balance'), 'Money-Wallet-1--Streamline-Ultimate.png', '#3B82F6', null, true, 'Total customer wallet balance the platform owes back — a point-in-time balance, not period-scoped.'),
        ];
        // Wallet liabilities is a live balance (not a period flow), so no meaningful delta — mark explicitly.
        $financial[5]['delta'] = null;

        $customer = [
            $this->kpi('Total users', (float) User::count(), (float) User::where('created_at', '<', $from)->count(), 'Multiple-Users-1--Streamline-Ultimate.svg', '#3B82F6', route('admin.users.index')),
            $this->kpi('New users', (float) $countIn(User::query(), $from, $to), (float) $countIn(User::query(), $pf, $pt), 'User-Story--Streamline-Ultimate.png', '#8B5CF6', route('admin.users.index')),
            $this->kpi('Verified users (KYC)', (float) User::where('kyc_status', 'approved')->count(), (float) User::where('kyc_status', 'approved')->where('created_at', '<', $from)->count(), 'Verified--Streamline-Rounded-Streamline-Material.png', '#0EA5E9', route('admin.users.index', ['kyc_status' => 'approved'])),
            $this->kpi('Pending KYC', (float) User::where('kyc_status', 'pending')->count(), (float) User::where('kyc_status', 'pending')->count(), 'Work-Pending-For-Review--Streamline-Bangalore.png', '#F59E0B', route('admin.kyc.index')),
            $this->kpi('Suspended accounts', (float) User::where('status', 'suspended')->count(), (float) User::where('status', 'suspended')->count(), 'Disability-Help-Alarm-Sos--Streamline-Ultimate.png', '#EF4444', route('admin.users.index', ['status' => 'suspended'])),
            $this->kpi('Online now', (float) User::where('last_seen_at', '>', now()->subMinutes(5))->count(), 0.0, 'User-Network--Streamline-Ultimate.png', '#22C55E', route('admin.users.index', ['online' => 1])),
        ];
        $customer[4]['delta'] = null; // point-in-time count
        $customer[5]['delta'] = null; // point-in-time count

        $operational = [
            $this->kpi('Pending deposits', (float) Deposit::whereIn('status', ['pending', 'under_review', 'processing'])->count(), 0.0, 'Credit-Card-Receive--Streamline-Sharp-Streamline-Material.png', '#F59E0B', route('admin.deposits.index')),
            $this->kpi('Pending funding', (float) FundingRequest::whereIn('status', ['manual_review', 'funding_processing', 'payment_pending'])->count(), 0.0, 'Real-Estate-Insurance-Dollar-Hand-House--Streamline-Freehand.png', '#F97316', route('admin.funding.index')),
            $this->kpi('Pending orders', (float) ShopOrder::where('status', 'pending')->count(), 0.0, 'Receipt-Slip-1--Streamline-Ultimate.png', '#EC4899', route('admin.shop.orders.index')),
            $this->kpi('Open disputes', (float) Dispute::whereIn('status', ['open', 'in_progress'])->count(), 0.0, 'Customer-Relationship-Management-Call-Center-Support--Streamline-Ultimate.png', '#EF4444', route('admin.disputes.index')),
            $this->kpi('Failed transactions', (float) (Deposit::where('status', 'failed')->count() + FundingRequest::where('status', 'funding_failed')->count()), 0.0, 'Identity-Theft--Streamline-Brooklyn.png', '#EF4444', null),
            $this->kpi('Agent applications', (float) Agent::where('status', 'pending')->count(), 0.0, 'Delivery-Package-Give--Streamline-Freehand.png', '#0EA5E9', route('admin.agents.index')),
        ];
        foreach ($operational as $i => $row) { $operational[$i]['delta'] = null; }

        return ['financial' => $financial, 'customer' => $customer, 'operational' => $operational];
    }

    /* -------------------------------------------------- Attention center */

    public function attention(): array
    {
        $items = [];

        if (($n = User::where('kyc_status', 'pending')->count()) > 0) {
            $oldest = User::where('kyc_status', 'pending')->min('created_at');
            $items[] = ['label' => 'Pending KYC reviews', 'count' => $n, 'amount' => null, 'severity' => $n > 20 ? 'high' : 'medium', 'oldest' => $oldest, 'href' => route('admin.kyc.index')];
        }
        if (($n = Deposit::where('status', 'failed')->where('created_at', '>=', now()->subDays(7))->count()) > 0) {
            $items[] = ['label' => 'Failed deposits (7d)', 'count' => $n, 'amount' => null, 'severity' => 'medium', 'oldest' => Deposit::where('status', 'failed')->min('created_at'), 'href' => route('admin.deposits.index', ['status' => 'failed'])];
        }
        if (($n = WebhookEvent::whereIn('status', ['received', 'failed'])->where('processed_at', null)->count()) > 0) {
            $items[] = ['label' => 'Unprocessed webhooks', 'count' => $n, 'amount' => null, 'severity' => $n > 10 ? 'high' : 'low', 'oldest' => WebhookEvent::whereNull('processed_at')->min('created_at'), 'href' => route('admin.webhooks.index')];
        }
        if (($n = RiskFlag::where('status', 'open')->count()) > 0) {
            $items[] = ['label' => 'Open risk flags', 'count' => $n, 'amount' => null, 'severity' => 'high', 'oldest' => RiskFlag::where('status', 'open')->min('created_at'), 'href' => route('admin.risk.index')];
        }
        if (($n = FundingRequest::whereIn('status', ['manual_review', 'payment_pending'])->count()) > 0) {
            $amt = FundingRequest::whereIn('status', ['manual_review', 'payment_pending'])->sum('total_charged');
            $items[] = ['label' => 'Pending funding requests', 'count' => $n, 'amount' => $amt, 'severity' => 'medium', 'oldest' => FundingRequest::whereIn('status', ['manual_review', 'payment_pending'])->min('created_at'), 'href' => route('admin.funding.index')];
        }
        if (($n = Dispute::whereIn('status', ['open', 'in_progress'])->where('priority', 'high')->count()) > 0) {
            $items[] = ['label' => 'Urgent support tickets', 'count' => $n, 'amount' => null, 'severity' => 'critical', 'oldest' => Dispute::where('priority', 'high')->min('created_at'), 'href' => route('admin.disputes.index')];
        }
        if (($n = DB::table('failed_jobs')->count()) > 0) {
            $items[] = ['label' => 'Failed background jobs', 'count' => $n, 'amount' => null, 'severity' => $n > 5 ? 'high' : 'medium', 'oldest' => DB::table('failed_jobs')->min('failed_at'), 'href' => route('admin.dashboard').'#system'];
        }
        if (($n = Agent::where('status', 'pending')->count()) > 0) {
            $items[] = ['label' => 'Agent applications awaiting review', 'count' => $n, 'amount' => null, 'severity' => 'low', 'oldest' => Agent::where('status', 'pending')->min('created_at'), 'href' => route('admin.agents.index')];
        }

        $order = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        usort($items, fn ($a, $b) => $order[$a['severity']] <=> $order[$b['severity']]);

        return $items;
    }

    /* -------------------------------------------------- Geography (country-level only — no lat/lng exists anywhere in the schema) */

    public function geoBreakdown(array $p): array
    {
        [$from, $to] = [$p['from'], $p['to']];

        $userScope = fn ($q) => $q->whereBetween('created_at', [$from, $to]);
        $byUsers = Country::query()
            ->whereHas('users', $userScope)
            ->withCount(['users' => $userScope])
            ->orderByDesc('users_count')->take(8)->get(['id', 'name', 'iso2', 'flag_emoji']);

        $byDeposits = DB::table('deposits')
            ->join('users', 'users.id', '=', 'deposits.user_id')
            ->join('countries', 'countries.id', '=', 'users.country_id')
            ->where('deposits.status', 'confirmed')
            ->whereBetween('deposits.created_at', [$from, $to])
            ->select('countries.name', 'countries.iso2', 'countries.flag_emoji', DB::raw('SUM(deposits.net_amount) as total'), DB::raw('COUNT(*) as tx_count'))
            ->groupBy('countries.id', 'countries.name', 'countries.iso2', 'countries.flag_emoji')
            ->orderByDesc('total')->take(8)->get();

        $byOrders = DB::table('shop_orders')
            ->join('users', 'users.id', '=', 'shop_orders.user_id')
            ->join('countries', 'countries.id', '=', 'users.country_id')
            ->whereIn('shop_orders.status', ['paid', 'fulfilled'])
            ->whereBetween('shop_orders.created_at', [$from, $to])
            ->select('countries.name', 'countries.iso2', 'countries.flag_emoji', DB::raw('SUM(shop_orders.total) as total'), DB::raw('COUNT(*) as order_count'))
            ->groupBy('countries.id', 'countries.name', 'countries.iso2', 'countries.flag_emoji')
            ->orderByDesc('order_count')->take(8)->get();

        return ['byUsers' => $byUsers, 'byDeposits' => $byDeposits, 'byOrders' => $byOrders];
    }

    /* -------------------------------------------------- Financial performance series */

    public function financialSeries(array $p): array
    {
        $granularity = request()->query('granularity', 'daily');
        $now = now();

        // Weekly/monthly need a meaningfully longer lookback than whatever the
        // separate top-level Period filter happens to be set to elsewhere on
        // this page — otherwise picking "Monthly" while Period="Last 30 days"
        // can only ever produce a single, useless bucket (no line to draw).
        // Each granularity gets its own natural window, bucketed on real
        // calendar boundaries — not generic 7/30-day chunks that drift away
        // from actual week/month lines and mislabel accordingly.
        [$from, $to, $rangeLabel] = match ($granularity) {
            'weekly' => [$now->copy()->subWeeks(11)->startOfWeek(), $now->copy()->endOfDay(), 'Last 12 weeks'],
            'monthly' => [$now->copy()->subMonthsNoOverflow(11)->startOfMonth(), $now->copy()->endOfDay(), 'Last 12 months'],
            default => [$p['from']->copy(), $p['to']->copy(), $p['label']],
        };

        $points = [];
        $cursor = $from->copy();

        while ($cursor->lte($to) && count($points) < 60) {
            $segEnd = match ($granularity) {
                'weekly' => $cursor->copy()->endOfWeek()->min($to),
                'monthly' => $cursor->copy()->endOfMonth()->min($to),
                default => $cursor->copy()->min($to),
            };
            $points[] = [
                'label' => $cursor->format($granularity === 'monthly' ? 'M Y' : 'M j'),
                'deposits' => (float) Deposit::whereBetween('created_at', [$cursor, $segEnd->copy()->endOfDay()])->where('status', 'confirmed')->sum('net_amount'),
                'funding' => (float) FundingRequest::whereBetween('created_at', [$cursor, $segEnd->copy()->endOfDay()])->where('status', 'funding_successful')->sum('target_amount'),
                'sales' => (float) ShopOrder::whereBetween('created_at', [$cursor, $segEnd->copy()->endOfDay()])->whereIn('status', ['paid', 'fulfilled'])->sum('total'),
                'refunds' => (float) ShopOrder::whereBetween('created_at', [$cursor, $segEnd->copy()->endOfDay()])->where('status', 'refunded')->sum('total'),
            ];
            $cursor = match ($granularity) {
                'weekly' => $cursor->copy()->addWeek(),
                'monthly' => $cursor->copy()->addMonthNoOverflow(),
                default => $cursor->copy()->addDay(),
            };
        }

        $depositsByMethod = DB::table('deposits')
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'deposits.payment_method_id')
            ->where('deposits.status', 'confirmed')
            ->whereBetween('deposits.created_at', [$from, $to])
            ->select(DB::raw("COALESCE(payment_methods.name, 'Unknown') as name"), DB::raw('SUM(deposits.net_amount) as total'))
            ->groupBy('name')->orderByDesc('total')->get();

        $fundingByWallet = FundingRequest::where('status', 'funding_successful')
            ->whereBetween('created_at', [$from, $to])
            ->select('app_type', DB::raw('SUM(target_amount) as total'))
            ->groupBy('app_type')->get()
            ->map(fn ($r) => ['app_type' => $r->app_type->label(), 'total' => (float) $r->total]);

        return ['granularity' => $granularity, 'points' => $points, 'rangeLabel' => $rangeLabel, 'depositsByMethod' => $depositsByMethod, 'fundingByWallet' => $fundingByWallet];
    }

    /* -------------------------------------------------- Reconciliation */

    public function reconciliation(): array
    {
        $balance = (float) Wallet::sum('balance');
        $locked = (float) Wallet::sum('locked_balance');
        $available = $balance - $locked;

        $lifetimeDeposits = (float) Deposit::where('status', 'confirmed')->sum('net_amount');
        $lifetimeFunding = (float) FundingRequest::where('status', 'funding_successful')->sum('target_amount');
        $lifetimeAdjustmentsNet = (float) DB::table('wallet_transactions')->where('category', 'adjustment')
            ->selectRaw("SUM(CASE WHEN type='credit' THEN amount ELSE -amount END) as net")->value('net') ?? 0;
        $lifetimeShopSpend = (float) DB::table('wallet_transactions')->where('category', 'shop')->where('type', 'debit')->sum('amount');
        $lifetimeRefunds = (float) DB::table('wallet_transactions')->where('category', 'refund')->where('type', 'credit')->sum('amount');

        // Expected wallet balance from the ledger's own credit/debit history (a genuine
        // internal-consistency check, not a claim about external provider settlement).
        $expected = (float) DB::table('wallet_transactions')
            ->selectRaw("SUM(CASE WHEN type='credit' THEN amount ELSE -amount END) as net")->value('net') ?? 0;

        $gap = round($balance - $expected, 2);
        $status = abs($gap) < 0.5 ? 'balanced' : (abs($gap) < max(1000, $balance * 0.01) ? 'minor' : 'critical');

        $pendingSettlement = (float) Deposit::whereIn('status', ['under_review', 'processing'])->sum('net_amount');

        return compact('balance', 'locked', 'available', 'lifetimeDeposits', 'lifetimeFunding', 'lifetimeShopSpend', 'lifetimeRefunds', 'lifetimeAdjustmentsNet', 'expected', 'gap', 'status', 'pendingSettlement');
    }

    /* -------------------------------------------------- Live transaction feed */

    public function transactionFeed()
    {
        $deposits = Deposit::with('user.country')->latest()->take(10)->get()->map(fn ($d) => [
            'id' => $d->id, 'kind' => 'deposit', 'time' => $d->created_at, 'ref' => $d->reference, 'user' => $d->user, 'type' => 'Deposit',
            'amount' => $d->net_amount, 'currency' => $d->currency, 'status' => $d->status, 'risk' => $d->risk_flagged,
            'url' => route('admin.deposits.show', $d),
        ]);
        $funding = FundingRequest::with('user.country')->latest()->take(10)->get()->map(fn ($f) => [
            'id' => $f->id, 'kind' => 'funding', 'time' => $f->created_at, 'ref' => $f->reference, 'user' => $f->user, 'type' => 'Funding',
            'amount' => $f->target_amount, 'currency' => $f->target_currency, 'status' => $f->status, 'risk' => $f->risk_flagged,
            'url' => route('admin.funding.show', $f),
        ]);
        $orders = ShopOrder::with('user.country')->latest()->take(10)->get()->map(fn ($o) => [
            'id' => $o->id, 'kind' => 'order', 'time' => $o->created_at, 'ref' => $o->reference, 'user' => $o->user, 'type' => 'Order',
            'amount' => $o->total, 'currency' => $o->currency, 'status' => $o->status->value, 'risk' => false,
            'url' => route('admin.shop.orders.show', $o),
        ]);

        return collect()->concat($deposits)->concat($funding)->concat($orders)->sortByDesc('time')->take(20)->values();
    }

    /* -------------------------------------------------- Customer operations */

    public function customerOps(array $p): array
    {
        [$from, $to] = [$p['from'], $p['to']];

        return [
            'total' => User::count(),
            'active' => User::where('status', 'active')->count(),
            'online' => User::where('last_seen_at', '>', now()->subMinutes(5))->count(),
            'newInPeriod' => User::whereBetween('created_at', [$from, $to])->count(),
            'kycRate' => User::count() > 0 ? round(User::where('kyc_status', 'approved')->count() / User::count() * 100, 1) : 0,
            'byRole' => User::select('role', DB::raw('count(*) as n'))->groupBy('role')->pluck('n', 'role'),
            'incompleteKyc' => User::where('kyc_status', 'pending')->orWhere('kyc_status', 'rejected')->count(),
            'frozenWallets' => Wallet::where('status', 'frozen')->count(),
            'failedDeposits' => User::whereHas('deposits', fn ($q) => $q->where('status', 'failed'))->count(),
            'inactive30d' => User::where('status', 'active')->where(fn ($q) => $q->whereNull('last_login_at')->orWhere('last_login_at', '<', now()->subDays(30)))->count(),
            'unresolvedTickets' => User::whereHas('disputes', fn ($q) => $q->whereIn('status', ['open', 'in_progress']))->count(),
        ];
    }

    /* -------------------------------------------------- Compliance & risk */

    public function compliance(array $p): array
    {
        $trend = collect(range(13, 0))->map(function ($d) {
            $day = now()->subDays($d);
            return ['label' => $day->format('D'), 'count' => RiskFlag::whereDate('created_at', $day->toDateString())->count()];
        });

        return [
            'pendingKyc' => User::where('kyc_status', 'pending')->count(),
            'approvedKyc' => User::where('kyc_status', 'approved')->count(),
            'rejectedKyc' => User::where('kyc_status', 'rejected')->count(),
            'manualReviews' => KycVerification::where('status', 'pending')->count(),
            'openFlags' => RiskFlag::where('status', 'open')->count(),
            'flaggedDeposits' => Deposit::where('risk_flagged', true)->count(),
            'flaggedFunding' => FundingRequest::where('risk_flagged', true)->count(),
            'trend' => $trend,
            'alerts' => RiskFlag::with('user')->latest()->take(10)->get(),
        ];
    }

    /* -------------------------------------------------- Marketplace operations */

    public function marketplace(array $p): array
    {
        [$from, $to] = [$p['from'], $p['to']];
        $inPeriod = fn () => ShopOrder::whereBetween('created_at', [$from, $to]);

        $byStatus = ShopOrder::whereBetween('created_at', [$from, $to])
            ->select('status', DB::raw('count(*) as n'), DB::raw('sum(total) as total'))
            ->groupBy('status')->get()->keyBy('status');

        $gross = (float) (clone $inPeriod())->whereIn('status', ['paid', 'fulfilled'])->sum('total');
        $refunded = (float) (clone $inPeriod())->where('status', 'refunded')->sum('total');
        $orderCount = (clone $inPeriod())->whereIn('status', ['paid', 'fulfilled'])->count();

        $topProducts = DB::table('shop_order_items')
            ->join('shop_orders', 'shop_orders.id', '=', 'shop_order_items.shop_order_id')
            ->whereIn('shop_orders.status', ['paid', 'fulfilled'])
            ->whereBetween('shop_orders.created_at', [$from, $to])
            ->select('shop_order_items.name', DB::raw('SUM(shop_order_items.quantity) as qty'), DB::raw('SUM(shop_order_items.line_total) as total'))
            ->groupBy('shop_order_items.name')->orderByDesc('total')->take(6)->get();

        return [
            'gross' => $gross, 'net' => $gross - $refunded, 'refunded' => $refunded,
            'orderCount' => $orderCount, 'aov' => $orderCount > 0 ? $gross / $orderCount : 0,
            'byStatus' => $byStatus, 'topProducts' => $topProducts,
        ];
    }

    /* -------------------------------------------------- Agent network */

    public function agentNetwork(): array
    {
        return [
            'total' => Agent::count(),
            'approved' => Agent::where('status', 'approved')->count(),
            'pending' => Agent::where('status', 'pending')->count(),
            'suspended' => Agent::where('status', 'suspended')->count(),
            'avgRating' => round((float) Agent::where('status', 'approved')->avg('rating'), 2),
            'topAgents' => Agent::where('status', 'approved')->orderByDesc('completed_orders')->take(6)->get(),
            'lowRated' => Agent::where('status', 'approved')->where('reviews_count', '>', 0)->orderBy('rating')->take(5)->get(),
            'byCountry' => Agent::with('warehouseCountry')->select('warehouse_country_id', DB::raw('count(*) as n'))->groupBy('warehouse_country_id')->having('n', '>', 0)->get(),
        ];
    }

    /* -------------------------------------------------- Provider health (derived from real webhook history — no fabricated uptime/response-time) */

    public function providerHealth(): \Illuminate\Support\Collection
    {
        return $this->health->providerHealth();
    }

    /* -------------------------------------------------- System health (only what PHP/Laravel can safely, honestly report) */

    public function systemHealth(): array
    {
        return $this->health->systemHealth();
    }

    /* -------------------------------------------------- Support operations */

    public function support(array $p): array
    {
        [$from, $to] = [$p['from'], $p['to']];

        $resolvedToday = Dispute::whereDate('resolved_at', today())->count();

        $firstResponses = Dispute::whereBetween('created_at', [$from, $to])
            ->whereHas('messages', fn ($q) => $q->where('is_staff', true))
            ->with(['messages' => fn ($q) => $q->where('is_staff', true)->oldest()->limit(1)])
            ->get()
            ->map(fn ($d) => $d->created_at->diffInMinutes($d->messages->first()?->created_at))
            ->filter();
        $avgFirstResponseMin = $firstResponses->isNotEmpty() ? round($firstResponses->avg()) : null;

        $resolved = Dispute::whereNotNull('resolved_at')->whereBetween('resolved_at', [$from, $to])->get();
        $avgResolutionHrs = $resolved->isNotEmpty() ? round($resolved->avg(fn ($d) => $d->created_at->diffInHours($d->resolved_at)), 1) : null;

        return [
            'open' => Dispute::whereIn('status', ['open', 'in_progress'])->count(),
            'urgent' => Dispute::where('priority', 'high')->whereIn('status', ['open', 'in_progress'])->count(),
            'unassigned' => Dispute::whereNull('assigned_to')->whereIn('status', ['open', 'in_progress'])->count(),
            'resolvedToday' => $resolvedToday,
            'avgFirstResponseMin' => $avgFirstResponseMin,
            'avgResolutionHrs' => $avgResolutionHrs,
            'byCategory' => Dispute::whereBetween('created_at', [$from, $to])->select('category', DB::raw('count(*) as n'))->groupBy('category')->pluck('n', 'category'),
            'latest' => Dispute::with('user')->latest()->take(6)->get(),
        ];
    }

    /* -------------------------------------------------- Business insights (deterministic, computed from real deltas — not AI) */

    public function insights(array $p): array
    {
        $insights = [];
        $kpis = $this->kpis($p);

        foreach (['Total deposited' => 'deposit volume', 'Wallet funding sent' => 'wallet funding', 'Marketplace sales' => 'marketplace sales'] as $label => $noun) {
            $row = collect($kpis['financial'])->firstWhere('label', $label);
            if ($row && $row['delta'] !== null && abs($row['delta']) >= 5) {
                $dir = $row['delta'] > 0 ? 'increased' : 'decreased';
                $insights[] = ucfirst($noun)." {$dir} by ".abs($row['delta'])."% compared with the previous period.";
            }
        }

        $topCountry = $this->geoBreakdown($p)['byOrders']->first();
        if ($topCountry) {
            $insights[] = "{$topCountry->name} produced the highest marketplace order volume this period ({$topCountry->order_count} orders).";
        }

        $stalePendingKyc = User::where('kyc_status', 'pending')->where('created_at', '<', now()->subHours(48))->count();
        if ($stalePendingKyc > 0) {
            $insights[] = "{$stalePendingKyc} KYC application".($stalePendingKyc === 1 ? ' has' : 's have')." been waiting longer than 48 hours.";
        }

        $reconciliation = $this->reconciliation();
        if ($reconciliation['status'] !== 'balanced') {
            $insights[] = 'Wallet ledger shows a '.($reconciliation['status'] === 'critical' ? 'critical' : 'minor')." reconciliation gap of ".money(abs($reconciliation['gap']), $this->currency).'.';
        }

        foreach ($this->providerHealth() as $ph) {
            if (in_array($ph['status'], ['Degraded', 'Partial outage'], true)) {
                $insights[] = "{$ph['provider']->name} webhook success rate dropped to {$ph['successRate']}% in the last 24 hours.";
            }
        }

        if (empty($insights)) {
            $insights[] = 'No significant changes detected for this period — platform metrics are stable.';
        }

        return $insights;
    }
}
