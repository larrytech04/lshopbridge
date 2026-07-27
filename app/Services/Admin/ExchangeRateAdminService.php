<?php

namespace App\Services\Admin;

use App\Enums\ExchangeRateMarginType;
use App\Enums\ExchangeRateStatus;
use App\Models\ExchangeRate;
use App\Models\ExchangeRateSchedule;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use App\Services\Funding\RateService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Admin-side create/update/schedule/archive logic for exchange rates. Rate
 * *resolution* (what a live transaction actually charges) stays in
 * App\Services\Funding\RateService — this service only ever writes to the
 * admin-managed tables and never touches a FundingRequest/Deposit directly.
 */
class ExchangeRateAdminService
{
    public function __construct(private AuditLogger $audit, private RateService $rates)
    {
    }

    /**
     * @return array{ok:bool, errors:array<string,string>, warnings:array<string>}
     */
    public function validateRate(array $data, ?ExchangeRate $existing = null): array
    {
        $errors = [];
        $warnings = [];

        if (($data['base_currency'] ?? null) === ($data['quote_currency'] ?? null)) {
            $errors['quote_currency'] = 'Source and destination currencies cannot be identical.';
        }

        $duplicate = ExchangeRate::where('base_currency', $data['base_currency'] ?? null)
            ->where('quote_currency', $data['quote_currency'] ?? null)
            ->where('is_active', true)
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();
        if ($duplicate) {
            $errors['quote_currency'] = 'An active rate already exists for this currency pair.';
        }

        if ((float) ($data['rate'] ?? 0) <= 0) {
            $errors['rate'] = 'Rate must be greater than zero.';
        }

        $marginType = ExchangeRateMarginType::from($data['margin_type'] ?? 'percentage');
        $maxMargin = (float) config('platform.risk.max_margin_percent', 10);
        if ($marginType === ExchangeRateMarginType::Percentage && (float) ($data['margin_percent'] ?? 0) > $maxMargin) {
            $errors['margin_percent'] = "Margin cannot exceed the configured administrative limit ({$maxMargin}%).";
        }

        $effective = ExchangeRate::computeEffectiveRate(
            (float) ($data['rate'] ?? 0),
            $marginType,
            (float) ($data['margin_percent'] ?? 0),
            isset($data['margin_fixed']) ? (float) $data['margin_fixed'] : null,
            isset($data['custom_effective_rate']) ? (float) $data['custom_effective_rate'] : null,
        );
        if ($effective <= 0) {
            $errors['margin_percent'] = 'The effective rate must be greater than zero — check the margin.';
        }

        if ($existing && $effective > 0) {
            $previousEffective = $existing->effectiveRate();
            if ($previousEffective > 0) {
                $change = abs(($effective - $previousEffective) / $previousEffective) * 100;
                if ($change >= (float) config('platform.risk.large_rate_change_percent', 5)) {
                    $warnings[] = 'This changes the effective rate by '.round($change, 1).'% from the previous value — confirm before saving.';
                }
            }

            $pendingCount = $this->pendingTransactionCount($existing->base_currency, $existing->quote_currency);
            if ($pendingCount > 0) {
                $warnings[] = "{$pendingCount} funding request(s) are currently in flight for this pair — they already locked in their own rate at creation time and are unaffected, but review before deactivating.";
            }
        }

        if (($data['rate_source'] ?? 'manual') === 'provider') {
            $warnings[] = 'No automatic FX provider is connected in this platform — a "provider" rate still requires the value above to be entered and kept up to date manually.';
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    public function createRate(array $data, User $admin): ExchangeRate
    {
        return DB::transaction(function () use ($data, $admin) {
            $rate = ExchangeRate::create($data + ['updated_by' => $admin->id]);
            $this->snapshotHistory($rate, 'created', $admin, $data['reason'] ?? null);
            $this->audit->log('rate.created', "Created exchange rate {$rate->pair()}", $rate, $data);

            return $rate;
        });
    }

    public function updateRate(ExchangeRate $rate, array $data, User $admin, ?string $reason = null): ExchangeRate
    {
        return DB::transaction(function () use ($rate, $data, $admin, $reason) {
            $before = $rate->only(['rate', 'margin_percent', 'margin_type', 'margin_fixed', 'custom_effective_rate', 'is_active']);
            $rate->update($data + ['updated_by' => $admin->id]);
            $this->snapshotHistory($rate->fresh(), 'updated', $admin, $reason);
            $this->audit->log('rate.updated', "Updated exchange rate {$rate->pair()}", $rate, ['before' => $before, 'after' => $data, 'reason' => $reason]);

            return $rate->fresh();
        });
    }

    public function setActive(ExchangeRate $rate, bool $active, User $admin): ExchangeRate
    {
        $rate->update(['is_active' => $active, 'updated_by' => $admin->id]);
        $this->snapshotHistory($rate->fresh(), $active ? 'activated' : 'deactivated', $admin);
        $this->audit->log($active ? 'rate.activated' : 'rate.deactivated', ($active ? 'Activated' : 'Deactivated')." exchange rate {$rate->pair()}", $rate);

        return $rate->fresh();
    }

    public function archive(ExchangeRate $rate, User $admin): void
    {
        $rate->update(['is_active' => false, 'updated_by' => $admin->id]);
        $this->snapshotHistory($rate->fresh(), 'archived', $admin);
        $this->audit->log('rate.archived', "Archived exchange rate {$rate->pair()}", $rate);
        $rate->delete(); // soft delete — history/schedules and any transaction snapshot are unaffected
    }

    /**
     * @return array{ok:bool, errors:array<string,string>}
     */
    public function validateSchedule(array $data): array
    {
        $errors = [];

        if ((float) ($data['rate'] ?? 0) <= 0) {
            $errors['rate'] = 'Rate must be greater than zero.';
        }

        $from = $data['effective_from'] ?? null;
        $to = $data['effective_to'] ?? null;

        // Two windows [existing.from, existing.to] and [new.from, new.to] overlap iff
        // existing.from <= new.to (or new has no end) AND existing.to >= new.from (or existing has no end).
        $conflict = ExchangeRateSchedule::where('base_currency', $data['base_currency'] ?? null)
            ->where('quote_currency', $data['quote_currency'] ?? null)
            ->where('status', 'scheduled')
            ->whereDate('effective_from', '<=', $to ?? '9999-12-31')
            ->where(fn ($q) => $q->whereNull('effective_to')->orWhereDate('effective_to', '>=', $from))
            ->exists();
        if ($conflict) {
            $errors['effective_from'] = 'A scheduled change already exists for this pair in an overlapping window.';
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    public function createSchedule(array $data, User $admin): ExchangeRateSchedule
    {
        $schedule = ExchangeRateSchedule::create($data + ['created_by' => $admin->id, 'status' => 'scheduled']);
        $this->audit->log('rate.schedule_created', "Scheduled a rate change for {$schedule->base_currency} → {$schedule->quote_currency}", $schedule, $data);

        return $schedule;
    }

    public function cancelSchedule(ExchangeRateSchedule $schedule, User $admin): void
    {
        $schedule->update(['status' => 'cancelled', 'cancelled_by' => $admin->id, 'cancelled_at' => now()]);
        $this->audit->log('rate.schedule_cancelled', "Cancelled scheduled rate change for {$schedule->base_currency} → {$schedule->quote_currency}", $schedule);
    }

    /**
     * Promotes any due schedule into the live exchange_rates row. No queue/cron
     * exists in this codebase, so this runs opportunistically on page load —
     * cheap (a handful of rows at most) and idempotent.
     */
    public function applyDueSchedules(): int
    {
        $applied = 0;

        foreach (ExchangeRateSchedule::where('status', 'scheduled')->get() as $schedule) {
            if ($schedule->isExpired()) {
                $schedule->update(['status' => 'expired']);

                continue;
            }

            if (! $schedule->isDue()) {
                continue;
            }

            DB::transaction(function () use ($schedule) {
                $rate = ExchangeRate::updateOrCreate(
                    ['base_currency' => $schedule->base_currency, 'quote_currency' => $schedule->quote_currency],
                    [
                        'rate' => $schedule->rate,
                        'margin_percent' => $schedule->margin_percent,
                        'margin_type' => $schedule->margin_type,
                        'margin_fixed' => $schedule->margin_fixed,
                        'custom_effective_rate' => $schedule->custom_effective_rate,
                        'rate_source' => 'scheduled_manual',
                        'is_active' => true,
                    ],
                );
                $this->snapshotHistory($rate, 'schedule_applied', null, $schedule->reason);
                $schedule->update(['status' => 'applied']);
                $this->audit->log('rate.schedule_applied', "Applied scheduled rate change for {$schedule->base_currency} → {$schedule->quote_currency}", $rate);
            });

            $applied++;
        }

        return $applied;
    }

    public function computeStatus(ExchangeRate $rate): ExchangeRateStatus
    {
        if (! $rate->is_active) {
            return ExchangeRateStatus::Inactive;
        }

        if ($this->rates->upcomingSchedule($rate->base_currency, $rate->quote_currency)) {
            return ExchangeRateStatus::Scheduled;
        }

        if ($rate->rate_source === \App\Enums\ExchangeRateSource::Provider) {
            return ExchangeRateStatus::ProviderUnavailable;
        }

        if ($rate->updated_at->lt(now()->subDays(30))) {
            return ExchangeRateStatus::Outdated;
        }

        return ExchangeRateStatus::Active;
    }

    public function summary(): array
    {
        $rates = ExchangeRate::all();
        $today = now()->startOfDay();

        return [
            'active_pairs' => $rates->where('is_active', true)->count(),
            'inactive_pairs' => $rates->where('is_active', false)->count(),
            'automatic' => $rates->where('rate_source', 'provider')->count(),
            'manual' => $rates->whereIn('rate_source', ['manual', 'scheduled_manual'])->count(),
            'updated_today' => $rates->where('updated_at', '>=', $today)->count(),
            'requires_attention' => $rates->filter(fn ($r) => in_array($this->computeStatus($r), [ExchangeRateStatus::Outdated, ExchangeRateStatus::RequiresReview, ExchangeRateStatus::ProviderUnavailable], true))->count(),
        ];
    }

    private function pendingTransactionCount(string $base, string $quote): int
    {
        return \App\Models\FundingRequest::where('source_currency', $base)
            ->where('target_currency', $quote)
            ->whereIn('status', ['payment_pending', 'payment_successful', 'funding_processing', 'manual_review'])
            ->count();
    }

    private function snapshotHistory(ExchangeRate $rate, string $event, ?User $actor, ?string $reason = null): void
    {
        \App\Models\ExchangeRateHistory::create([
            'exchange_rate_id' => $rate->id,
            'base_currency' => $rate->base_currency,
            'quote_currency' => $rate->quote_currency,
            'rate' => $rate->rate,
            'margin_percent' => $rate->margin_percent,
            'margin_type' => $rate->margin_type,
            'margin_fixed' => $rate->margin_fixed,
            'custom_effective_rate' => $rate->custom_effective_rate,
            'effective_rate' => $rate->effectiveRate(),
            'is_active' => $rate->is_active,
            'event' => $event,
            'changed_by' => $actor?->id,
            'reason' => $reason,
        ]);
    }
}
