<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Agent;
use App\Models\Guide;
use App\Models\Page;
use App\Models\ShopCategory;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * A read-only sweep across every model with its own SEO title/description
 * (native columns on Page/Guide/ShopCategory, the seo_metadata table for
 * Agent) so an editor can see, in one place, what's missing or duplicated
 * without opening each record individually. Nothing here is computed from
 * a third-party crawl or invented metric, every figure is derived directly
 * from what's actually stored and what the public page actually renders
 * (see SeoService's docblock for the two storage paths this mirrors).
 *
 * Deliberately excludes ShopProduct (not yet wired into a public SEO
 * render, see SeoMetadataController's docblock) and Country (computed
 * title/description only, no admin-editable title/description to review).
 */
class SeoContentQualityController extends Controller
{
    private const TITLE_MIN = 30;

    private const TITLE_MAX = 65;

    private const DESCRIPTION_MIN = 50;

    private const DESCRIPTION_MAX = 160;

    public function index(): View
    {
        $rows = collect()
            ->merge($this->pageRows())
            ->merge($this->guideRows())
            ->merge($this->shopCategoryRows())
            ->merge($this->agentRows());

        $rows = $this->flagDuplicates($rows, 'title', 'duplicate_title');
        $rows = $this->flagDuplicates($rows, 'description', 'duplicate_description');

        $summary = [
            'total' => $rows->count(),
            'missing_title' => $rows->where('missing_title', true)->count(),
            'missing_description' => $rows->where('missing_description', true)->count(),
            'duplicate_title' => $rows->where('duplicate_title', true)->count(),
            'duplicate_description' => $rows->where('duplicate_description', true)->count(),
            'length_warning' => $rows->where('length_warning', true)->count(),
            'noindexed' => $rows->where('robots_status', 'noindex')->count(),
            'never_reviewed' => $rows->where('reviewed_label', 'Not tracked')->count()
                + $rows->whereNull('last_reviewed_at')->where('reviewed_label', '!=', 'Not tracked')->count(),
        ];

        return view('admin.seo.content-quality', [
            'rows' => $rows->sortByDesc(fn ($r) => count($r['warnings']))->values(),
            'summary' => $summary,
        ]);
    }

    private function pageRows(): Collection
    {
        return Page::published()->orderBy('title')->get()->map(function (Page $page) {
            $title = $page->meta_title ?: $page->title;
            $description = $page->meta_description;

            return $this->row(
                type: 'Legal / static page',
                label: $page->title,
                editUrl: route('admin.pages.edit', $page),
                title: $title,
                description: $description,
                robotsStatus: 'default',
                sitemapIncluded: true,
                lastReviewedAt: $page->last_reviewed_at,
                reviewerName: null,
                reviewedLabel: $page->last_reviewed_at ? null : 'Never reviewed',
            );
        });
    }

    private function guideRows(): Collection
    {
        return Guide::published()->orderBy('title')->get()->map(function (Guide $guide) {
            $title = $guide->meta_title ?: $guide->title;
            $description = $guide->meta_description;

            return $this->row(
                type: 'Learning Center guide',
                label: $guide->title,
                editUrl: route('admin.guides.edit', $guide),
                title: $title,
                description: $description,
                robotsStatus: 'default',
                sitemapIncluded: true,
                lastReviewedAt: null,
                reviewerName: null,
                reviewedLabel: 'Not tracked',
            );
        });
    }

    private function shopCategoryRows(): Collection
    {
        return ShopCategory::active()->orderBy('name')->get()->map(function (ShopCategory $category) {
            $title = $category->seo_title ?: ($category->name.', Digital Shop');
            $description = $category->meta_description;

            return $this->row(
                type: 'Shop category',
                label: $category->name,
                editUrl: route('admin.shop.categories.index').'#category-'.$category->id,
                title: $title,
                description: $description,
                robotsStatus: 'default',
                sitemapIncluded: true,
                lastReviewedAt: null,
                reviewerName: null,
                reviewedLabel: 'Not tracked',
            );
        });
    }

    private function agentRows(): Collection
    {
        return Agent::query()->approved()->with('seoMetadata.reviewer')->orderBy('business_name')->get()->map(function (Agent $agent) {
            $meta = $agent->seoMetadata;
            $title = $meta?->meta_title ?: $agent->business_name.' · Shipping agent';
            $description = $meta?->meta_description;

            return $this->row(
                type: 'Shipping agent',
                label: $agent->business_name,
                editUrl: route('admin.agents.show', $agent).'#seo',
                title: $title,
                description: $description,
                robotsStatus: $meta?->robots && str_starts_with($meta->robots, 'noindex') ? 'noindex' : 'default',
                sitemapIncluded: $meta?->sitemap_include ?? true,
                lastReviewedAt: $meta?->last_seo_review_at,
                reviewerName: $meta?->reviewer?->name,
                reviewedLabel: $meta ? null : 'Never reviewed',
            );
        });
    }

    /**
     * @return array{type: string, label: string, edit_url: string, title: string,
     *     description: ?string, title_length: int, description_length: int,
     *     missing_title: bool, missing_description: bool, length_warning: bool,
     *     robots_status: string, sitemap_included: bool, last_reviewed_at: mixed,
     *     reviewer_name: ?string, reviewed_label: ?string, warnings: array<int, string>}
     */
    private function row(
        string $type,
        string $label,
        string $editUrl,
        string $title,
        ?string $description,
        string $robotsStatus,
        bool $sitemapIncluded,
        mixed $lastReviewedAt,
        ?string $reviewerName,
        ?string $reviewedLabel,
    ): array {
        $titleLength = mb_strlen($title);
        $descriptionLength = $description ? mb_strlen($description) : 0;
        $missingDescription = blank($description);
        $lengthWarning = $titleLength < self::TITLE_MIN || $titleLength > self::TITLE_MAX
            || (! $missingDescription && ($descriptionLength < self::DESCRIPTION_MIN || $descriptionLength > self::DESCRIPTION_MAX));

        $warnings = array_values(array_filter([
            $missingDescription ? 'Missing meta description' : null,
            $titleLength > self::TITLE_MAX ? 'Title longer than '.self::TITLE_MAX.' characters, may be truncated in search results' : null,
            $titleLength < self::TITLE_MIN ? 'Title shorter than '.self::TITLE_MIN.' characters' : null,
            (! $missingDescription && $descriptionLength > self::DESCRIPTION_MAX) ? 'Description longer than '.self::DESCRIPTION_MAX.' characters, may be truncated in search results' : null,
            (! $missingDescription && $descriptionLength < self::DESCRIPTION_MIN) ? 'Description shorter than '.self::DESCRIPTION_MIN.' characters' : null,
            $robotsStatus === 'noindex' ? 'Marked noindex' : null,
            ! $sitemapIncluded ? 'Excluded from sitemap' : null,
        ]));

        return [
            'type' => $type,
            'label' => $label,
            'edit_url' => $editUrl,
            'title' => $title,
            'description' => $description,
            'title_length' => $titleLength,
            'description_length' => $descriptionLength,
            'missing_title' => false, // every row always resolves to at least a generated fallback title
            'missing_description' => $missingDescription,
            'length_warning' => $lengthWarning,
            'robots_status' => $robotsStatus,
            'sitemap_included' => $sitemapIncluded,
            'last_reviewed_at' => $lastReviewedAt,
            'reviewer_name' => $reviewerName,
            'reviewed_label' => $reviewedLabel,
            'duplicate_title' => false,
            'duplicate_description' => false,
            'warnings' => $warnings,
        ];
    }

    /** Marks every row sharing a non-blank title/description with at least one other row. */
    private function flagDuplicates(Collection $rows, string $field, string $flagKey): Collection
    {
        $counts = $rows->countBy(fn ($r) => mb_strtolower(trim($r[$field] ?? '')));

        return $rows->map(function ($row) use ($field, $flagKey, $counts) {
            $value = mb_strtolower(trim($row[$field] ?? ''));
            $isDuplicate = $value !== '' && $counts->get($value, 0) > 1;
            $row[$flagKey] = $isDuplicate;
            if ($isDuplicate) {
                $row['warnings'][] = $field === 'title' ? 'Duplicate title (shared with another page)' : 'Duplicate meta description (shared with another page)';
            }

            return $row;
        });
    }
}
