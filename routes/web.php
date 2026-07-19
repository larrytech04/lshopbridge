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

// Digital shop
use App\Http\Controllers\Shop\ShopController;
use App\Http\Controllers\Shop\CartController;
use App\Http\Controllers\Shop\CheckoutController;
use App\Http\Controllers\Shop\ShopOrderController;
use App\Http\Controllers\Admin\ShopCategoryController as AdminShopCategoryController;
use App\Http\Controllers\Admin\ShopProductController as AdminShopProductController;
use App\Http\Controllers\Admin\ShopOrderController as AdminShopOrderController;

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
use App\Http\Controllers\SecureFileController;
use App\Http\Controllers\SecurityController;

// Agent
use App\Http\Controllers\Agent\AgentDashboardController;
use App\Http\Controllers\Agent\AgentProfileController;
use App\Http\Controllers\Agent\AgentVerificationController;
use App\Http\Controllers\Agent\ShippingRateController;
use App\Http\Controllers\Agent\AgentLeadController;
use App\Http\Controllers\Agent\AgentReviewController;

// Admin
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\KycController as AdminKycController;
use App\Http\Controllers\Admin\AgentController as AdminAgentController;
use App\Http\Controllers\Admin\DepositController as AdminDepositController;
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

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::view('/how-it-works', 'public.how-it-works')->name('how-it-works');
Route::get('/fund-alipay', [PageController::class, 'fundAlipay'])->name('public.fund');
Route::get('/payment-methods', [PageController::class, 'paymentMethods'])->name('public.payment-methods');
Route::get('/fees', [PageController::class, 'fees'])->name('public.fees');
Route::get('/faqs', [PageController::class, 'faqs'])->name('public.faqs');
Route::post('/calculator', [CalculatorController::class, 'quote'])->name('calculator');

Route::get('/china-guide', [PublicGuideController::class, 'index'])->name('guides.index');
Route::get('/china-guide/{guide:slug}', [PublicGuideController::class, 'show'])->name('guides.show');

Route::get('/shipping-agents', [AgentDirectoryController::class, 'index'])->name('agents.index');
Route::get('/shipping-agents/{agent:slug}', [AgentDirectoryController::class, 'show'])->name('agents.show');

Route::get('/contact', [ContactController::class, 'show'])->name('contact');
Route::post('/contact', [ContactController::class, 'submit'])->name('contact.submit');
Route::get('/p/{page:slug}', [PageController::class, 'show'])->name('pages.show');

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

    // Wallet + transactions
    Route::get('/wallet', [WalletController::class, 'index'])->name('wallet.index');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');

    // Deposits
    Route::get('/deposit', [DepositController::class, 'index'])->name('deposit.index');
    Route::post('/deposit', [DepositController::class, 'store'])->name('deposit.store');
    Route::get('/deposit/{deposit}', [DepositController::class, 'show'])->name('deposit.show');
    Route::post('/deposit/{deposit}/proof', [DepositController::class, 'uploadProof'])->name('deposit.proof');

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

    // Learning center (in dashboard)
    Route::get('/learn', [LearningController::class, 'index'])->name('learning.index');
    Route::get('/learn/{guide:slug}', [LearningController::class, 'show'])->name('learning.show');

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

    // Secure private file streaming (KYC docs, proofs, receipts)
    Route::get('/files/{kind}/{id}', [SecureFileController::class, 'show'])->name('files.show');

    // Shop checkout + digital orders
    Route::get('/checkout', [CheckoutController::class, 'show'])->name('shop.checkout');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('shop.checkout.store');
    Route::get('/shop/orders', [ShopOrderController::class, 'index'])->name('shop.orders.index');
    Route::get('/shop/orders/{order}', [ShopOrderController::class, 'show'])->name('shop.orders.show');
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
});

/*
|--------------------------------------------------------------------------
| Admin area
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,super_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Users + wallets
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/wallet', [AdminUserController::class, 'adjustWallet'])->name('users.wallet');

    // KYC
    Route::get('/kyc', [AdminKycController::class, 'index'])->name('kyc.index');
    Route::get('/kyc/{kyc}', [AdminKycController::class, 'show'])->name('kyc.show');
    Route::post('/kyc/{kyc}/approve', [AdminKycController::class, 'approve'])->name('kyc.approve');
    Route::post('/kyc/{kyc}/reject', [AdminKycController::class, 'reject'])->name('kyc.reject');

    // Beneficiaries (China wallet review)
    Route::get('/beneficiaries', [AdminBeneficiaryController::class, 'index'])->name('beneficiaries.index');
    Route::post('/beneficiaries/{beneficiary}/approve', [AdminBeneficiaryController::class, 'approve'])->name('beneficiaries.approve');
    Route::post('/beneficiaries/{beneficiary}/reject', [AdminBeneficiaryController::class, 'reject'])->name('beneficiaries.reject');

    // Agents
    Route::get('/agents', [AdminAgentController::class, 'index'])->name('agents.index');
    Route::get('/agents/{agent}', [AdminAgentController::class, 'show'])->name('agents.show');
    Route::post('/agents/{agent}/approve', [AdminAgentController::class, 'approve'])->name('agents.approve');
    Route::post('/agents/{agent}/reject', [AdminAgentController::class, 'reject'])->name('agents.reject');
    Route::post('/agents/{agent}/feature', [AdminAgentController::class, 'toggleFeature'])->name('agents.feature');

    // Deposits
    Route::get('/deposits', [AdminDepositController::class, 'index'])->name('deposits.index');
    Route::get('/deposits/{deposit}', [AdminDepositController::class, 'show'])->name('deposits.show');
    Route::post('/deposits/{deposit}/confirm', [AdminDepositController::class, 'confirm'])->name('deposits.confirm');
    Route::post('/deposits/{deposit}/reject', [AdminDepositController::class, 'reject'])->name('deposits.reject');

    // Funding requests
    Route::get('/funding', [AdminFundingController::class, 'index'])->name('funding.index');
    Route::get('/funding/{funding}', [AdminFundingController::class, 'show'])->name('funding.show');
    Route::post('/funding/{funding}/complete', [AdminFundingController::class, 'complete'])->name('funding.complete');
    Route::post('/funding/{funding}/retry', [AdminFundingController::class, 'retry'])->name('funding.retry');
    Route::post('/funding/{funding}/refund', [AdminFundingController::class, 'refund'])->name('funding.refund');

    // Rates, fees, methods, providers, countries, channels
    Route::resource('rates', AdminRateController::class)->except(['show']);
    Route::resource('fees', AdminFeeController::class)->except(['show']);
    Route::resource('payment-methods', AdminMethodController::class)->except(['show'])->names('methods');
    Route::get('/providers', [AdminProviderController::class, 'index'])->name('providers.index');
    Route::put('/providers/{provider}', [AdminProviderController::class, 'update'])->name('providers.update');
    Route::resource('countries', AdminCountryController::class)->except(['show', 'create', 'edit']);
    Route::get('/channels', [AdminChannelController::class, 'index'])->name('channels.index');
    Route::post('/channels/{type}', [AdminChannelController::class, 'store'])->name('channels.store');
    Route::put('/channels/{type}/{id}', [AdminChannelController::class, 'update'])->name('channels.update');
    Route::delete('/channels/{type}/{id}', [AdminChannelController::class, 'destroy'])->name('channels.destroy');

    // Content
    Route::resource('guides', AdminGuideController::class)->except(['show']);
    Route::resource('faqs', AdminFaqController::class)->except(['show', 'create', 'edit']);
    Route::resource('banners', AdminBannerController::class)->except(['show', 'create', 'edit']);
    Route::resource('pages', AdminPageController::class)->except(['show']);

    // Site content (CMS), editable front-end text blocks
    Route::get('content', [\App\Http\Controllers\Admin\ContentController::class, 'index'])->name('content.index');
    Route::put('content', [\App\Http\Controllers\Admin\ContentController::class, 'update'])->name('content.update');

    // Digital shop management
    Route::resource('shop/categories', AdminShopCategoryController::class)->except(['show', 'create', 'edit'])->names('shop.categories');
    Route::resource('shop/products', AdminShopProductController::class)->except(['show'])->names('shop.products');
    Route::get('shop/orders', [AdminShopOrderController::class, 'index'])->name('shop.orders.index');
    Route::get('shop/orders/{order}', [AdminShopOrderController::class, 'show'])->name('shop.orders.show');
    Route::post('shop/orders/{order}/fulfill', [AdminShopOrderController::class, 'fulfill'])->name('shop.orders.fulfill');
    Route::post('shop/orders/{order}/refund', [AdminShopOrderController::class, 'refund'])->name('shop.orders.refund');

    // Reviews moderation
    Route::get('/reviews', [AdminReviewController::class, 'index'])->name('reviews.index');
    Route::post('/reviews/{review}/approve', [AdminReviewController::class, 'approve'])->name('reviews.approve');
    Route::post('/reviews/{review}/reject', [AdminReviewController::class, 'reject'])->name('reviews.reject');

    // Disputes
    Route::get('/disputes', [AdminDisputeController::class, 'index'])->name('disputes.index');
    Route::get('/disputes/{dispute}', [AdminDisputeController::class, 'show'])->name('disputes.show');
    Route::post('/disputes/{dispute}/reply', [AdminDisputeController::class, 'reply'])->name('disputes.reply');
    Route::post('/disputes/{dispute}/resolve', [AdminDisputeController::class, 'resolve'])->name('disputes.resolve');

    // Risk
    Route::get('/risk', [AdminRiskController::class, 'index'])->name('risk.index');
    Route::post('/risk/flags/{flag}/resolve', [AdminRiskController::class, 'resolveFlag'])->name('risk.flags.resolve');
    Route::post('/risk/rules', [AdminRiskController::class, 'storeRule'])->name('risk.rules.store');
    Route::put('/risk/rules/{rule}', [AdminRiskController::class, 'updateRule'])->name('risk.rules.update');

    // Webhooks log
    Route::get('/webhooks', [AdminWebhookController::class, 'index'])->name('webhooks.index');
    Route::get('/webhooks/{event}', [AdminWebhookController::class, 'show'])->name('webhooks.show');

    // Integrations / API keys
    Route::get('/integrations', [\App\Http\Controllers\Admin\IntegrationController::class, 'index'])->name('integrations.index');
    Route::put('/integrations/provider/{code}', [\App\Http\Controllers\Admin\IntegrationController::class, 'updateProvider'])->name('integrations.provider');
    Route::put('/integrations/general', [\App\Http\Controllers\Admin\IntegrationController::class, 'updateGeneral'])->name('integrations.general');

    // Settings + audit
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::get('/audit', [AdminAuditController::class, 'index'])->name('audit.index');
});
