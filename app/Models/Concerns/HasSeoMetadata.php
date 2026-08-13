<?php

namespace App\Models\Concerns;

use App\Models\SeoMetadata;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Give any model (Page, Guide, ShopProduct, ShopCategory, Agent, ...) an
 * optional SEO-metadata row without a dedicated migration per model — see
 * database/migrations/..._create_seo_metadata_table.php. Missing row is the
 * normal case, not an error; SeoService::forModel() falls back to site
 * defaults when one doesn't exist.
 */
trait HasSeoMetadata
{
    public function seoMetadata(): MorphOne
    {
        return $this->morphOne(SeoMetadata::class, 'seoable');
    }
}
