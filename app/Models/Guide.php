<?php

namespace App\Models;

use App\Enums\GuideDifficulty;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Guide extends Model
{
    // Deliberately NOT HasSeoMetadata — already has native meta_title/
    // meta_description columns with a real admin form (see Page.php's
    // docblock for the same reasoning).
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'steps' => 'array',
            'faqs' => 'array',
            'is_published' => 'boolean',
            'is_featured' => 'boolean',
            'difficulty' => GuideDifficulty::class,
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

    /**
     * The academy's table of contents: section => ordered category keys.
     * Shared by the dashboard Learning Center and the public academy page so
     * both present the same book-like structure.
     *
     * @return array<string, list<string>>
     */
    public static function academySections(): array
    {
        return [
            'start' => ['orientation'],
            'platforms' => ['1688', 'taobao', 'tmall', 'pinduoduo', 'jd', 'xiaohongshu', 'weidian', 'aliexpress', 'dhgate'],
            'payments' => ['alipay', 'wechatpay'],
            'logistics' => ['shipping', 'customs'],
            'safety' => ['mistakes'],
            'reference' => ['glossary'],
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function feedback(): HasMany
    {
        return $this->hasMany(GuideFeedback::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function helpfulCount(): int
    {
        return $this->feedback()->where('was_helpful', true)->count();
    }

    public function notHelpfulCount(): int
    {
        return $this->feedback()->where('was_helpful', false)->count();
    }
}
