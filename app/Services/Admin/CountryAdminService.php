<?php

namespace App\Services\Admin;

use App\Enums\CountryLaunchStatus;
use App\Models\Country;
use App\Models\User;
use App\Services\Audit\AuditLogger;

/**
 * Countries are permanent reference data (users, deposits, funding requests
 * all key off country_id) — never deleted, only moved through launch_status.
 * That status stays synced onto the legacy is_active/is_blocked booleans so
 * every existing consumer of those columns keeps working unchanged.
 */
class CountryAdminService
{
    public function __construct(private AuditLogger $audit) {}

    public function create(array $data, User $admin): Country
    {
        $country = new Country($this->normalize($data) + ['updated_by' => $admin->id]);
        $country->syncLegacyStatusFlags();
        $country->save();
        $this->audit->log('admin.country.created', "Created country {$country->name}", $country, [], $admin->id);

        return $country;
    }

    public function update(Country $country, array $data, User $admin): Country
    {
        $before = $country->only(['name', 'launch_status', 'is_active', 'is_blocked']);
        $country->fill($this->normalize($data) + ['updated_by' => $admin->id]);
        $country->syncLegacyStatusFlags();
        $country->save();
        $this->audit->log('admin.country.updated', "Updated country {$country->name}", $country, ['before' => $before], $admin->id);

        return $country->fresh();
    }

    private function normalize(array $data): array
    {
        // is_active/is_blocked are derived from launch_status (see
        // syncLegacyStatusFlags) — never accepted directly from a form here,
        // so there is exactly one authority for a country's status.
        unset($data['is_active'], $data['is_blocked']);

        return $data;
    }

    public function setLaunchStatus(Country $country, CountryLaunchStatus $status, User $admin): Country
    {
        $country->launch_status = $status;
        $country->syncLegacyStatusFlags();
        $country->updated_by = $admin->id;
        $country->save();
        $this->audit->log('admin.country.status_changed', "Set {$country->name} to {$status->value}", $country, [], $admin->id);

        return $country->fresh();
    }

    public function summary(): array
    {
        $countries = Country::all();

        return [
            'total' => $countries->count(),
            'active' => $countries->filter(fn ($c) => $c->launch_status === CountryLaunchStatus::Active)->count(),
            'coming_soon' => $countries->filter(fn ($c) => $c->launch_status === CountryLaunchStatus::ComingSoon)->count(),
            'restricted' => $countries->filter(fn ($c) => $c->launch_status === CountryLaunchStatus::Restricted)->count(),
            'disabled' => $countries->filter(fn ($c) => in_array($c->launch_status, [CountryLaunchStatus::Disabled, CountryLaunchStatus::Maintenance], true))->count(),
        ];
    }
}
