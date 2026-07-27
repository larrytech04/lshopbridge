<?php

namespace App\Services\Security;

use App\Services\Security\DTO\ContentRiskAssessment;
use Illuminate\Http\Request;

/**
 * Free-text content scoring shared by every form with a message/comment
 * field (contact, guest support, referral message, guide feedback). Rule
 * definitions are intentionally private — never exposed to visitors or
 * returned in any HTTP response, per the "don't expose detection logic"
 * requirement.
 */
class ContactFormRiskService
{
    private const SUSPICIOUS_TLDS = ['.xyz', '.top', '.click', '.loan', '.work', '.live', '.icu'];
    private const LINK_SHORTENERS = ['bit.ly', 'tinyurl.com', 'goo.gl', 't.co', 'is.gd', 'ow.ly'];

    private const SPAM_PHRASES = [
        'buy backlinks', 'seo services', 'click here now', 'make money fast', 'work from home opportunity',
        'weight loss pills', 'forex signals', 'crypto investment opportunity', 'guaranteed profit',
        'viagra', 'cialis', 'casino bonus', 'loan approved', 'increase your website traffic',
    ];

    private const BOT_USER_AGENT_PATTERNS = ['curl/', 'python-requests', 'scrapy', 'go-http-client', 'wget/', 'headlesschrome', 'phantomjs'];

    public function evaluate(array $fields, Request $request): ContentRiskAssessment
    {
        $text = implode(' ', array_filter([$fields['message'] ?? null, $fields['subject'] ?? null]));
        $score = 0;
        $rules = [];

        $urls = $this->extractUrls($text);
        if (count($urls) >= 3) {
            $score += 25;
            $rules[] = 'excessive_links';
        }

        if ($this->hasSuspiciousDomain($urls)) {
            $score += 30;
            $rules[] = 'suspicious_link_domain';
        }

        if ($this->hasExcessiveHtml($text)) {
            $score += 20;
            $rules[] = 'html_in_plain_text';
        }

        if (preg_match('/(.)\1{7,}/u', $text)) {
            $score += 15;
            $rules[] = 'repeated_characters';
        }

        if ($this->matchesSpamPhrase($text)) {
            $score += 35;
            $rules[] = 'spam_keyword';
        }

        $userAgent = (string) $request->userAgent();
        if ($userAgent === '') {
            $score += 25;
            $rules[] = 'missing_user_agent';
        } elseif ($this->looksLikeBotUserAgent($userAgent)) {
            $score += 20;
            $rules[] = 'abnormal_user_agent';
        }

        $score = min($score, 100);

        return new ContentRiskAssessment($score, $this->levelFor($score), $rules);
    }

    private function extractUrls(string $text): array
    {
        preg_match_all('/\bhttps?:\/\/[^\s]+|\bwww\.[^\s]+/i', $text, $matches);

        return $matches[0] ?? [];
    }

    private function hasSuspiciousDomain(array $urls): bool
    {
        foreach ($urls as $url) {
            $host = strtolower((string) parse_url(str_starts_with($url, 'http') ? $url : 'http://'.$url, PHP_URL_HOST));

            foreach (self::LINK_SHORTENERS as $shortener) {
                if ($host === $shortener || str_ends_with($host, '.'.$shortener)) {
                    return true;
                }
            }

            foreach (self::SUSPICIOUS_TLDS as $tld) {
                if (str_ends_with($host, $tld)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasExcessiveHtml(string $text): bool
    {
        return strlen($text) > 20 && strlen(strip_tags($text)) < strlen($text) * 0.85;
    }

    private function matchesSpamPhrase(string $text): bool
    {
        $lower = strtolower($text);
        foreach (self::SPAM_PHRASES as $phrase) {
            if (str_contains($lower, $phrase)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeBotUserAgent(string $userAgent): bool
    {
        $lower = strtolower($userAgent);
        foreach (self::BOT_USER_AGENT_PATTERNS as $pattern) {
            if (str_contains($lower, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function levelFor(int $score): string
    {
        return match (true) {
            $score >= 80 => 'critical',
            $score >= 50 => 'high',
            $score >= 20 => 'medium',
            default => 'low',
        };
    }
}
