<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row per changed setting key, written by SettingsService::set(). */
class SystemSettingRevision extends Model
{
    const UPDATED_AT = null;

    protected $guarded = [];

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
