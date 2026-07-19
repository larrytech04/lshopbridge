{{-- Full-page loading placeholder shown the instant an internal link/form navigates,
     across User/Admin/Agent areas alike. This is a traditional server-rendered app, so
     there's no client-side "data still loading" state to skeleton in-place, this is the
     honest equivalent: an instant placeholder covering the blank gap between click and the
     next page's paint, hidden automatically once that next page loads (see app.js).

     Since we don't know the destination page's real content yet, app.js matches the
     clicked link's URL against a small set of route patterns and picks the closest-shaped
     variant below (dashboard / list / form / grid / detail) rather than always showing one
     generic shape. --}}
<div id="page-skeleton" aria-hidden="true">
    <div class="mx-auto max-w-7xl space-y-6 px-4 py-8 sm:px-6 lg:py-10">

        {{-- dashboard: stat row + quick actions + chart + right rail --}}
        <div data-skel-variant="dashboard" class="hidden space-y-6">
            <div class="flex items-center justify-between">
                <div class="space-y-2">
                    <div class="skel-block h-6 w-48"></div>
                    <div class="skel-block h-4 w-64"></div>
                </div>
                <div class="skel-block h-9 w-24 rounded-full"></div>
            </div>
            <div class="grid gap-4 sm:grid-cols-3">
                <div class="skel-block h-28"></div>
                <div class="skel-block h-28"></div>
                <div class="skel-block h-28"></div>
            </div>
            <div class="flex items-center gap-4 rounded-2xl p-4">
                <div class="skel-block h-10 w-10 shrink-0 rounded-full"></div>
                <div class="skel-block h-4 w-2/3"></div>
            </div>
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-6 lg:col-span-2">
                    <div class="flex flex-wrap gap-4">
                        @for ($i = 0; $i < 6; $i++)
                            <div class="flex flex-col items-center gap-2">
                                <div class="skel-block h-14 w-14 rounded-full"></div>
                                <div class="skel-block h-3 w-10"></div>
                            </div>
                        @endfor
                    </div>
                    <div class="skel-block h-56"></div>
                </div>
                <div class="space-y-6">
                    <div class="skel-block h-24"></div>
                    <div class="space-y-3 rounded-2xl p-4">
                        @for ($i = 0; $i < 3; $i++)
                            <div class="flex items-center gap-3">
                                <div class="skel-block h-9 w-9 shrink-0 rounded-full"></div>
                                <div class="skel-block h-3 flex-1"></div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>

        {{-- list: header + filter bar + table-like rows (Transactions, admin Users/KYC/
             Deposits/Risk/Funding/Agents/Audit/Disputes/Webhooks, Orders, Notifications) --}}
        <div data-skel-variant="list" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="skel-block h-6 w-40"></div>
                <div class="skel-block h-9 w-28 rounded-full"></div>
            </div>
            <div class="flex gap-3">
                <div class="skel-block h-10 flex-1 rounded-xl"></div>
                <div class="skel-block h-10 w-32 rounded-xl"></div>
            </div>
            <div class="space-y-2 rounded-2xl p-4">
                @for ($i = 0; $i < 7; $i++)
                    <div class="flex items-center gap-3 py-1.5">
                        <div class="skel-block h-9 w-9 shrink-0 rounded-lg"></div>
                        <div class="skel-block h-3 w-1/3"></div>
                        <div class="skel-block ml-auto h-3 w-16"></div>
                        <div class="skel-block h-3 w-20"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- form: header + labeled-field card + submit (Deposit, Fund new, Profile,
             Checkout, Verification, admin Settings) --}}
        <div data-skel-variant="form" class="hidden mx-auto max-w-2xl space-y-5">
            <div class="skel-block h-6 w-56"></div>
            <div class="space-y-4 rounded-3xl p-6">
                @for ($i = 0; $i < 5; $i++)
                    <div class="space-y-2">
                        <div class="skel-block h-3 w-24"></div>
                        <div class="skel-block h-10 w-full rounded-xl"></div>
                    </div>
                @endfor
                <div class="skel-block h-11 w-full rounded-xl"></div>
            </div>
        </div>

        {{-- grid: header + search + card grid (Shop index/category, Marketplace,
             Learning center, China guide) --}}
        <div data-skel-variant="grid" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="skel-block h-6 w-40"></div>
                <div class="skel-block h-10 w-64 rounded-xl"></div>
            </div>
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                @for ($i = 0; $i < 8; $i++)
                    <div class="space-y-2 rounded-2xl p-3">
                        <div class="skel-block h-24 w-full"></div>
                        <div class="skel-block h-3 w-4/5"></div>
                        <div class="skel-block h-3 w-1/2"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- detail: back link + hero block + text lines + side panel (Shop product,
             order/deposit/funding show, guide show) --}}
        <div data-skel-variant="detail" class="hidden space-y-5">
            <div class="skel-block h-4 w-20"></div>
            <div class="grid gap-6 lg:grid-cols-3">
                <div class="space-y-4 lg:col-span-2">
                    <div class="skel-block h-64 w-full"></div>
                    <div class="skel-block h-5 w-2/3"></div>
                    <div class="skel-block h-3 w-full"></div>
                    <div class="skel-block h-3 w-5/6"></div>
                    <div class="skel-block h-3 w-3/4"></div>
                </div>
                <div class="space-y-3 rounded-2xl p-4">
                    <div class="skel-block h-5 w-1/2"></div>
                    <div class="skel-block h-9 w-full rounded-xl"></div>
                    <div class="skel-block h-9 w-full rounded-xl"></div>
                    <div class="skel-block h-11 w-full rounded-xl"></div>
                </div>
            </div>
        </div>

    </div>
</div>
