<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Saved Alipay / WeChat Pay / other China wallet profiles. Users may
        // add several; one can be the default funding target. Auto-funding pulls
        // the default (or chosen) beneficiary after payment clears.
        Schema::create('beneficiary_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('app_type');              // alipay | wechat | other
            $table->string('account_name');
            $table->string('account_id');            // phone / email / Alipay ID
            $table->string('qr_path')->nullable();   // PRIVATE disk
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('pending'); // pending | approved | rejected
            $table->text('rejection_reason')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('beneficiary_accounts');
    }
};
