<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Per-record SEO overrides for any model using HasSeoMetadata — see that
 * trait and app/Services/Seo/SeoService.php for how a row here (when it
 * exists) takes precedence over the site-wide defaults.
 */
class SeoMetadata extends Model
{
    protected $table = 'seo_metadata';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'sitemap_include' => 'boolean',
            'last_seo_review_at' => 'datetime',
        ];
    }

    public function seoable(): MorphTo
    {
        return $this->morphTo();
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seo_reviewed_by');
    }
}
