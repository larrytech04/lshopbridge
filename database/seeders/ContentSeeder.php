<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Faq;
use App\Models\Page;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    public function run(): void
    {
        $faqs = [
            ['question' => 'How fast is funding delivered?', 'answer' => 'In most cases instantly. Automated payments confirm within seconds and funding is delivered automatically.', 'category' => 'funding'],
            ['question' => 'Which payment methods can I use?', 'answer' => 'MTN MoMo, Orange Money, bank transfer, card and crypto.', 'category' => 'payments'],
            ['question' => 'Is my data safe?', 'answer' => 'Yes. Documents are encrypted, stored privately and never shared publicly.', 'category' => 'security'],
            ['question' => 'Do I need to upload proof of payment?', 'answer' => 'Not for automated methods, only manual bank transfers need proof.', 'category' => 'payments'],
            ['question' => 'What are the limits?', 'answer' => 'Limits depend on your verification level. Verify your ID to raise them.', 'category' => 'account'],
            ['question' => 'Can I get a refund?', 'answer' => 'If a funding cannot be completed, the amount is refunded to your wallet.', 'category' => 'funding'],
            ['question' => 'What is a KYC verification level?', 'answer' => 'Your verification level determines your funding and deposit limits and which features are available to you. Higher levels require more identity documents but unlock higher limits. You can see your current level and what is required to reach the next one from your Security Center.', 'category' => 'account'],
            ['question' => 'How do I enable two-factor authentication?', 'answer' => "Open your Security Center and turn on two-factor authentication. Scan the QR code with an authenticator app, confirm the 6-digit code, and save your recovery codes somewhere safe, you'll need them if you lose access to your authenticator.", 'category' => 'security'],
            ['question' => 'How do I view or remove active sessions and devices?', 'answer' => "Your Security Center lists every device and session currently signed in to your account, with the location and last-active time. You can revoke any session individually, or sign out everywhere else at once if something looks unfamiliar.", 'category' => 'security'],
            ['question' => 'What is a passkey?', 'answer' => 'A passkey lets you sign in using your device\'s built-in security, like a fingerprint, face scan or security key, instead of typing a password. Add one from your Security Center under Passkeys.', 'category' => 'security'],
            ['question' => 'Which China wallets can I fund?', 'answer' => 'Supported wallets are shown on the Fund Alipay page and include Alipay, WeChat Pay and other configured China wallets. Exact availability, limits and processing type depend on your country and verification level.', 'category' => 'funding'],
            ['question' => 'Why do I need to add and verify a recipient before funding?', 'answer' => "Every China wallet recipient is reviewed before it can receive funds. This protects you from sending money to a mistyped or unverified account. Once a recipient is approved, you can reuse it for future funding requests.", 'category' => 'funding'],
            ['question' => 'How is my exchange rate calculated?', 'answer' => 'Rates are admin-managed and include a transparent margin over the base rate. The exact rate, fees and amount your recipient will receive are always shown before you confirm a funding request, so there are no surprises at checkout.', 'category' => 'funding'],
            ['question' => 'Why is a payment method unavailable to me?', 'answer' => 'Only payment methods that are currently active for your country, currency, verification level and transaction amount are shown. If a method you expect is missing, it may require a higher verification level or may not yet be supported where you are.', 'category' => 'payments'],
            ['question' => 'How are digital products like gift cards and eSIMs delivered?', 'answer' => 'Once your payment is confirmed, digital products are delivered straight to your order history, gift card codes and eSIM QR codes usually appear within moments. Some payment methods require manual confirmation, which can take a little longer.', 'category' => 'marketplace'],
        ];
        foreach ($faqs as $i => $f) {
            Faq::updateOrCreate(['question' => $f['question']], $f + ['is_published' => true, 'sort' => $i]);
        }

        Banner::updateOrCreate(['title' => 'Fund Alipay, WeChat Pay and more'], [
            'subtitle' => 'Top up with MoMo, bank, card or crypto and we deliver to any China wallet automatically, plus shop gift cards, eSIMs, VPN & more, delivered in minutes.',
            'cta_label' => 'Start funding', 'cta_url' => '/register', 'type' => 'hero', 'position' => 'home', 'is_active' => true, 'sort' => 1,
        ]);

        $effective = now()->startOfDay();

        foreach ($this->legalPages($effective) as $p) {
            // meta_description is left unset deliberately: it's an optional SEO
            // override, and falls back (in the view) to the excerpt run through
            // token substitution — setting it here from the raw excerpt would
            // store the unresolved {{token}} text verbatim in a column nothing
            // else ever substitutes.
            Page::updateOrCreate(['slug' => $p['slug']], $p + [
                'type' => 'legal',
                'is_published' => true,
                'last_reviewed_at' => now(),
            ]);
        }

        Page::updateOrCreate(['slug' => 'about'], [
            'title' => 'About LshopBridge',
            'type' => 'info',
            'excerpt' => 'Bridging Africa and China, instant wallet funding plus a digital shop for gift cards, eSIMs, top-ups and more.',
            'body' => "This is placeholder content for the About LshopBridge page. Administrators can edit this from the admin panel under Legal pages.\n\nReplace this with your real, legally-reviewed copy before going live.",
            'is_published' => true, 'last_reviewed_at' => now(),
        ]);
    }

    /**
     * Phase 1 of the Legal & Policy Center: real, comprehensive, plain-language
     * policies replacing the old single-line placeholder body every legal
     * page used to ship with. Company/jurisdiction specifics that aren't yet
     * verified use {{token}} placeholders (see LegalContentRenderer) instead
     * of invented details — they resolve from Admin -> Settings -> Legal &
     * Company, and render as a clearly-bracketed "pending review" notice
     * until an admin fills them in.
     *
     * internal_review_notes are admin-only (never rendered publicly, see
     * public/legal-page.blade.php) and flag exactly what a lawyer still
     * needs to confirm before any of this is relied on in production.
     */
    private function legalPages($effective): array
    {
        return [
            $this->termsOfService($effective),
            $this->privacyPolicy($effective),
            $this->cookiePolicy($effective),
            $this->acceptableUsePolicy($effective),
            $this->depositPolicy($effective),
            $this->chinaWalletFundingTerms($effective),
            $this->refundReversalPolicy($effective),
            $this->marketplaceTerms($effective),
        ];
    }

    private function termsOfService($effective): array
    {
        return [
            'slug' => 'terms',
            'title' => 'Terms of Service',
            'category' => 'general',
            'excerpt' => 'The rules governing your use of {{site_name}} — your account, our services, fees, and what happens if something goes wrong.',
            'effective_date' => $effective,
            'plain_summary' => <<<'MD'
- This policy covers your relationship with {{site_name}}: creating an account, using the wallet, funding a China wallet, buying from the marketplace, and everything else on the platform.
- You must be old enough to legally enter into this agreement in your country, and the information you give us must be accurate.
- Registering does **not** automatically guarantee that any specific deposit, funding request, order, or withdrawal will be approved — every transaction can be reviewed, delayed, or declined for security, compliance, or operational reasons.
- We can restrict or suspend an account when we reasonably believe it's necessary — for fraud, compliance, or a violation of these terms — and we'll explain why where we're legally allowed to.
- We are not liable for losses caused by things outside our reasonable control, like a payment provider's outage or a mistake in the details you gave us.
- You can reach us at {{support_email}} with any question about these terms.
MD,
            'body' => <<<'MD'
## About This Policy

These Terms of Service ("Terms") are the agreement between you and {{company_trading_name}} ("{{site_name}}", "we", "us", "our") governing your access to and use of the {{site_name}} website, mobile experience, wallet, marketplace, and related services (together, the "Platform").

By creating an account or otherwise using the Platform, you agree to these Terms. If you do not agree, do not use the Platform.

## Who This Policy Applies To

These Terms apply to every visitor, registered customer, agent, and merchant using the Platform, except where a separate agreement (for example, the Shipping Agent Marketplace Terms or a merchant agreement) applies instead for that specific role or activity.

## Eligibility

To use {{site_name}}, you must:

- Be old enough to form a binding contract under the law of the country you're registering from (typically 18, but check your local age of majority).
- Have the legal capacity to agree to these Terms.
- Not be located in, or a resident of, a country or region where use of the Platform is prohibited by applicable law or sanctions.
- Provide accurate, current, and complete information when you register and whenever we ask you to update it.

We may decline to open an account, or may close one, if we reasonably believe these conditions aren't met.

## Account Registration and Ownership

**Accurate information.** You're responsible for the accuracy of the details you give us — your name, contact details, and any identity information used for verification. Giving us false information can lead to account restrictions or closure.

**One account per person.** Each individual may hold one customer account unless we've agreed otherwise in writing (for example, for a separate agent or merchant account).

**Account security.** You're responsible for keeping your password, PIN, and any multi-factor authentication method confidential, and for all activity that happens under your login unless you can show it wasn't authorized by you. Tell us immediately at {{support_email}} if you suspect unauthorized access.

**Identity verification (KYC).** Certain features — higher transaction limits, withdrawals, and China wallet funding among them — require identity verification. See the [KYC & Identity Verification Policy](/legal/kyc-identity-verification-policy) for details. We may ask for updated verification at any time.

## Supported and Restricted Countries

{{site_name}} is offered only in the countries and regions we actively support, shown during registration and in your account settings. We may add, suspend, or remove support for a country at any time, including where required by sanctions, payment-provider restrictions, or regulatory change. If a country becomes unsupported, we'll tell you what that means for your existing balance and any open transactions.

## What the Platform Offers

Depending on what's enabled for your account and country, the Platform may let you:

- Hold a balance in the {{site_name}} wallet ("Wallet") — see the [Wallet Terms](/legal/wallet-terms).
- Deposit funds into your Wallet — see the [Deposit Policy](/legal/deposit-policy).
- Fund a China-based wallet (such as Alipay or WeChat Pay) from your balance — see the [China Wallet Funding Terms](/legal/china-wallet-funding-terms).
- Withdraw available balance to a saved payment method, where withdrawals are enabled for your account.
- Buy digital and physical products through the marketplace — see the [Marketplace Terms](/legal/marketplace-terms).
- Request shipping through independent shipping agents — see the [Shipping Agent Marketplace Terms](/legal/shipping-agent-marketplace-terms).

Not every feature is available to every customer, country, or account tier. Availability can change, and using {{site_name}} doesn't create a right to any specific feature staying available.

## Fees, Exchange Rates, and Limits

Fees, exchange-rate margins, and transaction limits are shown to you before you confirm a transaction, and are also described in the Exchange Rate & Fees Disclosure. We may change fees, rates, and limits going forward; changes never apply retroactively to a transaction you've already confirmed.

## Payment Authorization and Processing

When you initiate a deposit, funding request, purchase, or withdrawal, you authorize us and the relevant payment or funding provider to process it using the details you've given. Processing times vary by method and provider and are estimates, not guarantees — see the specific policy for the service you're using (Deposit Policy, China Wallet Funding Terms, and so on) for what to expect and what can cause a delay.

## Third-Party Providers

Many services on the Platform are delivered with the help of third parties — payment processors, identity-verification providers, China wallet networks, gift-card and eSIM issuers, shipping agents, and marketplace suppliers. We select these providers carefully, but we don't control their systems, and their own delays, errors, or outages can affect your transaction. Where a policy names a third-party role, it explains what that means for you.

## Service Availability

We aim to keep the Platform available, but we don't guarantee uninterrupted access. Maintenance, technical issues, provider outages, or circumstances outside our control can make some or all of the Platform temporarily unavailable. We'll try to give notice of planned maintenance where practical.

## Prohibited Activity

You agree not to use {{site_name}} for anything described in the [Acceptable Use Policy](/legal/acceptable-use-policy), which covers fraud, money laundering, sanctions evasion, abuse of promotions or referrals, and similar activity. Violating that policy is also a violation of these Terms.

## Account Reviews, Restrictions, Suspension, and Termination

We may review, hold, restrict, suspend, or close an account, or decline or reverse a specific transaction, where we reasonably believe it's necessary — including for suspected fraud, a compliance requirement, a payment reversal or chargeback, a security concern, or a breach of these Terms. See the Account Restriction & Suspension Policy for how that process works, including how you can respond and what happens to your remaining balance.

You may close your own account at any time by contacting {{support_email}}, subject to settling any pending transactions and completing any legally required checks first.

## Refunds and Disputes

Refund eligibility depends on the type of transaction and is set out in the [Refund & Reversal Policy](/legal/refund-reversal-policy) and the policy for the specific service involved. If you disagree with how a transaction was handled, contact {{support_email}} to raise it.

## Intellectual Property

The {{site_name}} name, logo, website design, and original written content belong to {{company_trading_name}} or our licensors. You may use them only as needed to use the Platform normally — you may not copy, modify, or redistribute them for another purpose without our written permission. Product images, brand names, and descriptions supplied by marketplace suppliers or gift-card issuers remain the property of those third parties.

## Customer Content

If you submit content to the Platform — a product review, a support message, feedback — you confirm you have the right to share it and you grant us a licence to use it to operate and improve the Platform (for example, displaying a review on a product page).

## Platform Communications

By creating an account you agree to receive transactional, security, and legal communications from us electronically. These are not optional marketing and can't be switched off while your account is active.

## Disclaimers

The Platform is provided on an "as available" basis. We do not guarantee that:

- Any specific deposit, funding request, order, or withdrawal will be approved.
- Products, shipping agents, or suppliers will always be available.
- Delivery, funding, or processing will complete within a particular time.
- Refunds will be issued in every situation you might expect them.
- Access to your account will be uninterrupted or error-free.

Nothing in this section is intended to exclude a right that applicable law does not allow us to exclude.

## Limitation of Liability

To the fullest extent permitted by applicable law, {{company_trading_name}} is not liable for indirect, incidental, or consequential losses arising from your use of the Platform, or for delays or failures caused by circumstances outside our reasonable control — including a payment provider's outage, incorrect details you provided, or a third-party supplier's failure to deliver. This section does not limit liability that cannot lawfully be limited (for example, liability for our own fraud or gross negligence, where applicable law says it can't be excluded).

## Indemnification

Where permitted by applicable law, you agree to reimburse us for reasonable losses we incur because you breached these Terms, misused the Platform, or provided false information — except where that loss was caused by our own fault.

## Governing Law and Jurisdiction

*Requires legal review: the governing law and jurisdiction for this agreement have not yet been confirmed.* Until updated, disputes are intended to be handled under the law of {{company_jurisdiction}}, and this section will be finalized once that is confirmed.

## Changes to These Terms

We may update these Terms as the Platform evolves. For a material change, we'll take reasonable steps to notify you before it takes effect. Continuing to use the Platform after a change takes effect means you accept the updated Terms.

## Contact Information

Questions about these Terms: {{legal_email}}. General support: {{support_email}}.
MD,
            'internal_review_notes' => "Governing law/jurisdiction clause is a placeholder pending confirmation of {{company_jurisdiction}}. Indemnification clause needs local-law review (not enforceable/appropriate everywhere). Minimum age reference (18) should be confirmed per supported country. Confirm whether a formal arbitration/dispute-resolution clause is needed once jurisdiction is set.",
        ];
    }

    private function privacyPolicy($effective): array
    {
        return [
            'slug' => 'privacy',
            'title' => 'Privacy Policy',
            'category' => 'general',
            'excerpt' => 'How {{site_name}} collects, uses, shares, and protects your personal information.',
            'effective_date' => $effective,
            'plain_summary' => <<<'MD'
- We collect information you give us directly (like your name and ID documents), information collected automatically (like device and login data), and information generated by using the Platform (like transaction history).
- We use it to run your account, process transactions, verify your identity, prevent fraud, and provide support — not to sell it.
- We share information only in specific situations: with the providers who help deliver a service (payments, identity verification, shipping), when legally required, or with your consent — never as a general practice.
- Your information may be processed or stored outside your home country by our service providers.
- We keep information only as long as we need it for the purpose we collected it, including legal and security requirements.
- You have rights over your information — to access, correct, or ask us to delete it — described below.
- We use reasonable technical and organizational safeguards, but no online system can be guaranteed 100% secure.
MD,
            'body' => <<<'MD'
## About This Policy

This Privacy Policy explains how {{company_trading_name}} ("{{site_name}}", "we", "us", "our") collects, uses, shares, and protects personal information when you use the Platform.

**Data controller and contact.** {{company_legal_name}} is the data controller for the personal information described here. Registered address: {{company_registered_address}}. Privacy contact: {{privacy_email}}.

## Who This Policy Applies To

This policy applies to visitors, registered customers, agents, and merchants, and to anyone whose information reaches us because a customer used our services — for example, a China wallet funding recipient's name, or a shipping recipient's address.

## Information We Collect

**Information you provide directly:**

- Account details — name, email, phone number, country, password.
- Identity verification (KYC) information — document type and number, date of birth, address, uploaded ID documents, a selfie or liveness check where used.
- Payment method details you save (subject to what our payment providers allow us to store).
- China wallet recipient information you enter for a funding request (recipient account details, QR code, or similar).
- Marketplace order and shipping information — delivery address, recipient details, order contents.
- Anything you send us directly — support messages, agent chat messages, review content, survey responses.

**Information collected automatically:**

- Device information — device type, operating system, browser.
- Login and session information — timestamps, approximate location derived from IP address, and security signals used to detect suspicious activity.
- Usage information — pages viewed, features used, referring links.
- Cookies and similar technologies — see the [Cookie Policy](/legal/cookie-policy).

**Information generated by using the Platform:**

- Transaction records — deposits, wallet activity, funding requests, orders, refunds.
- Support and agent communications.
- Risk and fraud-review signals generated by our systems.

**Required vs. optional data.** Information needed to create an account, verify your identity, or complete a transaction you've requested is required — we can't provide that service without it. Information like marketing preferences or optional profile details is not required.

## How We Use Your Information

We use personal information to:

- Create and manage your account.
- Verify your identity and meet compliance obligations (KYC, sanctions screening, fraud prevention).
- Process deposits, China wallet funding, marketplace orders, and other transactions you request.
- Provide customer support and respond to your messages.
- Monitor for and prevent fraud, abuse, and unauthorized account access.
- Improve the reliability and usability of the Platform.
- Send transactional, security, and legal communications, and marketing communications where you've opted in.
- Meet legal, tax, and regulatory obligations.

## Who We Share Information With

We do not sell your personal information, and we don't share it as a general practice. We share it in specific, limited situations:

- **Payment providers** — to process a deposit, withdrawal, or payment you've initiated.
- **Identity-verification providers** — to complete KYC checks you've triggered.
- **Shipping agents and marketplace suppliers** — limited to what's needed to fulfil an order or shipment you've placed.
- **Technical service providers** — hosting, analytics, and infrastructure providers who process information on our behalf, under contract, only for the purposes we specify.
- **Government or legal requests** — where we're legally required to disclose information, or to protect the rights, safety, or property of {{site_name}}, our customers, or others.
- **With your consent** — for any other sharing not covered above.

## Cross-Border Data Transfers

Because we and our service providers operate across borders, your information may be processed or stored in a country other than the one you're in, including {{company_jurisdiction}} and the countries where our payment, verification, and hosting providers operate. Where required, we take steps intended to keep transferred information appropriately protected.

## Data Retention

We keep personal information only for as long as it's needed for the purpose it was collected, including to meet legal, tax, accounting, fraud-prevention, and dispute-resolution requirements. Retention periods differ by category of information (for example, KYC records are generally kept longer than a support chat log, to meet compliance obligations).

## Security

We use reasonable technical and organizational measures intended to protect personal information — including encryption of sensitive data, access controls, and monitoring for suspicious activity. No method of transmission or storage is completely secure, and we cannot guarantee absolute security. If we become aware of a breach affecting your information, we'll respond in line with our legal obligations.

## Automated Decisions and Human Review

Some fraud-prevention and risk decisions are supported by automated checks (for example, flagging an unusual transaction pattern). Where an automated check results in a hold or restriction, a human reviews it as part of our standard process.

## Your Privacy Rights

Depending on where you're located, you may have the right to:

- **Access** the personal information we hold about you.
- **Correct** inaccurate or incomplete information.
- **Request deletion** of information we no longer need to keep for a legal or operational reason.
- **Restrict or object** to certain processing.
- **Withdraw consent** where processing is based on consent (this doesn't affect processing already carried out).
- **Data portability**, where applicable, for information you provided to us.
- **Lodge a complaint** with your local data-protection authority, where one exists.

We honour requests we're able to fulfil, subject to identity verification and any legal obligation to retain certain information (for example, KYC and transaction records required for compliance). To make a request, contact {{privacy_email}}.

## Children's Privacy

{{site_name}} is not directed at children, and we don't knowingly collect personal information from anyone below the minimum age described in our [Terms of Service](/legal/terms-of-service). If we learn we've collected information from someone under that age, we'll take steps to delete it.

## Updates to This Policy

We may update this policy as our practices or legal obligations change. Material changes will be highlighted before they take effect.

## Contact Information

Privacy questions or rights requests: {{privacy_email}}. General support: {{support_email}}.
MD,
            'internal_review_notes' => "Needs confirmation of: actual data controller entity/address ({{company_legal_name}}/{{company_registered_address}}), whether a Data Protection Officer is required and who it is, which specific lawful bases apply per processing purpose (GDPR-style jurisdictions), which data-protection authority to reference per supported country, and confirmation of cross-border transfer safeguards actually in place with each named provider category. Children's minimum age should match the Terms of Service once that's finalized per jurisdiction.",
        ];
    }

    private function cookiePolicy($effective): array
    {
        return [
            'slug' => 'cookie-policy',
            'title' => 'Cookie Policy',
            'category' => 'general',
            'excerpt' => 'How {{site_name}} uses cookies and similar technologies, and how to control them.',
            'effective_date' => $effective,
            'plain_summary' => <<<'MD'
- Cookies are small files a website stores in your browser to remember information between visits.
- We use essential cookies (needed for login and security) that can't be turned off while you use the Platform, and optional cookies (preferences, analytics) that you can control.
- We do not load optional analytics or marketing technologies before you've made a cookie choice, where consent is legally required.
- You can change your choice at any time from the cookie preferences link in the site footer.
MD,
            'body' => <<<'MD'
## About This Policy

This Cookie Policy explains how {{site_name}} uses cookies and similar technologies (like browser local storage and device identifiers) when you visit the Platform, and how you can control them.

## What Cookies Are

A cookie is a small text file a website stores in your browser. It lets the site remember information about your visit — like whether you're logged in, or a preference you've set — the next time you load a page or return to the site.

## Categories of Cookies We Use

**Strictly necessary.** Required for the Platform to function — keeping you logged in, remembering items in your cart, and protecting against cross-site request forgery and other security risks. These can't be switched off while you use the Platform, because core features wouldn't work without them.

**Functional.** Remember preferences like your selected language, currency, or theme, so you don't have to reset them every visit.

**Analytics.** Help us understand how the Platform is used — which pages are visited, where errors happen — so we can improve it. These are optional.

**Marketing.** Used to measure the effectiveness of our own marketing and, where enabled, to show relevant content. These are optional and are not loaded until you've made a cookie choice, where consent is legally required.

## Third-Party Cookies

Some cookies are set by services we embed or rely on (for example, an analytics or fraud-prevention provider). We only allow third-party cookies that support a purpose described in this policy.

## Cookie Duration

Some cookies are **session cookies** and are deleted when you close your browser. Others are **persistent cookies** that remain until they expire or you delete them, used for things like remembering your login across visits.

## Local Storage and Device Identifiers

In addition to cookies, the Platform may use browser local storage (for example, to remember a draft form or a recently viewed item) and, in limited cases, device identifiers for fraud prevention. These are treated the same way as cookies under this policy where they're used for similar purposes.

## Managing Your Preferences

You can choose which optional cookie categories to allow using the cookie preferences link in the site footer, which lets you accept all, reject optional cookies, or customize by category. You can also control or delete cookies through your browser's own settings. If you reject optional cookies, the Platform will continue to work, but some conveniences (like remembering preferences) won't persist between visits.

## Updates to This Policy

We may update this policy as the technologies we use change. Material changes will be reflected in a new version with an updated effective date.

## Contact Information

Questions about this policy: {{privacy_email}}.
MD,
            'internal_review_notes' => "The interactive cookie-consent banner/preference manager and consent-record storage (category, version, timestamp, country) described in the full spec are Phase 3 work, not yet built. This page currently documents intended behavior ('not loaded until you've made a choice') ahead of the mechanism that enforces it; do not treat analytics/marketing scripts as gated until that ships. Flag to legal once the consent manager is live so this text matches actual behavior.",
        ];
    }

    private function acceptableUsePolicy($effective): array
    {
        return [
            'slug' => 'acceptable-use-policy',
            'title' => 'Acceptable Use Policy',
            'category' => 'general',
            'excerpt' => 'What you must not do on {{site_name}}, and what can happen if you do.',
            'effective_date' => $effective,
            'plain_summary' => <<<'MD'
- Don't use {{site_name}} for fraud, money laundering, sanctions evasion, or to buy or sell anything illegal.
- Don't use stolen payment methods, fake identity documents, or someone else's account without permission.
- Don't try to abuse promotions, referrals, or reviews, or attempt to bypass security controls or transaction limits.
- Breaking these rules can lead to a transaction being held, extra verification being required, a restriction or suspension, refund denial where legally valid, and, where legally required, a report to the relevant provider or authority.
- We don't publish the exact details of how we detect abuse, so this list isn't exhaustive.
MD,
            'body' => <<<'MD'
## About This Policy

This Acceptable Use Policy sets out what you must not do when using {{site_name}}. It applies alongside the [Terms of Service](/legal/terms-of-service), and breaking it is also a breach of those Terms.

## Prohibited Activity

You must not use the Platform to:

- Commit or attempt fraud of any kind, including identity theft.
- Use a stolen, unauthorized, or otherwise compromised payment method.
- Access or attempt to access another person's account without authorization.
- Launder money or attempt to move funds connected to criminal activity.
- Evade sanctions, export controls, or other legal restrictions.
- Manipulate a payment to reverse it improperly, or misuse the chargeback process (a chargeback is a request made through a bank or payment provider to reverse a payment — misusing it means disputing a legitimate transaction in bad faith).
- Submit false or altered identity documents, or fake proof-of-payment receipts.
- Create or use duplicate accounts to abuse limits, promotions, or referrals.
- Run automated attacks against the Platform, distribute malware, or scrape the Platform beyond what's permitted.
- Send spam, or harass, threaten, or abuse other users or our staff.
- Buy, sell, or list anything illegal, counterfeit, or that infringes someone else's intellectual property.
- Abuse independent shipping agents — for example, by requesting shipment of prohibited goods or providing false shipment details.
- Manipulate product reviews or ratings.
- Abuse the referral or promotion programs — for example, self-referral or coordinated fake sign-ups.
- Attempt to bypass transaction limits, KYC requirements, or other security controls.

This list gives examples and is not exhaustive — we may treat other clearly abusive conduct the same way even if it isn't listed here specifically.

## What Happens If This Policy Is Broken

Depending on what happened, we may:

- Place a transaction under review or on hold.
- Require additional identity or source-of-funds verification.
- Temporarily restrict specific features on your account.
- Suspend or terminate your account.
- Deny a refund where doing so is legally valid.
- Report the activity to a relevant payment provider, law enforcement, or regulator where we're legally required or permitted to.

We generally don't disclose the specific signals or thresholds we use to detect abuse, since publishing them would make them easier to evade.

## Contact Information

If you believe your account was restricted in error, contact {{support_email}}.
MD,
            'internal_review_notes' => 'No claims requiring evidence in this document. Keep wording consistent with the AML, Sanctions & Fraud Prevention Notice once written.',
        ];
    }

    private function depositPolicy($effective): array
    {
        return [
            'slug' => 'deposit-policy',
            'title' => 'Deposit Policy',
            'category' => 'money',
            'excerpt' => 'How deposits into your {{site_name}} wallet work, including processing times and what can go wrong.',
            'effective_date' => $effective,
            'plain_summary' => <<<'MD'
- You can add money to your wallet using the deposit methods shown in your account, which may include mobile money, bank transfer, card, or others depending on your country.
- Automated methods are usually confirmed within minutes; manual methods (like a bank transfer) need proof of payment and a review, which takes longer.
- A screenshot or receipt alone doesn't guarantee your deposit will be credited — we may need confirmation from the payment provider first.
- Deposits are reviewed for accuracy and fraud before being added to your available balance; unusual deposits can be held for extra checks.
- If a deposit fails, is a duplicate, or is for the wrong amount, contact support with your reference number.
MD,
            'body' => <<<'MD'
## About This Policy

This Deposit Policy explains how adding funds to your {{site_name}} Wallet works, from the methods available to how long it takes and what happens if something goes wrong.

## Services Covered

This policy covers deposits into your {{site_name}} Wallet. It does not cover sending funds onward to a China wallet, which is covered by the [China Wallet Funding Terms](/legal/china-wallet-funding-terms).

## Supported Deposit Methods

The deposit methods available to you depend on your country and account status, and are shown in your dashboard when you start a deposit. Depending on availability, these may include mobile money, bank transfer, card payment, and, where enabled, cryptocurrency.

**Automated deposits** are confirmed directly with the payment provider and typically credit your Wallet within minutes of the provider confirming payment.

**Manual deposits** (most commonly a bank transfer) require you to make the transfer using the reference we provide, then submit proof of payment for review. These take longer because a team member confirms the payment before crediting your Wallet.

## Deposit Instructions and References

Always use the exact deposit reference and account details shown to you at the time you start the deposit. Using an old or incorrect reference can delay or prevent your deposit from being matched and credited.

## Minimum and Maximum Amounts, and Fees

Minimum and maximum deposit amounts, and any fee, are shown before you confirm the deposit and can vary by method, country, and your verification level.

## Currency Conversion

If you deposit in a currency different from your Wallet's currency, the conversion is calculated using the rate shown to you at the time, consistent with the Exchange Rate & Fees Disclosure.

## Proof of Payment and Verification

For manual deposits, we generally require proof of payment (such as a bank confirmation) before crediting your Wallet. **A screenshot or receipt alone does not guarantee that your deposit will be credited** — we may need to confirm the payment directly with the provider or bank first, particularly for larger amounts or where something about the payment looks unusual.

## Pending, Failed, and Duplicate Deposits

**Pending** deposits are awaiting provider confirmation or manual review. **Failed** deposits (for example, a payment that didn't complete on the provider's side) are not credited; if you were charged for a failed deposit, contact {{support_email}} with your reference. **Duplicate** deposits (the same payment submitted or matched twice) are corrected once identified — see the [Refund & Reversal Policy](/legal/refund-reversal-policy).

## Incorrect Amount or Currency

If you send a different amount or currency than intended, contact {{support_email}} as soon as possible with your reference number. We'll review what's possible, which may include crediting the actual amount received (converted at the applicable rate) or, where that isn't possible, another resolution.

## Suspicious Deposits and Reviews

We may hold a deposit for additional review where it doesn't match your usual activity, appears linked to a third party's payment method rather than your own, or otherwise raises a fraud or compliance concern. This can delay when funds become available in your Wallet.

## Reporting a Problem

If a deposit hasn't appeared within the expected timeframe for your method, contact {{support_email}} with your deposit reference, the amount, and the date. Report a suspected problem as soon as you notice it — the sooner you report it, the easier it typically is to trace.

## Related Policies

See the [Refund & Reversal Policy](/legal/refund-reversal-policy) for how a duplicate, incorrect, or reversed deposit is handled, and the [Wallet Terms](/legal/wallet-terms) for how your balance works once it's credited.
MD,
            'internal_review_notes' => 'Minimum/maximum amounts and fee figures are intentionally not stated here (they vary by method/country and are shown live at deposit time) — confirm this is the correct approach rather than publishing a fee table. No specific deposit-reporting time window is stated; add one if operationally supported.',
        ];
    }

    private function chinaWalletFundingTerms($effective): array
    {
        return [
            'slug' => 'china-wallet-funding-terms',
            'title' => 'China Wallet Funding Terms',
            'category' => 'money',
            'excerpt' => 'How funding a China-based wallet (like Alipay or WeChat Pay) through {{site_name}} works.',
            'effective_date' => $effective,
            'plain_summary' => <<<'MD'
- You can use your {{site_name}} balance to fund a China-based wallet account you provide, at the exchange rate and fee shown before you confirm.
- **You are responsible for entering the correct recipient details.** Double-check the account, name, and QR code before confirming — funding sent to correctly submitted recipient information may not be recoverable once it's successfully delivered.
- Funding may be processed automatically or manually, and can be delayed by the provider on the China side, not just by us.
- A funding request can be held for extra verification, especially for larger amounts or if your account needs a higher KYC level.
- If a funding request fails, the amount is returned to your {{site_name}} balance, minus any fee that isn't refundable — see the Refund & Reversal Policy.
MD,
            'body' => <<<'MD'
## About This Policy

These China Wallet Funding Terms explain how using your {{site_name}} balance to fund a China-based wallet (such as Alipay or WeChat Pay) works, and what you're responsible for when you submit a funding request.

## Services Covered

This policy covers funding requests sent from your {{site_name}} Wallet to a China wallet account you provide. It does not cover depositing funds into your {{site_name}} Wallet in the first place — see the [Deposit Policy](/legal/deposit-policy) for that.

## Supported China Wallet Types

The China wallet types you can fund (for example, Alipay or WeChat Pay) are shown in your dashboard and depend on what's currently supported.

## Recipient Account Responsibility

**You are responsible for the accuracy of the recipient account you provide** — the account name, account identifier, and QR code (where used). Before confirming a funding request, carefully check that these details are correct and belong to the account you intend to fund.

> **Important:** Funding sent to correctly submitted recipient information may not be recoverable after it has been successfully delivered to that account. We do not claim that every China wallet transfer is irreversible in every situation — reversibility depends on the specific provider and transaction type — but you should treat a confirmed funding request as final once you submit it.

## Funding Amount, Exchange Rate, and Fees

The source amount (from your Wallet), the exchange rate, the delivered amount in the recipient currency, and any platform or provider fee are all shown to you before you confirm the request, consistent with the Exchange Rate & Fees Disclosure. Where a rate lock applies, it's shown with its expiry — if it expires before you confirm, you'll be shown a new rate.

## Funding Limits and KYC Level

The amount you can fund in a single request or over a period depends on your account's verification (KYC) level. Larger funding requests may require a higher KYC level before they can be processed — see the [KYC & Identity Verification Policy](/legal/kyc-identity-verification-policy).

## Wallet Balance Reservation

When you submit a funding request, the amount is reserved (held) from your available Wallet balance while the request is processed. It is only actually deducted once the funding is confirmed as delivered; if the request fails or is cancelled before that, the hold is released back to your available balance.

## Processing: Automated vs. Manual

Some funding requests are processed automatically through our provider integrations; others require manual processing by our team, particularly for larger amounts or where a review is triggered. Automated requests are typically completed faster; manual requests take longer because they involve a human step.

## Estimated Processing Times and Provider Delays

Estimated processing times are shown in your dashboard for reference. Actual delivery can be delayed by the China-side provider, network conditions, or a compliance review — these delays are often outside our control, and an estimated time is not a guarantee.

## Incorrect Recipient Details

If you realize you've entered incorrect recipient details **before** the funding is processed, contact {{support_email}} immediately — we'll try to stop it if it hasn't already been sent. Once funding has been successfully delivered to the account you provided, we may not be able to recover or reverse it, even if the details were wrong, because the funds have left our control.

## Suspended or Restricted Recipient Accounts

If the recipient account is suspended, restricted, or otherwise unable to receive funds, the provider may return the funding attempt. In that case, we'll process it under the [Refund & Reversal Policy](/legal/refund-reversal-policy).

## Completed, Failed, and Cancelled Funding

**Completed** funding has been confirmed as delivered to the recipient account. **Failed** funding did not complete — the reserved amount is returned to your available balance (fees already charged are handled per the Refund & Reversal Policy). You can **cancel** a funding request only while it's still pending and not yet sent to the provider; once it's been submitted for processing, it generally cannot be cancelled.

## Duplicate Funding Requests

If you submit the same funding request twice by mistake, contact {{support_email}} as soon as you notice — we'll review and correct a genuine duplicate.

## Recipient Disputes and Proof of Delivery

If the intended recipient says they didn't receive funding you believe was delivered, contact {{support_email}} with your reference number. Where available, we can provide confirmation of delivery from the provider to help resolve the question.

## Compliance Reviews

Funding requests, like other transactions, may be reviewed for compliance and fraud-prevention purposes, which can add delay. See the [AML, Sanctions & Fraud Prevention Notice](/legal/aml-sanctions-fraud-prevention-notice).

## Contact Information

Questions about a funding request: {{support_email}}, with your reference number.
MD,
            'internal_review_notes' => 'The exact reversibility of each supported China wallet provider/transaction type has not been independently verified — the "may not be recoverable" language is deliberately hedged rather than an absolute claim; confirm actual provider behavior before tightening this wording. Confirm whether a rate-lock duration is actually implemented; if not yet built, remove that sentence.',
        ];
    }

    private function refundReversalPolicy($effective): array
    {
        return [
            'slug' => 'refund-policy',
            'title' => 'Refund & Reversal Policy',
            'category' => 'money',
            'excerpt' => 'When you can get a refund, how a reversal works, and how long each takes.',
            'effective_date' => $effective,
            'plain_summary' => <<<'MD'
- **Cancellation** stops a request before it's completed. **Refund** returns eligible funds after a payment or purchase. **Reversal** corrects a wallet or transaction entry because the original payment was invalid, duplicated, or reversed on the payment provider's side.
- Some things are generally not refundable once completed — like successfully delivered China wallet funding, or a digital product once it's been revealed or redeemed — because they can't be "returned" the way a physical item can.
- We don't promise a fixed refund timeline for every case; where we can give an estimate, we do, but it depends on the payment provider or bank.
- If you think a charge was wrong, contact support first — going straight to a chargeback with your bank can be treated as a dispute under our Transaction Dispute Policy and may affect your account.
MD,
            'body' => <<<'MD'
## About This Policy

This Refund & Reversal Policy explains when funds are returned to you, how that works for different situations, and how it's different from a cancellation.

## Important Definitions

**Cancellation** — stopping a request before it's completed (for example, cancelling a funding request while it's still pending).

**Refund** — returning eligible funds after a payment or purchase has already gone through (for example, because a product couldn't be delivered).

**Reversal** — correcting a previous Wallet or transaction entry because the underlying payment was invalid, duplicated, or itself reversed (for example, a bank reverses a deposit after it was already credited to your Wallet).

**Chargeback** — a request made through your bank or payment provider to reverse a payment, rather than requesting a refund directly from us.

## Services Covered

This policy applies across the Platform — deposits, China wallet funding, marketplace orders (digital and physical), and withdrawals — alongside the specific rules in each service's own policy (Deposit Policy, China Wallet Funding Terms, Marketplace Terms, Digital Products Policy).

## When a Refund Applies

Refund eligibility depends on what happened:

- **Duplicate payment** — the same payment processed more than once.
- **Incorrect charge** — you were charged an amount that doesn't match what you authorized.
- **Failed delivery** — a digital product, gift card, eSIM, or physical order that could not be fulfilled.
- **Order cancellation** — a marketplace order cancelled before fulfilment, per the [Order & Cancellation Policy](/legal/order-cancellation-policy).
- **Failed China wallet funding** — a funding request that did not complete, per the [China Wallet Funding Terms](/legal/china-wallet-funding-terms).

## What Is Generally Not Refundable

Some things are difficult or impossible to "undo," so they're generally not refundable once they've happened:

- China wallet funding that has been successfully delivered to the recipient account.
- A digital product (licence key, gift-card code, eSIM QR code) once it has been revealed, activated, or redeemed.
- A completed marketplace order for a physical product, subject to that product's own return terms where offered.

Where a product or service page discloses different terms before you buy, those specific terms apply.

## Partial vs. Full Refunds

Depending on the situation, a refund may be for the full amount or a partial amount — for example, if only part of an order couldn't be fulfilled, or a non-refundable fee was already incurred by us with a third-party provider.

## Refund Destination, Currency, and Method

Refunds are generally returned to your {{site_name}} Wallet in the currency of the original transaction. Where a refund needs to go back to the original payment method instead (for example, a card refund), it follows that provider's own timeline, which we don't control.

## Processing Time

We aim to process eligible refunds promptly once approved, but we don't promise a fixed number of days for every case — timing can depend on the payment provider or bank handling the return. Where we can give you an estimate for your specific situation, we will.

## Fee Treatment

Platform and provider fees already incurred for a transaction may not be refundable, particularly where the fee covered work already done (for example, a provider fee charged the moment a funding request was submitted). Where a fee is refundable, it's returned along with the principal amount.

## Chargebacks

If you dispute a transaction directly with your bank or card issuer (a chargeback) instead of contacting us first, we treat that as a dispute under the [Transaction Dispute Policy](/legal/transaction-dispute-policy). We encourage you to contact {{support_email}} first — it's usually faster, and a chargeback for a legitimate transaction can be treated as chargeback abuse under the [Acceptable Use Policy](/legal/acceptable-use-policy).

## How to Request a Refund

Contact {{support_email}} with your transaction reference and what happened. We may ask for supporting evidence (for example, a screenshot of an error, or details of what wasn't received).

## If You Disagree With Our Decision

If we decline a refund and you believe that's wrong, you can ask for the decision to be reviewed by replying with any additional information — see the [Transaction Dispute Policy](/legal/transaction-dispute-policy) for the full process.

## Contact Information

Refund requests and questions: {{support_email}}.
MD,
            'internal_review_notes' => 'No fixed refund-timeline commitment is made here deliberately (spec explicitly says do not promise a specific period unless operationally supported) — if support can commit to a real SLA, add it. Confirm treatment of provider fees on failed transactions with finance before publishing as final.',
        ];
    }

    private function marketplaceTerms($effective): array
    {
        return [
            'slug' => 'marketplace-terms',
            'title' => 'Marketplace Terms',
            'category' => 'marketplace',
            'excerpt' => 'The rules governing purchases made through the {{site_name}} marketplace.',
            'effective_date' => $effective,
            'plain_summary' => <<<'MD'
- The {{site_name}} marketplace lets you buy digital products (gift cards, eSIMs, bill payments, and similar) and, where offered, physical products.
- Some products are sold directly by {{site_name}}; others are supplied by third-party suppliers or issuers — the product page tells you which.
- Prices, availability, and delivery timing can change, and we don't guarantee a specific product will always be in stock.
- Digital products can become non-refundable once delivered, revealed, or redeemed — this is shown before you buy.
- If something goes wrong with an order, contact support with your order reference.
MD,
            'body' => <<<'MD'
## About This Policy

These Marketplace Terms govern purchases made through the {{site_name}} marketplace, alongside the [Terms of Service](/legal/terms-of-service).

## Role of {{site_name}}

Depending on the product, {{site_name}} acts in one of these roles, shown on the product or order page:

- **Direct seller** — {{site_name}} sells the product directly.
- **Marketplace operator** — {{site_name}} operates the marketplace and payment flow, but the product is supplied by a third-party supplier or issuer (for example, many gift cards and eSIMs).
- **Connector to an independent provider** — {{site_name}} connects you with an independent shipping agent or service provider for fulfilment, under that provider's own terms as well as ours.

We do not present every product as directly sold by {{site_name}} when it is in fact supplied by a third party.

## Product Listings, Pricing, and Availability

Product information, images, and pricing are provided by us or by the relevant supplier and are shown at the time of purchase. Prices and availability can change without notice, and we don't guarantee that a listed product will remain available or that a listing is free of error — if a pricing or listing error is discovered before your order is fulfilled, we'll contact you with the corrected information before proceeding.

## Digital and Physical Products

The marketplace may include digital products (gift cards, eSIMs, airtime and data, bill payments, and similar) and, where enabled, physical products fulfilled through shipping agents. Each product type has its own additional policy — see the [Digital Products Policy](/legal/digital-products-policy) and [Shipping & Delivery Policy](/legal/shipping-delivery-policy).

## Order Acceptance and Payment

An order is confirmed once payment has been authorized from your Wallet or another available payment method. We may decline or cancel an order before fulfilment — for example, if the product becomes unavailable, a pricing error is found, or the order raises a fraud or compliance concern — in which case you'll be refunded.

## Fulfilment

Digital products are typically delivered to your account or email once payment is confirmed, subject to the processing times shown for that product. Physical products are fulfilled through a shipping agent per the Shipping & Delivery Policy.

## Customer and Supplier Responsibilities

You're responsible for providing accurate order details (recipient information, delivery address, phone number for eSIMs, and similar) — errors here can delay or prevent fulfilment. Third-party suppliers are responsible for the product they provide meeting its own description; where a product is defective or materially not as described, contact {{support_email}} so we can help resolve it with the supplier.

## Restricted Products and Authenticity

We don't knowingly list illegal, counterfeit, or prohibited products. If you believe a listing is counterfeit or infringes intellectual property, contact {{support_email}}.

## Reviews

Customers who've purchased a product may leave a review. Reviews must reflect a genuine experience — see the [Product Review Policy](/legal/product-review-policy) and [Community and User Content Rules](/legal/community-user-content-rules).

## Cancellations, Returns, and Refunds

See the [Order & Cancellation Policy](/legal/order-cancellation-policy) for when an order can be cancelled or changed, and the [Refund & Reversal Policy](/legal/refund-reversal-policy) for refund eligibility. Digital products can become non-refundable once delivered, revealed, or redeemed, as disclosed on the product page before purchase.

## Promotional Offers

Discounts, coupons, and promotions have their own eligibility rules, shown alongside the offer.

## Taxes

Where applicable, taxes on a marketplace purchase are calculated and shown before you confirm the order. You're responsible for any tax obligation that applies to you personally beyond what's collected at checkout (for example, import duties on a physical shipment — see the [Customs, Duties & Import Responsibility](/legal/customs-duties-import-responsibility) policy).

## Platform Intervention and Account Restrictions

We may intervene in an order — holding, cancelling, or reviewing it — where there's a fraud, compliance, or dispute concern, and may restrict marketplace access on an account involved in abuse, consistent with the [Acceptable Use Policy](/legal/acceptable-use-policy) and [Account Restriction & Suspension Policy](/legal/account-restriction-suspension-policy).

## Contact Information

Order questions: {{support_email}}, with your order reference.
MD,
            'internal_review_notes' => 'Confirm per-supplier whether LshopBridge is "direct seller" vs "marketplace operator" for each active product category so product pages can be labeled accurately and consistently with this policy.',
        ];
    }
}
