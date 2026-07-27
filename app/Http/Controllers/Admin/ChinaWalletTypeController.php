<?php

namespace App\Http\Controllers\Admin;

use App\Enums\WalletIdentifierType;
use App\Http\Controllers\Controller;
use App\Models\ChinaWalletType;
use App\Models\PaymentProvider;
use App\Services\Admin\ChinaWalletTypeAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ChinaWalletTypeController extends Controller
{
    public function __construct(private ChinaWalletTypeAdminService $service) {}

    public function index(): View
    {
        return view('admin.china-wallet-types.index', [
            'wallets' => ChinaWalletType::orderBy('sort')->get(),
            'providers' => PaymentProvider::orderBy('name')->get(),
            'identifierTypes' => WalletIdentifierType::cases(),
            'summary' => $this->service->summary(),
        ]);
    }

    public function store(Request $request)
    {
        $this->service->create($this->validated($request), $request->user());

        return back()->with('success', 'Wallet type added.');
    }

    public function update(Request $request, ChinaWalletType $wallet)
    {
        $this->service->update($wallet, $this->validated($request, $wallet), $request->user());

        return back()->with('success', 'Wallet type updated.');
    }

    public function setActive(Request $request, ChinaWalletType $wallet)
    {
        $this->service->setActive($wallet, $request->boolean('is_active'), $request->user());

        return back()->with('success', 'Status updated.');
    }

    private function validated(Request $request, ?ChinaWalletType $wallet = null): array
    {
        $data = $request->validate([
            'code' => $wallet ? ['sometimes'] : ['required', 'string', 'max:40', 'unique:china_wallet_types,code'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'account_identifier_type' => ['required', 'in:'.implode(',', array_column(WalletIdentifierType::cases(), 'value'))],
            'qr_required' => ['nullable', 'boolean'],
            'account_name_required' => ['nullable', 'boolean'],
            'phone_required' => ['nullable', 'boolean'],
            'country_restrictions' => ['nullable', 'array'],
            'country_restrictions.*' => ['string', 'size:2'],
            'min_kyc_level' => ['nullable', 'integer', 'min:0', 'max:3'],
            'min_funding_amount' => ['nullable', 'numeric', 'min:0'],
            'max_funding_amount' => ['nullable', 'numeric', 'min:0'],
            'daily_limit' => ['nullable', 'numeric', 'min:0'],
            'monthly_limit' => ['nullable', 'numeric', 'min:0'],
            'automated_funding' => ['nullable', 'boolean'],
            'manual_funding' => ['nullable', 'boolean'],
            'provider_code' => ['nullable', 'string', 'max:60'],
            'processing_time_estimate' => ['nullable', 'string', 'max:60'],
            'customer_instructions' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
            'sort' => ['nullable', 'integer'],
        ]);

        if (! $wallet) {
            $data['sort'] = $data['sort'] ?? (ChinaWalletType::max('sort') + 1);
        }

        return $data;
    }
}
