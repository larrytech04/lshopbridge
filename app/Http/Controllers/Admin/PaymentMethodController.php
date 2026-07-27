<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethodStatus;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\PaymentProvider;
use App\Services\Admin\PaymentMethodAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentMethodController extends Controller
{
    public function __construct(private PaymentMethodAdminService $service) {}

    public function index(): View
    {
        return view('admin.methods.index', [
            'methods' => PaymentMethod::withTrashed()->orderBy('sort')->get(),
            'providers' => PaymentProvider::orderBy('name')->get(),
            'countries' => Country::orderBy('name')->get(),
            'currencies' => Currency::orderBy('code')->get(),
            'summary' => $this->service->summary(),
            'statuses' => PaymentMethodStatus::cases(),
        ]);
    }

    public function store(Request $request)
    {
        $this->service->create($this->validated($request), $request->user());

        return back()->with('success', 'Payment method created.');
    }

    public function update(Request $request, PaymentMethod $method)
    {
        $this->service->update($method, $this->validated($request, $method), $request->user());

        return back()->with('success', 'Payment method updated.');
    }

    public function setStatus(Request $request, PaymentMethod $method)
    {
        $data = $request->validate(['status' => ['required', 'in:'.implode(',', array_column(PaymentMethodStatus::cases(), 'value'))]]);
        $this->service->setStatus($method, PaymentMethodStatus::from($data['status']), $request->user());

        return back()->with('success', 'Status updated.');
    }

    /** Archive-not-delete: soft-deletes so methods referenced by historical deposits/orders are never actually removed. */
    public function destroy(Request $request, PaymentMethod $method)
    {
        $this->service->archive($method, $request->user());

        return back()->with('success', 'Payment method archived.');
    }

    public function restore(Request $request, PaymentMethod $method)
    {
        $this->service->restore($method, $request->user());

        return back()->with('success', 'Payment method restored.');
    }

    private function validated(Request $request, ?PaymentMethod $method = null): array
    {
        $data = $request->validate([
            'code' => $method ? ['sometimes'] : ['required', 'string', 'max:60', 'unique:payment_methods,code'],
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', 'in:momo,bank,crypto,card'],
            'provider_code' => ['nullable', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:500'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'currency' => ['nullable', 'string', 'size:3'],
            'currencies' => ['nullable', 'array'],
            'currencies.*' => ['string', 'size:3'],
            'countries' => ['nullable', 'array'],
            'countries.*' => ['string', 'size:2'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'is_automated' => ['nullable', 'boolean'],
            'requires_proof' => ['nullable', 'boolean'],
            'status' => ['required', 'in:'.implode(',', array_column(PaymentMethodStatus::cases(), 'value'))],
            'deposit_enabled' => ['nullable', 'boolean'],
            'marketplace_enabled' => ['nullable', 'boolean'],
            'refund_support' => ['nullable', 'boolean'],
            'partial_refund_support' => ['nullable', 'boolean'],
            'requires_manual_review' => ['nullable', 'boolean'],
            'kyc_level_required' => ['nullable', 'integer', 'min:0', 'max:3'],
            'processing_time_estimate' => ['nullable', 'string', 'max:60'],
            'available_from' => ['nullable', 'date'],
            'available_until' => ['nullable', 'date', 'after_or_equal:available_from'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'sort' => ['nullable', 'integer'],
        ]);

        if (! $method) {
            $data['sort'] = $data['sort'] ?? (PaymentMethod::max('sort') + 1);
        }

        return $data;
    }
}
