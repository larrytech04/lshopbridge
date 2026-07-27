<?php

namespace App\Models;

use App\Enums\AppType;
use App\Enums\BeneficiaryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class BeneficiaryAccount extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'app_type' => AppType::class,
            'status' => BeneficiaryStatus::class,
            'is_default' => 'boolean',
            'resubmission_allowed' => 'boolean',
            'meta' => 'array',
            'review_checklist' => 'array',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function fundingRequests(): HasMany
    {
        return $this->hasMany(FundingRequest::class, 'beneficiary_account_id');
    }

    public function events(): HasMany
    {
        return $this->hasMany(BeneficiaryAccountEvent::class)->latest('created_at');
    }

    public function isApproved(): bool
    {
        return $this->status === BeneficiaryStatus::Approved;
    }

    public function checklistItem(string $key): array
    {
        return $this->review_checklist[$key] ?? ['status' => 'not_checked'];
    }

    /** Loose token-overlap between the submitted wallet name and the account holder's name. */
    public function nameMatch(): string
    {
        if (! $this->account_name || ! $this->user?->name) {
            return 'unknown';
        }

        $a = $this->tokens($this->account_name);
        $b = $this->tokens($this->user->name);

        if (empty($a) || empty($b)) {
            return 'unknown';
        }

        $overlap = count(array_intersect($a, $b));

        return match (true) {
            $overlap === count($a) && $overlap === count($b) => 'match',
            $overlap > 0 => 'partial',
            default => 'mismatch',
        };
    }

    private function tokens(string $value): array
    {
        return array_values(array_filter(explode(' ', \Illuminate\Support\Str::of($value)->lower()->ascii()->replaceMatches('/[^a-z ]/', '')->toString())));
    }
}
