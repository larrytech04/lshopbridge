<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FeeController extends Controller
{
    public function index(): View
    {
        return view('admin.fees.index', ['fees' => Fee::orderBy('sort')->get()]);
    }

    public function create(): View
    {
        return view('admin.fees.form', ['fee' => new Fee]);
    }

    public function store(Request $request)
    {
        Fee::create($this->validated($request));

        return redirect()->route('admin.fees.index')->with('success', 'Fee created.');
    }

    public function edit(Fee $fee): View
    {
        return view('admin.fees.form', ['fee' => $fee]);
    }

    public function update(Request $request, Fee $fee)
    {
        $fee->update($this->validated($request));

        return redirect()->route('admin.fees.index')->with('success', 'Fee updated.');
    }

    public function destroy(Fee $fee)
    {
        $fee->delete();

        return back()->with('success', 'Fee removed.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'applies_to' => ['required', 'in:deposit,funding,all'],
            'scope' => ['nullable', 'string', 'max:60'],
            'type' => ['required', 'in:percent,fixed'],
            'value' => ['required', 'numeric', 'min:0'],
            'min_fee' => ['nullable', 'numeric', 'min:0'],
            'max_fee' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
        ]);
    }
}
