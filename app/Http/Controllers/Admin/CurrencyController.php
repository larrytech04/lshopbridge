<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Services\Admin\CurrencyAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Currency metadata and availability only. Exchange rates live on the
 * existing Exchange Rates page (see the cross-link in the view) — this page
 * never duplicates that.
 */
class CurrencyController extends Controller
{
    public function __construct(private CurrencyAdminService $service) {}

    public function index(): View
    {
        return view('admin.currencies.index', [
            'currencies' => Currency::orderBy('code')->get(),
            'summary' => $this->service->summary(),
        ]);
    }

    public function store(Request $request)
    {
        $this->service->create($this->validated($request), $request->user());

        return back()->with('success', 'Currency added.');
    }

    public function update(Request $request, Currency $currency)
    {
        $this->service->update($currency, $this->validated($request, $currency), $request->user());

        return back()->with('success', 'Currency updated.');
    }

    public function setActive(Request $request, Currency $currency)
    {
        $this->service->setActive($currency, $request->boolean('is_active'), $request->user());

        return back()->with('success', 'Status updated.');
    }

    private function validated(Request $request, ?Currency $currency = null): array
    {
        return $request->validate([
            'code' => $currency ? ['sometimes'] : ['required', 'string', 'size:3', 'unique:currencies,code'],
            'name' => ['required', 'string', 'max:80'],
            'symbol' => ['nullable', 'string', 'max:8'],
            'decimals' => ['required', 'integer', 'min:0', 'max:6'],
            'thousands_separator' => ['nullable', 'string', 'max:4'],
            'decimal_separator' => ['nullable', 'string', 'max:4'],
            'is_active' => ['nullable', 'boolean'],
            'wallet_enabled' => ['nullable', 'boolean'],
            'deposit_enabled' => ['nullable', 'boolean'],
            'marketplace_enabled' => ['nullable', 'boolean'],
            'reporting_currency_enabled' => ['nullable', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
