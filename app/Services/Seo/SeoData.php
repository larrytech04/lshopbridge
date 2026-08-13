<?php

namespace App\Services\Seo;

/**
 * Everything a page needs to render its <head> SEO tags and JSON-LD, in one
 * immutable value object — built by SeoService, consumed by
 * <x-seo-head :seo="$seo" />. Pure data: no settings/DB/request access here,
 * that all happens before construction so this stays trivially testable.
 */
final readonly class SeoData
{
    /**
     * @param  array<int, array<string, mixed>>  $structuredData  Each entry is one complete JSON-LD block (its own @context/@type).
     * @param  array<int, array{name: string, url: string}>  $breadcrumbs
     * @param  array<int, array{hreflang: string, href: string}>  $alternates
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $canonical,
        public string $robots = 'index,follow',
        public ?string $ogTitle = null,
        public ?string $ogDescription = null,
        public ?string $ogImage = null,
        public string $ogType = 'website',
        public string $twitterCard = 'summary_large_image',
        public ?string $twitterSite = null,
        public array $structuredData = [],
        public array $breadcrumbs = [],
        public array $alternates = [],
        public ?string $publishedAt = null,
        public ?string $modifiedAt = null,
        public string $themeColor = '#840A20',
    ) {}

    public function effectiveOgTitle(): string
    {
        return $this->ogTitle ?: $this->title;
    }

    public function effectiveOgDescription(): string
    {
        return $this->ogDescription ?: $this->description;
    }

    /** A new instance with only the given fields replaced — used by SeoService
     *  to layer stored SeoMetadata and then controller overrides on top of
     *  site defaults without a giant constructor call at each layer. */
    public function with(array $changes): self
    {
        $props = get_object_vars($this);

        foreach ($changes as $key => $value) {
            if ($value !== null && array_key_exists($key, $props)) {
                $props[$key] = $value;
            }
        }

        return new self(...$props);
    }
}
