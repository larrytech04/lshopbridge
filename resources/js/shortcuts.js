/**
 * Centralized keyboard shortcut manager.
 *
 * One global keydown listener (never duplicated — see init()'s guard),
 * config-driven bindings (passed in from config/shortcuts.php via a data
 * attribute on <body>), typing-context aware, supports modifier combos
 * ("mod+k", "alt+h") and short multi-key sequences ("g s").
 *
 * "mod" resolves to Cmd on macOS and Ctrl everywhere else, matching the
 * GitHub/Linear/Notion convention this was modeled on.
 */
const IS_MAC = /Mac|iPod|iPhone|iPad/.test(navigator.platform || navigator.userAgent);
const SEQUENCE_TIMEOUT = 900; // ms between keys in a "g s" style sequence
const NAVIGATE_DELAY = 180;   // ms — lets the toast render before the page unloads

function isTypingContext(el) {
    if (!el) return false;
    const tag = el.tagName;
    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return true;
    if (el.isContentEditable) return true;
    return false;
}

/** Normalizes a keydown event into the same shape our binding strings use, e.g. "mod+shift+h". */
function eventToCombo(e) {
    const parts = [];
    if (IS_MAC ? e.metaKey : e.ctrlKey) parts.push('mod');
    if (e.altKey) parts.push('alt');
    if (e.shiftKey && e.key.length > 1) parts.push('shift'); // ignore shift for printable chars like "?"
    let key = e.key.toLowerCase();
    if (key === 'escape') key = 'esc';
    if (key === ' ') key = 'space';
    parts.push(key);
    return parts.join('+');
}

export const ShortcutManager = {
    bindings: [],
    sequenceBuffer: [],
    lastKeyAt: 0,
    started: false,
    actions: {},

    /** Registers what each `action` name in the config actually does. */
    registerAction(name, handler) {
        this.actions[name] = handler;
    },

    load(bindings) {
        this.bindings = (bindings || []).filter((b) => b.enabled !== false);
    },

    init() {
        if (this.started) return; // guards against duplicate listeners on Livewire/Turbo-style re-inits
        this.started = true;
        document.addEventListener('keydown', (e) => this.handle(e), true);
    },

    handle(e) {
        if (isTypingContext(document.activeElement) && e.key !== 'Escape') return;

        const combo = eventToCombo(e);
        const now = Date.now();
        if (now - this.lastKeyAt > SEQUENCE_TIMEOUT) this.sequenceBuffer = [];
        this.lastKeyAt = now;

        // Single-key/combo bindings (mod+k, alt+h, esc, ?, ...) — check before
        // touching the sequence buffer so plain letters don't eat "g"/"a"/"l".
        const direct = this.bindings.find((b) => b.key === combo);
        if (direct && !this.isSequence(direct.key)) {
            e.preventDefault();
            this.sequenceBuffer = [];
            this.fire(direct);
            return;
        }

        // Multi-key sequences ("g s", "a d", "l c", ...) — only the plain key
        // (no modifiers held) advances the buffer.
        if (!e.metaKey && !e.ctrlKey && !e.altKey && key_length_one(e.key)) {
            this.sequenceBuffer.push(e.key.toLowerCase());
            if (this.sequenceBuffer.length > 2) this.sequenceBuffer.shift();
            const seq = this.sequenceBuffer.join(' ');
            const match = this.bindings.find((b) => this.isSequence(b.key) && b.key === seq);
            if (match) {
                e.preventDefault();
                this.sequenceBuffer = [];
                this.fire(match);
            }
        }
    },

    isSequence(key) {
        return key.includes(' ');
    },

    fire(binding) {
        window.dispatchEvent(new CustomEvent('shortcut:fired', { detail: { label: binding.label } }));

        const handler = this.actions[binding.action];
        if (!handler) return;

        if (binding.action === 'navigate') {
            setTimeout(() => handler(binding), NAVIGATE_DELAY);
        } else {
            handler(binding);
        }
    },
};

function key_length_one(key) {
    return typeof key === 'string' && key.length === 1;
}

// ---- Default action wiring -------------------------------------------------
ShortcutManager.registerAction('open-palette', () => window.dispatchEvent(new CustomEvent('open-command-palette')));
ShortcutManager.registerAction('open-help', () => window.dispatchEvent(new CustomEvent('open-shortcuts-help')));
ShortcutManager.registerAction('close-overlay', () => window.dispatchEvent(new CustomEvent('close-overlays')));
// The header search bar is now a click-to-open trigger for the command
// palette (it's readonly), so "focus search" and "open palette" are the same action.
ShortcutManager.registerAction('focus-search', () => window.dispatchEvent(new CustomEvent('open-command-palette')));
ShortcutManager.registerAction('navigate', (binding) => { window.location = binding.url; });

window.ShortcutManager = ShortcutManager;
