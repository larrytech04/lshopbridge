{{-- Pull-to-refresh: dragging down from the very top of the page reveals the
     same branded spinner used elsewhere, and releasing past the threshold
     reloads the page — the touch-driven equivalent of a hard refresh. --}}
<div id="pull-refresh" aria-hidden="true">
    <div class="pull-refresh__ring">
        <img src="{{ site_favicon() }}" alt="" class="pull-refresh__logo">
    </div>
</div>
<script>
    (function () {
        var el = document.getElementById('pull-refresh');
        if (!el) return;

        var THRESHOLD = 70;
        var MAX_PULL = 110;
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
            var delta = e.touches[0].clientY - startY;
            if (delta <= 0) { pulling = false; return; }

            e.preventDefault();
            var dist = Math.min(delta, MAX_PULL);
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
                location.reload();
            } else {
                reset();
            }
        });
        document.addEventListener('touchcancel', reset);
    })();
</script>
