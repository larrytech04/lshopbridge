<?php

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Payments\PaymentManager;
use App\Services\Payments\WebhookProcessor;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Public, stateless endpoint for payment provider webhooks.
 * Excluded from CSRF (see bootstrap/app.php), authenticity is enforced by the
 * provider signature inside WebhookProcessor, never by a session.
 */
class WebhookController extends Controller
{
    public function __construct(
        private PaymentManager $payments,
        private WebhookProcessor $processor,
    ) {}

    public function payments(Request $request, string $provider): JsonResponse
    {
        if (! $this->payments->exists($provider)) {
            return response()->json(['status' => 'unknown_provider'], 404);
        }

        // Each provider declares the header it signs with.
        $headerName = $this->payments->driver($provider)->signatureHeader();
        $signature = $request->header($headerName);

        $event = $this->processor->handle(
            providerCode: $provider,
            rawBody: $request->getContent(),
            signature: $signature,
            headers: $this->safeHeaders($request),
            ip: $request->ip(),
        );

        $status = $event->status->value;
        $httpCode = $status === 'invalid_signature' ? 400 : 200;

        return response()->json(['status' => $status, 'event' => $event->event_id], $httpCode);
    }

    /**
     * China-wallet funding provider callbacks (async live mode). In sandbox the
     * payout settles synchronously, so this is a provider-ready stub.
     */
    public function funding(Request $request, string $provider): JsonResponse
    {
        // TODO[live]: verify the funding partner signature and complete/fail the
        // FundingRequest by its reference (FundingService::markFundingSuccessful
        // or ::setManualReview).
        return response()->json(['status' => 'received']);
    }

    private function safeHeaders(Request $request): array
    {
        // Never store Authorization / cookies in the webhook log.
        return collect($request->headers->all())
            ->except(['authorization', 'cookie'])
            ->map(fn ($v) => is_array($v) ? implode(',', $v) : $v)
            ->all();
    }
}
