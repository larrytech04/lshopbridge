<?php

namespace App\Services\Admin;

use App\Enums\FeeStatus;
use App\Enums\FeeType;
use App\Models\Fee;
use App\Models\FeeExemption;
use App\Models\FeeSchedule;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

/**
 * Admin-side create/update/schedule/exempt/archive logic for fees. Fee
 * *resolution and calculation* (what a live transaction actually charges)
 * stays in App\Services\Fees\FeeCalculationService — this service only ever
 * writes to the admin-managed tables.
 */
class FeeAdminService
{
    public function __construct(private AuditLogger $audit) {}

    private const DIMENSIONS = ['scope', 'country', 'customer_role', 'kyc_level', 'payment_provider', 'china_wallet_type', 'currency'];

    /**
     * @return array{ok:bool, errors:array<string,string>, warnings:array<string>}
     */
    public function validateFee(array $data, ?Fee $existing = null): array
    {
        $errors = [];
        $warnings = [];

        $type = FeeType::from($data['type'] ?? 'percent');
        $value = (float) ($data['value'] ?? 0);
        $fixedValue = isset($data['fixed_value']) ? (float) $data['fixed_value'] : null;

        if ($value < 0) {
            $errors['value'] = 'Value cannot be negative.';
        }
        if ($fixedValue !== null && $fixedValue < 0) {
            $errors['fixed_value'] = 'Fixed value cannot be negative.';
        }

        $maxMargin = (float) config('platform.risk.max_fee_percent', 20);
        if (in_array($type, [FeeType::Percent, FeeType::FixedPlusPercent, FeeType::ProviderPassed], true) && $value > $maxMargin) {
            $errors['value'] = "Percentage cannot exceed the configured administrative limit ({$maxMargin}%).";
        }

        $minFee = (float) ($data['min_fee'] ?? 0);
        $maxFee = isset($data['max_fee']) && $data['max_fee'] !== null && $data['max_fee'] !== '' ? (float) $data['max_fee'] : null;
        if ($maxFee !== null && $maxFee < $minFee) {
            $errors['max_fee'] = 'Maximum fee cannot be lower than minimum fee.';
        }

        $minAmount = isset($data['min_amount']) && $data['min_amount'] !== null && $data['min_amount'] !== '' ? (float) $data['min_amount'] : null;
        $maxAmount = isset($data['max_amount']) && $data['max_amount'] !== null && $data['max_amount'] !== '' ? (float) $data['max_amount'] : null;
        if ($minAmount !== null && $maxAmount !== null && $maxAmount < $minAmount) {
            $errors['max_amount'] = 'Maximum transaction amount cannot be lower than minimum transaction amount.';
        }

        if (in_array($type, [FeeType::Fixed, FeeType::FixedPlusPercent], true) && empty($data['currency'])) {
            $errors['currency'] = 'Currency is required for fixed fees.';
        }

        if ($type === FeeType::Fixed && $minAmount !== null && $value > $minAmount) {
            $warnings[] = "This fixed fee ({$value}) exceeds the minimum transaction amount this rule applies to ({$minAmount}) — it would consume the entire transaction.";
        }

        if ($type === FeeType::Tiered && empty($data['tiers'])) {
            $errors['tiers'] = 'At least one tier is required for a tiered fee.';
        }

        // Duplicate active "default" rule: same applies_to, no narrowing dimensions set on either side.
        $isDefault = collect(self::DIMENSIONS)->every(fn ($d) => empty($data[$d]));
        if ($isDefault) {
            $duplicateDefault = Fee::active()
                ->where('applies_to', $data['applies_to'] ?? null)
                ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
                ->get()
                ->contains(fn (Fee $f) => collect(self::DIMENSIONS)->every(fn ($d) => empty($f->{$d})));
            if ($duplicateDefault) {
                $errors['applies_to'] = 'An active default fee already exists for this category — deactivate or narrow it first.';
            }
        }

        if ($existing && $value > 0) {
            $previous = (float) $existing->value;
            $threshold = (float) config('platform.risk.large_fee_change_percent', 20);
            $changed = $previous <= 0 ? $value > 0 : (abs(($value - $previous) / $previous) * 100) >= $threshold;
            if ($changed) {
                $warnings[] = 'This changes the fee value by a large amount from the previous configuration — confirm before saving.';
            }
        }

        if (($data['type'] ?? null) === 'provider_passed') {
            $warnings[] = 'No payment provider in this platform exposes real-time fee data — the value above is a manually configured estimate, not a live provider fee.';
        }

        return ['ok' => empty($errors), 'errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * @return array{ok:bool, errors:array<string,string>}
     */
    public function validateTiers(array $tiers): array
    {
        $errors = [];
        $sorted = collect($tiers)->sortBy('min_amount')->values();

        foreach ($sorted as $i => $tier) {
            if ((float) ($tier['min_amount'] ?? -1) < 0) {
                $errors['tiers'] = 'Tier minimum amounts cannot be negative.';
                break;
            }
            $next = $sorted->get($i + 1);
            if ($next && $tier['max_amount'] !== null && $tier['max_amount'] !== '' && (float) $tier['max_amount'] >= (float) $next['min_amount']) {
                $errors['tiers'] = 'Tiers overlap — each tier\'s maximum must be below the next tier\'s minimum.';
                break;
            }
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    public function createFee(array $data, User $admin, array $tiers = []): Fee
    {
        return DB::transaction(function () use ($data, $admin, $tiers) {
            $fee = Fee::create($data + ['updated_by' => $admin->id]);
            $this->syncTiers($fee, $tiers);
            $this->snapshotHistory($fee, 'created', $admin, $data['reason'] ?? null);
            $this->audit->log('fee.created', "Created fee {$fee->name}", $fee, $data);

            return $fee;
        });
    }

    public function updateFee(Fee $fee, array $data, User $admin, ?string $reason = null, array $tiers = []): Fee
    {
        return DB::transaction(function () use ($fee, $data, $admin, $reason, $tiers) {
            $before = $fee->only(['value', 'fixed_value', 'type', 'min_fee', 'max_fee', 'is_active']);
            $fee->update($data + ['updated_by' => $admin->id]);
            $this->syncTiers($fee, $tiers);
            $this->snapshotHistory($fee->fresh(), 'updated', $admin, $reason);
            $this->audit->log('fee.updated', "Updated fee {$fee->name}", $fee, ['before' => $before, 'after' => $data, 'reason' => $reason]);

            return $fee->fresh();
        });
    }

    public function setActive(Fee $fee, bool $active, User $admin): Fee
    {
        $fee->update(['is_active' => $active, 'updated_by' => $admin->id]);
        $this->snapshotHistory($fee->fresh(), $active ? 'activated' : 'deactivated', $admin);
        $this->audit->log($active ? 'fee.activated' : 'fee.deactivated', ($active ? 'Activated' : 'Deactivated')." fee {$fee->name}", $fee);

        return $fee->fresh();
    }

    public function markUnderReview(Fee $fee, bool $flag, User $admin): Fee
    {
        $fee->update(['under_review' => $flag, 'updated_by' => $admin->id]);
        $this->audit->log('fee.marked_for_review', ($flag ? 'Flagged' : 'Cleared review flag on')." fee {$fee->name}", $fee);

        return $fee->fresh();
    }

    public function archive(Fee $fee, User $admin): void
    {
        $fee->update(['is_active' => false, 'updated_by' => $admin->id]);
        $this->snapshotHistory($fee->fresh(), 'archived', $admin);
        $this->audit->log('fee.archived', "Archived fee {$fee->name}", $fee);
        $fee->delete(); // soft delete — history/schedules and any transaction snapshot are unaffected
    }

    public function testFee(Fee $fee, float $amount, \App\Services\Fees\FeeCalculationService $engine): array
    {
        $result = $engine->calculate($amount, $fee->applies_to, ['scope' => $fee->scope]);
        $this->audit->log('fee.tested', "Tested fee {$fee->name} against {$amount}", $fee, ['amount' => $amount, 'result' => $result]);

        return $result;
    }

    /**
     * @return array{ok:bool, errors:array<string,string>}
     */
    public function validateSchedule(array $data): array
    {
        $errors = [];

        $from = $data['effective_start_date'] ?? null;
        $to = $data['effective_end_date'] ?? null;

        $conflict = FeeSchedule::where('fee_id', $data['fee_id'] ?? null)
            ->where('status', 'scheduled')
            ->whereDate('effective_start_date', '<=', $to ?? '9999-12-31')
            ->where(fn ($q) => $q->whereNull('effective_end_date')->orWhereDate('effective_end_date', '>=', $from))
            ->exists();
        if ($conflict) {
            $errors['effective_start_date'] = 'A scheduled change already exists for this fee in an overlapping window.';
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    public function createSchedule(array $data, User $admin): FeeSchedule
    {
        $schedule = FeeSchedule::create($data + ['created_by' => $admin->id, 'status' => 'scheduled']);
        $this->audit->log('fee.scheduled', "Scheduled a fee change for #{$schedule->fee_id}", $schedule, $data);

        return $schedule;
    }

    public function cancelSchedule(FeeSchedule $schedule, User $admin): void
    {
        $schedule->update(['status' => 'cancelled', 'cancelled_by' => $admin->id, 'cancelled_at' => now()]);
        $this->audit->log('fee.schedule_cancelled', "Cancelled scheduled fee change for #{$schedule->fee_id}", $schedule);
    }

    /** Promotes any due schedule into the live fee row. Runs opportunistically on page load — no queue/cron exists. */
    public function applyDueSchedules(): int
    {
        $applied = 0;

        foreach (FeeSchedule::where('status', 'scheduled')->get() as $schedule) {
            if ($schedule->isExpired()) {
                $schedule->update(['status' => 'expired']);

                continue;
            }

            if (! $schedule->isDue()) {
                continue;
            }

            DB::transaction(function () use ($schedule) {
                $fee = $schedule->fee;
                if (! $fee) {
                    $schedule->update(['status' => 'expired']);

                    return;
                }

                $updates = array_filter([
                    'type' => $schedule->new_type,
                    'value' => $schedule->new_value,
                    'min_fee' => $schedule->new_min_fee,
                    'max_fee' => $schedule->new_max_fee,
                ], fn ($v) => $v !== null);

                $fee->update($updates);
                $this->snapshotHistory($fee->fresh(), 'schedule_applied', null, $schedule->reason);
                $schedule->update(['status' => 'applied']);
                $this->audit->log('fee.schedule_applied', "Applied scheduled fee change for {$fee->name}", $fee);
            });

            $applied++;
        }

        return $applied;
    }

    public function createExemption(array $data, User $admin): FeeExemption
    {
        $exemption = FeeExemption::create($data + ['created_by' => $admin->id, 'approved_by' => $data['approved_by'] ?? $admin->id]);
        $this->audit->log('fee.exemption_created', "Created fee exemption ({$exemption->exemption_type}: {$exemption->target_value})", $exemption, $data);

        return $exemption;
    }

    public function revokeExemption(FeeExemption $exemption, User $admin): void
    {
        $exemption->update(['is_active' => false]);
        $this->audit->log('fee.exemption_revoked', "Revoked fee exemption ({$exemption->exemption_type}: {$exemption->target_value})", $exemption, [], $admin->id);
    }

    public function computeStatus(Fee $fee): FeeStatus
    {
        if (! $fee->is_active) {
            return FeeStatus::Inactive;
        }

        $today = now()->toDateString();
        if ($fee->effective_end_date && $fee->effective_end_date->toDateString() < $today) {
            return FeeStatus::Expired;
        }

        if ($fee->effective_start_date && $fee->effective_start_date->toDateString() > $today) {
            return FeeStatus::Scheduled;
        }

        if ($fee->schedules()->where('status', 'scheduled')->whereDate('effective_start_date', '>', $today)->exists()) {
            return FeeStatus::Scheduled;
        }

        if ($fee->under_review) {
            return FeeStatus::UnderReview;
        }

        return FeeStatus::Active;
    }

    public function summary(): array
    {
        $fees = Fee::all();
        $today = now()->startOfMonth();

        return [
            'total_rules' => $fees->count(),
            'active' => $fees->where('is_active', true)->count(),
            'inactive' => $fees->where('is_active', false)->count(),
            'scheduled' => $fees->filter(fn ($f) => $this->computeStatus($f) === FeeStatus::Scheduled)->count(),
            'percentage' => $fees->whereIn('type', [FeeType::Percent, FeeType::FixedPlusPercent])->count(),
            'fixed' => $fees->where('type', FeeType::Fixed)->count(),
            'requiring_review' => $fees->where('under_review', true)->count(),
            'revenue_this_month' => round(
                (float) \App\Models\Deposit::where('created_at', '>=', $today)->sum('fee')
                + (float) \App\Models\FundingRequest::where('created_at', '>=', $today)->sum('fee'),
                2
            ),
        ];
    }

    private function syncTiers(Fee $fee, array $tiers): void
    {
        if ($fee->type !== FeeType::Tiered) {
            $fee->tiers()->delete();

            return;
        }

        $fee->tiers()->delete();
        foreach (array_values($tiers) as $i => $tier) {
            $fee->tiers()->create([
                'min_amount' => $tier['min_amount'] ?? 0,
                'max_amount' => $tier['max_amount'] ?? null,
                'percent' => $tier['percent'] ?? 0,
                'fixed' => $tier['fixed'] ?? 0,
                'sort' => $i,
            ]);
        }
    }

    private function snapshotHistory(Fee $fee, string $event, ?User $actor, ?string $reason = null): void
    {
        \App\Models\FeeHistory::create([
            'fee_id' => $fee->id,
            'name' => $fee->name,
            'applies_to' => $fee->applies_to,
            'type' => $fee->type->value,
            'value' => $fee->value,
            'min_fee' => $fee->min_fee,
            'max_fee' => $fee->max_fee,
            'currency' => $fee->currency,
            'is_active' => $fee->is_active,
            'effective_start_date' => $fee->effective_start_date,
            'effective_end_date' => $fee->effective_end_date,
            'event' => $event,
            'changed_by' => $actor?->id,
            'reason' => $reason,
        ]);
    }
}
