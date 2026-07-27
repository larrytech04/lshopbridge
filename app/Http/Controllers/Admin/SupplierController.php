<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    public function index(): View
    {
        return view('admin.shop.suppliers', [
            'suppliers' => Supplier::withCount('products')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $data = $this->validated($request);
        $supplier = Supplier::create($data);
        $audit->log('supplier.created', "Created supplier {$supplier->name}", $supplier);

        return back()->with('success', 'Supplier added.');
    }

    public function update(Request $request, Supplier $supplier, AuditLogger $audit)
    {
        $supplier->update($this->validated($request, $supplier));
        $audit->log('supplier.updated', "Updated supplier {$supplier->name}", $supplier);

        return back()->with('success', 'Supplier updated.');
    }

    public function destroy(Supplier $supplier, AuditLogger $audit)
    {
        if ($supplier->products()->exists()) {
            $supplier->update(['is_active' => false]);
            $audit->log('supplier.deactivated', "Deactivated supplier {$supplier->name} (has products, not deleted)", $supplier);

            return back()->with('success', 'Supplier has assigned products, so it was deactivated instead of removed.');
        }

        $supplier->delete();
        $audit->log('supplier.deleted', "Deleted supplier {$supplier->name}");

        return back()->with('success', 'Supplier removed.');
    }

    private function validated(Request $request, ?Supplier $supplier = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'code' => ['required', 'string', 'max:60', Rule::unique('suppliers', 'code')->ignore($supplier?->id)],
            'contact_email' => ['nullable', 'email', 'max:160'],
            'contact_phone' => ['nullable', 'string', 'max:40'],
            'website' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }
}
