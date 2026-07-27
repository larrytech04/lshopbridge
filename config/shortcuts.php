<?php

/*
|--------------------------------------------------------------------------
| Keyboard shortcut registry
|--------------------------------------------------------------------------
|
| Single source of truth for every keyboard shortcut LshopBridge ships with.
| The JS ShortcutManager (resources/js/shortcuts.js) reads this list (passed
| into the page as JSON) to register bindings, and the Shortcuts Help modal
| and Settings page render straight from it too, add a shortcut here and it
| shows up everywhere automatically.
|
| Each entry:
|   key         string   Combo the manager parses, e.g. "mod+k", "alt+h",
|                         "g s" (sequence: press g, then s within 900ms).
|                         "mod" means Ctrl on Windows/Linux, Cmd on macOS.
|   action      string   Action name dispatched to the JS action handler map.
|   route       string?  Route name to navigate to, when action is "navigate".
|   label       string   Shown in the palette / help modal / settings list.
|   category    string   Grouping key for the help modal and settings page.
|   role        string?  Restricts the shortcut to a role ('admin' covers
|                         admin+super_admin); null = available to anyone
|                         signed in.
|
*/

return [

    'categories' => [
        'global' => 'Global',
        'navigation' => 'Navigation',
        'users' => 'Users management',
        'dashboard' => 'Command Center',
        'kyc' => 'KYC review',
        'agents' => 'Agent management',
        'wallets' => 'China Wallet Accounts',
        'deposits' => 'Deposit Management',
        'funding' => 'China Wallet Funding',
        'rates' => 'Exchange Rates',
        'fees' => 'Fees & Charges',
        'products' => 'Products',
        'categories' => 'Product Categories',
        'orders' => 'Shop Orders',
        'platform-config' => 'Platform Configuration',
    ],

    'defaults' => [
        ['key' => 'mod+k', 'action' => 'open-palette', 'label' => 'Open command palette', 'category' => 'global'],
        ['key' => 'mod+/', 'action' => 'focus-search', 'label' => 'Focus search', 'category' => 'global'],
        ['key' => '?', 'action' => 'open-help', 'label' => 'Open keyboard shortcuts help', 'category' => 'global'],
        ['key' => 'esc', 'action' => 'close-overlay', 'label' => 'Close modal / palette / drawer', 'category' => 'global'],

        ['key' => 'alt+h', 'action' => 'navigate', 'route' => 'dashboard', 'label' => 'Dashboard', 'category' => 'navigation'],
        ['key' => 'alt+b', 'action' => 'navigate', 'route' => 'wallet.index', 'label' => 'Wallet', 'category' => 'navigation'],
        ['key' => 'alt+d', 'action' => 'navigate', 'route' => 'deposit.index', 'label' => 'Deposit', 'category' => 'navigation'],
        ['key' => 'alt+w', 'action' => 'navigate', 'route' => 'funding.create', 'label' => 'Fund China Wallet', 'category' => 'navigation'],
        ['key' => 'alt+t', 'action' => 'navigate', 'route' => 'transactions.index', 'label' => 'Transactions', 'category' => 'navigation'],
        ['key' => 'alt+n', 'action' => 'navigate', 'route' => 'notifications.index', 'label' => 'Notifications', 'category' => 'navigation'],
        ['key' => 'alt+p', 'action' => 'navigate', 'route' => 'profile.edit', 'label' => 'Profile', 'category' => 'navigation'],
        ['key' => 'alt+s', 'action' => 'navigate', 'route' => 'profile.edit', 'label' => 'Settings', 'category' => 'navigation'],
        ['key' => 'alt+m', 'action' => 'navigate', 'route' => 'disputes.index', 'label' => 'Support Tickets', 'category' => 'navigation'],
        ['key' => 'alt+x', 'action' => 'navigate', 'route' => 'shop.index', 'label' => 'Marketplace', 'category' => 'navigation'],
        ['key' => 'alt+o', 'action' => 'navigate', 'route' => 'shop.orders.index', 'label' => 'My Orders', 'category' => 'navigation'],
        ['key' => 'alt+v', 'action' => 'navigate', 'route' => 'wishlist.index', 'label' => 'Wishlist', 'category' => 'navigation'],
        ['key' => 'mod+shift+h', 'action' => 'navigate', 'route' => 'dashboard', 'label' => 'Return to dashboard', 'category' => 'navigation'],

        // Only active on Users management (handlers registered by that page).
        ['key' => '/', 'action' => 'users-search', 'label' => 'Focus search (Users management)', 'category' => 'users', 'role' => 'admin'],
        ['key' => 'n', 'action' => 'users-create', 'label' => 'Create user (Users management)', 'category' => 'users', 'role' => 'admin'],
        ['key' => 'shift+f', 'action' => 'users-filters', 'label' => 'Toggle filters (Users management)', 'category' => 'users', 'role' => 'admin'],
        ['key' => 'mod+e', 'action' => 'users-export', 'label' => 'Export users (Users management)', 'category' => 'users', 'role' => 'admin'],
        ['key' => 'mod+shift+r', 'action' => 'users-refresh', 'label' => 'Refresh users (Users management)', 'category' => 'users', 'role' => 'admin'],

        // Only active on the Command Center (handlers registered by that page). Plain
        // "/" is intentionally not reused here — the Users management page already
        // claims it, and the shortcut registry is shared across all admin pages.
        ['key' => 'd', 'action' => 'dash-daterange', 'label' => 'Open date-range selector (Command Center)', 'category' => 'dashboard', 'role' => 'admin'],
        ['key' => 't', 'action' => 'dash-transactions', 'label' => 'Jump to transaction monitor (Command Center)', 'category' => 'dashboard', 'role' => 'admin'],
        ['key' => 'a', 'action' => 'dash-attention', 'label' => 'Jump to attention center (Command Center)', 'category' => 'dashboard', 'role' => 'admin'],
        ['key' => 'r', 'action' => 'dash-refresh', 'label' => 'Refresh dashboard (Command Center)', 'category' => 'dashboard', 'role' => 'admin'],
        ['key' => 'shift+e', 'action' => 'dash-export', 'label' => 'Export report (Command Center)', 'category' => 'dashboard', 'role' => 'admin'],

        // Only active on KYC review (handlers registered by those pages). Keys chosen
        // to avoid every combo already claimed above — this registry is a flat,
        // page-agnostic keymap, so reusing a claimed key would silently no-op here.
        ['key' => 'k', 'action' => 'kyc-search', 'label' => 'Focus search (KYC review)', 'category' => 'kyc', 'role' => 'admin'],
        ['key' => 'f', 'action' => 'kyc-filters', 'label' => 'Toggle filters (KYC review)', 'category' => 'kyc', 'role' => 'admin'],
        ['key' => 'shift+x', 'action' => 'kyc-export', 'label' => 'Export queue CSV (KYC review)', 'category' => 'kyc', 'role' => 'admin'],
        ['key' => 'shift+a', 'action' => 'kyc-approve', 'label' => 'Open approve confirmation (KYC case)', 'category' => 'kyc', 'role' => 'admin'],
        ['key' => 'shift+r', 'action' => 'kyc-reject', 'label' => 'Open reject confirmation (KYC case)', 'category' => 'kyc', 'role' => 'admin'],
        ['key' => 'e', 'action' => 'kyc-escalate', 'label' => 'Open escalate confirmation (KYC case)', 'category' => 'kyc', 'role' => 'admin'],
        ['key' => 'shift+n', 'action' => 'kyc-note', 'label' => 'Jump to notes (KYC case)', 'category' => 'kyc', 'role' => 'admin'],

        // Only active on Agent Management. Deliberately fewer keys than other admin
        // pages (this page is intentionally the simplest of the three) — the mnemonic
        // letters spec'd (/, F, N, R) are all already claimed elsewhere in this flat
        // keymap, so fresh unclaimed letters are used instead.
        ['key' => 's', 'action' => 'agents-search', 'label' => 'Focus search (Agent Management)', 'category' => 'agents', 'role' => 'admin'],
        ['key' => 'l', 'action' => 'agents-filters', 'label' => 'Toggle filters (Agent Management)', 'category' => 'agents', 'role' => 'admin'],
        ['key' => 'p', 'action' => 'agents-add', 'label' => 'Add agent (Agent Management)', 'category' => 'agents', 'role' => 'admin'],

        // Only active on China Wallet Accounts. Same fresh-key constraint as the other
        // review pages above — this is intentionally the simplest of the review pages.
        ['key' => 'w', 'action' => 'wallets-search', 'label' => 'Focus search (China Wallet Accounts)', 'category' => 'wallets', 'role' => 'admin'],
        ['key' => 'g', 'action' => 'wallets-filters', 'label' => 'Toggle filters (China Wallet Accounts)', 'category' => 'wallets', 'role' => 'admin'],

        // Only active on Deposit Management. Shift+I matches the spec's own
        // requested "mark for investigation" combo since it was free; the rest use
        // fresh keys for the same reason documented on every review page above.
        ['key' => 'b', 'action' => 'deposits-search', 'label' => 'Focus search (Deposit Management)', 'category' => 'deposits', 'role' => 'admin'],
        ['key' => 'c', 'action' => 'deposits-filters', 'label' => 'Open filters (Deposit Management)', 'category' => 'deposits', 'role' => 'admin'],
        ['key' => 'u', 'action' => 'deposits-refresh', 'label' => 'Refresh deposits (Deposit Management)', 'category' => 'deposits', 'role' => 'admin'],
        ['key' => 'shift+j', 'action' => 'deposits-note', 'label' => 'Add internal note (Deposit Management)', 'category' => 'deposits', 'role' => 'admin'],
        ['key' => 'shift+i', 'action' => 'deposits-investigate', 'label' => 'Mark for investigation (Deposit Management)', 'category' => 'deposits', 'role' => 'admin'],

        // Only active on China Wallet Funding. Shift+P matches the spec's own
        // requested "start processing" combo since it was free.
        ['key' => 'm', 'action' => 'funding-search', 'label' => 'Focus search (China Wallet Funding)', 'category' => 'funding', 'role' => 'admin'],
        ['key' => 'o', 'action' => 'funding-filters', 'label' => 'Open filters (China Wallet Funding)', 'category' => 'funding', 'role' => 'admin'],
        ['key' => 'shift+u', 'action' => 'funding-refresh', 'label' => 'Refresh requests (China Wallet Funding)', 'category' => 'funding', 'role' => 'admin'],
        ['key' => 'shift+m', 'action' => 'funding-note', 'label' => 'Add internal note (China Wallet Funding)', 'category' => 'funding', 'role' => 'admin'],
        ['key' => 'shift+g', 'action' => 'funding-investigate', 'label' => 'Mark for investigation (China Wallet Funding)', 'category' => 'funding', 'role' => 'admin'],
        ['key' => 'shift+p', 'action' => 'funding-process', 'label' => 'Retry / start processing (China Wallet Funding)', 'category' => 'funding', 'role' => 'admin'],

        // Only active on Exchange Rates. Every mnemonic letter the spec asked for
        // (/, N, F, R, C, K) is already claimed elsewhere in this flat keymap, so
        // fresh letters are substituted, same as every review page above. "j"/Enter
        // were free and kept literal since they matched the spec directly; Esc
        // needs no dedicated entry — the page listens for the global
        // "close-overlays" event fired by the shared close-overlay action.
        ['key' => 'v', 'action' => 'rates-search', 'label' => 'Focus search (Exchange Rates)', 'category' => 'rates', 'role' => 'admin'],
        ['key' => 'q', 'action' => 'rates-filters', 'label' => 'Toggle filters (Exchange Rates)', 'category' => 'rates', 'role' => 'admin'],
        ['key' => 'y', 'action' => 'rates-add', 'label' => 'Add rate (Exchange Rates)', 'category' => 'rates', 'role' => 'admin'],
        ['key' => 'h', 'action' => 'rates-refresh', 'label' => 'Refresh rates (Exchange Rates)', 'category' => 'rates', 'role' => 'admin'],
        ['key' => 'x', 'action' => 'rates-calculator', 'label' => 'Open rate calculator (Exchange Rates)', 'category' => 'rates', 'role' => 'admin'],
        ['key' => 'j', 'action' => 'rates-next', 'label' => 'Navigate to next rate (Exchange Rates)', 'category' => 'rates', 'role' => 'admin'],
        ['key' => 'i', 'action' => 'rates-prev', 'label' => 'Navigate to previous rate (Exchange Rates)', 'category' => 'rates', 'role' => 'admin'],
        ['key' => 'enter', 'action' => 'rates-open', 'label' => 'Open highlighted rate (Exchange Rates)', 'category' => 'rates', 'role' => 'admin'],

        // Only active on Fees & Charges. By this point in the flat keymap every plain
        // letter except "z" is already claimed, so this page leans on shift+ combos
        // for everything except the one free plain letter and "enter" (which matched
        // the spec directly, same as Exchange Rates).
        ['key' => 'z', 'action' => 'fees-search', 'label' => 'Focus search (Fees & Charges)', 'category' => 'fees', 'role' => 'admin'],
        ['key' => 'shift+c', 'action' => 'fees-filters', 'label' => 'Toggle filters (Fees & Charges)', 'category' => 'fees', 'role' => 'admin'],
        ['key' => 'shift+b', 'action' => 'fees-add', 'label' => 'Add fee (Fees & Charges)', 'category' => 'fees', 'role' => 'admin'],
        ['key' => 'shift+h', 'action' => 'fees-refresh', 'label' => 'Refresh fees (Fees & Charges)', 'category' => 'fees', 'role' => 'admin'],
        ['key' => 'shift+d', 'action' => 'fees-calculator', 'label' => 'Open fee calculator (Fees & Charges)', 'category' => 'fees', 'role' => 'admin'],
        ['key' => 'shift+k', 'action' => 'fees-next', 'label' => 'Navigate to next fee (Fees & Charges)', 'category' => 'fees', 'role' => 'admin'],
        ['key' => 'shift+l', 'action' => 'fees-prev', 'label' => 'Navigate to previous fee (Fees & Charges)', 'category' => 'fees', 'role' => 'admin'],
        ['key' => 'enter', 'action' => 'fees-open', 'label' => 'Open highlighted fee (Fees & Charges)', 'category' => 'fees', 'role' => 'admin'],

        // Only active on Products. Every plain letter in this flat keymap is now
        // claimed by an earlier page, so this page uses fresh shift+ combos;
        // "enter" is reused across pages by design (see Exchange Rates/Fees above).
        ['key' => 'shift+o', 'action' => 'products-search', 'label' => 'Focus search (Products)', 'category' => 'products', 'role' => 'admin'],
        ['key' => 'shift+q', 'action' => 'products-add', 'label' => 'Add product (Products)', 'category' => 'products', 'role' => 'admin'],
        ['key' => 'shift+s', 'action' => 'products-import', 'label' => 'Open Import Center (Products)', 'category' => 'products', 'role' => 'admin'],
        ['key' => 'shift+t', 'action' => 'products-filters', 'label' => 'Open filters (Products)', 'category' => 'products', 'role' => 'admin'],
        ['key' => 'shift+v', 'action' => 'products-next', 'label' => 'Navigate to next product (Products)', 'category' => 'products', 'role' => 'admin'],
        ['key' => 'shift+w', 'action' => 'products-prev', 'label' => 'Navigate to previous product (Products)', 'category' => 'products', 'role' => 'admin'],
        ['key' => 'enter', 'action' => 'products-open', 'label' => 'Open highlighted product (Products)', 'category' => 'products', 'role' => 'admin'],

        // Only active on Product Categories.
        ['key' => 'shift+y', 'action' => 'categories-add', 'label' => 'Add category (Product Categories)', 'category' => 'categories', 'role' => 'admin'],
        ['key' => 'shift+z', 'action' => 'categories-add-sub', 'label' => 'Add subcategory to selected category (Product Categories)', 'category' => 'categories', 'role' => 'admin'],

        // Only active on Shop Orders. Every shift+ letter is now also claimed
        // (by Products above), so this page uses fresh alt+ combos instead —
        // alt+ was previously only used for global navigation, but nothing
        // prevents reuse for a page-scoped action once every other prefix is full.
        ['key' => 'alt+q', 'action' => 'orders-search', 'label' => 'Focus search (Shop Orders)', 'category' => 'orders', 'role' => 'admin'],
        ['key' => 'alt+j', 'action' => 'orders-next', 'label' => 'Navigate to next order (Shop Orders)', 'category' => 'orders', 'role' => 'admin'],
        ['key' => 'alt+k', 'action' => 'orders-prev', 'label' => 'Navigate to previous order (Shop Orders)', 'category' => 'orders', 'role' => 'admin'],
        ['key' => 'alt+r', 'action' => 'orders-refresh', 'label' => 'Refresh orders (Shop Orders)', 'category' => 'orders', 'role' => 'admin'],
        ['key' => 'enter', 'action' => 'orders-open', 'label' => 'Open highlighted order (Shop Orders)', 'category' => 'orders', 'role' => 'admin'],

        // Platform Configuration pages. Only the pages with a JS-triggered "add"
        // drawer get a shortcut (Payment Providers/Deposit Accounts/Countries use
        // always-visible inline forms instead, so there's no action to bind).
        ['key' => 'alt+a', 'action' => 'methods-add', 'label' => 'Add payment method (Payment Methods)', 'category' => 'platform-config', 'role' => 'admin'],
        ['key' => 'alt+c', 'action' => 'currencies-add', 'label' => 'Add currency (Currencies)', 'category' => 'platform-config', 'role' => 'admin'],
        ['key' => 'alt+g', 'action' => 'wallet-types-add', 'label' => 'Add wallet type (China Wallet Types)', 'category' => 'platform-config', 'role' => 'admin'],
    ],

];
