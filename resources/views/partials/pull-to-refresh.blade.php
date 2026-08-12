{{-- Pull-to-refresh: dragging down hard from the very top of the page grows a
     plain native-style activity spinner into place in a fixed slot just below
     the header (not sliding down from off-screen above it, no logo, deliberately
     the same look as Safari's own), and releasing past the threshold shows the
     matching page skeleton (see partials.page-skeleton) and reloads, instead of
     a jarring blank-white location.reload().

     The behaviour itself lives in resources/js/app.js (initPullToRefresh),
     bundled rather than inlined here on purpose — see the comment above that
     function for why a raw <script> block with literal JS comparison
     operators in the page HTML is a real landmine, not just a style choice. --}}
<div id="pull-refresh" aria-hidden="true">
    <div class="pull-refresh__spinner">
        <div></div><div></div><div></div><div></div>
        <div></div><div></div><div></div><div></div>
    </div>
</div>
