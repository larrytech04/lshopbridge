<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Guide extends Model
{
    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'faqs' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Guide $guide) {
            $guide->slug ??= Str::slug($guide->title);
        });
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true)->orderBy('sort');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
