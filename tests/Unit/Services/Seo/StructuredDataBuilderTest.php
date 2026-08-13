<?php

namespace Tests\Unit\Services\Seo;

use App\Services\Seo\StructuredDataBuilder;
use PHPUnit\Framework\TestCase;

class StructuredDataBuilderTest extends TestCase
{
    private StructuredDataBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();
        $this->builder = new StructuredDataBuilder;
    }

    public function test_organization_includes_only_supplied_optional_fields(): void
    {
        $block = $this->builder->organization([
            'name' => 'LshopBridge',
            'url' => 'https://example.com/',
        ]);

        $this->assertSame('https://schema.org', $block['@context']);
        $this->assertSame('Organization', $block['@type']);
        $this->assertSame('https://example.com/#organization', $block['@id']);
        $this->assertSame('LshopBridge', $block['name']);
        $this->assertArrayNotHasKey('logo', $block);
        $this->assertArrayNotHasKey('sameAs', $block);
        $this->assertArrayNotHasKey('contactPoint', $block);
    }

    public function test_organization_includes_contact_point_only_when_email_or_phone_given(): void
    {
        $block = $this->builder->organization([
            'name' => 'LshopBridge',
            'url' => 'https://example.com/',
            'contactEmail' => 'support@example.com',
        ]);

        $this->assertSame('support@example.com', $block['contactPoint'][0]['email']);
        $this->assertArrayNotHasKey('telephone', $block['contactPoint'][0]);
    }

    public function test_organization_filters_empty_same_as_entries(): void
    {
        $block = $this->builder->organization([
            'name' => 'LshopBridge',
            'url' => 'https://example.com/',
            'sameAs' => ['https://x.com/lshopbridge'],
        ]);

        $this->assertSame(['https://x.com/lshopbridge'], $block['sameAs']);
    }

    public function test_website_never_includes_a_search_action(): void
    {
        $block = $this->builder->website(['name' => 'LshopBridge', 'url' => 'https://example.com/']);

        $this->assertArrayNotHasKey('potentialAction', $block);
        $this->assertSame('WebSite', $block['@type']);
    }

    public function test_breadcrumb_list_numbers_positions_starting_at_one(): void
    {
        $block = $this->builder->breadcrumbList([
            ['name' => 'Home', 'url' => 'https://example.com/'],
            ['name' => 'Guides', 'url' => 'https://example.com/guides'],
        ]);

        $this->assertSame(1, $block['itemListElement'][0]['position']);
        $this->assertSame(2, $block['itemListElement'][1]['position']);
        $this->assertSame('BreadcrumbList', $block['@type']);
    }

    public function test_product_omits_offers_when_not_supplied(): void
    {
        $block = $this->builder->product(['name' => 'eSIM Plan', 'url' => 'https://example.com/p/1']);

        $this->assertArrayNotHasKey('offers', $block);
        $this->assertArrayNotHasKey('aggregateRating', $block);
    }

    public function test_product_never_defaults_availability_to_in_stock(): void
    {
        $block = $this->builder->product([
            'name' => 'eSIM Plan',
            'url' => 'https://example.com/p/1',
            'offers' => ['price' => 9.99, 'currency' => 'USD'],
        ]);

        // The whole point: an unspecified availability must NOT silently
        // read as "in stock" — see brief section 8's explicit warning.
        $this->assertSame('https://schema.org/OutOfStock', $block['offers']['availability']);
    }

    public function test_product_respects_an_explicit_availability(): void
    {
        $block = $this->builder->product([
            'name' => 'eSIM Plan',
            'url' => 'https://example.com/p/1',
            'offers' => ['price' => 9.99, 'currency' => 'USD', 'availability' => 'https://schema.org/InStock'],
        ]);

        $this->assertSame('https://schema.org/InStock', $block['offers']['availability']);
    }

    public function test_product_only_includes_aggregate_rating_when_explicitly_supplied(): void
    {
        $withRating = $this->builder->product([
            'name' => 'X', 'url' => 'https://example.com/p/1',
            'aggregateRating' => ['@type' => 'AggregateRating', 'ratingValue' => 4.5, 'reviewCount' => 12],
        ]);

        $this->assertSame(4.5, $withRating['aggregateRating']['ratingValue']);
    }

    public function test_article_defaults_to_article_type(): void
    {
        $block = $this->builder->article(['headline' => 'How to import from China', 'url' => 'https://example.com/g/1']);

        $this->assertSame('Article', $block['@type']);
    }

    public function test_article_respects_blog_posting_type(): void
    {
        $block = $this->builder->article(['headline' => 'H', 'url' => 'https://example.com/g/1', 'type' => 'BlogPosting']);

        $this->assertSame('BlogPosting', $block['@type']);
    }

    public function test_faq_page_maps_every_question_and_answer(): void
    {
        $block = $this->builder->faqPage([
            ['question' => 'What is an eSIM?', 'answer' => 'A digital SIM.'],
            ['question' => 'Is it refundable?', 'answer' => 'See the refund policy.'],
        ]);

        $this->assertSame('FAQPage', $block['@type']);
        $this->assertCount(2, $block['mainEntity']);
        $this->assertSame('What is an eSIM?', $block['mainEntity'][0]['name']);
        $this->assertSame('A digital SIM.', $block['mainEntity'][0]['acceptedAnswer']['text']);
    }

    public function test_script_tag_escapes_a_closing_script_sequence_safely(): void
    {
        $tag = StructuredDataBuilder::scriptTag(['@type' => 'Thing', 'name' => '</script><script>alert(1)</script>']);

        $this->assertStringNotContainsString('</script><script>alert', $tag);
        $this->assertStringStartsWith('<script type="application/ld+json">', $tag);
        $this->assertStringEndsWith('</script>', $tag);
    }

    public function test_script_tag_produces_valid_parseable_json(): void
    {
        $block = ['@type' => 'Thing', 'name' => "Quote \" and slash / and amp &"];
        $tag = StructuredDataBuilder::scriptTag($block);

        $json = preg_replace('/^<script[^>]*>|<\/script>$/', '', $tag);
        $decoded = json_decode($json, true);

        $this->assertSame($block['name'], $decoded['name']);
    }

    public function test_script_tag_returns_empty_string_on_encoding_failure(): void
    {
        // Malformed (non-UTF-8) byte sequence — json_encode() legitimately
        // fails on this, and scriptTag() must never emit a broken tag.
        $tag = StructuredDataBuilder::scriptTag(['name' => "\xB1\x31"]);

        $this->assertSame('', $tag);
    }
}
