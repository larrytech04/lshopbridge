<?php

namespace App\Models;

use App\Services\Security\WebauthnService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Symfony\Component\Uid\Uuid;
use Webauthn\CredentialRecord;
use Webauthn\PublicKeyCredentialDescriptor;
use Webauthn\TrustPath\EmptyTrustPath;
use Webauthn\Util\Base64;

class WebauthnCredential extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'transports' => 'array',
            'counter' => 'integer',
            'backup_eligible' => 'boolean',
            'backup_status' => 'boolean',
            'uv_initialized' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** Rebuilds the library's runtime object from the stored row. Only "none" attestation is ever requested, so the trust path is always empty. */
    public function toCredentialRecord(): CredentialRecord
    {
        return CredentialRecord::create(
            Base64::decode($this->credential_id),
            PublicKeyCredentialDescriptor::CREDENTIAL_TYPE_PUBLIC_KEY,
            $this->transports ?? [],
            $this->attestation_type,
            EmptyTrustPath::create(),
            Uuid::fromString($this->aaguid),
            Base64::decode($this->public_key),
            app(WebauthnService::class)->userHandleFor($this->user_id),
            $this->counter,
            null,
            $this->backup_eligible,
            $this->backup_status,
            $this->uv_initialized,
        );
    }

    public static function fromCredentialRecord(User $user, string $name, CredentialRecord $record): self
    {
        return self::create([
            'user_id' => $user->id,
            'name' => $name,
            'credential_id' => base64_encode($record->publicKeyCredentialId),
            'public_key' => base64_encode($record->credentialPublicKey),
            'attestation_type' => $record->attestationType,
            'aaguid' => $record->aaguid->toRfc4122(),
            'transports' => $record->transports,
            'counter' => $record->counter,
            'backup_eligible' => $record->backupEligible,
            'backup_status' => $record->backupStatus,
            'uv_initialized' => $record->uvInitialized,
        ]);
    }
}
