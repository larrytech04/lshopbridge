<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Role-based access: user | agent | admin | super_admin
            $table->string('role')->default('user')->after('email');

            // Contact + phone verification (OTP)
            $table->string('phone')->nullable()->after('role');
            $table->string('phone_country', 8)->nullable()->after('phone');
            $table->timestamp('phone_verified_at')->nullable()->after('phone_country');

            // Location
            $table->foreignId('country_id')->nullable()->after('phone_verified_at')->constrained()->nullOnDelete();
            $table->string('city')->nullable()->after('country_id');
            $table->string('address')->nullable()->after('city');
            $table->date('date_of_birth')->nullable()->after('address');

            // KYC: level 0..3 and overall status
            $table->unsignedTinyInteger('kyc_level')->default(0)->after('date_of_birth');
            $table->string('kyc_status')->default('unverified')->after('kyc_level'); // unverified|pending|approved|rejected

            // Account status + lifecycle
            $table->string('status')->default('active')->after('kyc_status'); // active|suspended|blocked
            $table->text('status_reason')->nullable()->after('status');
            $table->unsignedInteger('points')->default(0)->after('status_reason');

            // Referrals
            $table->string('referral_code')->nullable()->unique()->after('points');
            $table->foreignId('referred_by')->nullable()->after('referral_code');

            // Profile + 2FA-ready structure
            $table->string('avatar_path')->nullable()->after('referred_by');
            $table->boolean('two_factor_enabled')->default(false)->after('avatar_path');
            $table->text('two_factor_secret')->nullable()->after('two_factor_enabled');

            // Telemetry for risk/audit
            $table->string('locale', 8)->default('en')->after('two_factor_secret');
            $table->timestamp('last_login_at')->nullable()->after('locale');
            $table->string('last_login_ip', 45)->nullable()->after('last_login_at');
            $table->json('preferences')->nullable()->after('last_login_ip');

            $table->index(['role', 'status']);
            $table->index('kyc_status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('country_id');
            $table->dropColumn([
                'role', 'phone', 'phone_country', 'phone_verified_at', 'city', 'address',
                'date_of_birth', 'kyc_level', 'kyc_status', 'status', 'status_reason', 'points',
                'referral_code', 'referred_by', 'avatar_path', 'two_factor_enabled',
                'two_factor_secret', 'locale', 'last_login_at', 'last_login_ip', 'preferences',
            ]);
        });
    }
};
