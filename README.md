<p align="center">
  <img width="100%" src="https://capsule-render.vercel.app/api?type=waving&height=260&color=gradient&customColorList=12,20,22,24,30&text=LshopBridge&fontSize=62&fontColor=ffffff&animation=fadeIn&fontAlignY=38&desc=The%20Financial%20Bridge%20Between%20Africa%20and%20China&descAlignY=60"/>
</p>

<p align="center">
  <img src="https://readme-typing-svg.herokuapp.com?font=Inter&weight=700&size=24&duration=2800&pause=800&color=C1121F&center=true&vCenter=true&width=950&lines=Fund+Alipay+with+MTN+MoMo.;Fund+WeChat+Pay+Automatically.;Deposit+with+Mobile+Money%2C+Card+or+Crypto.;Digital+Shop+for+Gift+Cards+and+eSIMs.;Verified+Shipping+Agent+Marketplace.;Built+with+Laravel+13." />
</p>

<p align="center">
  <a href="https://laravel.com">
    <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white"/>
  </a>
  <a href="https://www.php.net">
    <img src="https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white"/>
  </a>
  <a href="https://tailwindcss.com">
    <img src="https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white"/>
  </a>
  <a href="https://alpinejs.dev">
    <img src="https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpinedotjs&logoColor=black"/>
  </a>
  <img src="https://img.shields.io/badge/License-All_Rights_Reserved-informational?style=for-the-badge"/>
</p>

<p align="center">
  <img src="https://capsule-render.vercel.app/api?type=rect&height=2&color=gradient&customColorList=12,20,22,24,30"/>
</p>

# LshopBridge

**LshopBridge** is a premium Laravel fintech platform built to connect Africa and China through wallet funding, digital commerce, and verified logistics.

The platform allows users in Cameroon, Nigeria, Ghana, and other African countries to deposit using **MTN MoMo, Orange Money, bank transfer, card, or crypto**, then fund **Alipay, WeChat Pay, and other China wallets** through a secure automated flow.

It also includes a **China buying academy**, a **digital gift card and eSIM shop**, and a **verified shipping agent marketplace**.

<p align="center">
  <img src="https://readme-typing-svg.herokuapp.com?font=Inter&weight=600&size=18&duration=2500&pause=1000&color=7A0019&center=true&vCenter=true&width=750&lines=One+wallet.;One+marketplace.;One+verified+agent+network.;One+bridge+between+Africa+and+China." />
</p>

---

## Animated Preview

> Replace this GIF with your real screen recording.

<p align="center">
  <img src="assets/readme/lshopbridge-demo.gif" width="90%" alt="LshopBridge Animated Demo"/>
</p>

<p align="center">
  <sub>Suggested GIFs: home page animation, user dashboard, funding request, admin panel, and agent marketplace.</sub>
</p>

---

## Screenshots

> Replace these images with your real project screenshots.

<p align="center">
  <img src="assets/LshopBridge Home.png" width="48%" alt="LshopBridge Home"/>
  <img src="assets/lshopbridge-demo.png" width="48%" alt="LshopBridge Dashboard"/>
</p>

<p align="center">
  <img src="assets/readme/admin.png" width="48%" alt="LshopBridge Admin Panel"/>
  <img src="assets/readme/marketplace.png" width="48%" alt="LshopBridge Agent Marketplace"/>
</p>

---

## What is inside

| Module            | Description                                                   |
| ----------------- | ------------------------------------------------------------- |
| Wallet Funding    | Deposit locally and fund China wallets                        |
| Digital Shop      | Sell gift cards, eSIMs, and digital services                  |
| Agent Marketplace | Connect users with verified shipping agents                   |
| China Academy     | Teach users how to buy and import from China                  |
| Admin Panel       | Manage users, agents, fees, rates, providers, and settings    |
| Risk Engine       | Detect suspicious transactions and move them to manual review |

---

## Main Features

* Automatic payment collection
* Alipay and WeChat Pay funding flow
* MTN MoMo, Orange Money, Flutterwave, bank transfer, card, and crypto support
* Saved China wallet beneficiaries
* QR code upload for wallet profiles
* KYC levels 0 to 3
* Admin-controlled transaction limits
* Double-entry wallet ledger
* Secure private file storage
* Webhook verification and logging
* Risk and fraud detection
* Dispute management
* Verified shipping agent marketplace
* Digital gift card and eSIM shop
* China buying academy
* Multi-language-ready interface
* Glassmorphic responsive UI

<p align="center">
  <img src="https://readme-typing-svg.herokuapp.com?font=Inter&weight=600&size=18&duration=2600&pause=900&color=C1121F&center=true&vCenter=true&width=850&lines=Wallet+Funding.;Digital+Shop.;Verified+Agents.;China+Academy.;Admin+Control.;Secure+Automation." />
</p>

---

## Money Flow

```text
User enters amount
        |
System calculates rate and fee
        |
User selects payment method
        |
Payment provider charges user
        |
Webhook confirms payment
        |
Wallet is credited
        |
Funding request is submitted
        |
China wallet is funded
        |
Transaction is completed
```

If a transaction is risky, failed, or needs extra checks, the system moves it to **manual review**.

---

## User Roles

### User

Users can create an account, complete KYC, deposit funds, save China wallet details, fund Alipay or WeChat Pay, buy digital products, open disputes, review agents, and learn from the China academy.

### Agent

Agents can verify their business, create a logistics profile, publish shipping rates, receive leads, manage orders, collect reviews, and build reputation points.

### Admin

Admins can manage users, approve KYC, approve agents, configure fees, update exchange rates, view webhook logs, handle disputes, monitor risky transactions, and control platform settings.

### Super Admin

Super admins have full access to all platform modules and can override permission gates.

---

## Architecture

```text
app/
  Enums/                     Status enums and UI badge helpers
  Http/Controllers/          Public, User, Agent, Admin, Webhooks
  Http/Middleware/           Role, account status, KYC checks
  Models/                    Eloquent models and relationships
  Notifications/             Deposit, funding, KYC, agent events
  Policies/                  Authorization and ownership checks
  Services/
    Wallet/                  Double-entry ledger and balance updates
    Deposit/                 Manual and automated deposits
    Funding/                 Funding engine, rates, fees, providers
    Payments/                Payment manager, providers, webhooks
    Risk/                    Risk engine and review flags
    Kyc/                     Verification levels and limits
    Settings/                Cached database-backed settings
    Audit/                   Admin activity logging

config/                      Platform, payment, and funding config
database/                    Migrations and seeders
resources/views/             Public, auth, app, admin, dashboard views
routes/                      Web routes and webhook routes
```

---

## Provider Layer

LshopBridge uses a clean provider structure so live APIs can be added without rebuilding the platform.

```text
PaymentProvider Contract
        |
PaymentManager
        |
MtnMomoProvider
OrangeMoneyProvider
FlutterwaveProvider
CryptoProvider
CardProvider
```

Each provider handles charge creation, webhook verification, transaction status updates, response mapping, and failure handling.

---

## Funding Engine

```text
payment_pending
        |
payment_successful
        |
funding_processing
        |
funding_successful
```

Other possible statuses:

* funding_failed
* refunded
* manual_review
* cancelled

Live Alipay or WeChat funding must be handled through a licensed cross-border payout partner or PSP.

---

## Setup

Scaffolded with **Laravel Herd**, but any PHP 8.3+ environment works.

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

php artisan migrate:fresh --seed
php artisan storage:link

npm run build
php artisan serve
```

Open:

```text
http://localhost:8000
```

---

## Sandbox vs Live

LshopBridge runs in sandbox mode by default.

```env
PROVIDER_MODE=sandbox
```

Sandbox mode includes mock payments, mock funding, signed webhook simulation, real wallet settlement, real funding lifecycle, and no real external money movement.

### Going Live

Add real provider credentials to `.env`.

```env
MTN_MOMO_*
ORANGE_MONEY_*
FLUTTERWAVE_*
CRYPTO_GATEWAY_*
CARD_GATEWAY_*
ALIPAY_FUNDING_*
```

Set providers to live.

```env
PROVIDER_MODE=live
```

Complete the live API sections in:

```text
app/Services/Payments/Providers/*
app/Services/Funding/Providers/AlipayFundingProvider.php
```

Point provider dashboards to:

```text
https://your-domain.com/webhooks/payments/{provider}
```

---

## Security

LshopBridge includes security features expected from a financial platform.

* CSRF protection
* Login throttling
* Rate-limited OTP
* Role-based access control
* Policy-based authorization
* Private document storage
* Secure file streaming
* Signature-verified webhooks
* Idempotent webhook handling
* Double-entry wallet ledger
* Row-locked wallet updates
* Audit logging
* Risk queue
* KYC-based limits
* Manual review support

---

## Roadmap

| Feature                 | Status  |
| ----------------------- | ------- |
| Wallet System           | Done    |
| Deposit System          | Done    |
| Funding Engine          | Done    |
| Agent Marketplace       | Done    |
| Digital Shop            | Done    |
| KYC System              | Done    |
| Admin Panel             | Done    |
| Sandbox Providers       | Done    |
| Live Alipay Partner API | Planned |
| Live WeChat Partner API | Planned |
| Mobile App              | Planned |
| Merchant API            | Planned |
| AI Fraud Detection      | Planned |

---

## Disclaimer

This project is production-structured but currently uses sandbox and mock providers.

Before processing real money, you must complete live API integrations, obtain the required licenses, work with licensed PSPs or payout partners, complete compliance checks, and review Terms, Privacy Policy, and Refund Policy with legal counsel.

---

## License

**All Rights Reserved.**

This repository is public for portfolio and evaluation purposes only.

No part of this codebase may be copied, modified, distributed, sold, or used to create a derivative product without prior written permission from the author.

---

<p align="center">
  <img src="https://readme-typing-svg.herokuapp.com?font=Inter&weight=700&size=20&duration=3000&pause=1000&color=C1121F&center=true&vCenter=true&width=800&lines=LshopBridge.;The+financial+bridge+between+Africa+and+China.;Built+with+Laravel%2C+Tailwind+CSS+and+secure+fintech+architecture." />
</p>

<p align="center">
  <img width="100%" src="https://capsule-render.vercel.app/api?type=waving&height=150&section=footer&color=gradient&customColorList=12,20,22,24,30"/>
</p>
