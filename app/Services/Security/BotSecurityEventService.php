<?php

namespace App\Services\Security;

use App\Models\FormSecurityEvent;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\Cache;

/**
 * Records FormSecurityEvent rows and decides when a spike deserves an
 * immediate administrator alert. Alerts are grouped (one per event_type +
 * form_type per cooldown window) so a sustained attack sends one notification,
 * not one per request — the spec's "group repeated alerts" requirement.
 */
class BotSecurityEventService
{
    private const ALERT_COOLDOWN_MINUTES = 15;
    private const OUTAGE_ALERT_COOLDOWN_MINUTES = 30;

    public function __construct(
        private CriticalAlertService $alerts,
        private AuditLogger $audit,
    ) {}

    /** @param  array{triggered_rules?: array, ip_hash?: string, country?: string, user_agent?: string, payload_fingerprint?: string, note?: string, related_type?: string, related_id?: int}  $context */
    public function record(string $eventType, string $formType, string $riskLevel, string $actionTaken, array $context = []): FormSecurityEvent
    {
        $event = FormSecurityEvent::create([
            'event_type' => $eventType,
            'form_type' => $formType,
            'risk_level' => $riskLevel,
            'action_taken' => $actionTaken,
            'triggered_rules' => $context['triggered_rules'] ?? null,
            'ip_hash' => $context['ip_hash'] ?? null,
            'country' => $context['country'] ?? null,
            'user_agent' => $context['user_agent'] ?? null,
            'payload_fingerprint' => $context['payload_fingerprint'] ?? null,
            'note' => $context['note'] ?? null,
            'related_type' => $context['related_type'] ?? null,
            'related_id' => $context['related_id'] ?? null,
        ]);

        $this->maybeAlertOnSpike($event);

        return $event;
    }

    private function maybeAlertOnSpike(FormSecurityEvent $event): void
    {
        if (! in_array($event->risk_level, ['high', 'critical'], true)) {
            return;
        }

        $throttleKey = "bot-alert-throttle:{$event->event_type}:{$event->form_type}";
        if (Cache::has($throttleKey)) {
            return;
        }

        $threshold = max(1, (int) setting('admin_alert_threshold', 5));
        $recentCount = FormSecurityEvent::where('event_type', $event->event_type)
            ->where('form_type', $event->form_type)
            ->where('created_at', '>=', now()->subMinutes(self::ALERT_COOLDOWN_MINUTES))
            ->count();

        if ($recentCount < $threshold) {
            return;
        }

        Cache::put($throttleKey, true, now()->addMinutes(self::ALERT_COOLDOWN_MINUTES));

        $this->alerts->send(
            'Bot protection: possible attack in progress',
            "{$recentCount} '{$event->event_type}' events on the '{$event->form_type}' form in the last ".self::ALERT_COOLDOWN_MINUTES." minutes. Latest reference: {$event->reference}.",
        );

        $this->audit->log('security.bot_attack_alert', "Bot attack alert sent for {$event->event_type} on {$event->form_type}", null, [
            'event_type' => $event->event_type,
            'form_type' => $event->form_type,
            'recent_count' => $recentCount,
        ]);
    }

    public function alertProviderUnavailable(string $provider): void
    {
        $throttleKey = "bot-alert-throttle:provider_unavailable:{$provider}";
        if (Cache::has($throttleKey)) {
            return;
        }
        Cache::put($throttleKey, true, now()->addMinutes(self::OUTAGE_ALERT_COOLDOWN_MINUTES));

        $this->alerts->send(
            "Bot protection: {$provider} unavailable",
            "{$provider} verification requests have started failing due to a timeout or network error. Stricter local rate limiting is applied automatically while this continues.",
        );
    }

    public function alertConfigurationChanged(string $summary): void
    {
        $this->alerts->send('Bot protection configuration changed', $summary);
    }
}
