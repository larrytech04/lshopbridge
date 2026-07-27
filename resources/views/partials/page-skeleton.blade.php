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

        {{-- admin-command-center: date header + attention grid + grouped KPI rows +
             financial chart + reconciliation + geo/insights row + transaction table
             (admin Overview / Command Center) --}}
        <div data-skel-variant="admin-command-center" class="hidden space-y-5">
            <div class="space-y-3 rounded-3xl p-5">
                <div class="flex items-center justify-between">
                    <div class="space-y-2"><div class="skel-block h-6 w-56"></div><div class="skel-block h-3 w-80"></div></div>
                    <div class="skel-block h-4 w-40"></div>
                </div>
                <div class="flex gap-2 border-t border-app pt-3">
                    <div class="skel-block h-8 w-28 rounded-full"></div>
                    <div class="skel-block h-8 w-40 rounded-full"></div>
                    <div class="skel-block ml-auto h-8 w-24 rounded-full"></div>
                    <div class="skel-block h-8 w-24 rounded-full"></div>
                </div>
            </div>
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @for ($i = 0; $i < 4; $i++)<div class="skel-block h-20"></div>@endfor
            </div>
            @for ($g = 0; $g < 3; $g++)
                <div class="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @for ($i = 0; $i < 6; $i++)<div class="skel-block h-20"></div>@endfor
                </div>
            @endfor
            <div class="grid gap-5 xl:grid-cols-3">
                <div class="skel-block h-64 xl:col-span-2"></div>
                <div class="skel-block h-64"></div>
            </div>
            <div class="skel-block h-40"></div>
        </div>

        {{-- admin-users-list: KPI strip + search/filter/action bar + table with row
             checkboxes + insights sidebar (admin Users management) --}}
        <div data-skel-variant="admin-users-list" class="hidden space-y-5">
            <div class="grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-hidden">
                @for ($i = 0; $i < 8; $i++)<div class="skel-block h-16"></div>@endfor
            </div>
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-10 w-full rounded-full"></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-24 rounded-full"></div><div class="skel-block h-8 w-24 rounded-full"></div><div class="skel-block ml-auto h-8 w-28 rounded-full"></div></div>
            </div>
            <div class="grid gap-5 xl:grid-cols-4">
                <div class="space-y-2 rounded-2xl p-4 xl:col-span-3">
                    @for ($i = 0; $i < 7; $i++)
                        <div class="flex items-center gap-3 py-1.5">
                            <div class="skel-block h-4 w-4 shrink-0 rounded"></div>
                            <div class="skel-block h-9 w-9 shrink-0 rounded-full"></div>
                            <div class="skel-block h-3 w-1/3"></div>
                            <div class="skel-block ml-auto h-3 w-16"></div>
                            <div class="skel-block h-3 w-16"></div>
                        </div>
                    @endfor
                </div>
                <div class="space-y-3">
                    <div class="skel-block h-32"></div>
                    <div class="skel-block h-40"></div>
                </div>
            </div>
        </div>

        {{-- admin-user-profile: sticky identity header (avatar/badges/stats/actions/tabs)
             + tab content and sidebar (admin Manage User console) --}}
        <div data-skel-variant="admin-user-profile" class="hidden space-y-5">
            <div class="skel-block h-4 w-20"></div>
            <div class="space-y-4 rounded-3xl p-5">
                <div class="flex items-start gap-4">
                    <div class="skel-block h-16 w-16 shrink-0 rounded-2xl"></div>
                    <div class="flex-1 space-y-2"><div class="skel-block h-5 w-48"></div><div class="skel-block h-3 w-64"></div><div class="flex gap-1.5"><div class="skel-block h-5 w-20 rounded-full"></div><div class="skel-block h-5 w-16 rounded-full"></div><div class="skel-block h-5 w-24 rounded-full"></div></div></div>
                </div>
                <div class="grid grid-flow-col auto-cols-[minmax(6rem,1fr)] gap-3 overflow-hidden border-t border-app pt-3">
                    @for ($i = 0; $i < 6; $i++)<div class="skel-block h-9"></div>@endfor
                </div>
                <div class="flex gap-2 border-t border-app pt-3">
                    @for ($i = 0; $i < 5; $i++)<div class="skel-block h-8 w-24 rounded-full"></div>@endfor
                </div>
                <div class="flex gap-2 border-t border-app pt-3">
                    @for ($i = 0; $i < 5; $i++)<div class="skel-block h-7 w-28 rounded-full"></div>@endfor
                </div>
            </div>
            <div class="grid gap-6 lg:grid-cols-4">
                <div class="space-y-4 lg:col-span-3">
                    <div class="skel-block h-72"></div>
                    <div class="skel-block h-40"></div>
                </div>
                <div class="space-y-3">
                    @for ($i = 0; $i < 4; $i++)<div class="skel-block h-24"></div>@endfor
                </div>
            </div>
        </div>

        {{-- admin-kyc-queue: summary cards + queue tabs + filter bar + review table --}}
        <div data-skel-variant="admin-kyc-queue" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="space-y-2"><div class="skel-block h-6 w-40"></div><div class="skel-block h-3 w-64"></div></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-20 rounded-full"></div><div class="skel-block h-8 w-32 rounded-full"></div></div>
            </div>
            <div class="grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-hidden">
                @for ($i = 0; $i < 6; $i++)<div class="skel-block h-16"></div>@endfor
            </div>
            <div class="flex gap-1.5 rounded-2xl p-1.5">
                @for ($i = 0; $i < 8; $i++)<div class="skel-block h-7 w-24 shrink-0 rounded-lg"></div>@endfor
            </div>
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-10 w-full rounded-full"></div>
            </div>
            <div class="space-y-2 rounded-2xl p-4">
                @for ($i = 0; $i < 7; $i++)
                    <div class="flex items-center gap-3 py-1.5">
                        <div class="skel-block h-4 w-4 shrink-0 rounded"></div>
                        <div class="skel-block h-3 w-1/4"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block ml-auto h-3 w-14"></div>
                        <div class="skel-block h-3 w-16"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- admin-kyc-case: case header + split workspace (tabbed content / sticky decision panel) --}}
        <div data-skel-variant="admin-kyc-case" class="hidden space-y-4">
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-4 w-24"></div>
                <div class="flex items-center gap-2"><div class="skel-block h-6 w-48"></div><div class="skel-block h-5 w-16 rounded-full"></div><div class="skel-block h-5 w-16 rounded-full"></div></div>
                <div class="skel-block h-3 w-72"></div>
            </div>
            <div class="grid gap-5 xl:grid-cols-3">
                <div class="space-y-4 xl:col-span-2">
                    <div class="flex gap-1.5 rounded-2xl p-1.5">
                        @for ($i = 0; $i < 4; $i++)<div class="skel-block h-7 w-32 shrink-0 rounded-lg"></div>@endfor
                    </div>
                    <div class="skel-block h-48"></div>
                    <div class="skel-block h-32"></div>
                    <div class="skel-block h-40"></div>
                </div>
                <div class="space-y-3">
                    <div class="skel-block h-72"></div>
                </div>
            </div>
        </div>

        {{-- admin-agents-list: summary cards + tabs + filter bar + table (Agent Management) --}}
        <div data-skel-variant="admin-agents-list" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="space-y-2"><div class="skel-block h-6 w-40"></div><div class="skel-block h-3 w-64"></div></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-20 rounded-full"></div><div class="skel-block h-8 w-24 rounded-full"></div></div>
            </div>
            <div class="grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-hidden">
                @for ($i = 0; $i < 8; $i++)<div class="skel-block h-16"></div>@endfor
            </div>
            <div class="flex gap-1.5 rounded-2xl p-1.5">
                @for ($i = 0; $i < 6; $i++)<div class="skel-block h-7 w-20 shrink-0 rounded-lg"></div>@endfor
            </div>
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-10 w-full rounded-full"></div>
            </div>
            <div class="space-y-2 rounded-2xl p-4">
                @for ($i = 0; $i < 6; $i++)
                    <div class="flex items-center gap-3 py-1.5">
                        <div class="skel-block h-8 w-8 shrink-0 rounded-full"></div>
                        <div class="skel-block h-3 w-1/4"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block ml-auto h-3 w-14"></div>
                        <div class="skel-block h-3 w-16"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- admin-wallets-list: summary cards + tabs + filter bar + table (China Wallet Accounts) --}}
        <div data-skel-variant="admin-wallets-list" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="space-y-2"><div class="skel-block h-6 w-48"></div><div class="skel-block h-3 w-64"></div></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-20 rounded-full"></div><div class="skel-block h-8 w-20 rounded-full"></div></div>
            </div>
            <div class="grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-hidden">
                @for ($i = 0; $i < 6; $i++)<div class="skel-block h-16"></div>@endfor
            </div>
            <div class="flex gap-1.5 rounded-2xl p-1.5">
                @for ($i = 0; $i < 5; $i++)<div class="skel-block h-7 w-20 shrink-0 rounded-lg"></div>@endfor
            </div>
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-10 w-full rounded-full"></div>
            </div>
            <div class="space-y-2 rounded-2xl p-4">
                @for ($i = 0; $i < 6; $i++)
                    <div class="flex items-center gap-3 py-1.5">
                        <div class="skel-block h-8 w-8 shrink-0 rounded-full"></div>
                        <div class="skel-block h-3 w-1/4"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block ml-auto h-3 w-14"></div>
                        <div class="skel-block h-3 w-16"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- admin-deposits-list: summary cards + tabs + filter bar + wide table (Deposit Management) --}}
        <div data-skel-variant="admin-deposits-list" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="space-y-2"><div class="skel-block h-6 w-48"></div><div class="skel-block h-3 w-80"></div></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-20 rounded-full"></div><div class="skel-block h-8 w-24 rounded-full"></div><div class="skel-block h-8 w-24 rounded-full"></div></div>
            </div>
            <div class="grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-hidden">
                @for ($i = 0; $i < 10; $i++)<div class="skel-block h-16"></div>@endfor
            </div>
            <div class="flex gap-1.5 rounded-2xl p-1.5">
                @for ($i = 0; $i < 8; $i++)<div class="skel-block h-7 w-20 shrink-0 rounded-lg"></div>@endfor
            </div>
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-10 w-full rounded-full"></div>
            </div>
            <div class="space-y-2 rounded-2xl p-4">
                @for ($i = 0; $i < 7; $i++)
                    <div class="flex items-center gap-3 py-1.5">
                        <div class="skel-block h-8 w-8 shrink-0 rounded-full"></div>
                        <div class="skel-block h-3 w-1/5"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block ml-auto h-3 w-14"></div>
                        <div class="skel-block h-3 w-14"></div>
                        <div class="skel-block h-3 w-16"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- admin-funding-list: summary cards + tabs + filter bar + wide table (China Wallet Funding) --}}
        <div data-skel-variant="admin-funding-list" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="space-y-2"><div class="skel-block h-6 w-52"></div><div class="skel-block h-3 w-80"></div></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-20 rounded-full"></div><div class="skel-block h-8 w-24 rounded-full"></div><div class="skel-block h-8 w-24 rounded-full"></div></div>
            </div>
            <div class="grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-hidden">
                @for ($i = 0; $i < 10; $i++)<div class="skel-block h-16"></div>@endfor
            </div>
            <div class="flex gap-1.5 rounded-2xl p-1.5">
                @for ($i = 0; $i < 8; $i++)<div class="skel-block h-7 w-20 shrink-0 rounded-lg"></div>@endfor
            </div>
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-10 w-full rounded-full"></div>
            </div>
            <div class="space-y-2 rounded-2xl p-4">
                @for ($i = 0; $i < 7; $i++)
                    <div class="flex items-center gap-3 py-1.5">
                        <div class="skel-block h-8 w-8 shrink-0 rounded-full"></div>
                        <div class="skel-block h-3 w-1/5"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block ml-auto h-3 w-14"></div>
                        <div class="skel-block h-3 w-14"></div>
                        <div class="skel-block h-3 w-16"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- admin-rates-list: summary cards + filter bar + wide table (Exchange Rates) --}}
        <div data-skel-variant="admin-rates-list" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="space-y-2"><div class="skel-block h-6 w-44"></div><div class="skel-block h-3 w-80"></div></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-24 rounded-full"></div><div class="skel-block h-8 w-24 rounded-full"></div><div class="skel-block h-8 w-32 rounded-full"></div></div>
            </div>
            <div class="grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-hidden">
                @for ($i = 0; $i < 6; $i++)<div class="skel-block h-16"></div>@endfor
            </div>
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-10 w-full rounded-full"></div>
            </div>
            <div class="space-y-2 rounded-2xl p-4">
                @for ($i = 0; $i < 6; $i++)
                    <div class="flex items-center gap-3 py-1.5">
                        <div class="skel-block h-3 w-1/6"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block ml-auto h-3 w-16"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block h-3 w-14"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- admin-fees-list: summary cards + filter bar + wide table (Fees & Charges) --}}
        <div data-skel-variant="admin-fees-list" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="space-y-2"><div class="skel-block h-6 w-44"></div><div class="skel-block h-3 w-80"></div></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-24 rounded-full"></div><div class="skel-block h-8 w-24 rounded-full"></div><div class="skel-block h-8 w-32 rounded-full"></div></div>
            </div>
            <div class="grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-hidden">
                @for ($i = 0; $i < 8; $i++)<div class="skel-block h-16"></div>@endfor
            </div>
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-10 w-full rounded-full"></div>
            </div>
            <div class="space-y-2 rounded-2xl p-4">
                @for ($i = 0; $i < 6; $i++)
                    <div class="flex items-center gap-3 py-1.5">
                        <div class="skel-block h-3 w-1/6"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block ml-auto h-3 w-16"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block h-3 w-14"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- admin-products-list: summary cards + tabs + filter bar + wide table (Products) --}}
        <div data-skel-variant="admin-products-list" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="space-y-2"><div class="skel-block h-6 w-40"></div><div class="skel-block h-3 w-80"></div></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-28 rounded-full"></div><div class="skel-block h-8 w-28 rounded-full"></div><div class="skel-block h-8 w-24 rounded-full"></div></div>
            </div>
            <div class="grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-hidden">
                @for ($i = 0; $i < 10; $i++)<div class="skel-block h-16"></div>@endfor
            </div>
            <div class="flex gap-1.5 rounded-2xl p-1.5">
                @for ($i = 0; $i < 8; $i++)<div class="skel-block h-7 w-20 shrink-0 rounded-lg"></div>@endfor
            </div>
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-10 w-full rounded-full"></div>
            </div>
            <div class="space-y-2 rounded-2xl p-4">
                @for ($i = 0; $i < 7; $i++)
                    <div class="flex items-center gap-3 py-1.5">
                        <div class="skel-block h-9 w-9 shrink-0 rounded-lg"></div>
                        <div class="skel-block h-3 w-1/5"></div>
                        <div class="skel-block h-3 w-16"></div>
                        <div class="skel-block h-3 w-14"></div>
                        <div class="skel-block ml-auto h-3 w-14"></div>
                        <div class="skel-block h-3 w-14"></div>
                        <div class="skel-block h-3 w-16"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- admin-categories-tree: two-panel tree + editor (Product Categories) --}}
        <div data-skel-variant="admin-categories-tree" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="space-y-2"><div class="skel-block h-6 w-48"></div><div class="skel-block h-3 w-80"></div></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-28 rounded-full"></div><div class="skel-block h-8 w-28 rounded-full"></div></div>
            </div>
            <div class="grid gap-5 lg:grid-cols-5">
                <div class="space-y-2 rounded-3xl p-4 lg:col-span-2">
                    <div class="skel-block h-9 w-full rounded-full"></div>
                    @for ($i = 0; $i < 8; $i++)<div class="skel-block h-9 w-full rounded-xl"></div>@endfor
                </div>
                <div class="space-y-3 rounded-3xl p-5 lg:col-span-3">
                    <div class="skel-block h-5 w-40"></div>
                    @for ($i = 0; $i < 6; $i++)<div class="skel-block h-9 w-full rounded-xl"></div>@endfor
                </div>
            </div>
        </div>

        {{-- admin-orders-list: summary cards + tabs + filter bar + wide table (Shop Orders) --}}
        <div data-skel-variant="admin-orders-list" class="hidden space-y-5">
            <div class="flex items-center justify-between">
                <div class="space-y-2"><div class="skel-block h-6 w-40"></div><div class="skel-block h-3 w-80"></div></div>
                <div class="flex gap-2"><div class="skel-block h-8 w-28 rounded-full"></div><div class="skel-block h-8 w-24 rounded-full"></div></div>
            </div>
            <div class="grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-hidden">
                @for ($i = 0; $i < 8; $i++)<div class="skel-block h-16"></div>@endfor
            </div>
            <div class="flex gap-1.5 rounded-2xl p-1.5">
                @for ($i = 0; $i < 9; $i++)<div class="skel-block h-7 w-20 shrink-0 rounded-lg"></div>@endfor
            </div>
            <div class="space-y-3 rounded-3xl p-5">
                <div class="skel-block h-10 w-full rounded-full"></div>
            </div>
            <div class="space-y-2 rounded-2xl p-4">
                @for ($i = 0; $i < 7; $i++)
                    <div class="flex items-center gap-3 py-1.5">
                        <div class="skel-block h-3 w-1/6"></div>
                        <div class="skel-block h-3 w-1/5"></div>
                        <div class="skel-block h-3 w-14"></div>
                        <div class="skel-block ml-auto h-3 w-16"></div>
                        <div class="skel-block h-3 w-14"></div>
                    </div>
                @endfor
            </div>
        </div>

        {{-- list: header + filter bar + table-like rows (Transactions, admin Users/
             Risk/Audit/Disputes/Webhooks, Orders, Notifications) --}}
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
