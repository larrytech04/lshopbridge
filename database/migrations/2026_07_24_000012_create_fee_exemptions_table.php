<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_exemptions', function (Blueprint $table) {
            $table->id();
            $table->string('exemption_type'); // customer|role|vip_level|agent|merchant|country|promotion|coupon|internal_test|admin_exception
            $table->string('target_value'); // e.g. role value, country code, coupon code, vip level name
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete(); // set for customer/agent/merchant exemptions
            $table->json('applicable_services')->nullable(); // null/absent = all services
            $table->text('reason');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_exemptions');
    }
};
