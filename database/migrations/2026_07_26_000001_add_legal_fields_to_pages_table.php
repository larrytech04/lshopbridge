<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            // Groups legal pages on the /legal hub (general, money, marketplace,
            // shipping, identity, programs, company). Null for non-legal types.
            $table->string('category')->nullable()->after('type');

            // The "Plain-English Summary" block shown above the full policy —
            // kept separate from `body` so it can render in its own callout
            // regardless of how the formal policy is structured.
            $table->longText('plain_summary')->nullable()->after('excerpt');

            // Which platform services this policy applies to, e.g.
            // ["withdrawals", "shipping_agents"]. Null/empty = applies
            // platform-wide. Used to hide e.g. a Withdrawal Policy on
            // installs where withdrawals aren't offered.
            $table->json('applicable_services')->nullable()->after('plain_summary');

            // ISO country codes this policy applies to. Null = all supported
            // countries. Per-country legal supplements are a later phase;
            // this field just lets a page declare scope today.
            $table->json('applicable_countries')->nullable()->after('applicable_services');

            $table->date('effective_date')->nullable()->after('applicable_countries');

            // Admin-only notes flagging claims/clauses that need verification
            // or lawyer review before this document is relied on in
            // production. Never rendered on the public page.
            $table->text('internal_review_notes')->nullable()->after('effective_date');
        });
    }

    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn([
                'category', 'plain_summary', 'applicable_services',
                'applicable_countries', 'effective_date', 'internal_review_notes',
            ]);
        });
    }
};
