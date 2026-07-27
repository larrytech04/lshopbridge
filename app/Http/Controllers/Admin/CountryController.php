<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CountryLaunchStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Services\Admin\CountryAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Countries & Regions" in the Platform Configuration nav. Countries are
 * permanent reference data (users/deposits/funding key off country_id) so
 * there is no delete action — only launch_status transitions.
 */
class CountryController extends Controller
{
    public function __construct(private CountryAdminService $service) {}

    public function index(): View
    {
        return view('admin.countries.index', [
            'countries' => Country::orderBy('name')->get(),
            'summary' => $this->service->summary(),
            'statuses' => CountryLaunchStatus::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $this->service->create($this->validated($request), $request->user());

        return back()->with('success', 'Country added.');
    }

    public function update(Request $request, Country $country)
    {
        $this->service->update($country, $this->validated($request), $request->user());

        return back()->with('success', 'Country updated.');
    }

    public function setStatus(Request $request, Country $country)
    {
        $data = $request->validate(['launch_status' => ['required', 'in:'.implode(',', array_column(CountryLaunchStatus::cases(), 'value'))]]);
        $this->service->setLaunchStatus($country, CountryLaunchStatus::from($data['launch_status']), $request->user());

        return back()->with('success', 'Launch status updated.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'iso2' => ['required', 'string', 'size:2'],
            'dial_code' => ['nullable', 'string', 'max:8'],
            'currency_code' => ['nullable', 'string', 'size:3'],
            'flag_emoji' => ['nullable', 'string', 'max:8'],
            'launch_status' => ['required', 'in:'.implode(',', array_column(CountryLaunchStatus::cases(), 'value'))],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
