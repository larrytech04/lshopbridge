<?php

namespace App\Models;

use App\Enums\FeePayer;
use App\Enums\FeeType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Fee extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:4',
            'fixed_value' => 'decimal:2',
            'min_fee' => 'decimal:2',
            'max_fee' => 'decimal:2',
            'min_amount' => 'decimal:2',
            'max_amount' => 'decimal:2',
            'provider_markup_percent' => 'decimal:4',
            'type' => FeeType::class,
            'fee_payer' => FeePayer::class,
            'is_active' => 'boolean',
            'taxable' => 'boolean',
            'under_review' => 'boolean',
            'effective_start_date' => 'date',
            'effective_end_date' => 'date',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function tiers(): HasMany
    {
        return $this->hasMany(FeeTier::class)->orderBy('sort');
    }

    public function history(): HasMany
    {
        return $this->hasMany(FeeHistory::class)->latest('created_at');
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(FeeSchedule::class)->orderByDesc('effective_start_date');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function label(): string
    {
        return $this->code ? "{$this->name} ({$this->code})" : $this->name;
    }
}
