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
    ],

    'defaults' => [
        ['key' => 'mod+k', 'action' => 'open-palette', 'label' => 'Open command palette', 'category' => 'global'],
        ['key' => 'mod+/', 'action' => 'focus-search', 'label' => 'Focus search', 'category' => 'global'],
        ['key' => '?', 'action' => 'open-help', 'label' => 'Open keyboard shortcuts help', 'category' => 'global'],
        ['key' => 'esc', 'action' => 'close-overlay', 'label' => 'Close modal / palette / drawer', 'category' => 'global'],

        ['key' => 'alt+h', 'action' => 'navigate', 'route' => 'dashboard', 'label' => 'Dashboard', 'category' => 'navigation'],
        ['key' => 'alt+b', 'action' => 'navigate', 'route' => 'wallet.index', 'label' => 'Wallet', 'category' => 'navigation'],
        ['key' => 'alt+d', 'action' => 'navigate', 'route' => 'deposit.index', 'label' => 'Deposit', 'category' => 'navigation'],
        ['key' => 'alt+w', 'action' => 'navigate', 'route' => 'funding.index', 'label' => 'Fund a China wallet', 'category' => 'navigation'],
        ['key' => 'alt+t', 'action' => 'navigate', 'route' => 'transactions.index', 'label' => 'Transactions', 'category' => 'navigation'],
        ['key' => 'alt+n', 'action' => 'navigate', 'route' => 'notifications.index', 'label' => 'Notifications', 'category' => 'navigation'],
        ['key' => 'alt+p', 'action' => 'navigate', 'route' => 'profile.edit', 'label' => 'Profile', 'category' => 'navigation'],
        ['key' => 'alt+s', 'action' => 'navigate', 'route' => 'profile.edit', 'label' => 'Settings', 'category' => 'navigation'],
        ['key' => 'alt+m', 'action' => 'navigate', 'route' => 'disputes.index', 'label' => 'Support', 'category' => 'navigation'],
        ['key' => 'mod+shift+h', 'action' => 'navigate', 'route' => 'dashboard', 'label' => 'Return to dashboard', 'category' => 'navigation'],
    ],

];
