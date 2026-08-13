<?php

namespace Tests\Feature\Seo;

use App\Models\Agent;
use App\Models\Faq;
use App\Models\Guide;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Phase 4: breadcrumbs (visible + matching JSON-LD), Article schema on
 * guides, FAQPage schema on the FAQ page, and the homepage heading-hierarchy
 * fix. See SeoHeadRenderingTest for the generic <head>-tag coverage this
 * builds on.
 */
class Phase4ContentPagesTest extends TestCase
{
    use RefreshDatabase;

    private function blocksOf(string $type, string $content): array
    {
        preg_match_all('#<script type="application/ld\+json">(.*?)</script>#s', $content, $m);

        return array_values(array_filter(array_map(
            fn ($json) => json_decode($json, true),
            $m[1]
        ), fn ($block) => ($block['@type'] ?? null) === $type));
    }

    /** @return array<string, string> label => route name, for the pages that each get a
     *  simple static breadcrumb pushed from the view (see each blade file). */
    private function staticBreadcrumbPages(): array
    {
        return [
            'how it works' => 'how-it-works',
            'fund alipay' => 'public.fund',
            'payment methods' => 'public.payment-methods',
            'fees' => 'public.fees',
            'guides index' => 'guides.index',
            'agents index' => 'agents.index',
            'legal index' => 'legal.index',
        ];
    }

    public function test_every_static_page_has_exactly_one_valid_https_breadcrumb_list(): void
    {
        foreach ($this->staticBreadcrumbPages() as $label => $routeName) {
            $content = $this->get(route($routeName))->getContent();
            $blocks = $this->blocksOf('BreadcrumbList', $content);

            $this->assertCount(1, $blocks, "$label: expected exactly one BreadcrumbList block");
            $this->assertGreaterThanOrEqual(2, count($blocks[0]['itemListElement']), "$label: expected at least 2 breadcrumb items");
            $this->assertSame(1, $blocks[0]['itemListElement'][0]['position'], "$label: first item should be position 1");
            $this->assertSame('Home', $blocks[0]['itemListElement'][0]['name'], "$label: first item should be Home");

            foreach ($blocks[0]['itemListElement'] as $item) {
                $this->assertStringStartsWith('https://', $item['item'], "$label: breadcrumb URL must be https");
            }
        }
    }

    public function test_a_guide_page_has_a_matching_visible_and_schema_breadcrumb(): void
    {
        $guide = Guide::factory()->create(['is_published' => true, 'title' => 'Test Guide Title', 'slug' => 'test-guide']);

        $response = $this->get(route('guides.show', $guide));
        $content = $response->getContent();

        $response->assertSee('Test Guide Title', false);
        $response->assertSee('aria-label="Breadcrumb"', false);

        $block = $this->blocksOf('BreadcrumbList', $content)[0];
        $this->assertSame('Test Guide Title', end($block['itemListElement'])['name']);
    }

    public function test_a_guide_page_has_valid_article_schema_with_real_dates(): void
    {
        $guide = Guide::factory()->create(['is_published' => true, 'title' => 'Test Guide Title', 'slug' => 'test-guide-2']);

        $content = $this->get(route('guides.show', $guide))->getContent();
        $block = $this->blocksOf('Article', $content)[0];

        $this->assertSame('Test Guide Title', $block['headline']);
        $this->assertSame($guide->created_at->toAtomString(), $block['datePublished']);
        $this->assertSame($guide->updated_at->toAtomString(), $block['dateModified']);
    }

    public function test_a_guide_articles_author_is_the_real_organization_never_a_fabricated_person(): void
    {
        $guide = Guide::factory()->create(['is_published' => true, 'slug' => 'test-guide-3']);

        $content = $this->get(route('guides.show', $guide))->getContent();
        $block = $this->blocksOf('Article', $content)[0];

        $this->assertSame('Organization', $block['author']['@type']);
        $this->assertSame('LshopBridge', $block['author']['name']);
    }

    public function test_an_unpublished_guide_404s_and_carries_no_schema(): void
    {
        $guide = Guide::factory()->create(['is_published' => false, 'slug' => 'draft-guide']);

        $this->get(route('guides.show', $guide))->assertNotFound();
    }

    public function test_an_agent_page_has_a_matching_breadcrumb(): void
    {
        $agent = Agent::factory()->approved()->create(['business_name' => 'Test Shipping Co', 'slug' => 'test-shipping-co']);

        $content = $this->get(route('agents.show', $agent))->getContent();
        $block = $this->blocksOf('BreadcrumbList', $content)[0];

        $this->assertSame('Test Shipping Co', end($block['itemListElement'])['name']);
    }

    public function test_an_agent_page_never_carries_local_business_schema(): void
    {
        // Deliberate: the brief warns against LocalBusiness unless a page
        // genuinely represents a real public business location, which
        // isn't established data here (no public address field) — see
        // AgentDirectoryController's docblock reasoning.
        $agent = Agent::factory()->approved()->create();

        $content = $this->get(route('agents.show', $agent))->getContent();

        $this->assertEmpty($this->blocksOf('LocalBusiness', $content));
    }

    public function test_a_legal_page_has_a_three_level_breadcrumb(): void
    {
        $page = Page::factory()->create(['is_published' => true, 'type' => 'legal', 'category' => 'general', 'title' => 'Test Legal Policy', 'slug' => 'test-legal-policy']);

        $content = $this->get(route('legal.show', $page))->getContent();
        $block = $this->blocksOf('BreadcrumbList', $content)[0];

        $this->assertCount(3, $block['itemListElement']);
        $this->assertSame('Test Legal Policy', $block['itemListElement'][2]['name']);
    }

    public function test_faq_page_has_faq_schema_matching_real_published_faqs(): void
    {
        Faq::create(['question' => 'What is LshopBridge?', 'answer' => 'A funding and shopping platform.', 'is_published' => true]);
        Faq::create(['question' => 'Draft question', 'answer' => 'Draft answer', 'is_published' => false]);

        $content = $this->get(route('public.faqs'))->getContent();
        $blocks = $this->blocksOf('FAQPage', $content);

        $this->assertCount(1, $blocks);
        $this->assertCount(1, $blocks[0]['mainEntity']);
        $this->assertSame('What is LshopBridge?', $blocks[0]['mainEntity'][0]['name']);
    }

    public function test_faq_page_carries_no_fabricated_schema_when_there_are_no_published_faqs(): void
    {
        $content = $this->get(route('public.faqs'))->getContent();

        $this->assertEmpty($this->blocksOf('FAQPage', $content));
    }

    public function test_homepage_pillar_sections_are_h2_not_h3_no_heading_level_skipped_after_h1(): void
    {
        $content = $this->get(route('home'))->getContent();

        $this->assertStringContainsString('<h2', substr($content, strpos($content, 'Fund China wallets') - 200, 200));
        $this->assertStringNotContainsString('<h3 class="mt-1 text-xl font-extrabold text-strong sm:text-2xl">', $content);
    }
}
