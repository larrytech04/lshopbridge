import './bootstrap';
import 'flag-icons/css/flag-icons.min.css';
import 'tom-select/dist/css/tom-select.css';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';
import TomSelect from 'tom-select';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import { ShortcutManager } from './shortcuts';

Alpine.plugin(collapse);
Alpine.plugin(intersect);

/* --------------------------------------------------- GSAP (animations) */
// GreenSock — drives reactive / moving illustrations. Exposed globally so any
// Blade view can call `gsap.to(...)` or use the opt-in data-attribute helpers.
gsap.registerPlugin(ScrollTrigger);
window.gsap = gsap;
window.ScrollTrigger = ScrollTrigger;

// Opt-in helpers (no JS needed in views, just add the attribute):
//   data-float        → gentle infinite up/down float
//   data-reveal       → fade + slide up once it scrolls into view
//   data-float="3"    → optional: custom distance in px
function initGsapHelpers(root = document) {
    root.querySelectorAll('[data-float]').forEach((el) => {
        if (el._gsapFloat) return;
        el._gsapFloat = true;
        const dist = parseFloat(el.dataset.float) || 10;
        gsap.to(el, { y: -dist, duration: 2.6, ease: 'sine.inOut', yoyo: true, repeat: -1 });
    });
    root.querySelectorAll('[data-reveal]').forEach((el) => {
        if (el._gsapReveal) return;
        el._gsapReveal = true;
        gsap.from(el, {
            opacity: 0, y: 32, duration: 0.8, ease: 'power3.out',
            scrollTrigger: { trigger: el, start: 'top 85%', once: true },
        });
    });
}
// Empty-cart scene: the figure gently bobs while items drop into the cart on a
// repeating, staggered loop (with a soft landing). Honours reduced-motion.
function initCartScene(root = document) {
    root.querySelectorAll('[data-cart-scene]').forEach((scene) => {
        if (scene._cartGsap) return;
        scene._cartGsap = true;
        const fig = scene.querySelector('.cart-figure');
        if (fig) gsap.to(fig, { y: -4, duration: 1.6, ease: 'sine.inOut', yoyo: true, repeat: -1 });
        // One master timeline so the items drop one after another (sequentially),
        // each with a short gap before the next one falls, then the loop repeats.
        const master = gsap.timeline({ repeat: -1, repeatDelay: 0.6 });
        scene.querySelectorAll('.cart-drop').forEach((d) => {
            master.set(d, { opacity: 0, top: '2%', scale: 0.4, rotation: -15 })
                .to(d, { opacity: 1, duration: 0.16 })
                .to(d, { top: '58%', scale: 1, rotation: 8, duration: 0.7, ease: 'power1.in' }, '<')
                .to(d, { top: '61%', scale: 0.85, duration: 0.1, ease: 'power2.out' })
                .to(d, { opacity: 0, duration: 0.16 }, '-=0.04')
                .to({}, { duration: 0.4 });
        });
    });
}

window.PBGsap = { init: initGsapHelpers, cart: initCartScene, gsap, ScrollTrigger };
document.addEventListener('DOMContentLoaded', () => { initGsapHelpers(); initCartScene(); });
document.addEventListener('alpine:initialized', () => initCartScene());

/* --------------------------------------------------- Auto-hide header */
// On scroll-down the header's middle row (logo/search/actions) collapses; the
// utility strip and menu row stay pinned. Scroll-up brings it back anywhere.
// Anti-shake: collapsing the mid row shrinks the page, which makes the browser
// re-anchor the scroll position and fire a reverse scroll event — a feedback
// loop. So a spacer grows by exactly the mid row's height (same duration and
// easing) to keep the page height constant, and the mid row's max-height is
// driven in px (not a class) so both animate in lockstep.
function initAutoHideHeader() {
    const bar = document.querySelector('[data-autohide-header]');
    const mid = document.querySelector('.header-mid');
    const spacer = document.querySelector('[data-header-spacer]');
    if (!bar || !mid) return;

    let hidden = false;
    let expandTimer = null;

    const collapse = () => {
        clearTimeout(expandTimer);
        const h = mid.scrollHeight;
        mid.style.maxHeight = h + 'px';
        void mid.offsetHeight; // commit the start height so the shrink animates
        bar.classList.add('header-collapsed');
        mid.style.maxHeight = '0px';
        if (spacer) spacer.style.height = h + 'px';
    };
    const expand = () => {
        const h = mid.scrollHeight;
        bar.classList.remove('header-collapsed');
        mid.style.maxHeight = h + 'px';
        if (spacer) spacer.style.height = '0px';
        clearTimeout(expandTimer);
        // Once settled, release max-height so the row can grow (e.g. mobile search).
        expandTimer = setTimeout(() => { if (!hidden) mid.style.maxHeight = ''; }, 350);
    };

    let lastY = document.scrollingElement.scrollTop;
    const DEAD_ZONE = 8; // ignores momentum jitter between frames
    let ticking = false;

    document.addEventListener('scroll', () => {
        if (ticking) return;
        ticking = true;
        requestAnimationFrame(() => {
            const y = Math.max(0, document.scrollingElement.scrollTop); // clamp rubber-band overscroll
            const delta = y - lastY;

            if (y <= 60) {
                if (hidden) { hidden = false; expand(); }
            } else if (delta > DEAD_ZONE && !hidden) {
                hidden = true;
                collapse();
            } else if (delta < -DEAD_ZONE && hidden) {
                hidden = false;
                expand();
            }
            lastY = y;
            ticking = false;
        });
    }, { passive: true });
}
document.addEventListener('DOMContentLoaded', initAutoHideHeader);

/* --------------------------------------------------- Page navigation skeleton */
// Shows a full-page skeleton the instant an internal link/form navigates away,
// across User/Admin/Agent areas, holding briefly (HOLD_MS) before the real
// navigation so the transition reads as intentional without slowing pages down.
//
// We don't know the destination page's real markup yet (full page load, not an
// SPA), so instead of one generic shape we match the URL against known route
// patterns and pick the closest-shaped variant to show.
const SKELETON_VARIANTS = [
    // These three admin pages got a much larger, structurally different redesign than
    // their siblings that still share the generic buckets below — checked first so
    // they don't fall through to a mismatched generic shape.
    { variant: 'admin-user-profile', test: /\/admin\/users\/\d/ },
    { variant: 'admin-users-list', test: /\/admin\/users$/ },
    { variant: 'admin-command-center', test: /\/admin\/?$/ },
    { variant: 'admin-kyc-case', test: /\/admin\/kyc\/\d/ },
    { variant: 'admin-kyc-queue', test: /\/admin\/kyc$/ },
    { variant: 'admin-agents-list', test: /\/admin\/agents$/ },
    { variant: 'admin-wallets-list', test: /\/admin\/beneficiaries$/ },
    { variant: 'admin-deposits-list', test: /\/admin\/deposits$/ },
    { variant: 'admin-funding-list', test: /\/admin\/funding$/ },
    { variant: 'admin-rates-list', test: /\/admin\/rates$/ },
    { variant: 'admin-fees-list', test: /\/admin\/fees$/ },
    { variant: 'admin-products-list', test: /\/admin\/shop\/products$/ },
    { variant: 'admin-categories-tree', test: /\/admin\/shop\/categories$/ },
    { variant: 'admin-orders-list', test: /\/admin\/shop\/orders$/ },
    { variant: 'grid', test: /\/shop(\/c\/[^/]+)?$|\/shipping-agents$|\/marketplace$|\/learn$|\/china-guide$/ },
    { variant: 'detail', test: /\/shop\/p\/|\/fund\/\d|\/deposit\/\d|\/shop\/orders\/\d|\/admin\/shop\/orders\/\d|\/support\/\d|\/marketplace\/[^/]+$|\/china-guide\/[^/]+$|\/learn\/[^/]+$|\/admin\/(deposits|funding|agents|disputes|webhooks)\/\d/ },
    { variant: 'form', test: /\/deposit$|\/fund\/new$|\/profile$|\/checkout$|\/verification$|\/register|\/login$|\/admin\/(settings|integrations|channels|providers|payment-methods|countries|currencies|china-wallet-types|api-health)$/ },
    { variant: 'list', test: /\/transactions$|\/notifications$|\/beneficiaries$|\/support$|\/shop\/orders$|\/admin\/(risk|audit|disputes|webhooks|reviews)$|\/(leads|reviews)$/ },
    { variant: 'dashboard', test: /\/dashboard$|\/agent\/?$/ },
];
const DEFAULT_SKELETON_VARIANT = 'list';

function pickSkeletonVariant(url) {
    let path;
    try { path = new URL(url, window.location.origin).pathname; } catch { return DEFAULT_SKELETON_VARIANT; }
    const match = SKELETON_VARIANTS.find((v) => v.test.test(path));
    return match ? match.variant : DEFAULT_SKELETON_VARIANT;
}

function initPageSkeleton() {
    const el = document.getElementById('page-skeleton');
    if (!el) return;
    const variantEls = el.querySelectorAll('[data-skel-variant]');
    // Short beat before the real navigation starts: long enough that the skeleton
    // reads as a deliberate transition (not a flash), short enough that skeleton +
    // actual page load stays around a second total.
    const HOLD_MS = 500;

    // Restoring from bfcache (browser back/forward) — make sure it's hidden.
    window.addEventListener('pageshow', (e) => { if (e.persisted) el.classList.remove('is-active'); });

    /** Shows the matching skeleton variant immediately, then performs the real
     *  navigation after a short hold; the skeleton keeps covering the screen
     *  until the destination page paints. */
    const showThenGo = (url, go) => {
        const variant = pickSkeletonVariant(url);
        variantEls.forEach((v) => v.classList.toggle('hidden', v.dataset.skelVariant !== variant));
        requestAnimationFrame(() => el.classList.add('is-active'));
        setTimeout(go, HOLD_MS);
    };

    document.addEventListener('click', (e) => {
        if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
        const a = e.target.closest('a[href]');
        if (!a || a.hasAttribute('data-no-skeleton') || a.target === '_blank') return;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) return;
        if (a.origin && a.origin !== window.location.origin) return;
        e.preventDefault();
        showThenGo(href, () => { window.location.href = href; });
    });

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (e.defaultPrevented || !(form instanceof HTMLFormElement) || form.hasAttribute('data-no-skeleton')) return;
        e.preventDefault();
        showThenGo(form.action || window.location.href, () => form.submit());
    });

    // Exposed so other features (pull-to-refresh) can reuse the exact same
    // "show the matching skeleton, then perform the real navigation" beat
    // instead of a jarring blank-white location.reload().
    window.PBSkeleton = { showThenGo };
}
document.addEventListener('DOMContentLoaded', initPageSkeleton);

/* --------------------------------------------------- Searchable selects */
// Modern, type-to-filter country/language dropdowns (Tom Select), with flags.
function flagHtml(iso) {
    // Inline sizing so the flag renders identically in the dropdown AND in the
    // selected control item (where stock tom-select styles otherwise collapse it).
    return `<span class="fi fi-${String(iso).toLowerCase()} pb-ts-flag" style="display:inline-block;width:1.2rem;height:.85rem;border-radius:2px;background-size:cover;background-position:center;flex:none;box-shadow:inset 0 0 0 1px rgba(0,0,0,.15)"></span>`;
}

// Leading globe glyph for the language selector (uploaded Earth-Search asset, masked).
const langIconHtml = "<span class=\"png-icon\" style=\"display:inline-block;width:1.05rem;height:1.05rem;flex:none;background-color:currentColor;-webkit-mask:url('/assets/Earth-Search--Streamline-Ultimate.png') center/contain no-repeat;mask:url('/assets/Earth-Search--Streamline-Ultimate.png') center/contain no-repeat\"></span>";

function initSelects(root = document) {
    root.querySelectorAll('select[data-pbselect]').forEach((el) => {
        if (el.tomselect) return;
        const kind = el.dataset.pbselect; // 'country' | 'lang'
        const withFlag = kind === 'country';
        const ts = new TomSelect(el, {
            maxOptions: null,
            searchField: ['text', 'value'],
            sortField: { field: '$order' }, // keep server order
            // Search field lives INSIDE the dropdown (pinned to the bottom via CSS),
            // so the control pill never grows/extends while typing.
            plugins: ['dropdown_input'],
            render: {
                option: (d, esc) => `<div class="pb-ts-opt">${withFlag ? flagHtml(d.value) : ''}<span>${esc(d.text)}</span></div>`,
                item:   (d, esc) => `<div class="pb-ts-item">${withFlag ? flagHtml(d.value) : (kind === 'lang' ? langIconHtml : '')}<span>${esc(d.text)}</span></div>`,
                no_results: (_, esc) => `<div class="pb-ts-empty">${esc(el.dataset.empty || 'No matches')}</div>`,
            },
            onChange(value) {
                if (el.dataset.nav && value) window.location = el.dataset.nav.replace('__VALUE__', encodeURIComponent(value));
            },
        });
        // Translatable placeholder on the bottom search bar.
        if (ts.control_input && el.dataset.search) ts.control_input.placeholder = el.dataset.search;
        el._ts = ts;

        // Header quick-selectors open the full onboarding popup instead of their
        // own dropdown — capture-phase so this runs before Tom Select's own
        // control click handling.
        if (el.dataset.pbselectTrigger === 'onboarding') {
            ts.wrapper.addEventListener('mousedown', (e) => {
                e.preventDefault();
                e.stopPropagation();
                window.dispatchEvent(new CustomEvent('open-onboarding'));
            }, true);
        }
    });
}

window.PBSelect = { init: initSelects, flagHtml };
document.addEventListener('DOMContentLoaded', () => initSelects());
// Re-init for any content injected after first paint (Alpine transitions, etc.)
document.addEventListener('alpine:initialized', () => initSelects());

/* ------------------------------------------------------------------ Theme */
const THEME_KEY = 'pb-theme';
const validModes = ['light', 'dark', 'night', 'system'];

function systemDark() {
    return window.matchMedia('(prefers-color-scheme: dark)').matches;
}

function applyTheme(mode) {
    const root = document.documentElement;
    root.classList.remove('dark', 'night');
    const effective = mode === 'system' ? (systemDark() ? 'dark' : 'light') : mode;
    if (effective === 'dark') root.classList.add('dark');
    if (effective === 'night') root.classList.add('night');
    root.dataset.theme = mode;
}

function getThemeMode() {
    const stored = localStorage.getItem(THEME_KEY);
    return validModes.includes(stored) ? stored : 'system';
}

window.PBTheme = {
    get: getThemeMode,
    isDark() {
        return document.documentElement.classList.contains('dark') || document.documentElement.classList.contains('night');
    },
    set(mode) {
        if (!validModes.includes(mode)) mode = 'system';
        localStorage.setItem(THEME_KEY, mode);
        applyTheme(mode);
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: mode }));
    },
    toggle() {
        this.set(this.isDark() ? 'light' : 'dark');
    },
};

// React to OS changes while in "system" mode.
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (getThemeMode() === 'system') applyTheme('system');
});

// Global Alpine store so any toggle stays in sync.
Alpine.store('theme', {
    mode: getThemeMode(),
    set(mode) { this.mode = mode; window.PBTheme.set(mode); },
    is(mode) { return this.mode === mode; },
    // The actually-rendered theme (resolves "system"), so a single toggle always flips visibly.
    get isDark() {
        if (this.mode === 'dark' || this.mode === 'night') return true;
        if (this.mode === 'light') return false;
        return systemDark();
    },
    toggle() { this.set(this.isDark ? 'light' : 'dark'); },
});

/* ------------------------------------------------------------- Components */
Alpine.data('counter', (target = 0, duration = 1600, decimals = 0) => ({
    value: 0, target, duration, decimals, started: false,
    get display() {
        return Number(this.value).toLocaleString(undefined, { minimumFractionDigits: this.decimals, maximumFractionDigits: this.decimals });
    },
    start() {
        if (this.started) return;
        this.started = true;
        const t0 = performance.now();
        const step = (now) => {
            const p = Math.min((now - t0) / this.duration, 1);
            const eased = p === 1 ? 1 : 1 - Math.pow(2, -10 * p);
            this.value = this.target * eased;
            if (p < 1) requestAnimationFrame(step); else this.value = this.target;
        };
        requestAnimationFrame(step);
    },
}));

// Funding calculator: every figure (rate, margin, fee, total) comes from the
// same backend quote the real funding request will use — this component
// only formats and debounces, it never computes money itself.
Alpine.data('fundingQuote', (opts = {}) => ({
    amount: opts.amount ?? 0,
    appType: opts.appType ?? null,
    baseCurrency: opts.baseCurrency ?? 'XAF',
    targetCurrency: opts.targetCurrency ?? 'CNY',
    quoteUrl: opts.quoteUrl,
    quote: opts.initialQuote ?? null,
    wallets: opts.wallets ?? {},
    loading: false,
    error: null,
    _timer: null,
    init() {
        this.$watch('amount', () => this.debounced());
        this.$watch('appType', () => this.debounced());
    },
    debounced() {
        clearTimeout(this._timer);
        this._timer = setTimeout(() => this.fetchQuote(), 400);
    },
    async fetchQuote() {
        const amt = Number(this.amount);
        if (!amt || amt <= 0) { this.quote = null; this.error = null; return; }

        this.loading = true;
        this.error = null;
        try {
            const res = await fetch(this.quoteUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ amount: amt, app_type: this.appType }),
            });
            if (!res.ok) throw new Error('quote_failed');
            this.quote = await res.json();
        } catch (e) {
            this.error = 'unavailable';
        } finally {
            this.loading = false;
        }
    },
    money(v, c) { return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' ' + (c ?? ''); },
    rateLabel() {
        if (!this.quote?.exchange_rate) return '';
        const r = Number(this.quote.exchange_rate);
        return `1 ${this.baseCurrency} = ${r.toFixed(6).replace(/0+$/, '').replace(/\.$/, '')} ${this.targetCurrency}`;
    },
    rateAgo() {
        if (!this.quote?.rate_updated_at) return null;
        const when = new Date(this.quote.rate_updated_at);
        const mins = Math.max(0, Math.round((Date.now() - when.getTime()) / 60000));
        if (mins < 1) return 'just now';
        if (mins < 60) return `${mins} minute${mins === 1 ? '' : 's'} ago`;
        if (mins < 24 * 60) {
            const hours = Math.round(mins / 60);
            return `${hours} hour${hours === 1 ? '' : 's'} ago`;
        }
        // Beyond a day, an absolute timestamp reads more honestly than a
        // large, alarming-looking relative count (e.g. "428 hours ago").
        return when.toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
    },
}));

/* ---------------------------------------------------------- Help Center */
// Every FAQ is loaded once (the corpus is small); search/category filtering
// happens entirely client-side, so results are genuinely instant with no
// network round-trip and no debounce-to-server complexity to build.
Alpine.data('helpCenter', (opts = {}) => ({
    query: '',
    activeCategory: 'all',
    faqs: opts.faqs ?? [],
    openId: null,
    highlighted: -1,
    init() {
        this.$watch('query', () => { this.highlighted = -1; });
        window.addEventListener('keydown', (e) => {
            const tag = document.activeElement?.tagName;
            const typing = tag === 'INPUT' || tag === 'TEXTAREA';
            if ((e.ctrlKey || e.metaKey) && e.key.toLowerCase() === 'k') {
                e.preventDefault();
                this.$refs.searchInput?.focus();
            } else if (e.key === '/' && !typing) {
                e.preventDefault();
                this.$refs.searchInput?.focus();
            }
        });
    },
    get isSearching() { return this.query.trim().length >= 2; },
    get filtered() {
        const q = this.query.trim().toLowerCase();
        let list = this.faqs;
        if (!this.isSearching && this.activeCategory !== 'all') {
            list = list.filter((f) => f.category === this.activeCategory);
        }
        if (this.isSearching) {
            list = list.filter((f) =>
                f.question.toLowerCase().includes(q) ||
                f.answer.toLowerCase().includes(q) ||
                f.categoryLabel.toLowerCase().includes(q));
        }
        return list;
    },
    get resultCountLabel() {
        const n = this.filtered.length;
        return `${n} help result${n === 1 ? '' : 's'} found`;
    },
    selectCategory(cat) {
        this.activeCategory = cat;
        this.query = '';
    },
    toggle(id) {
        this.openId = this.openId === id ? null : id;
    },
    onArrow(dir) {
        const max = this.filtered.length - 1;
        if (max < 0) return;
        this.highlighted = Math.min(max, Math.max(0, this.highlighted + dir));
        this.$nextTick(() => {
            document.getElementById('help-result-' + this.highlighted)?.scrollIntoView({ block: 'nearest' });
        });
    },
    onEnter() {
        const item = this.filtered[this.highlighted];
        if (item) this.toggle(item.id);
    },
}));

/* -------------------------------------------------------- Install app */
// Chrome/Edge (desktop and Android) fire `beforeinstallprompt`, which we
// capture and replay on click — that's the real one-tap install. Neither
// iOS Safari nor some Android browser/version combos ever fire it (no
// programmatic install API, or the criteria/timing didn't line up), so on
// those we fall back to showing the manual "how to add it yourself" steps
// for whichever platform the visitor is actually on. Once running
// standalone (already installed), the box hides itself.
Alpine.data('installApp', () => ({
    deferredPrompt: null,
    canInstall: false,
    isIOS: false,
    isAndroid: false,
    isStandalone: false,
    showSteps: false,
    init() {
        this.isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
        this.isAndroid = /android/i.test(navigator.userAgent);
        this.isStandalone = window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.canInstall = true;
        });
        window.addEventListener('appinstalled', () => { this.isStandalone = true; });
    },
    get show() { return !this.isStandalone && (this.canInstall || this.isIOS || this.isAndroid); },
    async promptInstall() {
        if (this.deferredPrompt) {
            this.deferredPrompt.prompt();
            await this.deferredPrompt.userChoice;
            this.deferredPrompt = null;
            this.canInstall = false;
            return;
        }
        // No native prompt available (iOS, or Android without the event) — show manual steps.
        this.showSteps = true;
    },
}));

/* ------------------------------------------------------- Bottom dock */
// Slim glass dock with an iOS-style highlight that slides between tabs —
// it eases from the previously-active tab to the current one on each load,
// and follows taps before navigation.
Alpine.data('appDock', () => ({
    menu: false,
    init() {
        const root = this.$el;
        const ind = root.querySelector('[data-dock-indicator]');
        const nav = ind ? ind.parentElement : null;
        const slots = nav ? Array.from(nav.querySelectorAll('[data-dock-slot]')) : [];
        if (!ind || !nav || !slots.length) return;

        const n = slots.length;
        let active = Math.min(n - 1, Math.max(0, parseInt(nav.dataset.active ?? '0', 10) || 0));
        const EASE = 'transform .5s cubic-bezier(.34,1.4,.5,1), width .5s cubic-bezier(.34,1.4,.5,1)';

        const place = (i, animate) => {
            const el = slots[i];
            if (!el) return;
            ind.style.transition = animate ? EASE : 'none';
            ind.style.height = slots[0].offsetHeight + 'px';
            ind.style.width = el.offsetWidth + 'px';
            ind.style.transform = `translateX(${el.offsetLeft}px)`;
            ind.style.opacity = '1';
        };

        let last = parseInt(sessionStorage.getItem('pb-dock'), 10);
        if (isNaN(last) || last < 0 || last >= n) last = active;
        place(last, false);                                   // start at previous tab
        requestAnimationFrame(() => requestAnimationFrame(() => place(active, true))); // slide to current
        sessionStorage.setItem('pb-dock', String(active));

        // Navigation links animate once — via the load-slide on the next page.
        // Only the Menu button (last slot) doesn't navigate, so move it on toggle.
        this.$watch('menu', (open) => place(open ? n - 1 : active, true));
        window.addEventListener('resize', () => place(active, false));

        // Warm the destination up the moment a finger/cursor touches a tab —
        // by the time the tap actually registers (~100-150ms later) the page
        // is usually already in flight, so the real navigation lands closer
        // to instant instead of starting cold on click.
        const prefetched = new Set();
        const warm = (href) => {
            if (!href || prefetched.has(href)) return;
            prefetched.add(href);
            const link = document.createElement('link');
            link.rel = 'prefetch';
            link.href = href;
            document.head.appendChild(link);
        };
        // Swap the page in-place instead of a full browser navigation: the
        // indicator slides the instant a tab is tapped, then the new <main>
        // (already warmed by the prefetch above) drops in via a soft
        // crossfade rather than a hard white-flash reload. Any failure
        // (offline, non-2xx, no <main> found) falls back to a normal link
        // click so nothing is ever left in a broken half-navigated state.
        const swapPage = async (url, index) => {
            const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            if (!res.ok) throw new Error(`bad status ${res.status}`);
            const doc = new DOMParser().parseFromString(await res.text(), 'text/html');
            const newMain = doc.querySelector('main');
            const oldMain = document.querySelector('main');
            if (!newMain || !oldMain) throw new Error('missing <main>');

            const apply = () => {
                document.title = doc.title;
                oldMain.replaceWith(newMain);
                window.Alpine.initTree(newMain);
                window.scrollTo(0, 0);
            };
            if (document.startViewTransition) {
                await document.startViewTransition(apply).finished;
            } else {
                apply();
            }
            history.pushState({}, '', url);
            sessionStorage.setItem('pb-dock', String(index));
            active = index; // so re-closing the Menu sheet returns to THIS tab, not the page's original one
        };

        slots.forEach((el, i) => {
            if (el.tagName !== 'A' || !el.href) return;
            el.addEventListener('touchstart', () => warm(el.href), { passive: true });
            el.addEventListener('mousedown', () => warm(el.href));
            el.addEventListener('click', (e) => {
                if (e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) return;
                const url = el.href;
                e.preventDefault();
                this.menu = false; // tapping any other tab dismisses an open Menu sheet
                if (url === location.href) return;
                place(i, true);
                swapPage(url, i).catch(() => { location.href = url; });
            });
        });
    },
}));

window.addEventListener('popstate', () => location.reload());

/* ------------------------------------------------- Services carousel */
// Auto-rotating service explainer: the active item expands to show its
// title + description. Autoplay starts when it scrolls into view (x-intersect).
Alpine.data('serviceCarousel', (services = []) => ({
    services,
    active: 0,
    timer: null,
    shown: false,
    start() { this.play(); },
    play() {
        clearInterval(this.timer);
        this.timer = setInterval(() => { this.active = (this.active + 1) % this.services.length; }, 3500);
    },
    stop() { clearInterval(this.timer); },
    go(i) { this.active = i; this.play(); },
}));

/* ----------------------------------------------------- Typewriter */
// Types a word, pauses, deletes it, then moves to the next — looping.
Alpine.data('typewriter', (words = []) => ({
    words: words.length ? words : [''],
    txt: '',
    wi: 0,
    ci: 0,
    deleting: false,
    init() { this.tick(); },
    tick() {
        const word = this.words[this.wi] || '';
        if (this.deleting) {
            this.ci = Math.max(0, this.ci - 1);
            this.txt = word.slice(0, this.ci);
            if (this.ci === 0) {
                this.deleting = false;
                this.wi = (this.wi + 1) % this.words.length;
                return void setTimeout(() => this.tick(), 450);
            }
            return void setTimeout(() => this.tick(), 55);
        }
        this.ci++;
        this.txt = word.slice(0, this.ci);
        if (this.ci >= word.length) {
            this.deleting = true;
            return void setTimeout(() => this.tick(), 1500);
        }
        setTimeout(() => this.tick(), 95);
    },
}));

/* ------------------------------------------- Announcement bar (flips every 6s) */
Alpine.data('announceBar', (messages = []) => ({
    messages: messages.length ? messages : [''],
    i: 0,
    flipping: false,
    open: true,
    init() {
        if (localStorage.getItem('pb-announce') === 'closed') { this.open = false; return; }
        if (this.messages.length < 2) return;
        setInterval(() => {
            this.flipping = true;
            setTimeout(() => { this.i = (this.i + 1) % this.messages.length; this.flipping = false; }, 300);
        }, 6000);
    },
    dismiss() { this.open = false; localStorage.setItem('pb-announce', 'closed'); },
}));

/* ------------------------------------------- Review badge flip (Google/Trustpilot) */
Alpine.data('reviewFlip', () => ({
    flipped: false,
    init() { setInterval(() => { this.flipped = !this.flipped; }, 3500); },
}));

/* ----------------------------------------------------- Onboarding popup */
// Typewriter greeting: the name part ("Hi Larry") types once and STAYS, then a
// separator appears and the second part rotates in rounds of two sentences
// (fund → shop-product), each typed/deleted when its turn comes around.
Alpine.data('typeGreet', (name = '', phrases = []) => ({
    fixed: '',
    txt: '',
    sep: false,
    i: 0,
    j: 0,
    deleting: false,
    start() {
        this.fixed = ''; this.txt = '';
        if (name.length) this.typeName(0); else { this.sep = true; this.tick(); }
    },
    typeName(n) {
        this.fixed = name.slice(0, n + 1);
        if (n + 1 < name.length) return setTimeout(() => this.typeName(n + 1), 60);
        this.sep = true;
        setTimeout(() => this.tick(), 500);
    },
    tick() {
        if (!phrases.length) return;
        const full = phrases[this.i];
        if (!this.deleting) {
            this.txt = full.slice(0, ++this.j);
            if (this.j === full.length) {
                this.deleting = true;
                return setTimeout(() => this.tick(), 2000); // linger, fully typed
            }
            return setTimeout(() => this.tick(), 48);
        }
        this.txt = full.slice(0, --this.j);
        if (this.j === 0) {
            this.deleting = false;
            this.i = (this.i + 1) % phrases.length;
            return setTimeout(() => this.tick(), 400);
        }
        setTimeout(() => this.tick(), 22);
    },
}));

Alpine.data('onboarding', (geoDefault = '', hasGeo = false) => ({
    open: false,
    theme: window.PBTheme ? window.PBTheme.get() : 'system',
    init() {
        // Opens only when triggered externally (header country/language pill
        // dispatches 'open-onboarding' — see the x-on listener in the Blade root).
        // If the server couldn't geolocate, try a client-side IP lookup and
        // preselect the country in the Tom Select control.
        if (!hasGeo) {
            fetch('https://ipapi.co/json/')
                .then((r) => r.json())
                .then((d) => {
                    const sel = this.$root.querySelector('#ob-country');
                    if (d && d.country_code && sel && sel.tomselect && sel.querySelector(`option[value="${d.country_code}"]`)) {
                        sel.tomselect.setValue(d.country_code, true);
                    }
                })
                .catch(() => {});
        }
    },
    setTheme(t) { this.theme = t; if (window.PBTheme) window.PBTheme.set(t); },
    skip() { this.open = false; },
    finish() {
        const country = this.$root.querySelector('#ob-country')?.value || geoDefault;
        const locale = this.$root.querySelector('#ob-locale')?.value || 'en';
        const base = this.$root.dataset.onboardUrl;
        window.location = `${base}?country=${encodeURIComponent(country)}&locale=${encodeURIComponent(locale)}`;
    },
}));

Alpine.data('welcomeIntro', (geoDefault = '', hasGeo = false) => ({
    open: false,
    init() {
        if (localStorage.getItem('pb-welcomed')) return;
        setTimeout(() => { this.open = true; initSelects(this.$root); }, 600);
        // If the server couldn't geolocate, try a client-side IP lookup and
        // preselect the country in the Tom Select control.
        if (!hasGeo) {
            fetch('https://ipapi.co/json/')
                .then((r) => r.json())
                .then((d) => {
                    const sel = this.$root.querySelector('#wi-country');
                    if (d && d.country_code && sel && sel.tomselect && sel.querySelector(`option[value="${d.country_code}"]`)) {
                        sel.tomselect.setValue(d.country_code, true);
                    }
                })
                .catch(() => {});
        }
    },
    skip() { localStorage.setItem('pb-welcomed', '1'); this.open = false; },
    finish() {
        localStorage.setItem('pb-welcomed', '1');
        const country = this.$root.querySelector('#wi-country')?.value || geoDefault;
        const base = this.$root.dataset.onboardUrl;
        window.location = `${base}?country=${encodeURIComponent(country)}`;
    },
}));

// Floating hero bubbles drift on their own CSS animation; this layers an extra
// push offset on top so they visibly nudge away from the cursor, then ease back.
Alpine.data('pushBubble', () => ({
    dx: 0,
    dy: 0,
    push(e) {
        const rect = this.$el.getBoundingClientRect();
        const cx = rect.left + rect.width / 2;
        const cy = rect.top + rect.height / 2;
        const distX = cx - e.clientX;
        const distY = cy - e.clientY;
        const dist = Math.hypot(distX, distY);
        const radius = 130;
        if (dist < radius && dist > 0.01) {
            const force = (radius - dist) / radius;
            this.dx = (distX / dist) * force * 45;
            this.dy = (distY / dist) * force * 45;
        } else {
            this.dx = 0;
            this.dy = 0;
        }
    },
}));

/* ------------------------------------------------------ Keyboard shortcuts */
const IS_MAC_HELP = /Mac|iPod|iPhone|iPad/.test(navigator.platform || navigator.userAgent);

// Minimal icon path map for the command palette / help modal, where the icon
// name comes from server-supplied JSON (not a compile-time Blade component).
// Paths mirror <x-icon> exactly for the subset of names search results use.
const SHORTCUT_ICON_PATHS = {
    wallet: '<path d="M17 8V6.5A1.5 1.5 0 0 0 15.5 5H6.5A2.5 2.5 0 0 0 4 7.5v9A2.5 2.5 0 0 0 6.5 19h12a1.5 1.5 0 0 0 1.5-1.5V10a2 2 0 0 0-2-2H6.5"/><circle cx="16.5" cy="13.5" r="1.1" fill="currentColor" stroke="none"/>',
    deposit: '<path d="M12 3v11"/><path d="m8 10 4 4 4-4"/><path d="M5 21h14a0 0 0 0 1 0 0"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
    fund: '<path d="M12 21V10"/><path d="m8 14 4-4 4 4"/><path d="M4 7V5a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v2"/>',
    chart: '<path d="M3 3v16a2 2 0 0 0 2 2h16"/><path d="M18 9l-5 5-3-3-4 4"/>',
    receipt: '<path d="M5 3v18l2-1 2 1 2-1 2 1 2-1 2 1V3l-2 1-2-1-2 1-2-1-2 1-2-1Z"/><path d="M8 8h8M8 12h8M8 16h5"/>',
    shield: '<path d="M12 3 5 6v5.5c0 4.3 2.9 7.4 7 8.5 4.1-1.1 7-4.2 7-8.5V6l-7-3Z"/><path d="m9.2 12 1.9 1.9 3.7-3.8"/>',
    users: '<circle cx="9" cy="8" r="3.2"/><path d="M3.5 20a5.5 5.5 0 0 1 11 0"/><path d="M16 5.2a3.2 3.2 0 0 1 0 5.6"/><path d="M17.5 14a5 5 0 0 1 3 6"/>',
    user: '<circle cx="12" cy="8" r="3.5"/><path d="M5 20a7 7 0 0 1 14 0"/>',
    cog: '<circle cx="12" cy="12" r="3"/><path d="M12 2v3M12 19v3M4.2 4.2l2.1 2.1M17.7 17.7l2.1 2.1M2 12h3M19 12h3M4.2 19.8l2.1-2.1M17.7 6.3l2.1-2.1"/>',
    flag: '<path d="M5 21V4"/><path d="M5 4h11l-1.6 3.2L16 11H5"/>',
    bell: '<path d="M18 8a6 6 0 1 0-12 0c0 6-2.5 7-2.5 7h17S18 14 18 8Z"/><path d="M10.3 21a2 2 0 0 0 3.4 0"/>',
    home: '<path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.7V20a1 1 0 0 0 1 1H9.5v-5.5a1 1 0 0 1 1-1h3a1 1 0 0 1 1 1V21h3a1 1 0 0 0 1-1V9.7"/>',
    book: '<path d="M5 4.5A1.5 1.5 0 0 1 6.5 3H18a1 1 0 0 1 1 1v15a1 1 0 0 1-1 1H6.5A1.5 1.5 0 0 1 5 18.5Z"/><path d="M5 17.5A1.5 1.5 0 0 1 6.5 16H19"/><path d="M9 7.5h6"/>',
    truck: '<path d="M3 6.5A1.5 1.5 0 0 1 4.5 5H14v9.5H3Z"/><path d="M14 8.5h3.5L21 12v2.5h-7Z"/><circle cx="7" cy="17.5" r="1.8"/><circle cx="17" cy="17.5" r="1.8"/>',
    mail: '<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="m4 7 8 5.5L20 7"/>',
    bag: '<path d="M6.5 8h11a1 1 0 0 1 1 1.1l-.85 10A2 2 0 0 1 14.66 21H9.34a2 2 0 0 1-1.99-1.9l-.85-10A1 1 0 0 1 6.5 8Z"/><path d="M9 8.5V7a3 3 0 0 1 6 0v1.5"/>',
    cart: '<circle cx="9" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/><path d="M3 4h2l2.2 11.3a1 1 0 0 0 1 .8h8.2a1 1 0 0 0 1-.8L20 7.5H6.2"/>',
    giftcard: '<rect x="2.5" y="6" width="19" height="13" rx="2.5"/><path d="M2.5 11h19"/><path d="M7 15.5h3"/><path d="M15.5 4.5c1.5 0 1.5 2 0 2s-3 0-3 0 0-2 1.5-2Zm-3 2s-1.5 0-3 0-1.5-2 0-2 3 2 3 2Z"/>',
};

function shortcutIconSvg(name) {
    const inner = SHORTCUT_ICON_PATHS[name] || SHORTCUT_ICON_PATHS.bag;
    return `<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="h-5 w-5">${inner}</svg>`;
}

Alpine.store('toast', {
    items: [],
    push(message) {
        const id = Date.now() + Math.random();
        this.items.push({ id, message });
        setTimeout(() => { this.items = this.items.filter((t) => t.id !== id); }, 2600);
    },
});
window.addEventListener('shortcut:fired', (e) => Alpine.store('toast').push(e.detail.label));

Alpine.data('commandPalette', (tabs, mostUsed) => ({
    open: false,
    q: '',
    loading: false,
    groups: [],
    selectedIndex: -1,
    tabs,
    mostUsed,

    openPalette() {
        this.open = true;
        this.q = '';
        this.groups = [];
        this.selectedIndex = -1;
        this.$nextTick(() => {
            // Desktop types into the field above the dropdown; on mobile that field is
            // hidden (offsetParent null), so focus the dropdown's own input instead.
            const el = (this.$refs.input && this.$refs.input.offsetParent !== null) ? this.$refs.input : this.$refs.mobileInput;
            el?.focus();
        });
    },
    close() { this.open = false; },
    iconSvg(name) { return shortcutIconSvg(name); },

    async search() {
        if (!this.q) { this.groups = []; return; }
        this.loading = true;
        try {
            const res = await fetch(`/search?q=${encodeURIComponent(this.q)}`);
            const data = await res.json();
            this.groups = data.groups || [];
        } finally {
            this.loading = false;
            this.selectedIndex = -1;
        }
    },

    /** Flat index across whichever list is currently visible, so arrow keys can move through it. */
    flatIndex(groupKey, i) {
        const list = this.q === '' ? [{ key: 'most', items: this.mostUsed }] : this.groups;
        let idx = 0;
        for (const g of list) {
            for (let j = 0; j < g.items.length; j++) {
                if (g.key === groupKey && j === i) return idx;
                idx++;
            }
        }
        return -1;
    },
    flatList() {
        const list = this.q === '' ? [{ key: 'most', items: this.mostUsed }] : this.groups;
        return list.flatMap((g) => g.items);
    },
    onGlobalKey(e) {
        // Opening/closing the palette (mod+/, mod+k, esc) is handled by the
        // real ShortcutManager registry (resources/js/shortcuts.js) dispatching
        // open-command-palette/close-overlays — this only needs to handle
        // navigation *within* an already-open palette.
        if (!this.open) return;
        const items = this.flatList();
        if (e.key === 'ArrowDown') { e.preventDefault(); this.selectedIndex = Math.min(items.length - 1, this.selectedIndex + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); this.selectedIndex = Math.max(0, this.selectedIndex - 1); }
        else if (e.key === 'Enter') { const it = items[this.selectedIndex]; if (it) window.location = it.url; }
    },
}));

/* ----------------------------------------------------- Deposit wizard */
// 3-step popup: currency + amount -> pick a payment method -> that method's
// own details (charge phone if automated, destination + proof if manual).
// A plain server-rendered form the whole way through — only the step shown
// and which payment methods are offered change client-side.
Alpine.data('depositWizard', (methods, channels, currencies, userPhone) => ({
    open: false,
    step: 1,
    currency: (methods[0] && methods[0].currency) || (currencies[0] && currencies[0].code) || 'XAF',
    amount: '',
    methodId: null,
    phone: userPhone || '',
    txHash: '',
    methods,
    channels,
    currencies,

    get methodsForCurrency() { return this.methods.filter((m) => m.currency === this.currency); },
    get current() { return this.methods.find((m) => m.id === this.methodId) || null; },
    get channelsForCurrent() { return (this.current && this.channels[this.current.type]) || []; },

    launch() { this.step = 1; this.amount = ''; this.methodId = null; this.open = true; },
    close() { this.open = false; },
    selectMethod(id) { this.methodId = id; this.step = 3; },
}));

/* ----------------------------------------------------- Agent lead chat */
// Messages are sent via a plain form post (full reload, like the rest of the
// app); this just polls in the background so replies from the other side
// show up without the visitor having to refresh the page themselves.
Alpine.data('leadChat', (leadId, pollUrl, initial) => ({
    messages: initial.messages,
    timer: null,

    init() {
        this.scrollToBottom();
        this.timer = setInterval(() => this.poll(), 8000);
        this.$watch('messages', () => this.$nextTick(() => this.scrollToBottom()));
    },
    destroy() { clearInterval(this.timer); },
    async poll() {
        try {
            const res = await fetch(pollUrl, { headers: { Accept: 'application/json' } });
            if (!res.ok) return;
            const data = await res.json();
            if (data.messages.length !== this.messages.length) this.messages = data.messages;
        } catch (e) { /* transient network error — try again next tick */ }
    },
    scrollToBottom() {
        const el = this.$refs.thread;
        if (el) el.scrollTop = el.scrollHeight;
    },
}));

Alpine.data('discordLive', (inviteUrl) => ({
    online: null,
    members: null,
    name: '',
    icon: '',
    loading: true,
    failed: false,
    timer: null,
    init() {
        this.fetchStats();
        this.timer = setInterval(() => this.fetchStats(), 60000);
    },
    destroy() { clearInterval(this.timer); },
    async fetchStats() {
        let code = '';
        try { code = new URL(inviteUrl).pathname.replace(/^\/+/, ''); } catch (e) { /* invalid/placeholder URL */ }
        if (!code) { this.loading = false; this.failed = true; return; }
        try {
            const res = await fetch(`https://discord.com/api/v10/invites/${code}?with_counts=true`);
            if (!res.ok) throw new Error('bad status');
            const data = await res.json();
            this.online = data.approximate_presence_count ?? null;
            this.members = data.approximate_member_count ?? null;
            this.name = data.guild?.name || '';
            this.icon = data.guild?.icon ? `https://cdn.discordapp.com/icons/${data.guild_id}/${data.guild.icon}.png?size=64` : '';
            this.failed = false;
        } catch (e) {
            this.failed = true;
        } finally {
            this.loading = false;
        }
    },
}));

Alpine.data('shortcutsHelp', (groups) => ({
    open: false,
    q: '',
    groups,
    init() {
        window.addEventListener('open-shortcuts-help', () => { this.open = true; this.q = ''; });
        window.addEventListener('close-overlays', () => { this.open = false; });
    },
    close() { this.open = false; },
    filtered() {
        if (!this.q) return this.groups;
        const needle = this.q.toLowerCase();
        return this.groups
            .map((g) => ({ ...g, items: g.items.filter((i) => i.label.toLowerCase().includes(needle) || i.key.toLowerCase().includes(needle)) }))
            .filter((g) => g.items.length > 0);
    },
    formatKey(key) {
        if (key.includes(' ')) {
            const [a, b] = key.split(' ');
            return [a.toUpperCase(), 'then', b.toUpperCase()];
        }
        return key.split('+').map((part) => {
            if (part === 'mod') return IS_MAC_HELP ? '⌘' : 'Ctrl';
            if (part === 'alt') return IS_MAC_HELP ? '⌥' : 'Alt';
            if (part === 'shift') return 'Shift';
            if (part === 'esc') return 'Esc';
            return part.length === 1 ? part.toUpperCase() : part.charAt(0).toUpperCase() + part.slice(1);
        });
    },
    copyKey(key) {
        navigator.clipboard?.writeText(key);
        Alpine.store('toast').push('Shortcut copied');
    },
}));

// ── WebAuthn / passkeys ──────────────────────────────────────────────
// These live here (not a page-level <script> pushed from Blade) because
// layouts.app has no @stack('scripts') to render one into — every Alpine
// component used across the app is registered centrally like this.
function base64urlToBuffer(value) {
    const padded = value.replace(/-/g, '+').replace(/_/g, '/').padEnd(value.length + (4 - value.length % 4) % 4, '=');
    const raw = atob(padded);
    const buffer = new Uint8Array(raw.length);
    for (let i = 0; i < raw.length; i++) buffer[i] = raw.charCodeAt(i);
    return buffer.buffer;
}
function bufferToBase64url(buffer) {
    let str = '';
    new Uint8Array(buffer).forEach((b) => { str += String.fromCharCode(b); });
    return btoa(str).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
}
function csrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

Alpine.data('passkeyManager', (routes) => ({
    name: '',
    busy: false,
    error: '',
    success: '',
    async register() {
        this.busy = true;
        this.error = '';
        this.success = '';
        try {
            const optionsRes = await fetch(routes.optionsUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
            });
            if (!optionsRes.ok) throw new Error('Could not start passkey registration.');
            const options = await optionsRes.json();

            const publicKey = {
                ...options,
                challenge: base64urlToBuffer(options.challenge),
                user: { ...options.user, id: base64urlToBuffer(options.user.id) },
                excludeCredentials: (options.excludeCredentials || []).map((c) => ({ ...c, id: base64urlToBuffer(c.id) })),
            };

            const credential = await navigator.credentials.create({ publicKey });

            const payload = {
                id: credential.id,
                rawId: bufferToBase64url(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                    attestationObject: bufferToBase64url(credential.response.attestationObject),
                    transports: credential.response.getTransports ? credential.response.getTransports() : [],
                },
            };

            const storeRes = await fetch(routes.storeUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ name: this.name, response: payload }),
            });

            const data = await storeRes.json().catch(() => ({}));
            if (!storeRes.ok) throw new Error(data.errors?.response?.[0] || data.message || 'Could not save that passkey.');

            this.success = 'Passkey added.';
            setTimeout(() => window.location.reload(), 800);
        } catch (e) {
            this.error = e.message || 'Passkey registration was cancelled or failed.';
        } finally {
            this.busy = false;
        }
    },
}));

Alpine.data('passkeyChallenge', (routes) => ({
    busy: false,
    error: '',
    async verify() {
        this.busy = true;
        this.error = '';
        try {
            const optionsRes = await fetch(routes.optionsUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
            });
            if (!optionsRes.ok) throw new Error('Could not start passkey verification.');
            const options = await optionsRes.json();

            const publicKey = {
                ...options,
                challenge: base64urlToBuffer(options.challenge),
                allowCredentials: (options.allowCredentials || []).map((c) => ({ ...c, id: base64urlToBuffer(c.id) })),
            };

            const credential = await navigator.credentials.get({ publicKey });

            const payload = {
                id: credential.id,
                rawId: bufferToBase64url(credential.rawId),
                type: credential.type,
                response: {
                    clientDataJSON: bufferToBase64url(credential.response.clientDataJSON),
                    authenticatorData: bufferToBase64url(credential.response.authenticatorData),
                    signature: bufferToBase64url(credential.response.signature),
                    userHandle: credential.response.userHandle ? bufferToBase64url(credential.response.userHandle) : null,
                },
            };

            const verifyRes = await fetch(routes.verifyUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ response: payload }),
            });

            if (verifyRes.redirected) { window.location.href = verifyRes.url; return; }
            if (!verifyRes.ok) {
                const data = await verifyRes.json().catch(() => ({}));
                throw new Error(data.errors?.response?.[0] || data.message || 'That passkey could not be verified.');
            }
            window.location.href = routes.dashboardUrl;
        } catch (e) {
            this.error = e.message || 'Passkey verification was cancelled or failed.';
        } finally {
            this.busy = false;
        }
    },
}));

/**
 * Marketplace mobile drawer content: search-filter + "recently viewed
 * categories" only (see partials/marketplace-menu.blade.php). Open/close is
 * the shell's ctx/openCtx/closeCtx state, not this component's job. Desktop
 * has no panel at all: the Marketplace entry in the primary sidebar expands
 * inline on hover (see partials/nav-user.blade.php). `categories` here is a
 * flat list (all levels) used only to resolve slugs back to display info for
 * the recent-viewed list and the search results.
 */
Alpine.data('marketplaceMenu', (categories = []) => ({
    q: '',
    categories,
    recent: [],
    recentKey: 'pb-recent-categories',

    init() {
        this.loadRecent();
    },

    loadRecent() {
        try {
            const slugs = JSON.parse(localStorage.getItem(this.recentKey) || '[]');
            this.recent = slugs.map((slug) => this.categories.find((c) => c.slug === slug)).filter(Boolean).slice(0, 4);
        } catch {
            this.recent = [];
        }
    },

    trackVisit(slug) {
        try {
            let slugs = JSON.parse(localStorage.getItem(this.recentKey) || '[]');
            slugs = [slug, ...slugs.filter((s) => s !== slug)].slice(0, 6);
            localStorage.setItem(this.recentKey, JSON.stringify(slugs));
        } catch { /* localStorage unavailable — recently-viewed is a nicety, not required */ }
    },

    filtered() {
        const term = this.q.trim().toLowerCase();
        if (!term) return [];

        return this.categories.filter((c) => c.name.toLowerCase().includes(term));
    },

    visit(slug) {
        if (slug) this.trackVisit(slug);
    },
}));

/**
 * Admin financial performance chart: hover/touch crosshair + tooltip for the
 * smooth multi-series wave lines rendered server-side in
 * admin/dashboard/_financial.blade.php. `points` already carry pixel-space
 * x/y coordinates computed in PHP (single source of truth for the scale) —
 * this component only finds the nearest point to the pointer.
 */
Alpine.data('financialWaveChart', (points = [], currency = 'XAF') => ({
    points,
    currency,
    hover: null,
    // Real rendered px per viewBox unit. 1 when the SVG's width attribute
    // matches its viewBox (the admin chart's fixed-width, horizontally
    // scrollable version) — but a fluid `w-full` SVG (no width attribute,
    // e.g. the dashboard's Transactions chart) stretches its viewBox to fill
    // whatever the container's actual width is, so point x's (in viewBox
    // units) and mouse x's (in real px) need this to convert between them.
    scale: 1,

    nearestTo(clientX, svgEl) {
        if (!this.points.length) return;
        const rect = svgEl.getBoundingClientRect();
        const vbWidth = svgEl.viewBox && svgEl.viewBox.baseVal.width;
        this.scale = vbWidth ? rect.width / vbWidth : 1;
        const x = (clientX - rect.left) / this.scale;
        let nearest = 0;
        let best = Infinity;
        this.points.forEach((p, i) => {
            const dist = Math.abs(p.x - x);
            if (dist < best) { best = dist; nearest = i; }
        });
        this.hover = nearest;
    },

    handleMove(event) {
        this.nearestTo(event.clientX, event.currentTarget);
    },

    handleTouch(event) {
        const touch = event.touches[0];
        if (!touch) return;
        this.nearestTo(touch.clientX, event.currentTarget);
    },

    clear() { this.hover = null; },

    get active() { return this.hover === null ? null : this.points[this.hover]; },

    get activeX() { return this.active ? this.active.x * this.scale : 0; },

    fmt(v) { return Number(v || 0).toLocaleString(); },
}));

document.addEventListener('DOMContentLoaded', () => {
    ShortcutManager.load(window.__SHORTCUTS__ || []);
    ShortcutManager.init();
});

/**
 * Footer accessibility panel: text size, high contrast, reduced motion,
 * underline links. Each preference is a real CSS class toggled on <html> and
 * persisted to localStorage — theme-head.blade.php applies the saved state
 * before first paint (same anti-flash pattern as the light/dark/night theme)
 * so there's no flash of un-adjusted content on reload.
 */
Alpine.data('esimCompatibilityChecker', (routes, brands) => ({
    brands,
    brand: '',
    model: '',
    models: [],
    result: null,
    checking: false,

    async onBrandChange() {
        this.model = '';
        this.models = [];
        this.result = null;
        if (!this.brand) return;
        const res = await fetch(`${routes.modelsUrl}?brand=${encodeURIComponent(this.brand)}`, {
            headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
        });
        const data = await res.json();
        this.models = data.models || [];
    },

    async check() {
        if (!this.brand || !this.model) return;
        this.checking = true;
        this.result = null;
        try {
            const res = await fetch(routes.checkUrl, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ brand: this.brand, model: this.model }),
            });
            this.result = await res.json();
        } finally {
            this.checking = false;
        }
    },
}));

Alpine.data('notificationBell', ({ userId, unread, items }) => ({
    open: false,
    unread,
    items,

    init() {
        if (! window.Echo || ! userId) return;

        window.Echo.private(`App.Models.User.${userId}`).notification((n) => {
            this.items.unshift({
                id: n.id,
                title: n.title ?? 'Notification',
                message: n.message ?? '',
                url: n.url ?? '#',
                unread: true,
                time: 'Just now',
            });
            if (this.items.length > 6) this.items.pop();
            this.unread++;
            Alpine.store('toast').push(n.title ?? 'New notification');
        });
    },
}));

window.Alpine = Alpine;
Alpine.start();
