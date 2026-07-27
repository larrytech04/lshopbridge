<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A snapshot of a page's content taken immediately before each update, so a
 * published legal/info page is never silently overwritten with no way back.
 * Immutable — rows are only ever inserted, never edited.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('page_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->string('title');
            $table->string('slug');
            $table->string('type');
            $table->text('excerpt')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_published');
            $table->foreignId('edited_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('page_revisions');
    }
};
