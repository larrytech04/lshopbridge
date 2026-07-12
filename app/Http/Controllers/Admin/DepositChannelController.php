<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Country;
use App\Models\CryptoWallet;
use App\Models\MomoNumber;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manages the collection accounts shown to users for manual deposits:
 * MoMo numbers, crypto wallet addresses and bank accounts.
 */
class DepositChannelController extends Controller
{
    private array $models = [
        'momo' => MomoNumber::class,
        'crypto' => CryptoWallet::class,
        'bank' => BankAccount::class,
    ];

    public function index(): View
    {
        return view('admin.channels.index', [
            'momo' => MomoNumber::with('country')->get(),
            'crypto' => CryptoWallet::all(),
            'bank' => BankAccount::with('country')->get(),
            'countries' => Country::active()->get(),
        ]);
    }

    public function store(Request $request, string $type)
    {
        $model = $this->modelFor($type);
        $model::create($this->validated($request, $type));

        return back()->with('success', ucfirst($type).' channel added.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $model = $this->modelFor($type);
        $model::findOrFail($id)->update($this->validated($request, $type));

        return back()->with('success', ucfirst($type).' channel updated.');
    }

    public function destroy(string $type, int $id)
    {
        $this->modelFor($type)::findOrFail($id)->delete();

        return back()->with('success', ucfirst($type).' channel removed.');
    }

    private function modelFor(string $type): string
    {
        abort_unless(isset($this->models[$type]), 404);

        return $this->models[$type];
    }

    private function validated(Request $request, string $type): array
    {
        $rules = match ($type) {
            'momo' => [
                'provider' => ['required', 'in:mtn,orange'],
                'number' => ['required', 'string', 'max:40'],
                'account_name' => ['required', 'string', 'max:120'],
                'country_id' => ['nullable', 'exists:countries,id'],
                'instructions' => ['nullable', 'string', 'max:500'],
            ],
            'crypto' => [
                'asset' => ['required', 'string', 'max:20'],
                'network' => ['required', 'string', 'max:30'],
                'address' => ['required', 'string', 'max:160'],
                'memo' => ['nullable', 'string', 'max:120'],
            ],
            'bank' => [
                'bank_name' => ['required', 'string', 'max:120'],
                'account_name' => ['required', 'string', 'max:120'],
                'account_number' => ['required', 'string', 'max:60'],
                'swift' => ['nullable', 'string', 'max:30'],
                'country_id' => ['nullable', 'exists:countries,id'],
                'instructions' => ['nullable', 'string', 'max:500'],
            ],
            default => abort(404),
        };

        $rules['is_active'] = ['nullable', 'boolean'];
        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
