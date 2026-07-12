<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CountryController extends Controller
{
    public function index(): View
    {
        return view('admin.countries.index', ['countries' => Country::orderBy('name')->get()]);
    }

    public function store(Request $request)
    {
        Country::create($this->validated($request));

        return back()->with('success', 'Country added.');
    }

    public function update(Request $request, Country $country)
    {
        $country->update($this->validated($request));

        return back()->with('success', 'Country updated.');
    }

    public function destroy(Country $country)
    {
        $country->delete();

        return back()->with('success', 'Country removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'iso2' => ['required', 'string', 'size:2'],
            'dial_code' => ['nullable', 'string', 'max:8'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'flag_emoji' => ['nullable', 'string', 'max:8'],
            'is_active' => ['nullable', 'boolean'],
            'is_blocked' => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        $data['is_blocked'] = $request->boolean('is_blocked');

        return $data;
    }
}
