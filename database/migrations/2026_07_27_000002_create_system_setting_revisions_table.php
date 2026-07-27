<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per changed setting key, written by SettingsService::set() only
 * when the value actually changes. Secret-typed keys (e.g. mail_password)
 * store a masked placeholder instead of the real old/new value — see
 * SettingsService::isSensitiveKey().
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_setting_revisions', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['key', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_setting_revisions');
    }
};
