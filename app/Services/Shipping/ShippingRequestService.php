<?php

namespace App\Services\Shipping;

use App\Enums\ShippingQuoteStatus;
use App\Enums\ShippingRequestStatus;
use App\Models\Agent;
use App\Models\ShippingQuote;
use App\Models\ShippingRequest;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * The multi-agent quote/bidding workflow: a customer posts one request, any
 * number of approved agents can quote on it independently, and the customer
 * accepts exactly one — the rest are rejected automatically. Distinct from
 * AgentLead (a 1:1 buyer<->agent chat), which has no concept of competing bids.
 */
class ShippingRequestService
{
    public function __construct(private AuditLogger $audit) {}

    public function createDraft(User $user, array $data): ShippingRequest
    {
        return ShippingRequest::create($data + [
            'reference' => reference('PB-SHR'),
            'user_id' => $user->id,
            'status' => ShippingRequestStatus::Draft,
        ]);
    }

    public function submit(ShippingRequest $request, User $user): ShippingRequest
    {
        abort_unless($request->user_id === $user->id, 403);
        abort_unless($request->status === ShippingRequestStatus::Draft, 422);

        $request->update(['status' => ShippingRequestStatus::AwaitingQuotes]);
        $this->audit->log('shipping_request.submitted', "Shipping request {$request->reference} submitted", $request, [], $user->id);

        return $request->fresh();
    }

    public function cancel(ShippingRequest $request, User $user): ShippingRequest
    {
        abort_unless($request->user_id === $user->id, 403);
        abort_unless($request->status->isCancellable(), 422);

        $request->update(['status' => ShippingRequestStatus::Cancelled, 'cancelled_at' => now()]);
        $this->audit->log('shipping_request.cancelled', "Shipping request {$request->reference} cancelled", $request, [], $user->id);

        return $request->fresh();
    }

    public function submitQuote(ShippingRequest $request, Agent $agent, float $price, int $etaDays, ?string $notes): ShippingQuote
    {
        abort_unless($request->status->isOpenForQuotes(), 422);

        $quote = ShippingQuote::updateOrCreate(
            ['shipping_request_id' => $request->id, 'agent_id' => $agent->id],
            ['price' => $price, 'currency' => $request->package_currency, 'eta_days' => $etaDays, 'notes' => $notes, 'status' => ShippingQuoteStatus::Pending],
        );

        if ($request->status === ShippingRequestStatus::AwaitingQuotes) {
            $request->update(['status' => ShippingRequestStatus::QuoteReceived]);
        }

        $this->audit->log('shipping_request.quoted', "Agent quoted {$price} {$request->package_currency} on {$request->reference}", $request, ['quote_id' => $quote->id], $agent->user_id);

        return $quote;
    }

    public function withdrawQuote(ShippingQuote $quote, Agent $agent): ShippingQuote
    {
        abort_unless($quote->agent_id === $agent->id, 403);
        abort_unless($quote->status === ShippingQuoteStatus::Pending, 422);

        $quote->update(['status' => ShippingQuoteStatus::Withdrawn]);

        return $quote->fresh();
    }

    public function acceptQuote(ShippingRequest $request, ShippingQuote $quote, User $user): ShippingRequest
    {
        abort_unless($request->user_id === $user->id, 403);
        abort_unless($quote->shipping_request_id === $request->id, 422);
        abort_unless($request->status === ShippingRequestStatus::QuoteReceived, 422);
        abort_unless($quote->status === ShippingQuoteStatus::Pending, 422);

        return DB::transaction(function () use ($request, $quote, $user) {
            $quote->update(['status' => ShippingQuoteStatus::Accepted]);
            $request->quotes()->where('id', '!=', $quote->id)->where('status', ShippingQuoteStatus::Pending)
                ->update(['status' => ShippingQuoteStatus::Rejected]);

            $request->update(['status' => ShippingRequestStatus::Accepted, 'accepted_quote_id' => $quote->id]);
            $this->audit->log('shipping_request.quote_accepted', "Quote #{$quote->id} accepted on {$request->reference}", $request, [], $user->id);

            return $request->fresh();
        });
    }

    /** Agent-side progress updates once a quote has been accepted. */
    public function advance(ShippingRequest $request, Agent $agent, ShippingRequestStatus $to, ?string $trackingNumber = null): ShippingRequest
    {
        abort_unless($request->acceptedQuote?->agent_id === $agent->id, 403);

        $allowed = [
            ShippingRequestStatus::Accepted->value => ShippingRequestStatus::AwaitingPickup,
            ShippingRequestStatus::AwaitingPickup->value => ShippingRequestStatus::InTransit,
            ShippingRequestStatus::InTransit->value => ShippingRequestStatus::Delivered,
        ];

        if (($allowed[$request->status->value] ?? null) !== $to) {
            throw ValidationException::withMessages(['status' => 'Invalid status transition.']);
        }

        $request->update(array_filter([
            'status' => $to,
            'tracking_number' => $trackingNumber ?: $request->tracking_number,
            'delivered_at' => $to === ShippingRequestStatus::Delivered ? now() : null,
        ], fn ($v) => $v !== null));

        $this->audit->log('shipping_request.advanced', "{$request->reference} moved to {$to->value}", $request, [], $agent->user_id);

        return $request->fresh();
    }
}
