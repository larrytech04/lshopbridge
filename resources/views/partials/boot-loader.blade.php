@php $holdMs ??= 1200; @endphp
{{-- Branded boot loader: covers the blank gap on a hard refresh with the logo
     centered in a spinning ring, held for a minimum so the brand moment
     registers on a fast connection and faded out once the page has truly
     loaded. On a slow/poor connection this adds nothing — it only ever waits
     for the real `load` event, which naturally takes longer, so the spinner
     keeps going until the page is actually ready; it never cuts a slow load
     short at the minimum. --}}
<div id="boot-loader" aria-hidden="true">
    <div class="boot-loader__ring">
        <img src="{{ site_favicon() }}" alt="" class="boot-loader__logo">
    </div>
</div>
<script>
    (function () {
        var start = Date.now();
        window.addEventListener('load', function () {
            var remaining = Math.max(0, {{ (int) $holdMs }} - (Date.now() - start));
            setTimeout(function () {
                var el = document.getElementById('boot-loader');
                if (el) el.classList.add('boot-loader--done');
            }, remaining);
        });
    })();
</script>
