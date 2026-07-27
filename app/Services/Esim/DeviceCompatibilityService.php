<?php

namespace App\Services\Esim;

use App\Models\EsimDevice;
use Illuminate\Support\Collection;

/**
 * Reads the curated EsimDevice reference table — never invents a
 * compatibility answer for a device that isn't in it.
 */
class DeviceCompatibilityService
{
    public function brands(): Collection
    {
        return EsimDevice::active()->orderBy('brand')->distinct()->pluck('brand');
    }

    public function modelsForBrand(string $brand): Collection
    {
        return EsimDevice::active()->where('brand', $brand)->orderBy('model')->get(['id', 'model', 'esim_supported']);
    }

    /**
     * @return array{found: bool, device?: EsimDevice}
     */
    public function check(string $brand, string $model): array
    {
        $device = EsimDevice::active()->where('brand', $brand)->where('model', $model)->first();

        return $device ? ['found' => true, 'device' => $device] : ['found' => false];
    }
}
