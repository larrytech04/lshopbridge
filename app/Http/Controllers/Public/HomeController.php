<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Banner;
use App\Models\Deposit;
use App\Models\Faq;
use App\Models\FundingRequest;
use App\Models\Guide;
use App\Models\PaymentMethod;
use App\Models\ShopProduct;
use App\Models\Testimonial;
use App\Services\Admin\BannerAdminService;
use App\Services\Funding\RateService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request, RateService $rates, BannerAdminService $bannerService): View
    {
        $heroBanners = Banner::active()->where('position', 'home')->where('type', 'hero')->get();

        return view('public.home', [
            'hero' => $bannerService->firstVisible($heroBanners, $request->user()),
            'testimonials' => Testimonial::active()->get(),
            'guides' => Guide::published()->latest()->take(3)->get(),
            'agents' => Agent::approved()->with(['warehouseCountry', 'shippingRates'])->orderByDesc('is_featured')->orderByDesc('rating')->take(3)->get(),
            'methods' => PaymentMethod::active()->get(),
            'giftCards' => ShopProduct::active()->where('type', 'giftcard')
                ->whereHas('variants', fn ($q) => $q->where('is_active', true))
                ->with('variants')->orderByDesc('is_featured')->orderByDesc('sales_count')->take(10)->get(),
            'esimProducts' => ShopProduct::active()->where('type', 'esim')
                ->whereHas('variants', fn ($q) => $q->where('is_active', true))
                ->with('variants')->orderByDesc('is_featured')->orderByDesc('sales_count')->take(6)->get(),
            'faqs' => Faq::published()->take(5)->get(),
            'rate' => $rates->rate(),
            'stats' => [
                'funded' => FundingRequest::where('status', 'funding_successful')->sum('target_amount'),
                'users' => \App\Models\User::where('role', 'user')->count(),
                'agents' => Agent::approved()->count(),
                'volume' => Deposit::where('status', 'confirmed')->sum('net_amount'),
            ],
        ]);
    }
}
