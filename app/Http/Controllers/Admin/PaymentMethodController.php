<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentMethod;
use App\Models\PaymentProvider;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function index(): View
    {
        return view('admin.methods.index', ['methods' => PaymentMethod::orderBy('sort')->get()]);
    }

    public function create(): View
    {
        return view('admin.methods.form', ['method' => new PaymentMethod, 'providers' => PaymentProvider::all()]);
    }

    public function store(Request $request)
    {
        PaymentMethod::create($this->validated($request));

        return redirect()->route('admin.methods.index')->with('success', 'Payment method created.');
    }

    public function edit(PaymentMethod $method): View
    {
        return view('admin.methods.form', ['method' => $method, 'providers' => PaymentProvider::all()]);
    }

    public function update(Request $request, PaymentMethod $method)
    {
        $method->update($this->validated($request));

        return redirect()->route('admin.methods.index')->with('success', 'Payment method updated.');
    }

    public function destroy(PaymentMethod $method)
    {
        $method->delete();

        return back()->with('success', 'Payment method removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:60'],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:momo,bank,crypto,card'],
            'provider_code' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'is_automated' => ['nullable', 'boolean'],
            'requires_proof' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
        ]);

        $data['is_automated'] = $request->boolean('is_automated');
        $data['requires_proof'] = $request->boolean('requires_proof');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
