<?php

use Illuminate\Support\Facades\Route;

// Auth
use App\Http\Controllers\Auth\RegisteredUserController;
use App\Http\Controllers\Auth\AgentRegistrationController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PhoneVerificationController;

// Public
use App\Http\Controllers\Public\HomeController;
use App\Http\Controllers\Public\PageController;
use App\Http\Controllers\Public\GuideController as PublicGuideController;
use App\Http\Controllers\Public\AgentDirectoryController;
use App\Http\Controllers\Public\CalculatorController;
use App\Http\Controllers\Public\ContactController;
use App\Http\Controllers\Public\NewsletterController;
use App\Http\Controllers\Public\GuestSupportController;
use App\Http\Controllers\Public\ReferralLeadController;

// Digital shop
use App\Http\Controllers\Shop\ShopController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\ShopOrderController;
use App\Http\Controllers\Shop\EsimController;
use App\Http\Controllers\Shop\EsimDeliveryController;
use App\Http\Controllers\Shop\EsimCompatibilityController;
use App\Http\Controllers\Admin\ShopCategoryController as AdminShopCategoryController;
use App\Http\Controllers\Admin\ShopProductController as AdminShopProductController;
use App\Http\Controllers\Admin\ShopOrderController as AdminShopOrderController;
use App\Http\Controllers\Admin\ImportSourceController as AdminImportSourceController;
use App\Http\Controllers\Admin\SupplierController as AdminSupplierController;
use App\Http\Controllers\Admin\EsimController as AdminEsimController;

// User dashboard
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\FundingController;
use App\Http\Controllers\BeneficiaryController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DisputeController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\SavedPaymentMethodController;
use App\Http\Controllers\HelpCenterController;
use App\Http\Controllers\RefundController;
use App\Http\Controllers\WithdrawalController;
use App\Http\Controllers\ShippingRequestController;
use App\Http\Controllers\TrackShipmentController;
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\PasskeyController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;

// Agent
use App\Http\Controllers\Agent\AgentDashboardController;
use App\Http\Controllers\Agent\AgentProfileController;
use App\Http\Controllers\Agent\AgentVerificationController;
use App\Http\Controllers\Agent\ShippingRateController;
use App\Http\Controllers\Agent\AgentLeadController;
use App\Http\Controllers\Agent\AgentReviewController;
use App\Http\Controllers\Agent\ShippingRequestController as AgentShippingRequestController;

// Admin
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\KycController as AdminKycController;
use App\Http\Controllers\Admin\KycDecisionTemplateController;
use App\Http\Controllers\Admin\AgentController as AdminAgentController;
use App\Http\Controllers\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Admin\FundingController as AdminFundingController;
use App\Http\Controllers\Admin\ExchangeRateController as AdminRateController;
use App\Http\Controllers\Admin\FeeController as AdminFeeController;
use App\Http\Controllers\Admin\PaymentMethodController as AdminMethodController;
use App\Http\Controllers\Admin\PaymentProviderController as AdminProviderController;
use App\Http\Controllers\Admin\CountryController as AdminCountryController;
use App\Http\Controllers\Admin\DepositChannelController as AdminChannelController;
use App\Http\Controllers\Admin\GuideController as AdminGuideController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\BannerController as AdminBannerController;
use App\Http\Controllers\Admin\PageController as AdminPageController;
use App\Http\Controllers\Admin\ReviewController as AdminReviewController;
use App\Http\Controllers\Admin\DisputeController as AdminDisputeController;
use App\Http\Controllers\Admin\RiskController as AdminRiskController;
use App\Http\Controllers\Admin\WebhookController as AdminWebhookController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\AuditLogController as AdminAuditController;
use App\Http\Controllers\Admin\BeneficiaryController as AdminBeneficiaryController;
use App\Http\Controllers\Admin\ConfirmablePasswordController;
use App\Http\Controllers\Admin\CurrencyController as AdminCurrencyController;
use App\Http\Controllers\Admin\ChinaWalletTypeController as AdminChinaWalletTypeController;
use App\Http\Controllers\Admin\ApiHealthController as AdminApiHealthController;
use App\Http\Controllers\Admin\SchedulerController as AdminSchedulerController;
use App\Http\Controllers\Admin\QueueController as AdminQueueController;
use App\Http\Controllers\Admin\CacheController as AdminCacheController;
use App\Http\Controllers\Admin\StorageController as AdminStorageController;
use App\Http\Controllers\Admin\SystemInfoController as AdminSystemInfoController;
use App\Http\Controllers\Admin\SystemOverviewController as AdminSystemOverviewController;
use App\Http\Controllers\Admin\SupportTicketController as AdminSupportTicketController;
use App\Http\Controllers\Admin\ReferralLeadController as AdminReferralLeadController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\SecurityEventController as AdminSecurityEventController;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/how-it-works', [PageController::class, 'howItWorks'])->name('how-it-works');
Route::get('/fund-alipay', [PageController::class, 'fundAlipay'])->name('public.fund');
Route::get('/payment-methods', [PageController::class, 'paymentMethods'])->name('public.payment-methods');
Route::get('/fees', [PageController::class, 'fees'])->name('public.fees');
Route::get('/faqs', [PageController::class, 'faqs'])->name('public.faqs');
Route::post('/calculator', [CalculatorController::class, 'quote'])->name('calculator');

Route::get('/china-guide', [PublicGuideController::class, 'index'])->name('guides.index');
Route::get('/china-guide/{guide:slug}', [PublicGuideController::class, 'show'])->name('guides.show');
Route::post('/china-guide/{guide:slug}/feedback', [PublicGuideController::class, 'feedback'])->name('guides.feedback');

Route::get('/shipping-agents', [AgentDirectoryController::class, 'index'])->name('agents.index');
Route::get('/shipping-agents/{agent:slug}', [AgentDirectoryController::class, 'show'])->name('agents.show');
Route::post('/shipping-agents/{agent:slug}/feedback', [AgentDirectoryController::class, 'guestReview'])->name('agents.guest-review');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');

// Legal & Policy Center. Individual legal documents live under /legal/{slug}
// (matches the deep-link anchor pattern, e.g. /legal/privacy-policy#section);
// /p/{slug} (pages.show) still resolves and 301-redirects legal-type pages
// here so no previously shared/bookmarked/indexed link breaks.
Route::get('/legal', [PageController::class, 'legalCenter'])->name('legal.index');
Route::get('/legal/{page:slug}', [PageController::class, 'showLegal'])->name('legal.show');
Route::get('/p/{page:slug}', [PageController::class, 'show'])->name('pages.show');

Route::post('/newsletter/subscribe', [NewsletterController::class, 'subscribe'])->name('newsletter.subscribe');
Route::get('/newsletter/unsubscribe/{token}', [NewsletterController::class, 'unsubscribe'])->name('newsletter.unsubscribe');

Route::get('/support/guest', [GuestSupportController::class, 'create'])->name('support.guest.create');
Route::post('/support/guest', [GuestSupportController::class, 'store'])->name('support.guest.store');

Route::get('/become-an-agent/interest', [ReferralLeadController::class, 'create'])->name('referral.create');
Route::post('/become-an-agent/interest', [ReferralLeadController::class, 'store'])->name('referral.store');

// Language + country selectors (apply site-wide via session/user)
Route::get('/locale/{locale}', [\App\Http\Controllers\LocalizationController::class, 'setLocale'])->name('locale.set');
Route::get('/region/{iso}', [\App\Http\Controllers\LocalizationController::class, 'setCountry'])->name('region.set');
Route::get('/onboard', [\App\Http\Controllers\LocalizationController::class, 'onboard'])->name('locale.onboard');

/*
|--------------------------------------------------------------------------
| Digital shop (catalog + cart are public; checkout/orders require auth)
|--------------------------------------------------------------------------
*/
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/c/{category:slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/shop/p/{product:slug}', [ShopController::class, 'show'])->name('shop.show');
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart', [CartController::class, 'add'])->name('cart.add');

// eSIM destination-selector landing page (reuses the shop catalog — see EsimController)
Route::get('/esim', [EsimController::class, 'index'])->name('esim.index');

// eSIM device compatibility checker (public — a pre-purchase discovery tool)
Route::get('/esim/compatibility', [EsimCompatibilityController::class, 'index'])->name('esim.compatibility.index');
Route::get('/esim/compatibility/models', [EsimCompatibilityController::class, 'models'])->name('esim.compatibility.models');
Route::post('/esim/compatibility/check', [EsimCompatibilityController::class, 'check'])->name('esim.compatibility.check');
Route::patch('/cart/{variant}', [CartController::class, 'update'])->name('cart.update');
Route::delete('/cart/{variant}', [CartController::class, 'remove'])->name('cart.remove');

/*
|--------------------------------------------------------------------------
| Guest auth
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('/register', [RegisteredUserController::class, 'store']);
    Route::get('/register/agent', [AgentRegistrationController::class, 'create'])->name('register.agent');
    Route::post('/register/agent', [AgentRegistrationController::class, 'store']);

    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);

    // Two-factor login challenge (reached mid-login, before any session is established)
    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'verify'])->name('two-factor.verify');
    Route::post('/two-factor-challenge/cancel', [TwoFactorChallengeController::class, 'cancel'])->name('two-factor.cancel');
    Route::post('/two-factor-challenge/passkey/options', [TwoFactorChallengeController::class, 'passkeyOptions'])->name('two-factor.passkey.options');
    Route::post('/two-factor-challenge/passkey', [TwoFactorChallengeController::class, 'passkeyVerify'])->name('two-factor.passkey.verify');

    // Google OAuth sign-in / sign-up
    Route::get('/auth/google/redirect', [\App\Http\Controllers\Auth\GoogleController::class, 'redirect'])->name('google.redirect');
    Route::get('/auth/google/callback', [\App\Http\Controllers\Auth\GoogleController::class, 'callback'])->name('google.callback');

    Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('/reset-password', [NewPasswordController::class, 'store'])->name('password.update');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->middleware('auth')->name('logout');

/*
|--------------------------------------------------------------------------
| Email verification
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/verify-email', [EmailVerificationController::class, 'notice'])->name('verification.notice');
    Route::get('/verify-email/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware('signed')->name('verification.verify');
    Route::post('/email/verification-notification', [EmailVerificationController::class, 'send'])
        ->middleware('throttle:6,1')->name('verification.send');
});

/*
|--------------------------------------------------------------------------
| Authenticated users (all roles)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Command palette search (Ctrl/Cmd+K)
    Route::get('/search', [\App\Http\Controllers\SearchController::class, 'search'])->name('search');

    // Admin impersonation: reachable while logged in AS the impersonated (non-admin) user.
    Route::post('/stop-impersonating', [AdminUserController::class, 'stopImpersonating'])->name('impersonate.stop');

    // Wallet + transactions
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/wallet/statement', [WalletController::class, 'statement'])->name('wallet.statement');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // Deposits
    Route::get('/deposit', [DepositController::class, 'index'])->name('deposit.index');
    Route::post('/deposit', [DepositController::class, 'store'])->name('deposit.store');
    Route::get('/deposit/{deposit}', [DepositController::class, 'show'])->name('deposit.show');
    Route::post('/deposit/{deposit}/proof', [DepositController::class, 'uploadProof'])->name('deposit.proof');

    // Withdraw funds (payout workflow: hold on request, admin approves + pays out)
    Route::get('/withdrawals', [WithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/withdrawals/quote', [WithdrawalController::class, 'quote'])->name('withdrawals.quote');
    Route::post('/withdrawals', [WithdrawalController::class, 'store'])->name('withdrawals.store');
    Route::post('/withdrawals/{withdrawal}/cancel', [WithdrawalController::class, 'cancel'])->name('withdrawals.cancel');

    // Saved payment methods (customer's own deposit-source shortcuts). URI is
    // distinct from the public "/payment-methods" marketing page (same path
    // would otherwise silently evict that route from the collection).
    Route::get('/saved-payment-methods', [SavedPaymentMethodController::class, 'index'])->name('payment-methods.index');
    Route::post('/saved-payment-methods', [SavedPaymentMethodController::class, 'store'])->name('payment-methods.store');
    Route::put('/saved-payment-methods/{savedPaymentMethod}', [SavedPaymentMethodController::class, 'update'])->name('payment-methods.update');
    Route::post('/saved-payment-methods/{savedPaymentMethod}/default', [SavedPaymentMethodController::class, 'makeDefault'])->name('payment-methods.default');
    Route::delete('/saved-payment-methods/{savedPaymentMethod}', [SavedPaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

    // China-wallet beneficiaries
    Route::get('/beneficiaries', [BeneficiaryController::class, 'index'])->name('beneficiaries.index');
    Route::post('/beneficiaries', [BeneficiaryController::class, 'store'])->name('beneficiaries.store');
    Route::put('/beneficiaries/{beneficiary}', [BeneficiaryController::class, 'update'])->name('beneficiaries.update');
    Route::post('/beneficiaries/{beneficiary}/default', [BeneficiaryController::class, 'makeDefault'])->name('beneficiaries.default');
    Route::delete('/beneficiaries/{beneficiary}', [BeneficiaryController::class, 'destroy'])->name('beneficiaries.destroy');

    // Funding (China wallet), requires verified phone (KYC level 1+)
    Route::get('/fund', [FundingController::class, 'index'])->name('funding.index');
    Route::get('/fund/new', [FundingController::class, 'create'])->middleware('kyc:1')->name('funding.create');
    Route::post('/fund/quote', [FundingController::class, 'quote'])->name('funding.quote');
    Route::post('/fund', [FundingController::class, 'store'])->middleware('kyc:1')->name('funding.store');
    Route::get('/fund/{funding}', [FundingController::class, 'show'])->name('funding.show');

    // Verification (KYC + phone)
    Route::get('/verification', [VerificationController::class, 'index'])->name('verification.index');
    Route::post('/verification', [VerificationController::class, 'store'])->name('verification.store');
    Route::post('/verification/phone/send', [PhoneVerificationController::class, 'send'])->name('verification.phone.send');
    Route::post('/verification/phone/verify', [PhoneVerificationController::class, 'verify'])->name('verification.phone.verify');

    // Disputes / support
    Route::get('/support', [DisputeController::class, 'index'])->name('disputes.index');
    Route::post('/support', [DisputeController::class, 'store'])->name('disputes.store');
    Route::get('/support/{dispute}', [DisputeController::class, 'show'])->name('disputes.show');
    Route::post('/support/{dispute}/reply', [DisputeController::class, 'reply'])->name('disputes.reply');

    // Agent marketplace (in dashboard)
    Route::get('/marketplace', [MarketplaceController::class, 'index'])->name('marketplace.index');
    Route::get('/marketplace/{agent:slug}', [MarketplaceController::class, 'show'])->name('marketplace.show');
    Route::post('/marketplace/{agent:slug}/contact', [MarketplaceController::class, 'contact'])->name('marketplace.contact');
    Route::post('/marketplace/{agent:slug}/review', [MarketplaceController::class, 'review'])->name('marketplace.review');
    Route::post('/marketplace/leads/{lead}/message', [MarketplaceController::class, 'sendMessage'])->name('marketplace.leads.message');
    Route::get('/marketplace/leads/{lead}/poll', [MarketplaceController::class, 'pollMessages'])->name('marketplace.leads.poll');
    Route::post('/marketplace/leads/{lead}/complete', [MarketplaceController::class, 'confirmComplete'])->name('marketplace.leads.complete');

    // Shipping requests (multi-agent quote workflow, distinct from the 1:1 marketplace lead chat above)
    Route::get('/shipping-requests', [ShippingRequestController::class, 'index'])->name('shipping-requests.index');
    Route::get('/shipping-requests/create', [ShippingRequestController::class, 'create'])->name('shipping-requests.create');
    Route::post('/shipping-requests', [ShippingRequestController::class, 'store'])->name('shipping-requests.store');
    Route::get('/shipping-requests/{shippingRequest}', [ShippingRequestController::class, 'show'])->name('shipping-requests.show');
    Route::post('/shipping-requests/{shippingRequest}/cancel', [ShippingRequestController::class, 'cancel'])->name('shipping-requests.cancel');
    Route::post('/shipping-requests/{shippingRequest}/quotes/{quote}/accept', [ShippingRequestController::class, 'acceptQuote'])->name('shipping-requests.quotes.accept');
    Route::get('/shipping-requests/{shippingRequest}/documents/{index}', [ShippingRequestController::class, 'downloadDocument'])->name('shipping-requests.documents.show');

    Route::get('/shipments/track', [TrackShipmentController::class, 'index'])->name('shipments.track');

    // Learning center (in dashboard)
    Route::get('/learn', [LearningController::class, 'index'])->name('learning.index');
    Route::get('/learn/{guide:slug}', [LearningController::class, 'show'])->name('learning.show');

    // Help center (searchable FAQs, in-app)
    Route::get('/help', [HelpCenterController::class, 'index'])->name('help.index');

    // Customer-initiated refund requests
    Route::get('/refunds', [RefundController::class, 'index'])->name('refunds.index');
    Route::post('/refunds', [RefundController::class, 'store'])->name('refunds.store');

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'read'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'readAll'])->name('notifications.readAll');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');
    Route::put('/profile/shortcuts', [ProfileController::class, 'updateShortcuts'])->name('profile.shortcuts');
    Route::post('/profile/shortcuts/reset', [ProfileController::class, 'resetShortcuts'])->name('profile.shortcuts.reset');
    Route::get('/referrals', [ProfileController::class, 'referrals'])->name('referrals.index');
    Route::delete('/profile/photo', [ProfileController::class, 'removePhoto'])->name('profile.photo.remove');
    Route::put('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences');
    Route::delete('/profile', [ProfileController::class, 'deleteAccount'])->name('profile.delete');

    // Security Center
    Route::get('/security', [SecurityController::class, 'index'])->name('security.index');
    Route::put('/security/pin', [SecurityController::class, 'updatePin'])->name('security.pin');
    Route::post('/security/forgot-password', [SecurityController::class, 'forgotPassword'])->name('security.forgot-password');
    Route::delete('/security/sessions/revoke-others', [SecurityController::class, 'revokeOtherSessions'])->name('security.sessions.revoke-others');
    Route::delete('/security/sessions/{session}', [SecurityController::class, 'revokeSession'])->name('security.sessions.revoke');

    // Two-factor authentication management
    Route::get('/security/two-factor', [TwoFactorController::class, 'show'])->name('security.two-factor.show');
    Route::post('/security/two-factor/confirm', [TwoFactorController::class, 'confirm'])->name('security.two-factor.confirm');
    Route::delete('/security/two-factor', [TwoFactorController::class, 'disable'])->name('security.two-factor.disable');
    Route::post('/security/two-factor/recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('security.two-factor.recovery-codes');

    // Passkeys (WebAuthn)
    Route::get('/security/passkeys', [PasskeyController::class, 'index'])->name('security.passkeys.index');
    Route::post('/security/passkeys/options', [PasskeyController::class, 'registerOptions'])->name('security.passkeys.register-options');
    Route::post('/security/passkeys', [PasskeyController::class, 'store'])->name('security.passkeys.store');
    Route::delete('/security/passkeys/{passkey}', [PasskeyController::class, 'destroy'])->name('security.passkeys.destroy');

    // Secure private file streaming (KYC docs, proofs, receipts)
    Route::get('/files/{kind}/{id}', [SecureFileController::class, 'show'])->name('files.show');

    // Shop checkout + digital orders
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('shop.checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('shop.checkout.store');
    Route::get('/shop/orders', [ShopOrderController::class, 'index'])->name('shop.orders.index');
    Route::get('/shop/orders/digital', [ShopOrderController::class, 'digital'])->name('shop.orders.digital');
    Route::get('/shop/orders/{order}', [ShopOrderController::class, 'show'])->name('shop.orders.show');

    // My eSIMs — owner-gated install pages. QR/activation data is never exposed via a public URL.
    Route::get('/esim/mine', [EsimDeliveryController::class, 'index'])->name('esim.mine.index');
    Route::get('/esim/mine/{provisioning}', [EsimDeliveryController::class, 'show'])->name('esim.mine.show');
    Route::get('/esim/mine/{provisioning}/qr', [EsimDeliveryController::class, 'qr'])->name('esim.mine.qr');

    // Wishlist
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist.index');
    Route::post('/wishlist/{product:slug}', [WishlistController::class, 'store'])->name('wishlist.store');
    Route::delete('/wishlist/{product:slug}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
});

/*
|--------------------------------------------------------------------------
| Agent area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:agent'])->prefix('agent')->name('agent.')->group(function () {
    Route::get('/', [AgentDashboardController::class, 'index'])->name('dashboard');
    Route::get('/profile', [AgentProfileController::class, 'edit'])->name('profile');
    Route::put('/profile', [AgentProfileController::class, 'update'])->name('profile.update');
    Route::get('/verification', [AgentVerificationController::class, 'edit'])->name('verification');
    Route::post('/verification', [AgentVerificationController::class, 'store'])->name('verification.store');
    Route::resource('rates', ShippingRateController::class)->except(['show', 'create', 'edit']);
    Route::get('/leads', [AgentLeadController::class, 'index'])->name('leads.index');
    Route::get('/leads/{lead}', [AgentLeadController::class, 'show'])->name('leads.show');
    Route::put('/leads/{lead}', [AgentLeadController::class, 'update'])->name('leads.update');
    Route::post('/leads/{lead}/message', [AgentLeadController::class, 'sendMessage'])->name('leads.message');
    Route::get('/leads/{lead}/poll', [AgentLeadController::class, 'pollMessages'])->name('leads.poll');
    Route::get('/reviews', [AgentReviewController::class, 'index'])->name('reviews.index');

    Route::get('/shipping-requests', [AgentShippingRequestController::class, 'index'])->name('shipping-requests.index');
    Route::get('/shipping-requests/{shippingRequest}', [AgentShippingRequestController::class, 'show'])->name('shipping-requests.show');
    Route::post('/shipping-requests/{shippingRequest}/quote', [AgentShippingRequestController::class, 'quote'])->name('shipping-requests.quote');
    Route::post('/shipping-requests/{shippingRequest}/advance', [AgentShippingRequestController::class, 'advance'])->name('shipping-requests.advance');
    Route::post('/shipping-quotes/{quote}/withdraw', [AgentShippingRequestController::class, 'withdrawQuote'])->name('shipping-quotes.withdraw');
});

/*
|--------------------------------------------------------------------------
| Admin area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,super_admin', 'admin.mfa'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/widgets', [AdminDashboardController::class, 'updateWidgets'])->name('dashboard.widgets');
    Route::get('/dashboard/export', [AdminDashboardController::class, 'exportReport'])->name('dashboard.export');
    Route::get('/dashboard/transaction/{type}/{id}', [AdminDashboardController::class, 'transactionDetail'])->name('dashboard.transaction');

    // Users + wallets
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::post('/users', [AdminUserController::class, 'store'])->name('users.store');
    Route::get('/users/export', [AdminUserController::class, 'exportCsv'])->name('users.export');
    Route::post('/users/bulk-action', [AdminUserController::class, 'bulkAction'])->name('users.bulk-action');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::get('/users/{user}/row-detail', [AdminUserController::class, 'rowDetail'])->name('users.row-detail');
    Route::post('/users/{user}/tags', [AdminUserController::class, 'assignTags'])->name('users.tags');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/wallet', [AdminUserController::class, 'adjustWallet'])->name('users.wallet');
    Route::post('/users/{user}/wallet/freeze', [AdminUserController::class, 'toggleWalletFreeze'])->name('users.wallet.freeze');
    Route::post('/users/{user}/status', [AdminUserController::class, 'quickStatus'])->name('users.status');
    Route::post('/users/{user}/notes', [AdminUserController::class, 'updateNotes'])->name('users.notes');
    Route::post('/users/{user}/reset-password', [AdminUserController::class, 'resetPassword'])->name('users.reset-password');
    Route::post('/users/{user}/reset-2fa', [AdminUserController::class, 'resetTwoFactor'])->name('users.reset-2fa');
    Route::post('/users/{user}/impersonate', [AdminUserController::class, 'impersonate'])->name('users.impersonate');
    Route::post('/users/{user}/notify', [AdminUserController::class, 'notify'])->name('users.notify');
    Route::delete('/users/{user}/sessions/{session}', [AdminUserController::class, 'revokeSession'])->name('users.sessions.revoke');
    Route::get('/users/{user}/activity/export', [AdminUserController::class, 'exportActivity'])->name('users.activity.export');

    // KYC — static/named routes MUST precede the /kyc/{kyc} wildcard below.
    Route::get('/kyc', [AdminKycController::class, 'index'])->name('kyc.index');
    Route::get('/kyc/export', [AdminKycController::class, 'exportCsv'])->name('kyc.export');
    Route::post('/kyc/bulk-assign', [AdminKycController::class, 'bulkAssign'])->name('kyc.bulk-assign');
    Route::post('/kyc/bulk-priority', [AdminKycController::class, 'bulkPriority'])->name('kyc.bulk-priority');
    Route::get('/kyc/templates', [KycDecisionTemplateController::class, 'index'])->name('kyc.templates.index');
    Route::post('/kyc/templates', [KycDecisionTemplateController::class, 'store'])->name('kyc.templates.store');
    Route::put('/kyc/templates/{template}', [KycDecisionTemplateController::class, 'update'])->name('kyc.templates.update');
    Route::delete('/kyc/templates/{template}', [KycDecisionTemplateController::class, 'destroy'])->name('kyc.templates.destroy');

    Route::get('/kyc/{kyc}', [AdminKycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc/{kyc}/decide', [AdminKycController::class, 'decide'])->name('kyc.decide');
    Route::post('/kyc/{kyc}/assign', [AdminKycController::class, 'assign'])->name('kyc.assign');
    Route::post('/kyc/{kyc}/priority', [AdminKycController::class, 'setPriority'])->name('kyc.priority');
    Route::post('/kyc/{kyc}/lock', [AdminKycController::class, 'lock'])->name('kyc.lock');
    Route::post('/kyc/{kyc}/unlock', [AdminKycController::class, 'unlock'])->name('kyc.unlock');
    Route::post('/kyc/{kyc}/heartbeat', [AdminKycController::class, 'heartbeat'])->name('kyc.heartbeat');
    Route::post('/kyc/{kyc}/notes', [AdminKycController::class, 'storeNote'])->name('kyc.notes');
    Route::post('/kyc/{kyc}/review-check', [AdminKycController::class, 'reviewCheck'])->name('kyc.review-check');
    Route::post('/kyc/{kyc}/expiry', [AdminKycController::class, 'setDocumentExpiry'])->name('kyc.expiry');
    Route::post('/kyc/{kyc}/reveal', [AdminKycController::class, 'revealField'])->name('kyc.reveal');

    // Beneficiaries (China wallet review)
    Route::get('/beneficiaries', [AdminBeneficiaryController::class, 'index'])->name('beneficiaries.index');
    Route::get('/beneficiaries/export', [AdminBeneficiaryController::class, 'exportCsv'])->name('beneficiaries.export');
    Route::post('/beneficiaries/bulk-action', [AdminBeneficiaryController::class, 'bulkAction'])->name('beneficiaries.bulk-action');

    Route::get('/beneficiaries/{beneficiary}/row-detail', [AdminBeneficiaryController::class, 'rowDetail'])->name('beneficiaries.row-detail');
    Route::post('/beneficiaries/{beneficiary}/approve', [AdminBeneficiaryController::class, 'approve'])->name('beneficiaries.approve');
    Route::post('/beneficiaries/{beneficiary}/reject', [AdminBeneficiaryController::class, 'reject'])->name('beneficiaries.reject');
    Route::post('/beneficiaries/{beneficiary}/suspend', [AdminBeneficiaryController::class, 'suspend'])->name('beneficiaries.suspend');
    Route::post('/beneficiaries/{beneficiary}/restore', [AdminBeneficiaryController::class, 'restore'])->name('beneficiaries.restore');
    Route::post('/beneficiaries/{beneficiary}/request-info', [AdminBeneficiaryController::class, 'requestInfo'])->name('beneficiaries.request-info');
    Route::post('/beneficiaries/{beneficiary}/review-check', [AdminBeneficiaryController::class, 'reviewCheck'])->name('beneficiaries.review-check');
    Route::post('/beneficiaries/{beneficiary}/notes', [AdminBeneficiaryController::class, 'updateNotes'])->name('beneficiaries.notes');
    Route::post('/beneficiaries/{beneficiary}/reveal', [AdminBeneficiaryController::class, 'revealField'])->name('beneficiaries.reveal');
    Route::delete('/beneficiaries/{beneficiary}', [AdminBeneficiaryController::class, 'destroy'])->name('beneficiaries.destroy');

    // Agents
    Route::get('/agents', [AdminAgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/export', [AdminAgentController::class, 'exportCsv'])->name('agents.export');
    Route::post('/agents/bulk-action', [AdminAgentController::class, 'bulkAction'])->name('agents.bulk-action');

    Route::get('/agents/{agent}', [AdminAgentController::class, 'show'])->name('agents.show');
    Route::get('/agents/{agent}/row-detail', [AdminAgentController::class, 'rowDetail'])->name('agents.row-detail');
    Route::post('/agents/{agent}/approve', [AdminAgentController::class, 'approve'])->name('agents.approve');
    Route::post('/agents/{agent}/reject', [AdminAgentController::class, 'reject'])->name('agents.reject');
    Route::post('/agents/{agent}/suspend', [AdminAgentController::class, 'suspend'])->name('agents.suspend');
    Route::post('/agents/{agent}/restore', [AdminAgentController::class, 'restore'])->name('agents.restore');
    Route::post('/agents/{agent}/request-info', [AdminAgentController::class, 'requestInfo'])->name('agents.request-info');
    Route::post('/agents/{agent}/feature', [AdminAgentController::class, 'toggleFeature'])->name('agents.feature');
    Route::post('/agents/{agent}/feature-settings', [AdminAgentController::class, 'updateFeatureSettings'])->name('agents.feature-settings');
    Route::post('/agents/{agent}/notes', [AdminAgentController::class, 'updateNotes'])->name('agents.notes');
    Route::post('/agents/{agent}/notify', [AdminAgentController::class, 'notify'])->name('agents.notify');
    Route::delete('/agents/{agent}', [AdminAgentController::class, 'destroy'])->name('agents.destroy');

    // Deposits
    Route::get('/deposits', [AdminDepositController::class, 'index'])->name('deposits.index');
    Route::get('/deposits/export', [AdminDepositController::class, 'exportCsv'])->name('deposits.export');
    Route::post('/deposits/bulk-action', [AdminDepositController::class, 'bulkAction'])->name('deposits.bulk-action');

    Route::get('/deposits/{deposit}', [AdminDepositController::class, 'show'])->name('deposits.show');
    Route::get('/deposits/{deposit}/row-detail', [AdminDepositController::class, 'rowDetail'])->name('deposits.row-detail');
    Route::post('/deposits/{deposit}/confirm', [AdminDepositController::class, 'confirm'])->name('deposits.confirm');
    Route::post('/deposits/{deposit}/reject', [AdminDepositController::class, 'reject'])->name('deposits.reject');
    Route::post('/deposits/{deposit}/under-review', [AdminDepositController::class, 'placeUnderReview'])->name('deposits.under-review');
    Route::post('/deposits/{deposit}/request-info', [AdminDepositController::class, 'requestInfo'])->name('deposits.request-info');
    Route::post('/deposits/{deposit}/escalate', [AdminDepositController::class, 'escalate'])->name('deposits.escalate');
    Route::post('/deposits/{deposit}/investigate', [AdminDepositController::class, 'markForInvestigation'])->name('deposits.investigate');
    Route::post('/deposits/{deposit}/assign', [AdminDepositController::class, 'assign'])->name('deposits.assign');
    Route::post('/deposits/{deposit}/notes', [AdminDepositController::class, 'addNote'])->name('deposits.notes');
    Route::post('/deposits/{deposit}/refund', [AdminDepositController::class, 'refund'])->name('deposits.refund');
    Route::post('/deposits/{deposit}/reverse', [AdminDepositController::class, 'reverse'])->name('deposits.reverse');
    Route::post('/deposits/{deposit}/requery', [AdminDepositController::class, 'requery'])->name('deposits.requery');
    Route::post('/deposits/{deposit}/reconcile', [AdminDepositController::class, 'reconcile'])->name('deposits.reconcile');

    // Withdrawals
    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::post('/withdrawals/{withdrawal}/approve', [AdminWithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/reject', [AdminWithdrawalController::class, 'reject'])->name('withdrawals.reject');
    Route::post('/withdrawals/{withdrawal}/mark-paid', [AdminWithdrawalController::class, 'markPaid'])->name('withdrawals.mark-paid');

    // Funding requests
    Route::get('/funding', [AdminFundingController::class, 'index'])->name('funding.index');
    Route::get('/funding/export', [AdminFundingController::class, 'exportCsv'])->name('funding.export');
    Route::post('/funding/bulk-action', [AdminFundingController::class, 'bulkAction'])->name('funding.bulk-action');

    Route::get('/funding/{funding}', [AdminFundingController::class, 'show'])->name('funding.show');
    Route::get('/funding/{funding}/row-detail', [AdminFundingController::class, 'rowDetail'])->name('funding.row-detail');
    Route::post('/funding/{funding}/complete', [AdminFundingController::class, 'complete'])->name('funding.complete');
    Route::post('/funding/{funding}/retry', [AdminFundingController::class, 'retry'])->name('funding.retry');
    Route::post('/funding/{funding}/refund', [AdminFundingController::class, 'refund'])->name('funding.refund');
    Route::post('/funding/{funding}/mark-failed', [AdminFundingController::class, 'markFailed'])->name('funding.mark-failed');
    Route::post('/funding/{funding}/cancel', [AdminFundingController::class, 'cancel'])->name('funding.cancel');
    Route::post('/funding/{funding}/under-review', [AdminFundingController::class, 'placeUnderReview'])->name('funding.under-review');
    Route::post('/funding/{funding}/request-info', [AdminFundingController::class, 'requestInfo'])->name('funding.request-info');
    Route::post('/funding/{funding}/escalate', [AdminFundingController::class, 'escalate'])->name('funding.escalate');
    Route::post('/funding/{funding}/investigate', [AdminFundingController::class, 'markForInvestigation'])->name('funding.investigate');
    Route::post('/funding/{funding}/assign', [AdminFundingController::class, 'assign'])->name('funding.assign');
    Route::post('/funding/{funding}/notes', [AdminFundingController::class, 'addNote'])->name('funding.notes');
    Route::post('/funding/{funding}/requery', [AdminFundingController::class, 'requery'])->name('funding.requery');
    Route::post('/funding/{funding}/reconcile', [AdminFundingController::class, 'reconcile'])->name('funding.reconcile');

    // Rates, fees, methods, providers, countries, channels
    Route::resource('rates', AdminRateController::class)->except(['show']);
    Route::get('/rates/export', [AdminRateController::class, 'exportCsv'])->name('rates.export');
    Route::post('/rates/calculate', [AdminRateController::class, 'calculate'])->name('rates.calculate');
    Route::post('/rates/bulk-action', [AdminRateController::class, 'bulkAction'])->name('rates.bulk-action');
    Route::post('/rates/schedules', [AdminRateController::class, 'storeSchedule'])->name('rates.schedules.store');
    Route::post('/rates/schedules/{schedule}/cancel', [AdminRateController::class, 'cancelSchedule'])->name('rates.schedules.cancel');
    Route::get('/rates/{rate}/row-detail', [AdminRateController::class, 'rowDetail'])->name('rates.row-detail');
    Route::post('/rates/{rate}/toggle-active', [AdminRateController::class, 'toggleActive'])->name('rates.toggle-active');
    Route::resource('fees', AdminFeeController::class)->except(['show']);
    Route::get('/fees/export', [AdminFeeController::class, 'exportCsv'])->name('fees.export');
    Route::post('/fees/calculate', [AdminFeeController::class, 'calculate'])->name('fees.calculate');
    Route::post('/fees/bulk-action', [AdminFeeController::class, 'bulkAction'])->name('fees.bulk-action');
    Route::post('/fees/schedules', [AdminFeeController::class, 'storeSchedule'])->name('fees.schedules.store');
    Route::post('/fees/schedules/{schedule}/cancel', [AdminFeeController::class, 'cancelSchedule'])->name('fees.schedules.cancel');
    Route::post('/fees/exemptions', [AdminFeeController::class, 'storeExemption'])->name('fees.exemptions.store');
    Route::post('/fees/exemptions/{exemption}/revoke', [AdminFeeController::class, 'revokeExemption'])->name('fees.exemptions.revoke');
    Route::get('/fees/{fee}/row-detail', [AdminFeeController::class, 'rowDetail'])->name('fees.row-detail');
    Route::post('/fees/{fee}/toggle-active', [AdminFeeController::class, 'toggleActive'])->name('fees.toggle-active');
    Route::post('/fees/{fee}/duplicate', [AdminFeeController::class, 'duplicate'])->name('fees.duplicate');
    Route::post('/fees/{fee}/test', [AdminFeeController::class, 'test'])->name('fees.test');
    // Platform Configuration: password re-confirmation gate for sensitive actions.
    Route::get('/confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('/confirm-password', [ConfirmablePasswordController::class, 'store']);

    // Payment Methods
    Route::get('/payment-methods', [AdminMethodController::class, 'index'])->name('methods.index');
    Route::post('/payment-methods', [AdminMethodController::class, 'store'])->name('methods.store');
    Route::put('/payment-methods/{method}', [AdminMethodController::class, 'update'])->name('methods.update');
    Route::post('/payment-methods/{method}/status', [AdminMethodController::class, 'setStatus'])->name('methods.status');
    Route::post('/payment-methods/{method}/restore', [AdminMethodController::class, 'restore'])->name('methods.restore')->withTrashed();
    Route::delete('/payment-methods/{method}', [AdminMethodController::class, 'destroy'])->name('methods.destroy');

    // Payment Providers — credential edits + connection tests require a recently-confirmed password.
    Route::get('/providers', [AdminProviderController::class, 'index'])->name('providers.index');
    Route::post('/providers/{provider}/active', [AdminProviderController::class, 'setActive'])->name('providers.active');
    Route::delete('/providers/{provider}', [AdminProviderController::class, 'destroy'])->name('providers.destroy');
    Route::post('/providers/{provider}/restore', [AdminProviderController::class, 'restore'])->name('providers.restore')->withTrashed();
    Route::middleware(\Illuminate\Auth\Middleware\RequirePassword::using('admin.password.confirm', 900))->group(function () {
        Route::put('/providers/{provider}', [AdminProviderController::class, 'update'])->name('providers.update');
        Route::post('/providers/{provider}/test-connection', [AdminProviderController::class, 'testConnection'])->name('providers.test-connection');
    });

    // Countries & Regions — permanent reference data, no delete; only launch_status transitions.
    Route::resource('countries', AdminCountryController::class)->except(['show', 'create', 'edit', 'destroy']);
    Route::post('/countries/{country}/status', [AdminCountryController::class, 'setStatus'])->name('countries.status');

    // Currencies — metadata/availability only; exchange rates stay on the Exchange Rates page.
    Route::get('/currencies', [AdminCurrencyController::class, 'index'])->name('currencies.index');
    Route::post('/currencies', [AdminCurrencyController::class, 'store'])->name('currencies.store');
    Route::put('/currencies/{currency}', [AdminCurrencyController::class, 'update'])->name('currencies.update');
    Route::post('/currencies/{currency}/active', [AdminCurrencyController::class, 'setActive'])->name('currencies.active');

    // Deposit Accounts (MoMo numbers, crypto wallets, bank accounts) — reveal is password-gated + always audited.
    Route::get('/channels', [AdminChannelController::class, 'index'])->name('channels.index');
    Route::post('/channels/{type}', [AdminChannelController::class, 'store'])->name('channels.store');
    Route::put('/channels/{type}/{id}', [AdminChannelController::class, 'update'])->name('channels.update');
    Route::post('/channels/{type}/{id}/active', [AdminChannelController::class, 'setActive'])->name('channels.active');
    Route::delete('/channels/{type}/{id}', [AdminChannelController::class, 'destroy'])->name('channels.destroy');
    Route::post('/channels/{type}/{id}/restore', [AdminChannelController::class, 'restore'])->name('channels.restore');
    Route::middleware(\Illuminate\Auth\Middleware\RequirePassword::using('admin.password.confirm', 900))->group(function () {
        Route::post('/channels/{type}/{id}/reveal', [AdminChannelController::class, 'reveal'])->name('channels.reveal');
    });

    // China Wallet Types — configures the existing 3 wallet types only (alipay/wechat/other).
    Route::get('/china-wallet-types', [AdminChinaWalletTypeController::class, 'index'])->name('china-wallet-types.index');
    Route::post('/china-wallet-types', [AdminChinaWalletTypeController::class, 'store'])->name('china-wallet-types.store');
    Route::put('/china-wallet-types/{wallet}', [AdminChinaWalletTypeController::class, 'update'])->name('china-wallet-types.update');
    Route::post('/china-wallet-types/{wallet}/active', [AdminChinaWalletTypeController::class, 'setActive'])->name('china-wallet-types.active');

    // API Health — honest reuse of the dashboard's Provider Health data (no fabricated metrics).
    Route::get('/api-health', [AdminApiHealthController::class, 'index'])->name('api-health.index');

    // System & Operations
    Route::get('/system', [AdminSystemOverviewController::class, 'index'])->name('system.index');
    Route::get('/system/info', [AdminSystemInfoController::class, 'index'])->name('system-info.index');

    Route::get('/system/scheduler', [AdminSchedulerController::class, 'index'])->name('scheduler.index');

    Route::get('/system/queues', [AdminQueueController::class, 'index'])->name('queues.index');
    Route::post('/system/queues/{uuid}/retry', [AdminQueueController::class, 'retry'])->name('queues.retry');
    Route::delete('/system/queues/{uuid}', [AdminQueueController::class, 'destroy'])->name('queues.destroy');

    Route::get('/system/storage', [AdminStorageController::class, 'index'])->name('storage.index');

    Route::get('/system/cache', [AdminCacheController::class, 'index'])->name('cache.index');
    Route::post('/system/cache/{key}', [AdminCacheController::class, 'clear'])->name('cache.clear');

    // Content
    Route::resource('guides', AdminGuideController::class)->except(['show']);
    Route::post('guides/{guide}/restore', [AdminGuideController::class, 'restore'])->name('guides.restore')->withTrashed();
    Route::resource('faqs', AdminFaqController::class)->except(['show', 'create', 'edit']);
    Route::resource('banners', AdminBannerController::class)->except(['show', 'create', 'edit']);
    Route::post('banners/{banner}/restore', [AdminBannerController::class, 'restore'])->name('banners.restore')->withTrashed();
    Route::resource('pages', AdminPageController::class)->except(['show']);
    Route::post('pages/{page}/restore', [AdminPageController::class, 'restore'])->name('pages.restore')->withTrashed();
    Route::post('pages/{page}/revisions/{revision}/restore', [AdminPageController::class, 'restoreRevision'])->name('pages.revisions.restore');

    // Site content (CMS): named text blocks, testimonials, and How It Works process steps.
    Route::get('content', [\App\Http\Controllers\Admin\ContentController::class, 'index'])->name('content.index');
    Route::put('content', [\App\Http\Controllers\Admin\ContentController::class, 'update'])->name('content.update');
    Route::post('content/testimonials', [\App\Http\Controllers\Admin\ContentController::class, 'storeTestimonial'])->name('content.testimonials.store');
    Route::put('content/testimonials/{testimonial}', [\App\Http\Controllers\Admin\ContentController::class, 'updateTestimonial'])->name('content.testimonials.update');
    Route::delete('content/testimonials/{testimonial}', [\App\Http\Controllers\Admin\ContentController::class, 'destroyTestimonial'])->name('content.testimonials.destroy');
    Route::post('content/steps', [\App\Http\Controllers\Admin\ContentController::class, 'storeStep'])->name('content.steps.store');
    Route::put('content/steps/{step}', [\App\Http\Controllers\Admin\ContentController::class, 'updateStep'])->name('content.steps.update');
    Route::delete('content/steps/{step}', [\App\Http\Controllers\Admin\ContentController::class, 'destroyStep'])->name('content.steps.destroy');

    // Digital shop management — Commerce Operations module
    Route::resource('shop/categories', AdminShopCategoryController::class)->except(['show', 'create', 'edit'])->names('shop.categories');
    Route::get('shop/categories/{category}/row-detail', [AdminShopCategoryController::class, 'rowDetail'])->name('shop.categories.row-detail');
    Route::post('shop/categories/{category}/toggle-active', [AdminShopCategoryController::class, 'toggleActive'])->name('shop.categories.toggle-active');
    Route::post('shop/categories/reorder', [AdminShopCategoryController::class, 'reorder'])->name('shop.categories.reorder');

    Route::resource('shop/products', AdminShopProductController::class)->except(['show'])->names('shop.products');
    Route::get('shop/products/export', [AdminShopProductController::class, 'exportCsv'])->name('shop.products.export');
    Route::post('shop/products/bulk-action', [AdminShopProductController::class, 'bulkAction'])->name('shop.products.bulk-action');
    Route::get('shop/products/{product}/row-detail', [AdminShopProductController::class, 'rowDetail'])->name('shop.products.row-detail');
    Route::post('shop/products/{product}/toggle-active', [AdminShopProductController::class, 'toggleActive'])->name('shop.products.toggle-active');
    Route::post('shop/products/{product}/schedule', [AdminShopProductController::class, 'schedule'])->name('shop.products.schedule');
    Route::post('shop/products/{product}/duplicate', [AdminShopProductController::class, 'duplicate'])->name('shop.products.duplicate');

    Route::get('shop/orders', [AdminShopOrderController::class, 'index'])->name('shop.orders.index');
    Route::get('shop/orders/export', [AdminShopOrderController::class, 'exportCsv'])->name('shop.orders.export');
    Route::post('shop/orders/bulk-action', [AdminShopOrderController::class, 'bulkAction'])->name('shop.orders.bulk-action');
    Route::get('shop/orders/{order}', [AdminShopOrderController::class, 'show'])->name('shop.orders.show');
    Route::get('shop/orders/{order}/row-detail', [AdminShopOrderController::class, 'rowDetail'])->name('shop.orders.row-detail');
    Route::post('shop/orders/{order}/fulfill', [AdminShopOrderController::class, 'fulfill'])->name('shop.orders.fulfill');
    Route::post('shop/orders/{order}/start-processing', [AdminShopOrderController::class, 'startProcessing'])->name('shop.orders.start-processing');
    Route::post('shop/orders/{order}/assign', [AdminShopOrderController::class, 'assign'])->name('shop.orders.assign');
    Route::post('shop/orders/{order}/mark-shipped', [AdminShopOrderController::class, 'markShipped'])->name('shop.orders.mark-shipped');
    Route::post('shop/orders/{order}/mark-delivered', [AdminShopOrderController::class, 'markDelivered'])->name('shop.orders.mark-delivered');
    Route::post('shop/orders/{order}/resend-delivery', [AdminShopOrderController::class, 'resendDelivery'])->name('shop.orders.resend-delivery');
    Route::post('shop/orders/{order}/cancel', [AdminShopOrderController::class, 'cancel'])->name('shop.orders.cancel');
    Route::post('shop/orders/{order}/request-refund', [AdminShopOrderController::class, 'requestRefund'])->name('shop.orders.request-refund');
    Route::post('shop/orders/{order}/refund', [AdminShopOrderController::class, 'refund'])->name('shop.orders.refund');
    Route::post('shop/orders/{order}/refunds/{refund}/reject', [AdminShopOrderController::class, 'rejectRefund'])->name('shop.orders.refunds.reject');
    Route::post('shop/orders/{order}/notes', [AdminShopOrderController::class, 'addNote'])->name('shop.orders.notes');

    Route::get('shop/imports', [AdminImportSourceController::class, 'index'])->name('shop.imports.index');
    Route::post('shop/imports/{source}/connect', [AdminImportSourceController::class, 'connect'])->name('shop.imports.connect');
    Route::post('shop/imports/{source}/disconnect', [AdminImportSourceController::class, 'disconnect'])->name('shop.imports.disconnect');
    Route::post('shop/imports/{source}/test-connection', [AdminImportSourceController::class, 'testConnection'])->name('shop.imports.test-connection');
    Route::post('shop/imports/{source}/auto-sync', [AdminImportSourceController::class, 'updateAutoSync'])->name('shop.imports.auto-sync');
    Route::post('shop/imports/{source}/start', [AdminImportSourceController::class, 'startImport'])->name('shop.imports.start');
    Route::get('shop/imports/runs/{import}', [AdminImportSourceController::class, 'importDetail'])->name('shop.imports.runs.detail');
    Route::post('shop/imports/runs/{import}/rollback', [AdminImportSourceController::class, 'rollback'])->name('shop.imports.runs.rollback');

    Route::resource('shop/suppliers', AdminSupplierController::class)->only(['index', 'store', 'update', 'destroy'])->names('shop.suppliers');

    // eSIM manual-review queue — every row here is a paid order with no live provider connection yet.
    Route::get('esim/provisioning', [AdminEsimController::class, 'index'])->name('esim.provisioning.index');
    Route::get('esim/provisioning/{provisioning}/row-detail', [AdminEsimController::class, 'rowDetail'])->name('esim.provisioning.row-detail');
    Route::post('esim/provisioning/{provisioning}/complete', [AdminEsimController::class, 'complete'])->name('esim.provisioning.complete');
    Route::post('esim/provisioning/{provisioning}/notes', [AdminEsimController::class, 'addNote'])->name('esim.provisioning.notes');
    Route::post('esim/provisioning/{provisioning}/fail', [AdminEsimController::class, 'fail'])->name('esim.provisioning.fail');
    Route::post('esim/provider', [AdminEsimController::class, 'updateProvider'])->name('esim.provider.update');
    Route::post('esim/provider/disconnect', [AdminEsimController::class, 'disconnectProvider'])->name('esim.provider.disconnect');

    // Reviews moderation
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');

    // Disputes
    Route::get('/disputes', [AdminDisputeController::class, 'index'])->name('disputes.index');
    Route::get('/disputes/{dispute}', [AdminDisputeController::class, 'show'])->name('disputes.show');
    Route::post('/disputes/{dispute}/reply', [AdminDisputeController::class, 'reply'])->name('disputes.reply');
    Route::post('/disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve'])->name('disputes.resolve');

    // Guest support tickets (no account required to submit)
    Route::get('/support-tickets', [AdminSupportTicketController::class, 'index'])->name('support-tickets.index');
    Route::get('/support-tickets/{supportTicket}', [AdminSupportTicketController::class, 'show'])->name('support-tickets.show');
    Route::post('/support-tickets/{supportTicket}/assign', [AdminSupportTicketController::class, 'assign'])->name('support-tickets.assign');
    Route::post('/support-tickets/{supportTicket}/resolve', [AdminSupportTicketController::class, 'resolve'])->name('support-tickets.resolve');
    Route::post('/support-tickets/{supportTicket}/convert', [AdminSupportTicketController::class, 'convertToDispute'])->name('support-tickets.convert');

    // Referral / agent-interest leads
    Route::get('/referral-leads', [AdminReferralLeadController::class, 'index'])->name('referral-leads.index');
    Route::put('/referral-leads/{referralLead}', [AdminReferralLeadController::class, 'update'])->name('referral-leads.update');

    // Newsletter subscribers
    Route::get('/newsletter', [AdminNewsletterController::class, 'index'])->name('newsletter.index');

    // Risk
    Route::get('/risk', [AdminRiskController::class, 'index'])->name('risk.index');
    Route::post('/risk/flags/{flag}/resolve', [AdminRiskController::class, 'resolveFlag'])->name('risk.flags.resolve');
    Route::post('/risk/rules', [AdminRiskController::class, 'storeRule'])->name('risk.rules.store');
    Route::put('/risk/rules/{rule}', [AdminRiskController::class, 'updateRule'])->name('risk.rules.update');

    // Security events (forms & bot protection)
    Route::get('/security-events', [AdminSecurityEventController::class, 'index'])->name('security-events.index');
    Route::post('/security-events/{securityEvent}/false-positive', [AdminSecurityEventController::class, 'markFalsePositive'])->name('security-events.false-positive');
    Route::post('/security-events/review/{reviewCase}/legitimate', [AdminSecurityEventController::class, 'markLegitimate'])->name('security-events.review.legitimate');
    Route::post('/security-events/review/{reviewCase}/spam', [AdminSecurityEventController::class, 'markSpam'])->name('security-events.review.spam');
    Route::post('/security-events/review/{reviewCase}/archive', [AdminSecurityEventController::class, 'archive'])->name('security-events.review.archive');
    Route::post('/security-events/review/{reviewCase}/block-fingerprint', [AdminSecurityEventController::class, 'blockFingerprint'])->name('security-events.review.block-fingerprint');
    Route::post('/security-events/review/{reviewCase}/allow-sender', [AdminSecurityEventController::class, 'allowSender'])->name('security-events.review.allow-sender');
    Route::delete('/security-events/allowlist/{allowlistEntry}', [AdminSecurityEventController::class, 'removeAllowlistEntry'])->name('security-events.allowlist.destroy');

    // Webhook monitor
    Route::get('/webhooks', [AdminWebhookController::class, 'index'])->name('webhooks.index');
    Route::get('/webhooks/{event}', [AdminWebhookController::class, 'show'])->name('webhooks.show');
    Route::post('/webhooks/{event}/retry', [AdminWebhookController::class, 'retry'])->name('webhooks.retry');

    // General Integrations (Google/Turnstile/SMS on-off switches only — credentials moved to Payment Providers)
    Route::get('/integrations', [\App\Http\Controllers\Admin\IntegrationController::class, 'index'])->name('integrations.index');
    Route::put('/integrations/general', [\App\Http\Controllers\Admin\IntegrationController::class, 'updateGeneral'])->name('integrations.general');

    // Settings + audit
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit.index');
    Route::post('/audit/verify', [AdminAuditController::class, 'verify'])->name('audit.verify');
    Route::get('/audit/{log}', [AdminAuditController::class, 'show'])->name('audit.show');
});
