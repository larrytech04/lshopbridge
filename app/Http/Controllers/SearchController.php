<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\AuditLog;
use App\Models\Guide;
use App\Models\ShopOrder;
use App\Models\ShopProduct;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

/**
 * Backs the global command palette (Ctrl/Cmd+K). Searches across the
 * signed-in user's own records plus public catalog/content, and, only for
 * admin/super_admin, a few staff-facing tables. Every group is capped and
 * permission-scoped; nothing here can leak another user's data.
 */
class SearchController extends Controller
{
    public function search(Request $request): JsonResponse
    {
        $user = $request->user();
        $q = trim((string) $request->query('q', ''));
        $isAdmin = in_array($user->role->value ?? $user->role, ['admin', 'super_admin'], true);

        $groups = collect();

        $groups->push(['key' => 'pages', 'label' => __('Pages'), 'items' => $this->pages($q, $isAdmin)]);

        if ($q !== '') {
            $groups->push(['key' => 'deposits', 'label' => __('Deposits'), 'items' => $this->deposits($user, $q)]);
            $groups->push(['key' => 'transactions', 'label' => __('Transactions'), 'items' => $this->transactions($user, $q)]);
            $groups->push(['key' => 'orders', 'label' => __('Orders'), 'items' => $this->orders($user, $q)]);
            $groups->push(['key' => 'products', 'label' => __('Products'), 'items' => $this->products($q)]);
            $groups->push(['key' => 'agents', 'label' => __('Agents'), 'items' => $this->agents($q)]);
            $groups->push(['key' => 'tutorials', 'label' => __('Tutorials'), 'items' => $this->tutorials($q)]);

            if ($isAdmin) {
                $groups->push(['key' => 'users', 'label' => __('Users'), 'items' => $this->users($q)]);
                $groups->push(['key' => 'reports', 'label' => __('Reports'), 'items' => $this->reports($q)]);
            }
        }

        // Drop empty groups so the palette doesn't render blank headers.
        $groups = $groups->filter(fn ($g) => count($g['items']) > 0)->values();

        return response()->json(['groups' => $groups]);
    }

    /** Every word in the query must appear somewhere in $haystack (simple fuzzy match). */
    private function fuzzyMatches(string $haystack, string $q): bool
    {
        $haystack = Str::lower($haystack);
        foreach (preg_split('/\s+/', Str::lower($q), -1, PREG_SPLIT_NO_EMPTY) as $word) {
            if (! str_contains($haystack, $word)) {
                return false;
            }
        }

        return true;
    }

    private function pages(string $q, bool $isAdmin): array
    {
        $pages = [
            ['icon' => 'home', 'title' => __('Dashboard'), 'description' => __('Your account overview'), 'route' => 'dashboard'],
            ['icon' => 'wallet', 'title' => __('Wallet'), 'description' => __('Top up & balance'), 'route' => 'wallet.index'],
            ['icon' => 'deposit', 'title' => __('Deposits'), 'description' => __('Add money to your wallet'), 'route' => 'deposit.index'],
            ['icon' => 'fund', 'title' => __('Fund China Wallet'), 'description' => __('Alipay, WeChat Pay & more'), 'route' => 'funding.create'],
            ['icon' => 'card', 'title' => __('Saved Payment Methods'), 'description' => __('Your saved deposit sources'), 'route' => 'payment-methods.index'],
            ['icon' => 'arrow-up', 'title' => __('Withdraw Funds'), 'description' => __('Cash out to your saved payment method'), 'route' => 'withdrawals.index'],
            ['icon' => 'chart', 'title' => __('Transactions'), 'description' => __('Activity history'), 'route' => 'transactions.index'],
            ['icon' => 'cart', 'title' => __('My Orders'), 'description' => __('Recent purchases'), 'route' => 'shop.orders.index'],
            ['icon' => 'bag', 'title' => __('Marketplace'), 'description' => __('Gift cards, eSIMs, VPN & more'), 'route' => 'shop.index'],
            ['icon' => 'heart', 'title' => __('Wishlist'), 'description' => __('Products you have saved'), 'route' => 'wishlist.index'],
            ['icon' => 'download', 'title' => __('Digital Purchases'), 'description' => __('Gift cards, eSIMs & instant purchases'), 'route' => 'shop.orders.digital'],
            ['icon' => 'user-circle', 'title' => __('Identity Verification'), 'description' => __('KYC status & documents'), 'route' => 'verification.index'],
            ['icon' => 'shield', 'title' => __('Security & Devices'), 'description' => __('Password, 2FA & sessions'), 'route' => 'security.index'],
            ['icon' => 'wallet', 'title' => __('My China Wallets'), 'description' => __('Alipay & WeChat destinations'), 'route' => 'beneficiaries.index'],
            ['icon' => 'clock', 'title' => __('Funding History'), 'description' => __('Past China wallet fundings'), 'route' => 'funding.index'],
            ['icon' => 'truck', 'title' => __('Shipping Agents'), 'description' => __('Verified China agents'), 'route' => 'marketplace.index'],
            ['icon' => 'truck', 'title' => __('Shipping Requests'), 'description' => __('Get competing quotes from agents'), 'route' => 'shipping-requests.index'],
            ['icon' => 'search', 'title' => __('Track Shipment'), 'description' => __('Look up a shipment by reference'), 'route' => 'shipments.track'],
            ['icon' => 'book', 'title' => __('Learning Center'), 'description' => __('China buying guides'), 'route' => 'learning.index'],
            ['icon' => 'help', 'title' => __('Help Center'), 'description' => __('Search frequently asked questions'), 'route' => 'help.index'],
            ['icon' => 'bell', 'title' => __('Notifications'), 'description' => __('Recent alerts'), 'route' => 'notifications.index'],
            ['icon' => 'user', 'title' => __('Profile'), 'description' => __('Account information'), 'route' => 'profile.edit'],
            ['icon' => 'users', 'title' => __('Referrals & Rewards'), 'description' => __('Invite friends, earn rewards'), 'route' => 'referrals.index'],
            ['icon' => 'mail', 'title' => __('Support Tickets'), 'description' => __('Open a support ticket'), 'route' => 'disputes.index'],
            ['icon' => 'refresh', 'title' => __('Disputes & Refunds'), 'description' => __('Request a refund on an eligible order'), 'route' => 'refunds.index'],
        ];

        if ($isAdmin) {
            $pages[] = ['icon' => 'cog', 'title' => __('Admin dashboard'), 'description' => __('Staff overview'), 'route' => 'admin.dashboard'];
            $pages[] = ['icon' => 'users', 'title' => __('Admin: users'), 'description' => __('Manage all users'), 'route' => 'admin.users.index'];
            $pages[] = ['icon' => 'shield', 'title' => __('Admin: KYC queue'), 'description' => __('Verification review'), 'route' => 'admin.kyc.index'];
            $pages[] = ['icon' => 'flag', 'title' => __('Admin: risk queue'), 'description' => __('Flagged activity'), 'route' => 'admin.risk.index'];
            $pages[] = ['icon' => 'cog', 'title' => __('Admin: settings'), 'description' => __('Platform settings'), 'route' => 'admin.settings.index'];
        }

        return collect($pages)
            ->filter(fn ($p) => $q === '' || $this->fuzzyMatches($p['title'].' '.$p['description'], $q))
            ->map(fn ($p) => ['icon' => $p['icon'], 'title' => $p['title'], 'description' => $p['description'], 'url' => route($p['route'])])
            ->values()->take(8)->all();
    }

    private function deposits($user, string $q): array
    {
        return $user->deposits()
            ->where(fn ($qq) => $qq->where('reference', 'like', "%{$q}%")->orWhere('status', 'like', "%{$q}%"))
            ->latest()->take(5)->get()
            ->map(fn ($d) => [
                'icon' => 'deposit', 'title' => $d->reference,
                'description' => __(':status · :amount :currency', ['status' => $d->status, 'amount' => number_format($d->amount), 'currency' => $d->currency]),
                'url' => route('deposit.show', $d),
            ])->all();
    }

    private function transactions($user, string $q): array
    {
        return $user->walletTransactions()
            ->where(fn ($qq) => $qq->where('type', 'like', "%{$q}%")->orWhere('reference', 'like', "%{$q}%"))
            ->latest()->take(5)->get()
            ->map(fn ($t) => [
                'icon' => 'chart', 'title' => Str::title($t->type).' · '.number_format((float) $t->amount),
                'description' => $t->created_at->diffForHumans(),
                'url' => route('transactions.index'),
            ])->all();
    }

    private function orders($user, string $q): array
    {
        return $user->shopOrders()
            ->where('reference', 'like', "%{$q}%")
            ->latest()->take(5)->get()
            ->map(fn ($o) => [
                'icon' => 'cart', 'title' => $o->reference,
                'description' => __(':status · :amount :currency', ['status' => $o->status, 'amount' => number_format($o->total), 'currency' => $o->currency]),
                'url' => route('shop.orders.show', $o),
            ])->all();
    }

    private function products(string $q): array
    {
        return ShopProduct::active()
            ->where('name', 'like', "%{$q}%")
            ->take(5)->get()
            ->map(fn ($p) => [
                'icon' => 'giftcard', 'title' => $p->name,
                'description' => $p->summary ?? $p->type->label(),
                'url' => route('shop.show', $p),
            ])->all();
    }

    private function agents(string $q): array
    {
        return Agent::where('status', 'approved')
            ->where('business_name', 'like', "%{$q}%")
            ->take(5)->get()
            ->map(fn ($a) => [
                'icon' => 'truck', 'title' => $a->business_name,
                'description' => $a->warehouse_city,
                'url' => route('marketplace.show', $a),
            ])->all();
    }

    private function tutorials(string $q): array
    {
        return Guide::where('title', 'like', "%{$q}%")
            ->take(5)->get()
            ->map(fn ($g) => [
                'icon' => 'book', 'title' => $g->title,
                'description' => Str::title($g->category),
                'url' => route('learning.show', $g),
            ])->all();
    }

    private function users(string $q): array
    {
        return User::where(fn ($qq) => $qq->where('name', 'like', "%{$q}%")->orWhere('email', 'like', "%{$q}%"))
            ->take(5)->get()
            ->map(fn ($u) => [
                'icon' => 'user', 'title' => $u->name,
                'description' => $u->email,
                'url' => route('admin.users.show', $u),
            ])->all();
    }

    private function reports(string $q): array
    {
        return AuditLog::where(fn ($qq) => $qq->where('action', 'like', "%{$q}%")->orWhere('description', 'like', "%{$q}%"))
            ->latest()->take(5)->get()
            ->map(fn ($l) => [
                'icon' => 'receipt', 'title' => $l->action,
                'description' => $l->description ?? $l->created_at->diffForHumans(),
                'url' => route('admin.audit.index'),
            ])->all();
    }
}
