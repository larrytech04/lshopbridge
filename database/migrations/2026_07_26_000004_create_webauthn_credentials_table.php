<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stores WebAuthn/passkey credentials. Only "none" attestation is requested
 * at registration (see WebauthnService), so trust_path is always the
 * library's EmptyTrustPath — not stored, reconstructed on read — and
 * attestation_type is always "none"; both columns exist so a future,
 * broader attestation policy wouldn't need a schema change.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('webauthn_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('credential_id')->unique(); // base64url
            $table->text('public_key'); // base64, CBOR-encoded COSE key
            $table->string('attestation_type')->default('none');
            $table->string('aaguid');
            $table->json('transports')->nullable();
            $table->unsignedBigInteger('counter')->default(0);
            $table->boolean('backup_eligible')->nullable();
            $table->boolean('backup_status')->nullable();
            $table->boolean('uv_initialized')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('webauthn_credentials');
    }
};
