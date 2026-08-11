{{-- Pull-to-refresh: dragging down hard from the very top of the page reveals the
     same branded spinner used elsewhere, and releasing past the threshold shows
     the matching page skeleton (see partials.page-skeleton) and reloads, instead
     of a jarring blank-white location.reload(). --}}
<div id="pull-refresh" aria-hidden="true">
    <div class="pull-refresh__ring">
        <img src="{{ site_favicon() }}" alt="" class="pull-refresh__logo">
    </div>
</div>
<script>
    (function () {
        var el = document.getElementById('pull-refresh');
        if (!el) return;

        // Deliberately hard to trigger by accident: RESISTANCE means the
        // finger has to travel far more than the ring actually moves, on top
        // of a high visual THRESHOLD — roughly 275px of real, sustained
        // finger movement before it arms, not a light scroll-stutter at the
        // top of the page.
        var THRESHOLD = 110;
        var MAX_PULL = 100;
        var RESISTANCE = 2.5;
        var startY = null;
        var pulling = false;
        var armed = false;

        document.addEventListener('touchstart', function (e) {
            if (window.scrollY <= 0) {
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
            el.style.transform = 'translateY(' + ((dist / MAX_PULL) * 100 - 100) + '%)';
            armed = dist >= THRESHOLD;
            el.classList.toggle('pull-refresh--armed', armed);
        }, { passive: false });

        function reset() {
            pulling = false;
            el.style.transform = '';
            el.classList.remove('pull-refresh--armed');
        }

        document.addEventListener('touchend', function () {
            if (!pulling) return;
            if (armed) {
                el.classList.add('pull-refresh--spinning');
                el.style.transform = 'translateY(0%)';
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
