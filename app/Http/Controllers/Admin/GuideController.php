<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GuideDifficulty;
use App\Http\Controllers\Controller;
use App\Models\Guide;
use App\Services\Admin\GuideAdminService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GuideController extends Controller
{
    /**
     * The full category taxonomy already used by the dashboard "Learning
     * Center" (LearningController::sections()) — previously the admin form
     * only offered 8 of these 17, so guides could never be created in
     * "tmall", "jd", "xiaohongshu", "weidian", "aliexpress", "dhgate",
     * "orientation", "wechatpay" or "glossary" even though the dashboard
     * already groups guides by them.
     */
    public const CATEGORIES = [
        'orientation', '1688', 'taobao', 'tmall', 'pinduoduo', 'jd', 'xiaohongshu',
        'weidian', 'aliexpress', 'dhgate', 'alipay', 'wechatpay', 'shipping',
        'customs', 'mistakes', 'glossary', 'general',
    ];

    public function __construct(private GuideAdminService $service) {}

    public function index(): View
    {
        return view('admin.guides.index', ['guides' => Guide::withTrashed()->orderBy('sort')->latest()->get()]);
    }

    public function create(): View
    {
        return view('admin.guides.form', ['guide' => new Guide, 'categories' => self::CATEGORIES, 'difficulties' => GuideDifficulty::cases()]);
    }

    public function store(Request $request)
    {
        $this->service->create($this->validated($request), $request->user());

        return redirect()->route('admin.guides.index')->with('success', 'Guide created.');
    }

    public function edit(Guide $guide): View
    {
        return view('admin.guides.form', [
            'guide' => $guide,
            'categories' => self::CATEGORIES,
            'difficulties' => GuideDifficulty::cases(),
            'feedback' => $this->service->feedbackSummary($guide),
        ]);
    }

    public function update(Request $request, Guide $guide)
    {
        $this->service->update($guide, $this->validated($request, $guide), $request->user());

        return redirect()->route('admin.guides.index')->with('success', 'Guide updated.');
    }

    /** Archive-not-delete: soft-deletes so links to this guide (support chats, other guides) don't 404. */
    public function destroy(Request $request, Guide $guide)
    {
        $this->service->archive($guide, $request->user());

        return back()->with('success', 'Guide archived.');
    }

    public function restore(Request $request, Guide $guide)
    {
        $this->service->restore($guide, $request->user());

        return back()->with('success', 'Guide restored.');
    }

    private function validated(Request $request, ?Guide $guide = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'category' => ['required', 'string', 'in:'.implode(',', self::CATEGORIES)],
            'difficulty' => ['required', 'in:'.implode(',', array_column(GuideDifficulty::cases(), 'value'))],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'video_url' => ['nullable', 'url'],
            'cover' => ['nullable', 'image', 'max:3072'],
            'steps' => ['nullable', 'array'],
            'faqs' => ['nullable', 'array'],
            'cta_label' => ['nullable', 'string', 'max:60'],
            'cta_url' => ['nullable', 'string', 'max:200'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:300'],
            'read_minutes' => ['nullable', 'integer', 'min:1'],
            'is_published' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'integer'],
        ]);

        $data['is_published'] = $request->boolean('is_published');
        $data['is_featured'] = $request->boolean('is_featured');

        // Clean repeatable rows (drop empties).
        $data['steps'] = collect($request->input('steps', []))->filter(fn ($s) => ! empty($s['title']) || ! empty($s['body']))->values()->all();
        $data['faqs'] = collect($request->input('faqs', []))->filter(fn ($f) => ! empty($f['q']) || ! empty($f['a']))->values()->all();

        if ($request->hasFile('cover')) {
            $data['cover_image_path'] = $request->file('cover')->store('guides', 'public');
        }
        unset($data['cover']);

        return $data;
    }
}
