<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use App\Notifications\ReauthCodeMail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Markdown;
use Tests\TestCase;

/**
 * Every notification's toMail() goes through the shared, published theme at
 * resources/views/vendor/mail — this locks in that it's actually the
 * branded LshopBridge theme and not Laravel's unbranded stock default.
 */
class MailThemeTest extends TestCase
{
    use RefreshDatabase;

    private function render(object $mailMessage): string
    {
        $markdown = app(Markdown::class)->theme('default');

        return $markdown->render($mailMessage->markdown ?: 'mail::message', $mailMessage->data());
    }

    public function test_the_reauth_code_email_uses_the_branded_theme_not_the_laravel_default(): void
    {
        $user = User::factory()->make(['name' => 'Super Admin', 'email' => 'admin@example.com']);
        $html = $this->render((new ReauthCodeMail('HXKYMS', 10))->toMail($user));

        // Our own logo, not Laravel's.
        $this->assertStringContainsString('shopbridge%20logo.png', $html);
        $this->assertStringNotContainsString('laravel.com', $html);

        // The brand color is actually present in the inlined output.
        $this->assertStringContainsString('#9c0f26', $html);

        // Dark-mode support survived the CSS inliner (it strips @media
        // blocks from the theme file, so this has to live in layout.blade.php
        // itself — see resources/views/vendor/mail/html/layout.blade.php).
        $this->assertStringContainsString('prefers-color-scheme: dark', $html);

        // The code renders as the highlighted h2 display, not a plain line.
        $this->assertStringContainsString('>HXKYMS</h2>', $html);
    }
}
