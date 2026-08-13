<?php

namespace App\Services\Seo;

/**
 * Turns already-vetted, real data into valid JSON-LD arrays — one method
 * per schema.org type actually used in this app (see brief section 12).
 * Deliberately decoupled from Eloquent/Request: every method takes plain
 * arrays, so a caller can only pass what it actually has, and this class
 * stays trivially unit-testable. This builder never invents a value; a
 * missing optional field is just omitted from the block, not defaulted to
 * something that looks real.
 */
class StructuredDataBuilder
{
    /** @param  array{name: string, url: string, logo?: ?string, description?: ?string, sameAs?: array<int, string>, contactEmail?: ?string, contactPhone?: ?string}  $data */
    public function organization(array $data): array
    {
        $block = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            '@id' => $data['url'].'#organization',
            'name' => $data['name'],
            'url' => $data['url'],
        ];

        if (! empty($data['logo'])) {
            $block['logo'] = $data['logo'];
        }
        if (! empty($data['description'])) {
            $block['description'] = $data['description'];
        }
        if (! empty($data['sameAs'])) {
            $block['sameAs'] = array_values($data['sameAs']);
        }
        if (! empty($data['contactEmail']) || ! empty($data['contactPhone'])) {
            $contact = ['@type' => 'ContactPoint', 'contactType' => 'customer support'];
            if (! empty($data['contactEmail'])) {
                $contact['email'] = $data['contactEmail'];
            }
            if (! empty($data['contactPhone'])) {
                $contact['telephone'] = $data['contactPhone'];
            }
            $block['contactPoint'] = [$contact];
        }

        return $block;
    }

    /** @param  array{name: string, url: string}  $data */
    public function website(array $data): array
    {
        // Deliberately NO potentialAction/SearchAction here — the brief
        // explicitly forbids a fake SearchAction. Only add one later if a
        // real site-search endpoint exists and this is updated on purpose.
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            '@id' => $data['url'].'#website',
            'name' => $data['name'],
            'url' => $data['url'],
        ];
    }

    /** @param  array<int, array{name: string, url: string}>  $items */
    public function breadcrumbList(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $this->positioned($items, fn ($item, $position) => [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $item['name'],
                'item' => $item['url'],
            ]),
        ];
    }

    /** @param  array{url: string, name: string, description?: ?string}  $data */
    public function webPage(array $data): array
    {
        $block = [
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            '@id' => $data['url'].'#webpage',
            'url' => $data['url'],
            'name' => $data['name'],
        ];

        if (! empty($data['description'])) {
            $block['description'] = $data['description'];
        }

        return $block;
    }

    /**
     * @param  array{
     *     name: string, url: string, description?: ?string, image?: string|array<int, string>|null,
     *     sku?: ?string, brand?: ?string,
     *     offers?: array{price: float|string, currency: string, availability?: string, url?: string}|null,
     *     aggregateRating?: array|null,
     * }  $data
     *
     * `offers.availability` must be a real https://schema.org/ItemAvailability
     * value (InStock, OutOfStock, PreOrder, ...) reflecting the live
     * product/plan status — never defaulted to InStock, see brief section 8.
     * `aggregateRating` is only emitted if the caller explicitly supplies
     * one built from genuine, moderated reviews (see brief section 12).
     */
    public function product(array $data): array
    {
        $block = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $data['url'].'#product',
            'name' => $data['name'],
            'url' => $data['url'],
        ];

        if (! empty($data['description'])) {
            $block['description'] = $data['description'];
        }
        if (! empty($data['image'])) {
            $block['image'] = $data['image'];
        }
        if (! empty($data['sku'])) {
            $block['sku'] = $data['sku'];
        }
        if (! empty($data['brand'])) {
            $block['brand'] = ['@type' => 'Brand', 'name' => $data['brand']];
        }
        if (! empty($data['offers'])) {
            $offer = $data['offers'];
            $block['offers'] = [
                '@type' => 'Offer',
                'price' => (string) $offer['price'],
                'priceCurrency' => $offer['currency'],
                'availability' => $offer['availability'] ?? 'https://schema.org/OutOfStock',
                'url' => $offer['url'] ?? $data['url'],
            ];
        }
        if (! empty($data['aggregateRating'])) {
            $block['aggregateRating'] = $data['aggregateRating'];
        }

        return $block;
    }

    /**
     * @param  array{
     *     headline: string, url: string, description?: ?string, image?: ?string,
     *     datePublished?: ?string, dateModified?: ?string, authorName?: ?string,
     *     authorType?: 'Person'|'Organization', publisherName?: ?string,
     *     publisherLogo?: ?string, type?: 'Article'|'BlogPosting',
     * }  $data
     */
    public function article(array $data): array
    {
        $block = [
            '@context' => 'https://schema.org',
            '@type' => $data['type'] ?? 'Article',
            '@id' => $data['url'].'#article',
            'headline' => $data['headline'],
            'url' => $data['url'],
            'mainEntityOfPage' => $data['url'],
        ];

        if (! empty($data['description'])) {
            $block['description'] = $data['description'];
        }
        if (! empty($data['image'])) {
            $block['image'] = $data['image'];
        }
        if (! empty($data['datePublished'])) {
            $block['datePublished'] = $data['datePublished'];
        }
        if (! empty($data['dateModified'])) {
            $block['dateModified'] = $data['dateModified'];
        }
        if (! empty($data['authorName'])) {
            // 'Organization' when the byline is the platform itself, not a
            // named individual — never fabricate a person (see brief
            // section 10/12); schema.org's Article.author accepts either.
            $block['author'] = ['@type' => $data['authorType'] ?? 'Person', 'name' => $data['authorName']];
        }
        if (! empty($data['publisherName'])) {
            $publisher = ['@type' => 'Organization', 'name' => $data['publisherName']];
            if (! empty($data['publisherLogo'])) {
                $publisher['logo'] = ['@type' => 'ImageObject', 'url' => $data['publisherLogo']];
            }
            $block['publisher'] = $publisher;
        }

        return $block;
    }

    /** @param  array<int, array{question: string, answer: string}>  $qa */
    public function faqPage(array $qa): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($item) => [
                '@type' => 'Question',
                'name' => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $item['answer'],
                ],
            ], array_values($qa)),
        ];
    }

    /** @param  array<int, array{name: string, url: string}>  $items */
    public function itemList(array $items, string $type = 'ItemList'): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => $type,
            'itemListElement' => $this->positioned($items, fn ($item, $position) => [
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $item['name'],
                'url' => $item['url'],
            ]),
        ];
    }

    /** Full, ready-to-embed <script> tag for one JSON-LD block — the ONLY
     *  place this app should json_encode() structured data, so the safe
     *  escaping flags are never forgotten at a call site. HEX_TAG/AMP/APOS/
     *  QUOT let this be embedded directly in HTML without a second escaping
     *  pass, even if a value happens to contain "</script>" or similar. */
    public static function scriptTag(array $block): string
    {
        $json = json_encode(
            $block,
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        // json_encode() only returns false on failure (e.g. malformed UTF-8
        // input) — never emit a broken or empty <script> tag in that case.
        if ($json === false) {
            return '';
        }

        return '<script type="application/ld+json">'.$json.'</script>';
    }

    /** @param  array<int, mixed>  $items */
    private function positioned(array $items, callable $map): array
    {
        $position = 0;

        return array_map(function ($item) use ($map, &$position) {
            $position++;

            return $map($item, $position);
        }, array_values($items));
    }
}
