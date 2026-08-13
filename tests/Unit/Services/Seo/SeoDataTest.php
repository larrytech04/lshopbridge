<?php

namespace Tests\Unit\Services\Seo;

use App\Services\Seo\SeoData;
use PHPUnit\Framework\TestCase;

class SeoDataTest extends TestCase
{
    private function make(array $overrides = []): SeoData
    {
        return new SeoData(
            title: $overrides['title'] ?? 'Page Title',
            description: $overrides['description'] ?? 'Page description.',
            canonical: $overrides['canonical'] ?? 'https://example.com/page',
        );
    }

    public function test_effective_og_title_falls_back_to_title_when_unset(): void
    {
        $seo = $this->make();

        $this->assertSame('Page Title', $seo->effectiveOgTitle());
    }

    public function test_effective_og_title_prefers_its_own_value_when_set(): void
    {
        $seo = new SeoData(
            title: 'Page Title',
            description: 'Desc',
            canonical: 'https://example.com/',
            ogTitle: 'Custom OG Title',
        );

        $this->assertSame('Custom OG Title', $seo->effectiveOgTitle());
    }

    public function test_effective_og_description_falls_back_to_description_when_unset(): void
    {
        $seo = $this->make();

        $this->assertSame('Page description.', $seo->effectiveOgDescription());
    }

    public function test_with_overrides_only_the_given_fields(): void
    {
        $seo = $this->make();

        $updated = $seo->with(['title' => 'New Title']);

        $this->assertSame('New Title', $updated->title);
        $this->assertSame($seo->description, $updated->description);
        $this->assertSame($seo->canonical, $updated->canonical);
    }

    public function test_with_ignores_null_values_and_keeps_the_original(): void
    {
        $seo = $this->make(['title' => 'Original Title']);

        $updated = $seo->with(['title' => null, 'description' => 'New description.']);

        $this->assertSame('Original Title', $updated->title);
        $this->assertSame('New description.', $updated->description);
    }

    public function test_with_applies_an_explicit_empty_array_as_a_real_override(): void
    {
        $seo = new SeoData(
            title: 'T',
            description: 'D',
            canonical: 'https://example.com/',
            structuredData: [['@type' => 'Thing']],
        );

        $updated = $seo->with(['structuredData' => []]);

        $this->assertSame([], $updated->structuredData);
    }

    public function test_with_does_not_mutate_the_original_instance(): void
    {
        $seo = $this->make(['title' => 'Original']);

        $seo->with(['title' => 'Changed']);

        $this->assertSame('Original', $seo->title);
    }

    public function test_defaults_are_sane(): void
    {
        $seo = $this->make();

        $this->assertSame('index,follow', $seo->robots);
        $this->assertSame('website', $seo->ogType);
        $this->assertSame('summary_large_image', $seo->twitterCard);
        $this->assertSame([], $seo->structuredData);
        $this->assertSame([], $seo->breadcrumbs);
        $this->assertSame([], $seo->alternates);
    }
}
