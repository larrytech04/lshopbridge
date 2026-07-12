<div align="center">

# LshopBridge

### The financial bridge between Africa and China — wallet funding, digital shop, and a verified agent marketplace in one platform.

[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net)
[![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-v4-06B6D4?style=for-the-badge&logo=tailwindcss&logoColor=white)](https://tailwindcss.com)
[![Alpine.js](https://img.shields.io/badge/Alpine.js-8BC0D0?style=for-the-badge&logo=alpine.js&logoColor=black)](https://alpinejs.dev)
[![License](https://img.shields.io/badge/License-All_Rights_Reserved-informational?style=for-the-badge)](#-license)

</div>

---

A premium, glassmorphic Laravel fintech platform that lets users in Cameroon, Nigeria,
Ghana and other African countries deposit with **MTN MoMo, Orange Money, bank transfer,
card or crypto** and **fund Alipay / WeChat Pay and other China wallets automatically** —
plus a China buying academy, a digital gift‑card/eSIM shop, and a verified shipping‑agent
marketplace.

> Built with **Laravel 13 · PHP 8.3 · SQLite · Tailwind CSS v4 · Alpine.js**.
> Production‑structured, secure and scalable, with **sandbox/mock** payment + funding
> providers that are ready to swap for live APIs. Fully internationalized UI across
> 5 languages and growing.

---

## 📚 Table of contents

- [What's inside](#-whats-inside)
- [Money flow](#money-flow-fully-automated-with-safe-manual-fallback)
- [Highlights](#highlights)
- [Setup](#-setup)
- [Sandbox vs Live](#-sandbox-vs-live)
- [Architecture](#-architecture)
- [Security](#-security)
- [Disclaimer](#️-disclaimer)

---

## ✨ What's inside

### Roles
- **User** — wallet, deposits, automated funding, saved China wallets, KYC, disputes, reviews, learning.
- **Agent** — business verification, shipping rates, leads/orders, reviews, reputation points.
- **Admin / Super Admin** — full control panel (everything is DB‑driven, nothing hard‑coded).

### Money flow (fully automated, with safe manual fallback)
```
enter amount → quote (rate + fee) → choose method → provider charge
        → provider webhook (signature‑verified, idempotent)
        → wallet credited / funding auto‑submitted to China wallet
        → funding_successful   (or → manual_review on risk/failure)
```

### Highlights
- **Automatic payment collection** via a clean provider layer: `MtnMomo`, `OrangeMoney`,
  `Flutterwave`, `Crypto`, `Card` — one `PaymentProvider` contract, resolved by `PaymentManager`.
- **Secure webhook pipeline** (`WebhookProcessor`): logs every event, verifies HMAC
  signatures, dedupes by `(provider, event_id)`, then settles exactly once.
- **Automatic Alipay funding engine** (`FundingService` + `AlipayFundingProvider`) with the
  full status machine: `payment_pending → payment_successful → funding_processing →
  funding_successful` (+ `funding_failed`, `refunded`, `manual_review`).
- **Risk & fraud engine** (`RiskEngine`) — name mismatch, velocity, large tx, blocked
  country, failed attempts, unverified account → flags + manual review.
- **KYC levels 0–3** with admin‑editable per‑level daily / monthly / per‑transaction limits.
- **Saved beneficiary (China wallet) profiles** — Alipay / WeChat / other, with QR upload,
  default account and approval workflow.
- **Double‑entry wallet ledger** (`WalletService`) — every balance change is atomic,
  row‑locked and recorded.
- **Secure file handling** — KYC IDs, selfies, proofs, receipts and QR codes live on a
  **private disk** and are streamed only through `SecureFileController` (owner/admin only).
- **Admin audit log** + webhook log + risk queue.
- **Glassmorphic UI** — aurora gradients, frosted glass cards, floating shapes, animated
  counters, smooth transitions; fully responsive, mobile‑first.

---

## 🚀 Setup

Scaffolded with **Laravel Herd** (PHP 8.3). Any PHP 8.3+ / Composer / Node environment works.

```bash
composer install
npm install

cp .env.example .env
php artisan key:generate

# SQLite — zero config (database/database.sqlite is auto-created)
php artisan migrate:fresh --seed
php artisan storage:link

npm run build          # or: npm run dev
php artisan serve
```

Open http://localhost:8000.

### Demo accounts (password: `password`)
| Role | Email |
|------|-------|
| Super admin | `superadmin@paybridge.test` |
| Admin | `admin@paybridge.test` |
| User (funded, verified) | `kofi@example.com` |
| Agent (verified) | `agent@example.com` |

---

## 🧪 Sandbox vs Live

Everything runs in **sandbox** by default (`PROVIDER_MODE=sandbox`) — no real money, no
external calls. In sandbox a charge returns a **signed webhook payload** that is replayed
through the *real* `WebhookController` pipeline (`SandboxSimulator`), so you see the full
automation end‑to‑end offline. Phone OTP codes are shown on screen (sandbox only).

### Going live
1. Put real credentials in `.env` (never commit them):
   `MTN_MOMO_*`, `ORANGE_MONEY_*`, `FLUTTERWAVE_*`, `CRYPTO_GATEWAY_*`, `CARD_GATEWAY_*`,
   `ALIPAY_FUNDING_*`.
2. Flip each provider to **live** in **Admin → Providers** (or set `PROVIDER_MODE=live`).
3. Implement the `// TODO[live]` sections (clearly marked) in:
   - `app/Services/Payments/Providers/*` (real charge calls + webhook parsing)
   - `app/Services/Funding/Providers/AlipayFundingProvider.php` (payout partner API)
4. Point each provider's dashboard webhook to:
   `https://your-domain/webhooks/payments/{provider}`

> **Note on Alipay:** there is no public "send to any Alipay account" API. Live funding must
> go through a licensed cross‑border payout partner / PSP — wired in `AlipayFundingProvider`.

---

## 🗂 Architecture

```
app/
  Enums/                     status enums (label() + color() for UI badges)
  Http/Controllers/          Public / User / Agent / Admin / Webhooks
  Http/Middleware/           role, active-account, kyc-level gates
  Models/                    Eloquent models (+ relationships)
  Notifications/             deposit/funding/kyc/agent/beneficiary events
  Policies/                  ownership policies
  Services/
    Wallet/      WalletService (double-entry ledger)
    Deposit/     DepositService (manual + automated)
    Funding/     FundingService (auto-funding engine), FeeCalculator, RateService,
                 FundingManager, Providers/AlipayFundingProvider
    Payments/    PaymentManager, WebhookProcessor, SandboxSimulator,
                 Contracts/, DTO/, Providers/ (MoMo, Orange, Flutterwave, Crypto, Card)
    Risk/        RiskEngine
    Kyc/         LimitService
    Settings/    SettingsService (cached, DB-backed)
    Audit/       AuditLogger
config/   platform.php · payments.php · funding.php
database/ migrations (25) · seeders (8)
resources/views/  components · layouts (public/auth/app/admin) · public · dashboard · agent · admin
routes/   web.php · webhooks.php (stateless, CSRF-exempt, signature-verified)
```

## 🔐 Security
CSRF protection, login throttling, rate‑limited OTP, 2FA‑ready user fields, private document
storage, signature‑verified + idempotent webhooks, audit + risk logging, KYC‑based limits,
and role/policy authorization (super admin passes all gates).

## ⚠️ Disclaimer
This is a production‑*structured* foundation with **mock/sandbox** payment & funding
integrations. Before processing real money you must complete the live API integrations,
obtain the required payment/PSP licences and partner agreements, and have the legal pages
(Terms, Privacy, Refund) reviewed.

---

## 📄 License

**All rights reserved.** This repository is public for portfolio and evaluation purposes
only. No part of this codebase may be copied, modified, distributed, or used to build a
derivative product without prior written permission from the author.

---

<div align="center">

</div>
