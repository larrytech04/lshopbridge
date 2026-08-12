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

    /** Real OS-level push/banner notifications (see WebPushChannel) — same
     *  "Web Push" preference as the live in-tab broadcast, since from the
     *  user's side both are just "push me updates", only the delivery
     *  mechanism differs. Safe to gate every notification on this: the
     *  channel itself is a no-op for anyone with zero active subscriptions
     *  (never subscribed, or VAPID isn't configured on this environment). */
    protected function wantsPush(object $notifiable, ?string $category = null): bool
    {
        return $this->wantsBroadcast($notifiable, $category);
    }
}
