{{-- Pull-to-refresh: dragging down hard from the very top of the page grows a
     plain native-style activity spinner into place in a fixed slot just below
     the header (not sliding down from off-screen above it, no logo, deliberately
     the same look as Safari's own), and releasing past the threshold shows the
     matching page skeleton (see partials.page-skeleton) and reloads, instead of
     a jarring blank-white location.reload(). --}}
<div id="pull-refresh" aria-hidden="true">
    <div class="pull-refresh__spinner">
        <div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div>
    </div>
</div>
<script>
    (function () {
        var el = document.getElementById('pull-refresh');
        if (!el) return;

        // Deliberately hard to trigger by accident: RESISTANCE means the
        // finger has to travel far more than the indicator actually grows,
        // on top of a high visual THRESHOLD — roughly 275px of real,
        // sustained finger movement before it arms, not a light
        // scroll-stutter at the top of the page.
        var THRESHOLD = 110;
        var MAX_PULL = 100;
        var RESISTANCE = 2.5;
        var startY = null;
        var pulling = false;
        var armed = false;

        // Any touch that starts inside a scrollable overlay (the mobile menu
        // sheet, the marketplace drawer, a modal's own scroll area, ...)
        // must never be hijacked into a page-refresh gesture — that's what
        // was blocking scrolling inside those sheets entirely.
        function insideScrollable(node) {
            while (node && node !== document.body && node !== document.documentElement) {
                var style = window.getComputedStyle(node);
                if ((style.overflowY === 'auto' || style.overflowY === 'scroll') && node.scrollHeight > node.clientHeight) {
                    return true;
                }
                node = node.parentElement;
            }
            return false;
        }

        document.addEventListener('touchstart', function (e) {
            if (window.scrollY <= 0 && !insideScrollable(e.target)) {
                startY = e.touches[0].clientY;
                pulling = true;
                armed = false;
            }
        }, { passive: true });

        document.addEventListener('touchmove', function (e) {
            if (!pulling || startY === null) return;
            var raw = e.touches[0].clientY - startY;
            if (raw <= 0) { pulling = false; return; }

            e.preventDefault();
            var dist = Math.min(raw / RESISTANCE, MAX_PULL);
            var progress = dist / MAX_PULL;
            el.style.opacity = progress;
            el.style.transform = 'scale(' + (0.6 + progress * 0.4) + ')';
            armed = dist >= THRESHOLD;
            el.classList.toggle('pull-refresh--armed', armed);
        }, { passive: false });

        function reset() {
            pulling = false;
            el.style.opacity = '';
            el.style.transform = '';
            el.classList.remove('pull-refresh--armed');
        }

        document.addEventListener('touchend', function () {
            if (!pulling) return;
            if (armed) {
                el.classList.add('pull-refresh--spinning');
                el.style.opacity = '1';
                el.style.transform = 'scale(1)';
                var reload = function () { location.reload(); };
                // Same "show the matching skeleton, then navigate" beat as a
                // normal internal link click, so refreshing feels like the
                // rest of the app instead of a hard browser reload.
                if (window.PBSkeleton) {
                    window.PBSkeleton.showThenGo(window.location.href, reload);
                } else {
                    reload();
                }
            } else {
                reset();
            }
        });
        document.addEventListener('touchcancel', reset);
    })();
</script>
