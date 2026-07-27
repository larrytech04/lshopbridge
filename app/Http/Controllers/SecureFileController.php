<?php

namespace App\Http\Controllers;

use App\Models\Agent;
use App\Models\BeneficiaryAccount;
use App\Models\Deposit;
use App\Models\DisputeMessage;
use App\Models\FundingRequest;
use App\Models\GuestSupportTicket;
use App\Models\KycVerification;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams sensitive files from the PRIVATE disk. Documents are never exposed via
 * a public URL, access requires being the owner or an admin. This is the only
 * path through which KYC IDs, selfies, proofs, receipts and QR codes are served.
 */
class SecureFileController extends Controller
{
    public function show(Request $request, string $kind, int $id, AuditLogger $audit): StreamedResponse
    {
        [$path, $ownerId] = $this->resolve($kind, $id);

        abort_if($path === null, 404);

        $user = $request->user();
        abort_unless($user->isAdmin() || $user->id === $ownerId, 403);
        abort_unless(Storage::disk('private')->exists($path), 404);

        if ($user->isAdmin() && str_starts_with($kind, 'kyc-') && $user->id !== $ownerId) {
            $kyc = KycVerification::find($id);
            $audit->log('admin.kyc.document_viewed', "Viewed {$kind} for KYC case #{$id}", $kyc);
        }

        if ($user->isAdmin() && $kind === 'beneficiary-qr' && $user->id !== $ownerId) {
            $beneficiary = BeneficiaryAccount::find($id);
            $audit->log('admin.beneficiary.qr_viewed', "Viewed QR code for China wallet #{$id}", $beneficiary);
        }

        if ($user->isAdmin() && $kind === 'guest-support-attachment') {
            $ticket = GuestSupportTicket::find($id);
            $audit->log('admin.support_ticket.attachment_viewed', "Viewed attachment for guest support ticket #{$id}", $ticket);
        }

        return Storage::disk('private')->response($path);
    }

    /** @return array{0: ?string, 1: ?int} [relativePath, ownerUserId] */
    private function resolve(string $kind, int $id): array
    {
        return match ($kind) {
            'kyc-front' => $this->kyc($id, 'id_front_path'),
            'kyc-back' => $this->kyc($id, 'id_back_path'),
            'kyc-selfie' => $this->kyc($id, 'selfie_path'),
            'kyc-proof' => $this->kyc($id, 'proof_of_address_path'),
            'deposit-proof' => $this->from(Deposit::find($id), 'proof_path'),
            'funding-receipt' => $this->from(FundingRequest::find($id), 'receipt_path'),
            'beneficiary-qr' => $this->from(BeneficiaryAccount::find($id), 'qr_path'),
            'agent-business' => $this->fromAgent($id, 'business_doc_path'),
            'agent-id' => $this->fromAgent($id, 'id_doc_path'),
            'dispute-attachment' => $this->disputeAttachment($id),
            'guest-support-attachment' => $this->guestSupportAttachment($id),
            default => [null, null],
        };
    }

    /** No owning user exists for guest content, so access is admin-only (see the check in show()). */
    private function guestSupportAttachment(int $id): array
    {
        $ticket = GuestSupportTicket::find($id);

        return [$ticket?->attachment_path, null];
    }

    private function kyc(int $id, string $field): array
    {
        $kyc = KycVerification::find($id);

        return [$kyc?->{$field}, $kyc?->user_id];
    }

    private function from($model, string $field): array
    {
        return [$model?->{$field}, $model?->user_id];
    }

    private function fromAgent(int $id, string $field): array
    {
        $agent = Agent::find($id);

        return [$agent?->{$field}, $agent?->user_id];
    }

    private function disputeAttachment(int $messageId): array
    {
        $message = DisputeMessage::with('dispute')->find($messageId);

        return [$message?->attachment_path, $message?->dispute?->user_id];
    }
}
