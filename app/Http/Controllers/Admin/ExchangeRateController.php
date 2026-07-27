<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ExchangeRateMarginType;
use App\Http\Controllers\Controller;
use App\Models\Currency;
use App\Models\ExchangeRate;
use App\Models\ExchangeRateSchedule;
use App\Services\Admin\ExchangeRateAdminService;
use App\Services\Audit\AuditLogger;
use App\Services\Funding\RateService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExchangeRateController extends Controller
{
    public function index(Request $request, ExchangeRateAdminService $svc): View
    {
        $svc->applyDueSchedules();

        $query = ExchangeRate::with('updatedBy');

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(fn ($w) => $w->where('base_currency', 'like', "%{$search}%")
                ->orWhere('quote_currency', 'like', "%{$search}%")
                ->orWhere('rate_source', 'like', "%{$search}%"));
        }
        if ($active = $request->query('active')) {
            $query->where('is_active', $active === '1');
        }
        if ($method = $request->query('rate_source')) {
            $query->where('rate_source', $method);
        }
        if ($quote = $request->query('quote_currency')) {
            $query->where('quote_currency', $quote);
        }
        if ($since = $request->query('updated_since')) {
            $query->whereDate('updated_at', '>=', $since);
        }

        return view('admin.rates.index', [
            'rates' => $query->orderBy('base_currency')->get(),
            'q' => $request->query('q', ''),
            'summary' => $svc->summary(),
            'currencies' => Currency::where('is_active', true)->orderBy('code')->get(),
            'marginTypes' => ExchangeRateMarginType::cases(),
            'statusOf' => fn (ExchangeRate $r) => $svc->computeStatus($r),
        ]);
    }

    public function rowDetail(ExchangeRate $rate, ExchangeRateAdminService $svc, RateService $rates, AuditLogger $audit)
    {
        $audit->log('rate.viewed', "Viewed exchange rate {$rate->pair()}", $rate);

        return response()->json([
            'rate' => [
                'id' => $rate->id,
                'base_currency' => $rate->base_currency,
                'quote_currency' => $rate->quote_currency,
                'rate' => (float) $rate->rate,
                'margin_type' => $rate->margin_type->value,
                'margin_percent' => (float) $rate->margin_percent,
                'margin_fixed' => $rate->margin_fixed !== null ? (float) $rate->margin_fixed : null,
                'custom_effective_rate' => $rate->custom_effective_rate !== null ? (float) $rate->custom_effective_rate : null,
                'effective_rate' => $rate->effectiveRate(),
                'rate_source' => $rate->rate_source->value,
                'is_active' => $rate->is_active,
                'min_amount' => $rate->min_amount !== null ? (float) $rate->min_amount : null,
                'max_amount' => $rate->max_amount !== null ? (float) $rate->max_amount : null,
                'notes' => $rate->notes,
                'updated_by' => $rate->updatedBy?->name,
                'updated_at' => $rate->updated_at->format('M j, Y g:ia'),
                'status' => $svc->computeStatus($rate)->value,
                'status_label' => $svc->computeStatus($rate)->label(),
            ],
            'history' => $rate->history->map(fn ($h) => [
                'event' => $h->event, 'rate' => (float) $h->rate, 'margin_percent' => (float) $h->margin_percent,
                'margin_type' => $h->margin_type->value, 'effective_rate' => (float) $h->effective_rate,
                'is_active' => $h->is_active, 'changed_by' => $h->changedBy?->name ?? 'System',
                'reason' => $h->reason, 'at' => $h->created_at->format('M j, Y g:ia'),
            ]),
            'schedules' => ExchangeRateSchedule::where('base_currency', $rate->base_currency)
                ->where('quote_currency', $rate->quote_currency)
                ->latest('effective_from')->get()->map(fn ($s) => [
                    'id' => $s->id, 'rate' => (float) $s->rate, 'effective_rate' => $s->effectiveRate(),
                    'effective_from' => $s->effective_from->toDateString(), 'effective_to' => $s->effective_to?->toDateString(),
                    'status' => $s->status, 'reason' => $s->reason, 'created_by' => $s->createdBy?->name,
                ]),
        ]);
    }

    public function calculate(Request $request, RateService $rates)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0'],
            'base_currency' => ['required', 'string', 'size:3'],
            'quote_currency' => ['required', 'string', 'size:3'],
            'additional_fee' => ['nullable', 'numeric', 'min:0'],
        ]);

        return response()->json($rates->quote(
            (float) $data['amount'],
            strtoupper($data['base_currency']),
            strtoupper($data['quote_currency']),
            (float) ($data['additional_fee'] ?? 0),
        ));
    }

    public function create(): View
    {
        return view('admin.rates.form', ['rate' => new ExchangeRate, 'currencies' => Currency::all()]);
    }

    public function store(Request $request, ExchangeRateAdminService $svc)
    {
        $data = $this->validated($request);
        $check = $svc->validateRate($data);
        if (! $check['ok']) {
            return back()->withErrors($check['errors'])->withInput();
        }

        $svc->createRate($data, $request->user());

        return redirect()->route('admin.rates.index')->with('success', 'Exchange rate created.');
    }

    public function edit(ExchangeRate $rate): View
    {
        return view('admin.rates.form', ['rate' => $rate, 'currencies' => Currency::all()]);
    }

    public function update(Request $request, ExchangeRate $rate, ExchangeRateAdminService $svc)
    {
        $data = $this->validated($request, $rate);
        $check = $svc->validateRate($data, $rate);
        if (! $check['ok']) {
            return back()->withErrors($check['errors'])->withInput();
        }
        if ($check['warnings'] && ! $request->boolean('confirmed')) {
            return back()->with('warnings', $check['warnings'])->withInput();
        }

        $svc->updateRate($rate, $data, $request->user(), $request->input('reason'));

        return redirect()->route('admin.rates.index')->with('success', 'Exchange rate updated.');
    }

    public function toggleActive(Request $request, ExchangeRate $rate, ExchangeRateAdminService $svc)
    {
        $svc->setActive($rate, ! $rate->is_active, $request->user());

        return back()->with('success', $rate->is_active ? 'Rate deactivated.' : 'Rate activated.');
    }

    public function destroy(ExchangeRate $rate, ExchangeRateAdminService $svc, Request $request)
    {
        $svc->archive($rate, $request->user());

        return back()->with('success', 'Exchange rate archived.');
    }

    public function storeSchedule(Request $request, ExchangeRateAdminService $svc)
    {
        $data = $request->validate([
            'base_currency' => ['required', 'string', 'size:3'],
            'quote_currency' => ['required', 'string', 'size:3'],
            'rate' => ['required', 'numeric', 'min:0.00000001'],
            'margin_type' => ['required', Rule::enum(ExchangeRateMarginType::class)],
            'margin_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'margin_fixed' => ['nullable', 'numeric'],
            'custom_effective_rate' => ['nullable', 'numeric', 'min:0.00000001'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'reason' => ['required', 'string', 'max:500'],
        ]);
        $data['base_currency'] = strtoupper($data['base_currency']);
        $data['quote_currency'] = strtoupper($data['quote_currency']);

        $check = $svc->validateSchedule($data);
        if (! $check['ok']) {
            return back()->withErrors($check['errors'])->withInput();
        }

        $svc->createSchedule($data, $request->user());

        return back()->with('success', 'Rate change scheduled.');
    }

    public function cancelSchedule(ExchangeRateSchedule $schedule, ExchangeRateAdminService $svc, Request $request)
    {
        $svc->cancelSchedule($schedule, $request->user());

        return back()->with('success', 'Scheduled change cancelled.');
    }

    public function bulkAction(Request $request, ExchangeRateAdminService $svc, AuditLogger $audit)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['activate', 'deactivate', 'export', 'review'])],
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $rows = ExchangeRate::whereIn('id', $data['ids'])->get();
        foreach ($rows as $rate) {
            match ($data['action']) {
                'activate' => $svc->setActive($rate, true, $request->user()),
                'deactivate' => $svc->setActive($rate, false, $request->user()),
                'review' => $audit->log('rate.marked_for_review', "Marked {$rate->pair()} for review", $rate),
                default => null,
            };
        }

        return back()->with('success', ucfirst($data['action']).' applied to '.$rows->count().' rate(s).');
    }

    public function exportCsv(AuditLogger $audit): StreamedResponse
    {
        $rows = ExchangeRate::orderBy('base_currency')->get();
        $audit->log('rate.exported', 'Exported '.$rows->count().' exchange rate(s) to CSV');

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Pair', 'Base rate', 'Margin type', 'Margin', 'Effective rate', 'Source', 'Active', 'Updated']);
            foreach ($rows as $r) {
                fputcsv($out, [
                    $r->pair(), $r->rate, $r->margin_type->label(), $r->margin_percent,
                    $r->effectiveRate(), $r->rate_source->label(), $r->is_active ? 'Yes' : 'No',
                    $r->updated_at->toDateTimeString(),
                ]);
            }
            fclose($out);
        }, 'exchange-rates-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function validated(Request $request, ?ExchangeRate $rate = null): array
    {
        $data = $request->validate([
            'base_currency' => ['required', 'string', 'size:3'],
            'quote_currency' => ['required', 'string', 'size:3'],
            'rate' => ['required', 'numeric', 'min:0.00000001'],
            'margin_type' => ['nullable', Rule::enum(ExchangeRateMarginType::class)],
            'margin_percent' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'margin_fixed' => ['nullable', 'numeric'],
            'custom_effective_rate' => ['nullable', 'numeric', 'min:0.00000001'],
            'rate_source' => ['nullable', 'string', 'in:manual,provider,scheduled_manual'],
            'min_amount' => ['nullable', 'numeric', 'min:0'],
            'max_amount' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['base_currency'] = strtoupper($data['base_currency']);
        $data['quote_currency'] = strtoupper($data['quote_currency']);
        $data['margin_type'] ??= 'percentage';
        $data['margin_percent'] ??= 0;
        $data['rate_source'] ??= 'manual';
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
