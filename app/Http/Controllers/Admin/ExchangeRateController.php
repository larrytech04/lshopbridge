<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ExchangeRate;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExchangeRateController extends Controller
{
    public function index(): View
    {
        return view('admin.rates.index', ['rates' => ExchangeRate::with('updatedBy')->get()]);
    }

    public function create(): View
    {
        return view('admin.rates.form', ['rate' => new ExchangeRate, 'currencies' => Currency::all()]);
    }

    public function store(Request $request)
    {
        $rate = ExchangeRate::create($this->validated($request) + ['updated_by' => auth()->id()]);

        return redirect()->route('admin.rates.index')->with('success', 'Exchange rate created.');
    }

    public function edit(ExchangeRate $rate): View
    {
        return view('admin.rates.form', ['rate' => $rate, 'currencies' => Currency::all()]);
    }

    public function update(Request $request, ExchangeRate $rate)
    {
        $rate->update($this->validated($request, $rate) + ['updated_by' => auth()->id()]);

        return redirect()->route('admin.rates.index')->with('success', 'Exchange rate updated.');
    }

    public function destroy(ExchangeRate $rate)
    {
        $rate->delete();

        return back()->with('success', 'Exchange rate removed.');
    }

    private function validated(Request $request, ?ExchangeRate $rate = null): array
    {
        return $request->validate([
            'base_currency' => ['required', 'string', 'size:3'],
            'quote_currency' => ['required', 'string', 'size:3'],
            'rate' => ['required', 'numeric', 'min:0'],
            'margin_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }
}
