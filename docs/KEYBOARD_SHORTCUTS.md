# Keyboard shortcuts

LshopBridge ships a centralized keyboard-shortcut system: a global command
palette (Ctrl/Cmd+K), a searchable shortcuts-help modal (`?`), toast
confirmations, and per-user preferences — modeled on GitHub/Linear/Notion/VS
Code, adapted to what LshopBridge actually has (funding, deposits, shop,
agents, guides — no live chat or withdrawals yet, see **Adaptations** below).

This was built in phases. **Phase 1 (this document, shipped)** is the full
foundation plus the global/navigation shortcut set. **Phase 2 (not yet
built)** is the long tail of domain-specific shortcuts, admin-configurable
defaults, and a few polish items — listed at the bottom so nothing is lost.

## How it works (architecture)

| Piece | File | Role |
|---|---|---|
| Shortcut registry | `config/shortcuts.php` | Single source of truth: every shortcut's key combo, action, label, category, and (for `admin`) role restriction. Add an entry here and it appears in the palette, the help modal, and gets registered in JS automatically. |
| Preferences | `users.shortcuts_enabled`, `users.shortcut_overrides` (migration `2026_07_16_000000_add_shortcut_preferences_to_users`) | Per-user enable/disable flag + a JSON map of `"action|route" => "new+key+combo"` overrides. |
| Resolver | `resources/views/partials/shortcuts.blade.php` | Merges the config defaults with the signed-in user's overrides/enabled flag, resolves `route()` URLs server-side, and prints the result as `window.__SHORTCUTS__` JSON. Renders the toast container and includes the palette + help modal partials. |
| Manager | `resources/js/shortcuts.js` | One `keydown` listener (guarded against double-init), parses combos (`mod+k`, `alt+h`) and two-key sequences (`g s`), ignores keystrokes while typing in `input`/`textarea`/`select`/`contenteditable` (except Escape), dispatches to an action-handler map. |
| Search backend | `app/Http/Controllers/SearchController.php`, route `GET /search` | Powers the palette's live results. Permission-scoped: a regular user only ever searches their *own* deposits/transactions/orders plus public catalog/content; `Users`/`Reports` groups only run for `admin`/`super_admin`. Simple fuzzy match: every word in the query must appear somewhere in the target string. |
| Command palette | `resources/views/partials/command-palette.blade.php` | Ctrl/Cmd+K. Empty query shows a static "Most used" list; typing hits `/search` (debounced 250ms) and renders grouped results. Arrow keys + Enter navigate; Esc (or click-outside) closes. |
| Help modal | `resources/views/partials/shortcuts-help.blade.php` | `?`. Renders every entry from `config/shortcuts.php`, grouped by category, with a search box, a formatted key badge per shortcut, a per-row copy button, and a Print button. |
| Settings | `resources/views/dashboard/profile.blade.php` ("Keyboard shortcuts" card) | Enable/disable toggle (`PUT /profile/shortcuts`) and a "Restore defaults" button (`POST /profile/shortcuts/reset`, clears `shortcut_overrides`). |

## Shipped shortcuts

| Shortcut | Action |
|---|---|
| `Ctrl/Cmd + K` | Open the command palette |
| `Ctrl/Cmd + /` | Focus the header search bar |
| `?` | Open this shortcuts list |
| `Esc` | Close whichever palette/modal is open |
| `Alt + H` | Dashboard |
| `Alt + B` | Wallet |
| `Alt + D` | Deposit |
| `Alt + W` | Fund a China wallet *(see Adaptations — this is where "Withdraw" was mapped)* |
| `Alt + T` | Transactions |
| `Alt + N` | Notifications |
| `Alt + P` | Profile |
| `Alt + S` | Settings *(→ Profile, see Adaptations)* |
| `Alt + M` | Support |
| `Ctrl/Cmd + Shift + H` | Return to dashboard from anywhere |

All of these are declared once in `config/shortcuts.php` — nothing is
hardcoded in the JS or Blade files beyond the resolver above.

## Adaptations from the original spec

The original request was written against a feature set closer to a full
neobank (withdrawals, live chat, a standalone Settings page, a Notification
Center, tables with row-level shortcuts). LshopBridge doesn't have those
*yet*, so a few shortcuts were deliberately re-pointed at the closest real
feature instead of being wired to a page that doesn't exist:

- **Withdraw** → aliased to **Fund a China wallet** (`Alt+W`), since sending
  money out of the wallet *to* a China wallet is the closest existing concept
  to a withdrawal in this app.
- **Settings** → aliased to **Profile** (`Alt+S`), since there's no separate
  user-level settings page yet (only `profile.edit` and the admin settings
  area).
- **Support Chat** → aliased to the existing **Support ticket system**
  (`disputes.index`), since there's no live-chat widget.
- **Reports** (admin) → aliased to the **Audit log** (`admin.audit.index`),
  the closest existing "reports" concept.

If/when those real features get built, just update the `route` in
`config/shortcuts.php` — the palette, help modal, and JS all pick it up
automatically.

## Phase 2 — not yet built

These were in the original spec and are real, valuable follow-up work, not
dropped silently:

- Domain shortcut sets: wallet funding (`Shift+A/W/U/M/C/Q/B`), payments
  (`Ctrl+D/W/T/P/F/R`), marketplace (`G then S/A/O/C/F`), learning center
  (`L then C/V/B/I`), admin (`A then D/U/T/V/S/N`).
- Table shortcuts (arrow-key row navigation, multi-select, copy/export).
- Notification-center shortcuts (`N`, `Shift+N`, `R`, `Shift+R`).
- Modal shortcuts beyond Esc (Tab/Shift+Tab field order, Enter-to-confirm).
- Chat shortcuts (depends on a live-chat feature existing first).
- Power-user shortcuts (`Ctrl+Shift+D/T/B/L/N/P`).
- Accessibility shortcuts (`+`/`-`/`0` font size, `Ctrl+Shift+A`).
- Full per-shortcut remapping UI (today: enable/disable + restore-defaults
  only — the `shortcut_overrides` JSON column is ready for this, it just
  doesn't have an editor UI yet).
- Admin panel UI for editing the *default* shortcut set (today: edit
  `config/shortcuts.php` directly).
- Conflict detection when a user (or admin) assigns a key combo that's
  already in use.
- Automated tests for `ShortcutManager`'s combo/sequence parsing.

## Adding a new shortcut

1. Add an entry to `config/shortcuts.php` → `defaults`: `key`, `action`,
   `label`, `category`, and `route` (if `action` is `navigate`).
2. If it needs a new action (not just `navigate`), register a handler in
   `resources/js/shortcuts.js` via `ShortcutManager.registerAction(name, fn)`.
3. That's it — the palette's "Most used"/tabs are hand-picked in
   `command-palette.blade.php` if you also want it there, but the help modal
   and the actual key binding pick up config changes with zero other edits.
