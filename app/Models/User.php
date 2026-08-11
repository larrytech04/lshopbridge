<?php

namespace App\Models;

use App\Enums\KycStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, SoftDeletes;

    protected $fillable = [
        'name', 'email', 'password', 'role', 'phone', 'phone_country', 'phone_verified_at',
        'country_id', 'city', 'address', 'date_of_birth', 'gender', 'kyc_level', 'kyc_status',
        'status', 'status_reason', 'admin_notes', 'tags', 'points', 'referral_code', 'referred_by', 'avatar_path',
        'two_factor_enabled', 'two_factor_secret', 'two_factor_confirmed_at', 'two_factor_recovery_codes',
        'two_factor_disabled_at', 'password_changed_at', 'locale', 'last_login_at', 'last_login_ip',
        'preferences', 'google_id', 'avatar_url', 'shortcuts_enabled', 'shortcut_overrides',
        'transaction_pin', 'transaction_pin_set_at', 'last_seen_at',
        'reauth_code', 'reauth_code_expires_at', 'reauth_code_sent_at',
    ];

    protected $hidden = ['password', 'remember_token', 'two_factor_secret', 'two_factor_recovery_codes', 'transaction_pin', 'reauth_code'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'phone_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'transaction_pin_set_at' => 'datetime',
            'reauth_code_expires_at' => 'datetime',
            'reauth_code_sent_at' => 'datetime',
            'date_of_birth' => 'date',
            'password' => 'hashed',
            'password_changed_at' => 'datetime',
            'transaction_pin' => 'hashed',
            'reauth_code' => 'hashed',
            'role' => UserRole::class,
            'kyc_status' => KycStatus::class,
            'kyc_level' => 'integer',
            'points' => 'integer',
            'two_factor_enabled' => 'boolean',
            'two_factor_secret' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_recovery_codes' => 'encrypted:array',
            'two_factor_disabled_at' => 'datetime',
            'preferences' => 'array',
            'shortcuts_enabled' => 'boolean',
            'shortcut_overrides' => 'array',
            'tags' => 'array',
        ];
    }

    public function hasTransactionPin(): bool
    {
        return ! is_null($this->transaction_pin);
    }

    /** Real MFA is only "on" once a generated secret has actually been confirmed with a live code. */
    public function hasMfaEnabled(): bool
    {
        return $this->two_factor_enabled && $this->two_factor_confirmed_at !== null;
    }

    public function hasPasskeys(): bool
    {
        return $this->webauthnCredentials()->exists();
    }

    /** Whether login must stop at the second-factor challenge — true if either TOTP or a passkey is registered. */
    public function requiresMfaChallenge(): bool
    {
        return $this->hasMfaEnabled() || $this->hasPasskeys();
    }

    public function isOnline(): bool
    {
        return (bool) $this->last_seen_at?->gt(now()->subMinutes(5));
    }

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            $user->referral_code ??= strtoupper(Str::random(8));
        });
    }

    /* -------------------------------------------------- Relationships */

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function wallet(): HasOne
    {
        // Primary wallet (platform base currency).
        return $this->hasOne(Wallet::class)->latestOfMany();
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class);
    }

    public function walletTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class);
    }

    public function kycVerifications(): HasMany
    {
        return $this->hasMany(KycVerification::class);
    }

    public function beneficiaryAccounts(): HasMany
    {
        return $this->hasMany(BeneficiaryAccount::class);
    }

    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function fundingRequests(): HasMany
    {
        return $this->hasMany(FundingRequest::class);
    }

    public function shopOrders(): HasMany
    {
        return $this->hasMany(ShopOrder::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function savedPaymentMethods(): HasMany
    {
        return $this->hasMany(SavedPaymentMethod::class);
    }

    public function withdrawalRequests(): HasMany
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    public function shippingRequests(): HasMany
    {
        return $this->hasMany(ShippingRequest::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(self::class, 'referred_by');
    }

    public function agent(): HasOne
    {
        return $this->hasOne(Agent::class);
    }

    public function disputes(): HasMany
    {
        return $this->hasMany(Dispute::class);
    }

    public function riskFlags(): HasMany
    {
        return $this->hasMany(RiskFlag::class);
    }

    public function loginAttempts(): HasMany
    {
        return $this->hasMany(LoginAttempt::class);
    }

    public function webauthnCredentials(): HasMany
    {
        return $this->hasMany(WebauthnCredential::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /* -------------------------------------------------- Role helpers */

    public function hasRole(UserRole|string $role): bool
    {
        $value = $role instanceof UserRole ? $role->value : $role;

        return $this->role->value === $value;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === UserRole::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [UserRole::Admin, UserRole::SuperAdmin], true);
    }

    public function isAgent(): bool
    {
        return $this->role === UserRole::Agent;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /* -------------------------------------------------- KYC helpers */

    public function isKycApproved(): bool
    {
        return $this->kyc_status === KycStatus::Approved;
    }

    public function isPhoneVerified(): bool
    {
        return $this->phone_verified_at !== null;
    }

    /** Get (or lazily create) the user's primary wallet. */
    public function primaryWallet(string $currency = null): Wallet
    {
        $currency ??= config('platform.base_currency', 'XAF');

        return $this->wallets()->firstOrCreate(
            ['currency' => $currency],
            ['balance' => 0, 'locked_balance' => 0, 'status' => 'active'],
        );
    }

    public function defaultBeneficiary(): ?BeneficiaryAccount
    {
        return $this->beneficiaryAccounts()
            ->where('status', 'approved')
            ->orderByDesc('is_default')
            ->first();
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->take(2)
            ->map(fn ($p) => Str::upper(Str::substr($p, 0, 1)))->implode('');
    }
}
