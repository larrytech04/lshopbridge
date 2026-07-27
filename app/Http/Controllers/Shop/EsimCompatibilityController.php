<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Services\Esim\DeviceCompatibilityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EsimCompatibilityController extends Controller
{
    public function index(DeviceCompatibilityService $svc): View
    {
        return view('shop.esim.compatibility', ['brands' => $svc->brands()]);
    }

    public function models(Request $request, DeviceCompatibilityService $svc)
    {
        $data = $request->validate(['brand' => ['required', 'string', 'max:80']]);

        return response()->json(['models' => $svc->modelsForBrand($data['brand'])]);
    }

    public function check(Request $request, DeviceCompatibilityService $svc)
    {
        $data = $request->validate(['brand' => ['required', 'string', 'max:80'], 'model' => ['required', 'string', 'max:150']]);
        $result = $svc->check($data['brand'], $data['model']);

        if (! $result['found']) {
            return response()->json([
                'found' => false,
                'message' => "We don't have compatibility data for this exact device yet. Check your device's settings for \"eSIM\" or \"Add Mobile Plan\" to confirm support.",
            ]);
        }

        $device = $result['device'];

        return response()->json([
            'found' => true,
            'esim_supported' => $device->esim_supported,
            'regional_restriction' => $device->regional_restriction,
            'carrier_lock_note' => $device->carrier_lock_note,
            'min_os_version' => $device->min_os_version,
            'dual_sim_support' => $device->dual_sim_support,
            'max_active_esims' => $device->max_active_esims,
            'installation_method' => $device->installation_method,
        ]);
    }
}
