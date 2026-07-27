<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\KycDecision;
use App\Models\KycNote;
use App\Models\KycVerification;
use App\Models\RiskFlag;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Populates the KYC review queue with realistic cases across every status,
 * priority and manual-review-check state so the workspace can be exercised
 * end to end in a fresh dev environment. Every document points at a real
 * placeholder file on the private disk so the secure document viewer has
 * something to actually stream.
 */
class KycVerificationSeeder extends Seeder
{
    public function run(): void
    {
        $password = env('DEMO_PASSWORD') ?: Str::password(14);
        $admin = User::where('email', 'admin@lshopbridge.test')->first();

        $gh = Country::where('iso2', 'GH')->first();
        $cm = Country::where('iso2', 'CM')->first();
        $ng = Country::where('iso2', 'NG')->first();

        $samplePath = $this->placeholderDocument();

        $cases = [
            [
                'email' => 'ama.owusu@example.test', 'name' => 'Ama Owusu', 'country' => $gh, 'phone' => '+233200000010',
                'status' => 'pending', 'submitted_days_ago' => 1, 'priority' => null,
            ],
            [
                'email' => 'kwame.boateng@example.test', 'name' => 'Kwame Boateng', 'country' => $gh, 'phone' => '+233200000011',
                'status' => 'in_review', 'submitted_days_ago' => 5, 'priority' => 'high', 'assign' => true,
                'document_expiry_days' => 20,
            ],
            [
                'email' => 'fatima.diallo@example.test', 'name' => 'Fatima Diallo', 'country' => $cm, 'phone' => '+237600000010',
                'status' => 'more_info_requested', 'submitted_days_ago' => 3, 'priority' => 'medium',
                'decision' => ['type' => 'request_more_info', 'internal_reason' => 'Selfie and ID photo comparison inconclusive.', 'customer_message' => 'We need a clearer selfie to match against your ID photo. Please retake it in good lighting.'],
                'review_checks' => ['face_match' => ['status' => 'unclear', 'notes' => 'Selfie too dark to compare confidently.']],
            ],
            [
                'email' => 'john.mensah@example.test', 'name' => 'John K. Mensah', 'country' => $gh, 'phone' => '+233200000012',
                'status' => 'returned_for_correction', 'submitted_days_ago' => 4, 'priority' => 'medium',
                'decision' => ['type' => 'return_for_correction', 'internal_reason' => 'Full name entered does not match the document.', 'customer_message' => 'The name you entered does not match your document. Please correct it and resubmit.'],
            ],
            [
                'email' => 'grace.nkemelu@example.test', 'name' => 'Grace Nkemelu', 'country' => $ng, 'phone' => '+234800000010',
                'status' => 'escalated', 'submitted_days_ago' => 6, 'priority' => 'critical', 'assign' => true,
                'decision' => ['type' => 'escalate', 'internal_reason' => 'Open high-severity risk flag needs a second reviewer before any decision.'],
                'risk_flag' => ['severity' => 'high', 'reason' => 'Submitted document number matches a previously rejected case.'],
                'note' => 'Called the number on file, no answer. Will retry tomorrow before escalating further.',
            ],
            [
                'email' => 'ibrahim.toure@example.test', 'name' => 'Ibrahim Toure', 'country' => $cm, 'phone' => '+237600000011',
                'status' => 'on_hold', 'submitted_days_ago' => 2, 'priority' => 'low',
                'decision' => ['type' => 'hold', 'internal_reason' => 'Paused pending confirmation from the payments team on a linked deposit.'],
            ],
            [
                'email' => 'linda.asante@example.test', 'name' => 'Linda Asante', 'country' => $gh, 'phone' => '+233200000013',
                'status' => 'rejected', 'submitted_days_ago' => 8, 'priority' => 'medium', 'reviewed' => true,
                'decision' => ['type' => 'reject', 'internal_reason' => 'Visual inspection shows signs of tampering on the ID photo.', 'customer_message' => 'We were unable to verify your identity with the documents provided.'],
                'review_checks' => ['document_authenticity' => ['status' => 'concerns', 'notes' => 'Font inconsistency around the date of birth field.']],
            ],
            [
                'email' => 'peter.osei@example.test', 'name' => 'Peter Osei', 'country' => $ng, 'phone' => '+234800000011',
                'status' => 'approved_limited', 'submitted_days_ago' => 7, 'priority' => 'medium', 'reviewed' => true,
                'country_mismatch' => $gh,
                'decision' => ['type' => 'approve_limited', 'internal_reason' => 'Identity confirmed; proof of address not strong enough for full limits.', 'customer_message' => 'Your identity is verified. Some limits are temporarily reduced until we receive a clearer proof of address.'],
            ],
        ];

        foreach ($cases as $c) {
            $user = User::updateOrCreate(['email' => $c['email']], [
                'name' => $c['name'],
                'password' => Hash::make($password),
                'role' => 'user',
                'phone' => $c['phone'],
                'country_id' => ($c['country_mismatch'] ?? $c['country'])?->id,
                'kyc_level' => 0,
                'kyc_status' => 'pending',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]);
            $user->primaryWallet();

            $submittedAt = now()->subDays($c['submitted_days_ago']);

            $kyc = KycVerification::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'document_type' => 'national_id',
                    'document_number' => 'DEMO-'.strtoupper(substr(md5($c['email']), 0, 8)),
                    'full_name' => $c['name'],
                    'date_of_birth' => now()->subYears(28)->subDays($c['submitted_days_ago']),
                    'country_id' => $c['country']?->id,
                    'city' => $c['country']?->name.' City',
                    'address' => '14 Liberation Road',
                    'occupation' => 'Trader',
                    'source_of_funds' => 'business',
                    'is_pep' => false,
                    'status' => $c['status'],
                    'target_level' => 2,
                    'priority' => $c['priority'],
                    'id_front_path' => $samplePath,
                    'id_back_path' => $samplePath,
                    'selfie_path' => $samplePath,
                    'proof_of_address_path' => $samplePath,
                    'review_checks' => $c['review_checks'] ?? null,
                    'document_expiry_date' => isset($c['document_expiry_days']) ? now()->addDays($c['document_expiry_days']) : null,
                    'assigned_to' => ($c['assign'] ?? false) ? $admin?->id : null,
                    'reviewed_by' => ($c['reviewed'] ?? false) ? $admin?->id : null,
                    'reviewed_at' => ($c['reviewed'] ?? false) ? $submittedAt->copy()->addHours(6) : null,
                    'rejection_reason' => $c['decision']['customer_message'] ?? null,
                    'created_at' => $submittedAt,
                    'updated_at' => $submittedAt,
                ],
            );
            KycVerification::where('id', $kyc->id)->update(['created_at' => $submittedAt]);

            if (isset($c['decision']) && $admin) {
                KycDecision::updateOrCreate(
                    ['kyc_verification_id' => $kyc->id, 'decision_type' => $c['decision']['type']],
                    [
                        'actor_id' => $admin->id,
                        'internal_reason' => $c['decision']['internal_reason'] ?? null,
                        'customer_message' => $c['decision']['customer_message'] ?? null,
                        'created_at' => $submittedAt->copy()->addHours(6),
                    ],
                );
            }

            if (isset($c['risk_flag'])) {
                RiskFlag::updateOrCreate(
                    ['flaggable_type' => KycVerification::class, 'flaggable_id' => $kyc->id],
                    [
                        'user_id' => $user->id,
                        'rule_code' => 'manual_kyc_review',
                        'severity' => $c['risk_flag']['severity'],
                        'reason' => $c['risk_flag']['reason'],
                        'status' => 'open',
                        'context' => ['kyc_verification_id' => $kyc->id],
                    ],
                );
            }

            if (isset($c['note']) && $admin) {
                KycNote::updateOrCreate(
                    ['kyc_verification_id' => $kyc->id, 'user_id' => $admin->id],
                    ['body' => $c['note']],
                );
            }
        }

        // One closed historical record for the existing "kofi" demo user, so the
        // review workspace's "previous KYC history" section has something to show.
        if ($kofi = User::where('email', 'kofi@example.com')->first()) {
            KycVerification::updateOrCreate(
                ['user_id' => $kofi->id, 'document_number' => 'DEMO-KOFI0001'],
                [
                    'document_type' => 'passport', 'full_name' => 'Kofi Mensah', 'date_of_birth' => now()->subYears(31),
                    'country_id' => $kofi->country_id, 'city' => 'Accra', 'address' => '12 Independence Ave',
                    'occupation' => 'Shop owner', 'source_of_funds' => 'business', 'is_pep' => false,
                    'status' => 'approved', 'target_level' => 2,
                    'id_front_path' => $samplePath, 'selfie_path' => $samplePath,
                    'reviewed_by' => $admin?->id, 'reviewed_at' => now()->subDays(30),
                    'created_at' => now()->subDays(31), 'updated_at' => now()->subDays(30),
                ],
            );
        }

        $this->command?->newLine();
        $this->command?->line('Seeded '.count($cases).' demo KYC cases across pending/in_review/more_info_requested/returned_for_correction/escalated/on_hold/rejected/approved_limited.');
    }

    private function placeholderDocument(): string
    {
        $path = 'kyc/demo-placeholder.png';

        if (! Storage::disk('private')->exists($path)) {
            $source = public_path('assets/about us.png');
            if (is_file($source)) {
                Storage::disk('private')->put($path, file_get_contents($source));
            }
        }

        return $path;
    }
}
