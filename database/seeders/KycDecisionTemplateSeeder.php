<?php

namespace Database\Seeders;

use App\Models\KycDecisionTemplate;
use Illuminate\Database\Seeder;

class KycDecisionTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            ['decision_type' => 'approve', 'name' => 'Standard approval', 'internal_reason' => 'All checks passed, no open flags.', 'customer_message' => null],
            ['decision_type' => 'approve_limited', 'name' => 'Approved, proof of address pending', 'internal_reason' => 'Identity confirmed; proof of address not yet strong enough for full limits.', 'customer_message' => 'Your identity is verified. Some limits are temporarily reduced until we receive a clearer proof of address.'],
            ['decision_type' => 'request_more_info', 'name' => 'Blurry or unreadable document', 'internal_reason' => 'Submitted ID photo is not legible enough to confirm details.', 'customer_message' => 'We could not clearly read your ID document. Please re-upload a clear, well-lit photo showing all four corners.'],
            ['decision_type' => 'request_more_info', 'name' => 'Selfie does not match document', 'internal_reason' => 'Selfie and ID photo comparison inconclusive, needs a clearer selfie.', 'customer_message' => 'We need a clearer selfie to match against your ID photo. Please retake it in good lighting without accessories.'],
            ['decision_type' => 'return_for_correction', 'name' => 'Name mismatch', 'internal_reason' => 'Full name entered does not match the name printed on the document.', 'customer_message' => 'The name you entered does not match your document. Please correct it and resubmit.'],
            ['decision_type' => 'return_for_correction', 'name' => 'Expired document', 'internal_reason' => 'Document expiry date has passed.', 'customer_message' => 'Your ID document has expired. Please submit a currently valid document.'],
            ['decision_type' => 'reject', 'name' => 'Document appears altered', 'internal_reason' => 'Visual inspection shows signs of tampering (inconsistent fonts/spacing).', 'customer_message' => 'We were unable to verify your identity with the documents provided.'],
            ['decision_type' => 'reject', 'name' => 'Sanctions/PEP match confirmed', 'internal_reason' => 'Compliance review confirmed a sanctions/PEP match after manual screening.', 'customer_message' => 'We were unable to verify your account at this time.'],
            ['decision_type' => 'escalate', 'name' => 'Needs compliance review', 'internal_reason' => 'Case has an open high-severity risk flag and needs a second reviewer.', 'customer_message' => null],
            ['decision_type' => 'hold', 'name' => 'Awaiting third-party confirmation', 'internal_reason' => 'Paused pending confirmation from another department.', 'customer_message' => null],
        ];

        foreach ($templates as $t) {
            KycDecisionTemplate::updateOrCreate(
                ['decision_type' => $t['decision_type'], 'name' => $t['name']],
                $t + ['is_active' => true],
            );
        }
    }
}
