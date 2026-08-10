@php $holdMs ??= 1200; @endphp
{{-- Branded boot loader: covers the blank gap on a hard refresh with the logo
     centered in a spinning ring, held for a minimum so the brand moment
     registers on a fast connection and faded out once the page is actually
     ready. Keyed off DOMContentLoaded (HTML parsed, deferred scripts run,
     Alpine ready) rather than the heavier `load` event — `load` waits for
     every subresource including background videos, which on a page with a
     hero video can take several extra seconds for no real benefit, since
     those are decorative and finish buffering fine after the page is
     already visible. On a slow/poor connection this still adds nothing —
     it only ever waits for the real DOMContentLoaded event, so the spinner
     keeps going until the page is genuinely ready; it never cuts a slow
     load short at the minimum. --}}
<div id="boot-loader" aria-hidden="true">
    <div class="boot-loader__ring">
        <img src="{{ site_favicon() }}" alt="" class="boot-loader__logo">
    </div>
</div>
<script>
    (function () {
        var start = Date.now();
        var reveal = function () {
            var remaining = Math.max(0, {{ (int) $holdMs }} - (Date.now() - start));
            setTimeout(function () {
                var el = document.getElementById('boot-loader');
                if (el) el.classList.add('boot-loader--done');
            }, remaining);
        };
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', reveal);
        } else {
            reveal();
        }
    })();
</script>
