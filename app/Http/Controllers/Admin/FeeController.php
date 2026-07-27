<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FeePayer;
use App\Enums\FeeType;
use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Fee;
use App\Models\FeeExemption;
use App\Models\FeeSchedule;
use App\Services\Admin\FeeAdminService;
use App\Services\Audit\AuditLogger;
use App\Services\Fees\FeeCalculationService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FeeController extends Controller
{
    public function index(Request $request, FeeAdminService $svc): View
    {
        $svc->applyDueSchedules();

        $query = Fee::with(['tiers', 'updatedBy']);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%")
                ->orWhere('applies_to', 'like', "%{$search}%")
                ->orWhere('scope', 'like', "%{$search}%")
                ->orWhere('payment_provider', 'like', "%{$search}%")
                ->orWhere('currency', 'like', "%{$search}%")
                ->orWhere('country', 'like', "%{$search}%"));
        }
        if ($active = $request->query('active')) {
            $query->where('is_active', $active === '1');
        }
        if ($appliesTo = $request->query('applies_to')) {
            $query->where('applies_to', $appliesTo);
        }
        if ($type = $request->query('type')) {
            $query->where('type', $type);
        }
        if ($currency = $request->query('currency')) {
            $query->where('currency', $currency);
        }
        if ($country = $request->query('country')) {
            $query->where('country', $country);
        }
        if ($provider = $request->query('payment_provider')) {
            $query->where('payment_provider', $provider);
        }
        if ($role = $request->query('customer_role')) {
            $query->where('customer_role', $role);
        }
        if ($since = $request->query('updated_since')) {
            $query->whereDate('updated_at', '>=', $since);
        }
        if ($request->query('automatic') === '1') {
            $query->whereIn('type', ['provider_passed']);
        } elseif ($request->query('automatic') === '0') {
            $query->whereNotIn('type', ['provider_passed']);
        }

        return view('admin.fees.index', [
            'fees' => $query->orderBy('sort')->orderBy('name')->get(),
            'q' => $request->query('q', ''),
            'summary' => $svc->summary(),
            'categories' => config('fee_categories'),
            'currencies' => \App\Models\Currency::where('is_active', true)->orderBy('code')->get(),
            'countries' => Country::orderBy('name')->get(),
            'feeTypes' => FeeType::cases(),
            'feePayers' => FeePayer::cases(),
            'statusOf' => fn (Fee $f) => $svc->computeStatus($f),
        ]);
    }

    public function rowDetail(Fee $fee, FeeAdminService $svc, AuditLogger $audit)
    {
        $audit->log('fee.viewed', "Viewed fee {$fee->name}", $fee);

        return response()->json([
            'fee' => [
                'id' => $fee->id,
                'name' => $fee->name,
                'code' => $fee->code,
                'description' => $fee->description,
                'applies_to' => $fee->applies_to,
                'type' => $fee->type->value,
                'value' => (float) $fee->value,
                'fixed_value' => $fee->fixed_value !== null ? (float) $fee->fixed_value : null,
                'min_fee' => (float) $fee->min_fee,
                'max_fee' => $fee->max_fee !== null ? (float) $fee->max_fee : null,
                'min_amount' => $fee->min_amount !== null ? (float) $fee->min_amount : null,
                'max_amount' => $fee->max_amount !== null ? (float) $fee->max_amount : null,
                'currency' => $fee->currency,
                'country' => $fee->country,
                'scope' => $fee->scope,
                'payment_provider' => $fee->payment_provider,
                'china_wallet_type' => $fee->china_wallet_type,
                'customer_role' => $fee->customer_role,
                'kyc_level' => $fee->kyc_level,
                'fee_payer' => $fee->fee_payer->value,
                'taxable' => $fee->taxable,
                'under_review' => $fee->under_review,
                'is_active' => $fee->is_active,
                'sort' => $fee->sort,
                'notes' => $fee->notes,
                'effective_start_date' => $fee->effective_start_date?->toDateString(),
                'effective_end_date' => $fee->effective_end_date?->toDateString(),
                'updated_by' => $fee->updatedBy?->name,
                'updated_at' => $fee->updated_at->format('M j, Y g:ia'),
                'status' => $svc->computeStatus($fee)->value,
                'status_label' => $svc->computeStatus($fee)->label(),
            ],
            'tiers' => $fee->tiers->map(fn ($t) => [
                'min_amount' => (float) $t->min_amount, 'max_amount' => $t->max_amount !== null ? (float) $t->max_amount : null,
                'percent' => (float) $t->percent, 'fixed' => (float) $t->fixed,
            ]),
            'history' => $fee->history->map(fn ($h) => [
                'event' => $h->event, 'value' => (float) $h->value, 'type' => $h->type,
                'min_fee' => (float) $h->min_fee, 'max_fee' => $h->max_fee !== null ? (float) $h->max_fee : null,
                'is_active' => $h->is_active, 'changed_by' => $h->changedBy?->name ?? 'System',
                'reason' => $h->reason, 'at' => $h->created_at->format('M j, Y g:ia'),
            ]),
            'schedules' => $fee->schedules->map(fn ($s) => [
                'id' => $s->id, 'new_value' => $s->new_value !== null ? (float) $s->new_value : null,
                'new_min_fee' => $s->new_min_fee !== null ? (float) $s->new_min_fee : null,
                'new_max_fee' => $s->new_max_fee !== null ? (float) $s->new_max_fee : null,
                'effective_start_date' => $s->effective_start_date->toDateString(),
                'effective_end_date' => $s->effective_end_date?->toDateString(),
                'status' => $s->status, 'reason' => $s->reason, 'created_by' => $s->createdBy?->name,
            ]),
        ]);
    }

    public function calculate(Request $request, FeeCalculationService $engine)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'applies_to' => ['required', 'string'],
            'scope' => ['nullable', 'string'],
            'country' => ['nullable', 'string'],
            'customer_role' => ['nullable', 'string'],
            'currency' => ['nullable', 'string'],
            'payment_provider' => ['nullable', 'string'],
            'china_wallet_type' => ['nullable', 'string'],
        ]);

        return response()->json($engine->calculate((float) $data['amount'], $data['applies_to'], [
            'scope' => $data['scope'] ?? null,
            'country' => $data['country'] ?? null,
            'customer_role' => $data['customer_role'] ?? null,
            'currency' => $data['currency'] ?? null,
            'payment_provider' => $data['payment_provider'] ?? null,
            'china_wallet_type' => $data['china_wallet_type'] ?? null,
        ]));
    }

    public function test(Request $request, Fee $fee, FeeAdminService $svc, FeeCalculationService $engine)
    {
        $data = $request->validate(['amount' => ['required', 'numeric', 'min:0']]);

        return response()->json($svc->testFee($fee, (float) $data['amount'], $engine));
    }

    public function create(): View
    {
        return view('admin.fees.form', ['fee' => new Fee]);
    }

    public function store(Request $request, FeeAdminService $svc)
    {
        $data = $this->validated($request);
        $tiers = $this->validatedTiers($request);

        $check = $svc->validateFee($data);
        if ($check['ok'] && $data['type'] === 'tiered') {
            $tierCheck = $svc->validateTiers($tiers);
            $check['ok'] = $tierCheck['ok'];
            $check['errors'] = array_merge($check['errors'], $tierCheck['errors']);
        }
        if (! $check['ok']) {
            return back()->withErrors($check['errors'])->withInput();
        }

        $svc->createFee($data, $request->user(), $tiers);

        return redirect()->route('admin.fees.index')->with('success', 'Fee created.');
    }

    public function edit(Fee $fee): View
    {
        return view('admin.fees.form', ['fee' => $fee]);
    }

    public function update(Request $request, Fee $fee, FeeAdminService $svc)
    {
        $data = $this->validated($request, $fee);
        $tiers = $this->validatedTiers($request);

        $check = $svc->validateFee($data, $fee);
        if ($check['ok'] && $data['type'] === 'tiered') {
            $tierCheck = $svc->validateTiers($tiers);
            $check['ok'] = $tierCheck['ok'];
            $check['errors'] = array_merge($check['errors'], $tierCheck['errors']);
        }
        if (! $check['ok']) {
            return back()->withErrors($check['errors'])->withInput();
        }
        if ($check['warnings'] && ! $request->boolean('confirmed')) {
            return back()->with('warnings', $check['warnings'])->withInput();
        }

        $svc->updateFee($fee, $data, $request->user(), $request->input('reason'), $tiers);

        return redirect()->route('admin.fees.index')->with('success', 'Fee updated.');
    }

    public function toggleActive(Request $request, Fee $fee, FeeAdminService $svc)
    {
        $svc->setActive($fee, ! $fee->is_active, $request->user());

        return back()->with('success', $fee->is_active ? 'Fee deactivated.' : 'Fee activated.');
    }

    public function duplicate(Fee $fee, FeeAdminService $svc, Request $request)
    {
        $data = $fee->only([
            'applies_to', 'type', 'value', 'fixed_value', 'min_fee', 'max_fee', 'min_amount', 'max_amount',
            'currency', 'country', 'region', 'customer_role', 'kyc_level', 'payment_provider', 'china_wallet_type',
            'fee_payer', 'provider_markup_percent', 'taxable', 'sort',
        ]);
        $data['name'] = $fee->name.' (copy)';
        $data['code'] = null;
        $data['is_active'] = false;

        $copy = $svc->createFee($data, $request->user());
        foreach ($fee->tiers as $tier) {
            $copy->tiers()->create($tier->only(['min_amount', 'max_amount', 'percent', 'fixed', 'sort']));
        }

        return redirect()->route('admin.fees.edit', $copy)->with('success', 'Fee duplicated — review and activate when ready.');
    }

    public function destroy(Fee $fee, FeeAdminService $svc, Request $request)
    {
        $svc->archive($fee, $request->user());

        return back()->with('success', 'Fee archived.');
    }

    public function storeSchedule(Request $request, FeeAdminService $svc)
    {
        $data = $request->validate([
            'fee_id' => ['required', 'exists:fees,id'],
            'new_type' => ['nullable', Rule::enum(FeeType::class)],
            'new_value' => ['nullable', 'numeric', 'min:0'],
            'new_min_fee' => ['nullable', 'numeric', 'min:0'],
            'new_max_fee' => ['nullable', 'numeric', 'min:0'],
            'effective_start_date' => ['required', 'date'],
            'effective_end_date' => ['nullable', 'date', 'after_or_equal:effective_start_date'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $check = $svc->validateSchedule($data);
        if (! $check['ok']) {
            return back()->withErrors($check['errors'])->withInput();
        }

        $svc->createSchedule($data, $request->user());

        return back()->with('success', 'Fee change scheduled.');
    }

    public function cancelSchedule(FeeSchedule $schedule, FeeAdminService $svc, Request $request)
    {
        $svc->cancelSchedule($schedule, $request->user());

        return back()->with('success', 'Scheduled change cancelled.');
    }

    public function storeExemption(Request $request, FeeAdminService $svc)
    {
        $data = $request->validate([
            'exemption_type' => ['required', 'in:customer,role,vip_level,agent,merchant,country,promotion,coupon,internal_test,admin_exception'],
            'target_value' => ['required', 'string', 'max:120'],
            'user_id' => ['nullable', 'exists:users,id'],
            'applicable_services' => ['nullable', 'array'],
            'reason' => ['required', 'string', 'max:500'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
        ]);

        $svc->createExemption($data, $request->user());

        return back()->with('success', 'Fee exemption created.');
    }

    public function revokeExemption(FeeExemption $exemption, FeeAdminService $svc, Request $request)
    {
        $svc->revokeExemption($exemption, $request->user());

        return back()->with('success', 'Fee exemption revoked.');
    }

    public function bulkAction(Request $request, FeeAdminService $svc, AuditLogger $audit)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'export', 'review', 'archive'])],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $rows = Fee::whereIn('id', $data['ids'])->get();
        foreach ($rows as $fee) {
            match ($data['action']) {
                'activate' => $svc->setActive($fee, true, $request->user()),
                'deactivate' => $svc->setActive($fee, false, $request->user()),
                'review' => $svc->markUnderReview($fee, true, $request->user()),
                'archive' => $svc->archive($fee, $request->user()),
                default => null,
            };
        }

        return back()->with('success', ucfirst($data['action']).' applied to '.$rows->count().' fee(s).');
    }

    public function exportCsv(AuditLogger $audit): StreamedResponse
    {
        $rows = Fee::orderBy('applies_to')->orderBy('name')->get();
        $audit->log('fee.exported', 'Exported '.$rows->count().' fee(s) to CSV');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Code', 'Applies to', 'Type', 'Value', 'Fixed value', 'Min fee', 'Max fee', 'Currency', 'Country', 'Active', 'Updated']);
            foreach ($rows as $f) {
                fputcsv($out, [
                    $f->name, $f->code, $f->applies_to, $f->type->label(), $f->value, $f->fixed_value,
                    $f->min_fee, $f->max_fee, $f->currency, $f->country, $f->is_active ? 'Yes' : 'No',
                    $f->updated_at->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, 'fees-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validated(Request $request, ?Fee $fee = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['nullable', 'string', 'max:40', Rule::unique('fees', 'code')->ignore($fee?->id)],
            'description' => ['nullable', 'string', 'max:2000'],
            'applies_to' => ['required', 'string', Rule::in(array_merge(array_keys(config('fee_categories')), ['all']))],
            'scope' => ['nullable', 'string', 'max:60'],
            'type' => ['required', Rule::enum(FeeType::class)],
            'value' => ['required', 'numeric', 'min:0'],
            'fixed_value' => ['nullable', 'numeric', 'min:0'],
            'min_fee' => ['nullable', 'numeric', 'min:0'],
            'max_fee' => ['nullable', 'numeric', 'min:0'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'currency' => ['nullable', 'string', 'size:3'],
            'country' => ['nullable', 'string', 'size:2'],
            'region' => ['nullable', 'string', 'max:60'],
            'customer_role' => ['nullable', 'string', 'max:30'],
            'kyc_level' => ['nullable', 'integer', 'min:0', 'max:3'],
            'payment_provider' => ['nullable', 'string', 'max:60'],
            'china_wallet_type' => ['nullable', 'string', 'max:30'],
            'provider_markup_percent' => ['nullable', 'numeric', 'min:0'],
            'fee_payer' => ['nullable', Rule::enum(FeePayer::class)],
            'taxable' => ['nullable', 'boolean'],
            'under_review' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
            'effective_start_date' => ['nullable', 'date'],
            'effective_end_date' => ['nullable', 'date', 'after_or_equal:effective_start_date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $data['country'] = ! empty($data['country']) ? strtoupper($data['country']) : null;
        $data['currency'] = ! empty($data['currency']) ? strtoupper($data['currency']) : null;
        $data['fee_payer'] ??= 'customer';
        $data['min_fee'] ??= 0;
        $data['is_active'] = $request->boolean('is_active', true);
        $data['taxable'] = $request->boolean('taxable');
        $data['under_review'] = $request->boolean('under_review');

        return $data;
    }

    private function validatedTiers(Request $request): array
    {
        if (! $request->has('tiers')) {
            return [];
        }

        return $request->validate([
            'tiers' => ['array'],
            'tiers.*.min_amount' => ['required', 'numeric', 'min:0'],
            'tiers.*.max_amount' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.percent' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.fixed' => ['nullable', 'numeric', 'min:0'],
        ])['tiers'] ?? [];
    }
}
