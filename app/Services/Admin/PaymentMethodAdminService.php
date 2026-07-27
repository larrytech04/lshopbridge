<?php

namespace App\Services\Admin;

use App\Enums\PaymentMethodStatus;
use App\Models\PaymentMethod;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;

class PaymentMethodAdminService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(array $data, User $admin): PaymentMethod
    {
        return DB::transaction(function () use ($data, $admin) {
            $method = PaymentMethod::create($this->normalize($data) + ['updated_by' => $admin->id]);
            $this->audit->log('admin.payment_method.created', "Created payment method {$method->name}", $method, $data, $admin->id);

            return $method;
        });
    }

    public function update(PaymentMethod $method, array $data, User $admin): PaymentMethod
    {
        // The code is a stable identifier already referenced by historical
        // deposits/orders — never let an update silently rename it, even if a
        // client submits one (the edit UI disables the field, but that's a
        // client-side courtesy, not the enforcement point).
        unset($data['code']);

        return DB::transaction(function () use ($method, $data, $admin) {
            $before = $method->only(['name', 'status', 'is_active', 'min_amount', 'max_amount']);
            $method->update($this->normalize($data) + ['updated_by' => $admin->id]);
            $this->audit->log('admin.payment_method.updated', "Updated payment method {$method->name}", $method, ['before' => $before, 'after' => $data], $admin->id);

            return $method->fresh();
        });
    }

    private function normalize(array $data): array
    {
        $data['countries'] = ! empty($data['countries']) ? array_values(array_filter((array) $data['countries'])) : null;
        $data['currencies'] = ! empty($data['currencies']) ? array_values(array_filter((array) $data['currencies'])) : null;
        $data['is_automated'] = ! empty($data['is_automated']);
        $data['requires_proof'] = ! empty($data['requires_proof']);
        $data['deposit_enabled'] = ! empty($data['deposit_enabled']);
        $data['marketplace_enabled'] = ! empty($data['marketplace_enabled']);
        $data['refund_support'] = ! empty($data['refund_support']);
        $data['partial_refund_support'] = ! empty($data['partial_refund_support']);
        $data['requires_manual_review'] = ! empty($data['requires_manual_review']);
        $data['is_active'] = ($data['status'] ?? null) === PaymentMethodStatus::Active->value;

        return $data;
    }

    public function setStatus(PaymentMethod $method, PaymentMethodStatus $status, User $admin): PaymentMethod
    {
        $method->update([
            'status' => $status,
            'is_active' => $status === PaymentMethodStatus::Active,
            'updated_by' => $admin->id,
        ]);
        $this->audit->log('admin.payment_method.status_changed', "Set {$method->name} to {$status->value}", $method, [], $admin->id);

        return $method->fresh();
    }

    /** Archive-not-delete: soft-deletes so methods referenced by historical deposits/orders are never actually removed. */
    public function archive(PaymentMethod $method, User $admin): void
    {
        $method->update(['status' => PaymentMethodStatus::Archived, 'is_active' => false, 'updated_by' => $admin->id]);
        $this->audit->log('admin.payment_method.archived', "Archived payment method {$method->name}", $method, [], $admin->id);
        $method->delete();
    }

    public function restore(PaymentMethod $method, User $admin): PaymentMethod
    {
        $method->restore();
        $method->update(['status' => PaymentMethodStatus::Disabled, 'updated_by' => $admin->id]);
        $this->audit->log('admin.payment_method.restored', "Restored payment method {$method->name}", $method, [], $admin->id);

        return $method->fresh();
    }

    public function summary(): array
    {
        $methods = PaymentMethod::withTrashed()->get();

        return [
            'total' => $methods->count(),
            'active' => $methods->filter(fn ($m) => $m->status === PaymentMethodStatus::Active)->count(),
            'disabled' => $methods->filter(fn ($m) => $m->status === PaymentMethodStatus::Disabled)->count(),
            'draft' => $methods->filter(fn ($m) => $m->status === PaymentMethodStatus::Draft)->count(),
            'archived' => $methods->filter(fn ($m) => $m->status === PaymentMethodStatus::Archived)->count(),
            'automated' => $methods->where('is_automated', true)->count(),
            'manual' => $methods->where('is_automated', false)->count(),
        ];
    }
}
