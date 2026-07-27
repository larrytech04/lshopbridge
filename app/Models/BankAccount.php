<?php

namespace App\Models;

use App\Models\Concerns\MasksSensitiveValue;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankAccount extends Model
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

    /** Masked for display in admin lists. Full account number available via reveal action only. */
    public function maskedAccountNumber(): string
    {
        return self::mask($this->account_number);
    }

    public function maskedIban(): ?string
    {
        return $this->iban ? self::mask($this->iban) : null;
    }
}
