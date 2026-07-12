<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BannerController extends Controller
{
    public function index(): View
    {
        return view('admin.banners.index', ['banners' => Banner::orderBy('sort')->get()]);
    }

    public function store(Request $request)
    {
        Banner::create($this->validated($request));

        return back()->with('success', 'Banner added.');
    }

    public function update(Request $request, Banner $banner)
    {
        $banner->update($this->validated($request));

        return back()->with('success', 'Banner updated.');
    }

    public function destroy(Banner $banner)
    {
        $banner->delete();

        return back()->with('success', 'Banner removed.');
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
