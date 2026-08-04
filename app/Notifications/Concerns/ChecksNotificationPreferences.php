<?php

namespace App\Notifications\Concerns;

/**
 * Shared preference gating for the Settings > Notifications toggles.
 * "Email notifications" and "Web Push" are master switches; a category key
 * (e.g. notify_order_updates) further narrows a specific notification type.
 * The in-app database record is never gated here — a user should always be
 * able to see what happened on their own account from the notification bell.
 */
trait ChecksNotificationPreferences
{
    protected function wantsMail(object $notifiable, ?string $category = null): bool
    {
        $prefs = $notifiable->preferences ?? [];

        if (! ($prefs['notify_email'] ?? true)) {
            return false;
        }

        return $category === null || ($prefs[$category] ?? true);
    }

    protected function wantsBroadcast(object $notifiable, ?string $category = null): bool
    {
        $prefs = $notifiable->preferences ?? [];

        if (! ($prefs['notify_web_push'] ?? true)) {
            return false;
        }

        return $category === null || ($prefs[$category] ?? true);
    }
}
