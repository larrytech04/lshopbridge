<?php

/*
|--------------------------------------------------------------------------
| Fee categories ("applies_to")
|--------------------------------------------------------------------------
|
| Single source of truth for what a fee rule can be attached to, used by the
| admin Fees UI, validation, and CSV export instead of a hardcoded list
| scattered across controllers/Blade/JS. "all" is a special wildcard handled
| directly by FeeCalculationService, not listed here.
|
| Only "deposit" and "funding" currently have a real call site
| (DepositService::quote(), FundingService::quote()) wired to the fee engine.
| The key stays "funding" (not renamed) because that literal string is what
| both services already pass to the fee engine — renaming it would silently
| stop existing fee rows from matching. The rest are honestly exposed so
| admins can pre-configure pricing ahead of those flows existing, but nothing
| in the app calls the fee engine for them yet — see the Fees page report.
|
*/

return [
    'deposit' => 'Deposit',
    'funding' => 'China Wallet Funding',
    'internal_transfer' => 'Internal Transfer',
    'currency_conversion' => 'Currency Conversion',
    'marketplace_order' => 'Marketplace Order',
    'seller_commission' => 'Seller Commission',
    'agent_commission' => 'Agent Commission',
    'shipping' => 'Shipping',
    'delivery' => 'Delivery',
    'refund' => 'Refund',
    'chargeback' => 'Chargeback',
    'card_processing' => 'Card Processing',
    'mobile_money_processing' => 'Mobile Money Processing',
    'bank_transfer' => 'Bank Transfer',
    'cryptocurrency' => 'Cryptocurrency',
    'priority_processing' => 'Priority Processing',
    'custom_service' => 'Custom Service',
];
