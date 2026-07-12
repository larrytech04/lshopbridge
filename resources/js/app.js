import './bootstrap';
import 'flag-icons/css/flag-icons.min.css';
import 'tom-select/dist/css/tom-select.css';

import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import intersect from '@alpinejs/intersect';
import TomSelect from 'tom-select';
import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';

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

Alpine.data('feeCalculator', (opts = {}) => ({
    amount: opts.amount ?? 0, rate: opts.rate ?? 1, feePercent: opts.feePercent ?? 0, feeFixed: opts.feeFixed ?? 0,
    baseCurrency: opts.baseCurrency ?? 'XAF', targetCurrency: opts.targetCurrency ?? 'CNY',
    get fee() { return Math.max(0, (Number(this.amount) * Number(this.feePercent)) / 100 + Number(this.feeFixed)); },
    get total() { return Number(this.amount) + this.fee; },
    get receives() { return Number(this.amount) * Number(this.rate); },
    money(v, c) { return Number(v || 0).toLocaleString(undefined, { maximumFractionDigits: 2 }) + ' ' + c; },
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
        const active = Math.min(n - 1, Math.max(0, parseInt(nav.dataset.active ?? '0', 10) || 0));
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
    },
}));

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
        if (localStorage.getItem('pb-onboarded')) return;
        setTimeout(() => { this.open = true; initSelects(this.$root); }, 600);
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
    skip() { localStorage.setItem('pb-onboarded', '1'); this.open = false; },
    finish() {
        localStorage.setItem('pb-onboarded', '1');
        const country = this.$root.querySelector('#ob-country')?.value || geoDefault;
        const locale = this.$root.querySelector('#ob-locale')?.value || 'en';
        const base = this.$root.dataset.onboardUrl;
        window.location = `${base}?country=${encodeURIComponent(country)}&locale=${encodeURIComponent(locale)}`;
    },
}));

window.Alpine = Alpine;
Alpine.start();
