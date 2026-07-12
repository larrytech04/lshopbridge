<?php

use App\Http\Controllers\Webhooks\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Provider webhooks (stateless, signature-verified, CSRF-exempt)
|--------------------------------------------------------------------------
| Mounted under the "webhooks" prefix in bootstrap/app.php. Configure these
| URLs as the notify/callback URL in each provider dashboard, e.g.:
|   https://your-domain.com/webhooks/payments/flutterwave
*/

Route::post('/payments/{provider}', [WebhookController::class, 'payments'])
    ->name('payments');

Route::post('/funding/{provider}', [WebhookController::class, 'funding'])
    ->name('funding');
