<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use App\Models\Faq;
use App\Models\Page;
use App\Models\PaymentMethod;
use App\Services\Funding\RateService;
use Illuminate\View\View;

class PageController extends Controller
{
    public function fundAlipay(RateService $rates): View
    {
        return view('public.fund-alipay', [
            'rate' => $rates->rate(),
            'apps' => config('funding.apps'),
            'methods' => PaymentMethod::active()->get(),
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

    public function faqs(): View
    {
        return view('public.faqs', [
            'faqs' => Faq::published()->get()->groupBy('category'),
        ]);
    }

    public function show(Page $page): View
    {
        abort_unless($page->is_published, 404);

        if ($page->slug === 'about') {
            return view('public.about', ['page' => $page]);
        }

        return view('public.page', ['page' => $page]);
    }
}
