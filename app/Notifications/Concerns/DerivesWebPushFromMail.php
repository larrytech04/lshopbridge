<?php

namespace App\Notifications\Concerns;

use Illuminate\Support\Str;
use NotificationChannels\WebPush\WebPushMessage;

/**
 * Every notification here already builds a MailMessage in toMail() — this
 * derives the OS-level push/banner notification from that SAME content
 * (subject, first line, action URL) instead of duplicating copy in a
 * fourteenth place. Reuse before rebuild, same as everywhere else in this
 * codebase (see e.g. ReauthService reusing the passkey Alpine factory).
 */
trait DerivesWebPushFromMail
{
    public function toWebPush(object $notifiable, object $notification): WebPushMessage
    {
        $mail = $this->toMail($notifiable);

        $title = $mail->subject ?: config('app.name');
        $body = $this->plainFirstLine($mail->introLines);
        $url = $mail->actionUrl ?: route('notifications.index');

        return (new WebPushMessage)
            ->title($title)
            ->icon(site_favicon())
            ->badge(site_favicon())
            ->body($body)
            ->data(['url' => $url, 'id' => $notification->id])
            ->options(['TTL' => 3600]);
    }

    /** Markdown lines render fine in an email but a push notification body
     *  is plain text — strip the syntax (##, **, __) rather than showing a
     *  literal "## HXKYMS" or "**bold**" on someone's lock screen. */
    private function plainFirstLine(array $introLines): string
    {
        $line = $introLines[0] ?? '';
        $line = preg_replace('/^#+\s*/', '', $line);
        $line = preg_replace('/(\*\*|__)(.*?)\1/', '$2', $line);

        return Str::limit(trim($line), 120);
    }
}
