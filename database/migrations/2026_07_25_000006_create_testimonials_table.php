<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Replaces the hardcoded PHP array of testimonials previously baked into
 * public/home.blade.php. The existing entries are migrated in as real,
 * admin-editable rows by a follow-up data seed — nothing displayed on the
 * homepage changes on cutover, but an admin can now edit/replace/add reviews
 * without touching a Blade file.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('testimonials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('source')->default('other'); // trustpilot|google|other
            $table->decimal('rating', 2, 1)->default(5.0);
            $table->date('review_date')->nullable();
            $table->boolean('verified')->default(false);
            $table->text('text');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('testimonials');
    }
};
