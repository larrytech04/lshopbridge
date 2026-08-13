<?php

namespace App\Http\Controllers\Public;

use App\Enums\AppType;
use App\Enums\BeneficiaryStatus;
use App\Http\Controllers\Controller;
use App\Models\ChinaWalletType;
use App\Models\Fee;
use App\Models\Faq;
use App\Models\Page;
use App\Models\PaymentMethod;
use App\Models\ProcessStep;
use App\Services\Content\LegalContentRenderer;
use App\Services\Funding\FundingService;
use App\Services\Funding\RateService;
use App\Services\Seo\StructuredDataBuilder;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * Real, admin-editable process steps (Admin -> Page content -> How It
     * Works), replacing the hardcoded $fundSteps/$shopSteps/$promises arrays
     * that used to live directly in this Blade view.
     */
    public function howItWorks(): View
    {
        $toTuple = fn (ProcessStep $s) => [$s->icon, $s->title, $s->body];

        return view('public.how-it-works', [
            'fundSteps' => ProcessStep::group('fund_step')->active()->get()->map($toTuple)->all(),
            'shopSteps' => ProcessStep::group('shop_step')->active()->get()->map($toTuple)->all(),
            'promises' => ProcessStep::group('promise')->active()->get()->map($toTuple)->all(),
        ]);
    }

    public function fundAlipay(RateService $rates): View
    {
        $user = request()->user();
        $defaultAmount = (float) setting('funding_calculator_default_amount', 100000);

        $wallets = ChinaWalletType::active()
            ->whereIn('code', array_column(AppType::cases(), 'value'))
            ->get();

        $defaultWalletCode = $wallets->first()?->code;
        $quote = app(FundingService::class)->quote($defaultAmount, $defaultWalletCode, $user);

        $eligibility = null;
        if ($user) {
            $eligibility = [
                'kyc_level' => (int) $user->kyc_level,
                'kyc_ok' => (int) $user->kyc_level >= 1,
                'has_approved_beneficiary' => $user->beneficiaryAccounts()->where('status', BeneficiaryStatus::Approved)->exists(),
            ];
        }

        return view('public.fund-alipay', [
            'rate' => $rates->rate(),
            'quote' => $quote,
            'defaultAmount' => $defaultAmount,
            'defaultWalletCode' => $defaultWalletCode,
            'apps' => config('funding.apps'),
            'wallets' => $wallets,
            'methods' => PaymentMethod::active()->get(),
            'steps' => ProcessStep::group('fund_step')->active()->get(),
            'eligibility' => $eligibility,
        ]);
    }

    public function paymentMethods(): View
    {
        return view('public.payment-methods', [
            'methods' => PaymentMethod::active()->get(),
        ]);
    }

    public function fees(RateService $rates): View
    {
        return view('public.fees', [
            'fees' => Fee::active()->orderBy('sort')->get(),
            'rate' => $rates->rate(),
        ]);
    }

    public function faqs(StructuredDataBuilder $schema): View
    {
        $faqs = Faq::published()->get();

        $categoryMeta = [
            'funding' => ['label' => __('China Wallet Funding'), 'icon' => 'swap'],
            'payments' => ['label' => __('Payments'), 'icon' => 'card'],
            'security' => ['label' => __('Security'), 'icon' => 'shield'],
            'account' => ['label' => __('Account'), 'icon' => 'user'],
            'marketplace' => ['label' => __('Marketplace'), 'icon' => 'bag'],
        ];

        $categories = $faqs->groupBy('category')->map(function ($items, $key) use ($categoryMeta) {
            return [
                'key' => $key,
                'label' => $categoryMeta[$key]['label'] ?? Str::headline($key),
                'icon' => $categoryMeta[$key]['icon'] ?? 'help',
                'count' => $items->count(),
            ];
        })->sortBy('label')->values();

        return view('public.faqs', [
            'faqs' => $faqs,
            'categories' => $categories,
            'faqsByCategory' => $faqs->groupBy('category'),
            // Straight from the real, published Faq rows this page actually
            // renders — never a separately curated/fabricated list, so the
            // schema can never drift from what's visible.
            'faqSchema' => $faqs->isNotEmpty()
                ? $schema->faqPage($faqs->map(fn (Faq $faq) => [
                    'question' => $faq->question,
                    'answer' => $faq->answer,
                ])->all())
                : null,
        ]);
    }

    public function show(Page $page)
    {
        abort_unless($page->is_published, 404);

        // Legal documents live under /legal/{slug} now — permanently redirect
        // so no previously shared/bookmarked/indexed /p/{slug} link 404s.
        if ($page->type === 'legal') {
            return redirect()->route('legal.show', $page, 301);
        }

        if ($page->slug === 'about') {
            return view('public.about', ['page' => $page]);
        }

        return view('public.page', ['page' => $page]);
    }

    /** Legal & Policy Center hub: every published legal document, grouped by category. */
    public function legalCenter(): View
    {
        $active = $this->activeServiceKeys();

        $grouped = Page::legal()->published()->orderBy('title')->get()
            ->filter(fn (Page $page) => $page->isApplicableToServices($active))
            ->groupBy(fn (Page $page) => $page->category ?: 'general');

        return view('public.legal-center', [
            'categories' => Page::CATEGORIES,
            'grouped' => $grouped,
        ]);
    }

    public function showLegal(Page $page, LegalContentRenderer $renderer): View
    {
        abort_unless($page->is_published && $page->type === 'legal', 404);

        $active = $this->activeServiceKeys();
        abort_unless($page->isApplicableToServices($active), 404);

        $related = Page::legal()->published()
            ->where('id', '!=', $page->id)
            ->where('category', $page->category)
            ->get()
            ->filter(fn (Page $p) => $p->isApplicableToServices($active))
            ->take(5);

        $canonical = app(\App\Services\Seo\CanonicalUrlService::class);
        $breadcrumbs = [
            ['name' => __('Home'), 'url' => $canonical->normalize(route('home'))],
            ['name' => __('Legal Center'), 'url' => $canonical->normalize(route('legal.index'))],
            ['name' => $page->title, 'url' => $canonical->normalize(route('legal.show', $page))],
        ];

        return view('public.legal-page', [
            'page' => $page,
            'excerptText' => $renderer->substitute($page->excerpt),
            'bodyHtml' => $renderer->toHtml($page->body),
            'summaryHtml' => $renderer->toHtml($page->plain_summary),
            'headings' => $renderer->extractHeadings($page->body),
            'related' => $related,
            'categoryLabel' => Page::CATEGORIES[$page->category] ?? 'General',
            'breadcrumbs' => $breadcrumbs,
            'breadcrumbSchema' => app(\App\Services\Seo\StructuredDataBuilder::class)->breadcrumbList($breadcrumbs),
        ]);
    }

    /**
     * Which optional platform services are actually active right now, used to
     * hide policies (e.g. a Withdrawal Policy) for services this install
     * doesn't offer. Mirrors the same Route::has() checks the customer
     * sidebar nav already uses to conditionally show/hide those features.
     */
    private function activeServiceKeys(): array
    {
        $checks = [
            'withdrawals' => 'withdrawals.index',
            'shipping_agents' => 'marketplace.index',
            'shipping_requests' => 'shipping-requests.index',
            'wishlist' => 'wishlist.index',
            'digital_purchases' => 'shop.orders.digital',
            'referrals' => 'referrals.index',
        ];

        return collect($checks)->filter(fn ($routeName) => Route::has($routeName))->keys()->all();
    }
}
