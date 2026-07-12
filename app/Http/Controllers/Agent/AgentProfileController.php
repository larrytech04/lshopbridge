<?php

namespace App\Http\Controllers\Agent;

use App\Http\Controllers\Controller;
use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AgentProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('agent.profile', [
            'agent' => $request->user()->agent,
            'countries' => Country::active()->get(),
            'allMethods' => ['air' => 'Air freight', 'sea' => 'Sea freight', 'express' => 'Express courier'],
        ]);
    }

    public function update(Request $request)
    {
        $agent = $request->user()->agent;

        $data = $request->validate([
            'business_name' => ['required', 'string', 'max:160'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'phone' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'wechat' => ['nullable', 'string', 'max:60'],
            'warehouse_address' => ['nullable', 'string', 'max:255'],
            'warehouse_city' => ['nullable', 'string', 'max:120'],
            'warehouse_country_id' => ['nullable', 'exists:countries,id'],
            'cities' => ['nullable', 'string', 'max:500'],
            'shipping_methods' => ['nullable', 'array'],
            'shipping_methods.*' => ['in:air,sea,express'],
            'countries' => ['nullable', 'array'],
            'countries.*' => ['exists:countries,id'],
            'logo' => ['nullable', 'image', 'max:2048'],
        ]);

        $agent->fill([
            'business_name' => $data['business_name'],
            'bio' => $data['bio'] ?? null,
            'phone' => $data['phone'] ?? null,
            'whatsapp' => $data['whatsapp'] ?? null,
            'wechat' => $data['wechat'] ?? null,
            'warehouse_address' => $data['warehouse_address'] ?? null,
            'warehouse_city' => $data['warehouse_city'] ?? null,
            'warehouse_country_id' => $data['warehouse_country_id'] ?? null,
            'cities' => array_filter(array_map('trim', explode(',', (string) ($data['cities'] ?? '')))),
            'shipping_methods' => $data['shipping_methods'] ?? [],
        ]);

        if ($request->hasFile('logo')) {
            $agent->logo_path = $request->file('logo')->store('agents/logos', 'public');
        }

        $agent->save();
        $agent->countries()->sync($data['countries'] ?? []);

        return back()->with('success', 'Agent profile updated.');
    }
}
