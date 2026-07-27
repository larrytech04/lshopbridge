<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TemporaryFormRestriction extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('expires_at', '>', now());
    }

    public static function isRestricted(string $subjectType, string $subjectValue, ?string $formType = null): bool
    {
        return static::active()
            ->where('subject_type', $subjectType)
            ->where('subject_value', $subjectValue)
            ->where(function ($q) use ($formType) {
                $q->whereNull('form_type');
                if ($formType) {
                    $q->orWhere('form_type', $formType);
                }
            })
            ->exists();
    }
}
