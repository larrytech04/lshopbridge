<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Faq;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __construct(private AuditLogger $audit) {}

    public function index(): View
    {
        return view('admin.faqs.index', ['faqs' => Faq::orderBy('sort')->get()]);
    }

    public function store(Request $request)
    {
        $faq = Faq::create($this->validated($request));
        $this->audit->log('admin.faq.created', "Created FAQ: {$faq->question}", $faq, [], $request->user()->id);

        return back()->with('success', 'FAQ added.');
    }

    public function update(Request $request, Faq $faq)
    {
        $faq->update($this->validated($request));
        $this->audit->log('admin.faq.updated', "Updated FAQ: {$faq->question}", $faq, [], $request->user()->id);

        return back()->with('success', 'FAQ updated.');
    }

    public function destroy(Request $request, Faq $faq)
    {
        $this->audit->log('admin.faq.deleted', "Removed FAQ: {$faq->question}", $faq, [], $request->user()->id);
        $faq->delete();

        return back()->with('success', 'FAQ removed.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['required', 'string', 'max:3000'],
            'category' => ['required', 'string', 'max:40'],
            'is_published' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
        ]);
        $data['is_published'] = $request->boolean('is_published');

        return $data;
    }
}
