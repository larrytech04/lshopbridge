<?php

namespace App\Http\Controllers\Admin;

use App\Enums\KycDecisionType;
use App\Http\Controllers\Controller;
use App\Models\KycDecisionTemplate;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class KycDecisionTemplateController extends Controller
{
    public function index()
    {
        return response()->json(
            KycDecisionTemplate::active()->orderBy('name')->get()->groupBy(fn ($t) => $t->decision_type->value)
        );
    }

    public function store(Request $request, AuditLogger $audit)
    {
        $data = $request->validate([
            'decision_type' => ['required', Rule::enum(KycDecisionType::class)],
            'name' => ['required', 'string', 'max:120'],
            'internal_reason' => ['nullable', 'string', 'max:2000'],
            'customer_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $template = KycDecisionTemplate::create($data + ['is_active' => true]);
        $audit->log('admin.kyc.template_created', "Created decision template \"{$template->name}\"", $template);

        return back()->with('success', 'Template created.');
    }

    public function update(Request $request, KycDecisionTemplate $template, AuditLogger $audit)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'internal_reason' => ['nullable', 'string', 'max:2000'],
            'customer_message' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $template->update($data);
        $audit->log('admin.kyc.template_updated', "Updated decision template \"{$template->name}\"", $template);

        return back()->with('success', 'Template updated.');
    }

    public function destroy(KycDecisionTemplate $template, AuditLogger $audit)
    {
        $template->update(['is_active' => false]);
        $audit->log('admin.kyc.template_deactivated', "Deactivated decision template \"{$template->name}\"", $template);

        return back()->with('success', 'Template removed.');
    }
}
