{{--
    LshopBridge Network Footer — a purpose-built 4-layer footer, not a
    generic "Company / Products / Resources / Legal" grid:
      1. Global Action Deck   (partials/footer/action-deck.blade.php)
      2. Network Footer       (Brand Command Center / Service Network / Discover / Support & Legal)
      3. Trust & Operations Rail
      4. Minimal Legal Bar
    Every link, stat, payment brand, and social profile is read from real
    routes/Settings/models — nothing here is hardcoded placeholder content.
--}}
<footer class="footer-shell mt-10 sm:mt-14">
    @include('partials.footer.action-deck')

    <div class="mx-auto grid max-w-none gap-6 px-4 py-6 sm:gap-8 sm:px-6 sm:py-9 lg:grid-cols-12">
        @include('partials.footer.brand-command-center')
        @include('partials.footer.service-network')
        @include('partials.footer.discovery-company')
        @include('partials.footer.support-legal')
    </div>

    <div class="border-y border-app">
        <div class="mx-auto flex max-w-none flex-col gap-6 px-4 py-6 sm:px-6 lg:flex-row lg:items-start lg:justify-between lg:gap-10">
            @include('partials.footer.newsletter-brief')
            <div class="flex flex-col gap-4">
                @include('partials.footer.trust-rail')
                @include('partials.footer.payment-strip')
                @include('partials.footer.install-webapp')
            </div>
        </div>
    </div>

    @include('partials.footer.legal-bar')
</footer>
