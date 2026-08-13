<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One reusable, polymorphic SEO-metadata table instead of duplicating
     * meta_title/meta_description/etc. columns across pages, guides,
     * shop_products, shop_categories, agents, and every future content
     * table. Any model gets an "seoMetadata" relation via the
     * HasSeoMetadata trait (see app/Models/Concerns/HasSeoMetadata.php).
     *
     * A row is optional per model — absence just means "use the site
     * default", resolved by SeoService. `robots`/`focus_topic`/
     * `structured_data_type` are editorial fields the admin sets directly
     * (see brief section 22); `focus_topic` is guidance text only, never
     * fed into any keyword-density scoring.
     */
    public function up(): void
    {
        Schema::create('seo_metadata', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('seoable_id');
            $table->string('seoable_type');
            $table->unique(['seoable_type', 'seoable_id']);

            $table->string('meta_title')->nullable();
            $table->string('meta_description', 500)->nullable();

            // A full URL or an app-relative path; CanonicalUrlService
            // normalizes either into an absolute, scheme/host-correct URL.
            $table->string('canonical_override')->nullable();

            // e.g. "noindex,nofollow" — null means "let SeoService compute
            // the default for this environment/page", not "index,follow".
            $table->string('robots')->nullable();

            $table->string('og_title')->nullable();
            $table->string('og_description', 500)->nullable();
            $table->string('og_image_path')->nullable();

            // Editorial hint for which JSON-LD block a page-specific
            // renderer should build (Article, Product, FAQPage, ...) — not
            // itself a schema value, just admin guidance.
            $table->string('structured_data_type')->nullable();

            // Editorial "what is this page primarily about" note shown to
            // the admin writing/reviewing the page. Never repeated into
            // visible copy or scored — see brief section 22's explicit
            // warning against keyword-density tooling.
            $table->string('focus_topic')->nullable();

            $table->boolean('sitemap_include')->default(true);

            $table->timestamp('last_seo_review_at')->nullable();
            $table->foreignId('seo_reviewed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seo_metadata');
    }
};
