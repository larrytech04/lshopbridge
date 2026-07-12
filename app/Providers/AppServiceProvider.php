<?php

namespace App\Providers;

use App\Services\Audit\AuditLogger;
use App\Services\Funding\FundingManager;
use App\Services\Payments\PaymentManager;
use App\Services\Settings\SettingsService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
        $this->app->singleton(AuditLogger::class);
        $this->app->singleton(PaymentManager::class);
        $this->app->singleton(FundingManager::class);
    }

    public function boot(): void
    {
        Paginator::useTailwind();

        $this->applyAdminMailSettings();

        // Super admins implicitly pass every authorization check.
        Gate::before(function ($user, $ability) {
            return $user->isSuperAdmin() ? true : null;
        });
    }

    /** Override the mail config from admin SMTP settings, when configured. */
    private function applyAdminMailSettings(): void
    {
        try {
            if (! Schema::hasTable('settings') || ! ($host = setting('mail_host'))) {
                return;
            }

            $password = setting('mail_password');
            if ($password) {
                try { $password = Crypt::decryptString($password); } catch (\Throwable $e) { /* legacy plaintext */ }
            }

            config([
                'mail.default' => 'smtp',
                'mail.mailers.smtp.host' => $host,
                'mail.mailers.smtp.port' => (int) setting('mail_port', 587),
                'mail.mailers.smtp.username' => setting('mail_username'),
                'mail.mailers.smtp.password' => $password,
                'mail.mailers.smtp.encryption' => setting('mail_encryption', 'tls') ?: null,
                'mail.from.address' => setting('mail_from_address') ?: config('mail.from.address'),
                'mail.from.name' => setting('mail_from_name') ?: setting('site_name', config('app.name')),
            ]);
        } catch (\Throwable $e) {
            // Never break booting over mail config.
        }
    }
}
