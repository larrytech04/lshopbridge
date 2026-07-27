<?php

namespace App\Services\Content;

use DOMDocument;
use DOMXPath;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\MarkdownConverter;

/**
 * Renders legal/policy page bodies (admin-authored Markdown) to HTML for the
 * Legal Center. Raw HTML in the source is always escaped, never executed —
 * there is no "trusted admin HTML" mode, since page bodies are exactly what
 * the Legal Center spec forbids executing (script tags, iframes, etc). This
 * also gives every ## / ### heading a stable id, which both the sidebar
 * table of contents and deep-link anchors (/legal/privacy-policy#section)
 * key off.
 */
class LegalContentRenderer
{
    private MarkdownConverter $converter;

    public function __construct()
    {
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'heading_permalink' => [
                'html_class' => 'legal-heading-anchor',
                'id_prefix' => '',
                'apply_id_to_heading' => true,
                'insert' => 'after',
                'min_heading_level' => 2,
                'max_heading_level' => 3,
                'title' => 'Copy link to this section',
                'symbol' => '#',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new HeadingPermalinkExtension);
        $environment->addExtension(new TableExtension);

        $this->converter = new MarkdownConverter($environment);
    }

    public function toHtml(?string $markdown): string
    {
        if (! $markdown) {
            return '';
        }

        return (string) $this->converter->convert($this->substitutePlaceholders($markdown));
    }

    /** Token substitution only, no Markdown conversion — for plain-text fields like `excerpt`. */
    public function substitute(?string $text): string
    {
        return $text ? $this->substitutePlaceholders($text) : '';
    }

    /**
     * Policy bodies are plain Markdown text in the database — they can't run
     * Blade or PHP — so company/jurisdiction details that should come from
     * Settings (never hardcoded/invented, per the Legal Center spec) use a
     * simple {{token}} substitution instead. An unset value never renders
     * blank or is silently dropped: it becomes a clearly-bracketed
     * placeholder so an unreviewed policy can never look production-ready.
     */
    private function substitutePlaceholders(string $markdown): string
    {
        $tokens = [
            'company_legal_name' => setting('company_legal_name'),
            'company_trading_name' => setting('company_trading_name', setting('site_name', config('platform.name'))),
            'company_registration_number' => setting('company_registration_number'),
            'company_registered_address' => setting('company_registered_address'),
            'company_jurisdiction' => setting('company_jurisdiction'),
            'legal_email' => setting('legal_email', setting('support_email', config('platform.support_email'))),
            'privacy_email' => setting('privacy_email', setting('support_email', config('platform.support_email'))),
            'compliance_email' => setting('compliance_email', setting('support_email', config('platform.support_email'))),
            'support_email' => setting('support_email', config('platform.support_email')),
            'support_phone' => setting('support_phone'),
            'site_name' => setting('site_name', config('platform.name')),
        ];

        return preg_replace_callback('/\{\{\s*([a-z_]+)\s*\}\}/', function ($m) use ($tokens) {
            if (! array_key_exists($m[1], $tokens)) {
                return $m[0];
            }

            $value = trim((string) ($tokens[$m[1]] ?? ''));

            return $value !== '' ? $value : '['.str_replace('_', ' ', ucfirst($m[1])).' — pending legal/company review]';
        }, $markdown);
    }

    /**
     * Flat, document-ordered list of ['id', 'text', 'level'] for every ##/###
     * heading, used to render the sidebar table of contents independently of
     * the main content flow.
     */
    public function extractHeadings(?string $markdown): array
    {
        $html = $this->toHtml($markdown);
        if ($html === '') {
            return [];
        }

        $dom = new DOMDocument;
        libxml_use_internal_errors(true);
        $dom->loadHTML('<?xml encoding="utf-8"?><div>'.$html.'</div>');
        libxml_clear_errors();

        $headings = [];
        $xpath = new DOMXPath($dom);
        foreach ($xpath->query('//h2 | //h3') as $node) {
            // textContent includes the trailing "#" permalink symbol appended
            // as a child <a> (see heading_permalink config) — strip it back off.
            $text = trim(preg_replace('/\s+/', ' ', $node->textContent));
            $text = rtrim($text, '# ');

            $headings[] = [
                'id' => $node->getAttribute('id'),
                'text' => $text,
                'level' => (int) substr($node->nodeName, 1),
            ];
        }

        return $headings;
    }
}
