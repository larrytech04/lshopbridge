<?php

namespace App\Http\Controllers\Admin;

use App\Enums\BannerAudience;
use App\Http\Controllers\Controller;
use App\Models\Banner;
use App\Models\Country;
use App\Services\Admin\BannerAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function __construct(private BannerAdminService $service) {}

    public function index(): View
    {
        return view('admin.banners.index', [
            'banners' => Banner::withTrashed()->orderBy('sort')->get(),
            'countries' => Country::orderBy('name')->get(),
            'audiences' => BannerAudience::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $this->service->create($this->validated($request), $request->user());

        return back()->with('success', 'Banner added.');
    }

    public function update(Request $request, Banner $banner)
    {
        $this->service->update($banner, $this->validated($request), $request->user());

        return back()->with('success', 'Banner updated.');
    }

    /** Archive-not-delete: soft-deletes so this banner's record isn't destroyed outright. */
    public function destroy(Request $request, Banner $banner)
    {
        $this->service->archive($banner, $request->user());

        return back()->with('success', 'Banner archived.');
    }

    public function restore(Request $request, Banner $banner)
    {
        $this->service->restore($banner, $request->user());

        return back()->with('success', 'Banner restored.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:200'],
            'type' => ['required', 'in:hero,promo,strip'],
            'position' => ['required', 'string', 'max:40'],
            'audience' => ['required', 'in:'.implode(',', array_column(BannerAudience::cases(), 'value'))],
            'country_id' => ['nullable', 'exists:countries,id'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'image' => ['nullable', 'image', 'max:3072'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
        ]);

        $data['is_active'] = $request->boolean('is_active');
        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('banners', 'public');
        }
        unset($data['image']);

        return $data;
    }
}
