<?php

namespace App\Models;

use App\Models\Concerns\MasksSensitiveValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CryptoWallet extends Model
{
    use HasFactory, SoftDeletes, MasksSensitiveValue;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_deposit' => 'decimal:2',
            'max_deposit' => 'decimal:2',
            'auto_reconciliation' => 'boolean',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** Masked for display in admin lists. Full address available via reveal action only. */
    public function maskedAddress(): string
    {
        return self::mask($this->address);
    }
}
