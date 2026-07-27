<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BankAccount;
use App\Models\Country;
use App\Models\CryptoWallet;
use App\Models\MomoNumber;
use App\Services\Admin\DepositAccountAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Manages the collection accounts shown to users for manual deposits:
 * MoMo numbers, crypto wallet addresses and bank accounts ("Deposit Accounts"
 * in the Platform Configuration nav). Sensitive numbers are masked in index()
 * and only unmasked via reveal(), which is behind password.confirm.
 */
class DepositChannelController extends Controller
{
    public function __construct(private DepositAccountAdminService $service) {}

    public function index(): View
    {
        return view('admin.channels.index', [
            'momo' => MomoNumber::withTrashed()->with('country')->orderBy('sort')->get(),
            'crypto' => CryptoWallet::withTrashed()->with('country')->orderBy('sort')->get(),
            'bank' => BankAccount::withTrashed()->with('country')->orderBy('sort')->get(),
            'countries' => Country::active()->get(),
            'summary' => $this->service->summary(),
        ]);
    }

    public function store(Request $request, string $type)
    {
        $this->service->create($type, $this->validated($request, $type), $request->user());

        return back()->with('success', ucfirst($type).' account added.');
    }

    public function update(Request $request, string $type, int $id)
    {
        $model = $this->service->modelClass($type)::findOrFail($id);
        $this->service->update($type, $model, $this->validated($request, $type), $request->user());

        return back()->with('success', ucfirst($type).' account updated.');
    }

    public function setActive(Request $request, string $type, int $id)
    {
        $model = $this->service->modelClass($type)::findOrFail($id);
        $this->service->setActive($type, $model, $request->boolean('is_active'), $request->user());

        return back()->with('success', 'Status updated.');
    }

    /** Archive-not-delete: soft-deletes so accounts referenced by historical deposits are never actually removed. */
    public function destroy(Request $request, string $type, int $id)
    {
        $model = $this->service->modelClass($type)::findOrFail($id);
        $this->service->archive($type, $model, $request->user());

        return back()->with('success', ucfirst($type).' account archived.');
    }

    public function restore(Request $request, string $type, int $id)
    {
        $model = $this->service->modelClass($type)::withTrashed()->findOrFail($id);
        $this->service->restore($type, $model, $request->user());

        return back()->with('success', ucfirst($type).' account restored.');
    }

    /** Sensitive: revealing the real account number/address requires a recently-confirmed password (see routes/web.php). Always audited. */
    public function reveal(Request $request, string $type, int $id)
    {
        $model = $this->service->modelClass($type)::withTrashed()->findOrFail($id);
        $value = $this->service->reveal($type, $model, $request->user());

        return response()->json(['value' => $value]);
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
                'country_id' => ['nullable', 'exists:countries,id'],
            ],
            'bank' => [
                'bank_name' => ['required', 'string', 'max:120'],
                'account_name' => ['required', 'string', 'max:120'],
                'account_number' => ['required', 'string', 'max:60'],
                'iban' => ['nullable', 'string', 'max:40'],
                'routing_number' => ['nullable', 'string', 'max:40'],
                'swift' => ['nullable', 'string', 'max:30'],
                'country_id' => ['nullable', 'exists:countries,id'],
                'instructions' => ['nullable', 'string', 'max:500'],
            ],
            default => abort(404),
        };

        $rules += [
            'internal_code' => ['nullable', 'string', 'max:60'],
            'purpose' => ['nullable', 'in:collection,settlement,escrow,other'],
            'min_deposit' => ['nullable', 'numeric', 'min:0'],
            'max_deposit' => ['nullable', 'numeric', 'min:0'],
            'confirmation_method' => ['nullable', 'in:manual_review,auto_reference_match'],
            'auto_reconciliation' => ['nullable', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
        ];

        $data = $request->validate($rules);
        $data['is_active'] = $request->boolean('is_active');
        $data['auto_reconciliation'] = $request->boolean('auto_reconciliation');

        return $data;
    }
}
