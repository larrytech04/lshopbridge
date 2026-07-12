<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ShopCategory;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ShopCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.shop.categories', ['categories' => ShopCategory::withCount('products')->orderBy('sort')->get()]);
    }

    public function store(Request $request)
    {
        ShopCategory::create($this->validated($request));

        return back()->with('success', 'Category added.');
    }

    public function update(Request $request, ShopCategory $category)
    {
        $category->update($this->validated($request));

        return back()->with('success', 'Category updated.');
    }

    public function destroy(ShopCategory $category)
    {
        $category->delete();

        return back()->with('success', 'Category removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:40'],
            'tagline' => ['nullable', 'string', 'max:160'],
            'accent' => ['nullable', 'string', 'max:20'],
            'sort' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active');
        $data['icon'] = $data['icon'] ?: 'sparkles';

        return $data;
    }
}
