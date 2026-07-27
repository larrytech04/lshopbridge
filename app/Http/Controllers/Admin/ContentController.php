<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProcessStep;
use App\Models\Testimonial;
use App\Services\Admin\ContentBlockAdminService;
use App\Services\Audit\AuditLogger;
use App\Services\Settings\SettingsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContentController extends Controller
{
    public function __construct(private ContentBlockAdminService $blocks) {}

    public function index(): View
    {
        return view('admin.content.index', [
            'groups' => config('cms.blocks', []),
            'testimonials' => Testimonial::orderBy('sort')->get(),
            'fundSteps' => ProcessStep::group('fund_step')->orderBy('sort')->get(),
            'shopSteps' => ProcessStep::group('shop_step')->orderBy('sort')->get(),
            'promises' => ProcessStep::group('promise')->orderBy('sort')->get(),
        ]);
    }

    public function update(Request $request, SettingsService $settings, AuditLogger $audit)
    {
        foreach (collect(config('cms.blocks', []))->flatten(1) as $block) {
            // $block = [key, label, input, default]
            $key = $block[0];
            if ($request->has($key)) {
                $settings->set($key, (string) $request->input($key, ''), 'string', 'cms');
            }
        }

        $audit->log('admin.content.updated', 'Site content updated', null, [], $request->user()->id);

        return back()->with('success', __('Content saved.'));
    }

    public function storeTestimonial(Request $request)
    {
        $this->blocks->createTestimonial($this->testimonialData($request), $request->user());

        return back()->with('success', 'Testimonial added.');
    }

    public function updateTestimonial(Request $request, Testimonial $testimonial)
    {
        $this->blocks->updateTestimonial($testimonial, $this->testimonialData($request), $request->user());

        return back()->with('success', 'Testimonial updated.');
    }

    public function destroyTestimonial(Request $request, Testimonial $testimonial)
    {
        $this->blocks->deleteTestimonial($testimonial, $request->user());

        return back()->with('success', 'Testimonial removed.');
    }

    public function storeStep(Request $request)
    {
        $this->blocks->createStep($this->stepData($request), $request->user());

        return back()->with('success', 'Step added.');
    }

    public function updateStep(Request $request, ProcessStep $step)
    {
        $this->blocks->updateStep($step, $this->stepData($request), $request->user());

        return back()->with('success', 'Step updated.');
    }

    public function destroyStep(Request $request, ProcessStep $step)
    {
        $this->blocks->deleteStep($step, $request->user());

        return back()->with('success', 'Step removed.');
    }

    private function testimonialData(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'source' => ['required', 'in:trustpilot,google,other'],
            'rating' => ['required', 'numeric', 'min:1', 'max:5'],
            'review_date' => ['nullable', 'date'],
            'verified' => ['nullable', 'boolean'],
            'text' => ['required', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
        ]);
        $data['verified'] = $request->boolean('verified');
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function stepData(Request $request): array
    {
        $data = $request->validate([
            'group' => ['required', 'in:fund_step,shop_step,promise'],
            'icon' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:500'],
            'is_active' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
        ]);
        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }
}
