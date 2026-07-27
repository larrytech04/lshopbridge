<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per eSIM order item. Deliberately separate from the generic
 * shop_order_items.delivered JSON blob (a plain array of strings used for
 * gift cards etc.) — eSIM fulfillment has a real lifecycle (provisioning ->
 * installed -> activated -> usage) and genuinely sensitive fields (ICCID,
 * activation code, SM-DP+ address) that need encryption-at-rest and
 * owner-gated access, not a plaintext order-history column.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('esim_provisionings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_order_item_id')->unique()->constrained()->cascadeOnDelete();

            // "manual" until a real provider connector fulfils it automatically.
            $table->string('provider')->default('manual');
            $table->string('provider_order_id')->nullable();
            $table->string('provider_package_id')->nullable();

            // Sensitive provisioning values — encrypted at rest (see model casts).
            // Every one nullable: only ever populated with a REAL value, either
            // by a provider connector or by staff during manual fulfillment.
            $table->text('iccid')->nullable();
            $table->text('activation_code')->nullable();
            $table->text('sm_dp_address')->nullable();
            $table->text('confirmation_code')->nullable();
            $table->text('lpa_string')->nullable(); // full LPA:1$...$... used to render the QR
            $table->text('direct_install_url')->nullable();

            // pending_provisioning | provisioning | ready | installed |
            // awaiting_activation | active | data_low | exhausted |
            // expiring_soon | expired | suspended | failed | cancelled | refunded
            $table->string('status')->default('pending_provisioning');

            $table->string('activation_policy')->nullable(); // snapshot from the variant at purchase time
            $table->timestamp('installation_deadline_at')->nullable();

            // Device-compatibility confirmation the customer must give before
            // purchase completes (section 7/10 of the spec) — stored with the
            // order, never assumed.
            $table->string('device_brand')->nullable();
            $table->string('device_model')->nullable();
            $table->string('device_os')->nullable();
            $table->boolean('compatibility_confirmed')->default(false);
            $table->timestamp('compatibility_confirmed_at')->nullable();
            $table->string('compatibility_confirmed_ip', 64)->nullable();

            $table->timestamp('installed_at')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('expires_at')->nullable();

            // QR-reveal audit trail (section 17: "log first reveal, log later reveals").
            $table->timestamp('first_qr_reveal_at')->nullable();
            $table->timestamp('last_qr_reveal_at')->nullable();
            $table->unsignedInteger('qr_reveal_count')->default(0);

            $table->text('provider_error')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('esim_provisionings');
    }
};
