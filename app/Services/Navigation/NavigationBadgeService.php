<?php

namespace App\Services\Navigation;

use App\Enums\KycStatus;
use App\Models\Dispute;
use App\Models\User;
use App\Notifications\SecurityAlert;

/**
 * Every sidebar/dock badge comes from here — one place, all computed from
 * real data, so nothing hardcoded like the old permanent "Popular"/"EARN"
 * pills. A badge with no real signal behind it (e.g. Shipping Requests
 * before that feature existed) simply returns 0/false rather than a
 * decorative placeholder.
 */
class NavigationBadgeService
{
    public function forUser(User $user): array
    {
        return [
            'notifications' => $user->unreadNotifications()->count(),
            'verification_action_required' => $user->kyc_status !== KycStatus::Approved,
            'support_awaiting_you' => $this->supportAwaitingCustomer($user),
            // Only alerts flagged as possibly-not-you (new device/country login) —
            // not every unread security notification, otherwise the routine
            // confirmation from changing your own password never stops re-lighting
            // this banner every time you touch your own account settings.
            'security_alert' => $user->unreadNotifications()->where('type', SecurityAlert::class)->where('data->requires_review', true)->exists(),
            'referral_reward_available' => (int) $user->points > 0,
            'shipping_requests_new_update' => $this->shippingRequestsNeedingAttention($user),
        ];
    }

    /**
     * A dispute where staff has the most recent word and the ticket isn't
     * closed — there is no read/unread column on disputes, so this is the
     * honest proxy: "staff replied, conversation still open."
     */
    private function supportAwaitingCustomer(User $user): int
    {
        return Dispute::where('user_id', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->with(['messages' => fn ($q) => $q->latest()->limit(1)])
            ->get()
            ->filter(fn (Dispute $d) => $d->messages->first()?->is_staff === true)
            ->count();
    }

    private function shippingRequestsNeedingAttention(User $user): int
    {
        if (! class_exists(\App\Models\ShippingRequest::class)) {
            return 0;
        }

        return \App\Models\ShippingRequest::where('user_id', $user->id)
            ->whereNotNull('customer_viewed_at')
            ->whereColumn('updated_at', '>', 'customer_viewed_at')
            ->count();
    }
}
