@props(['esimProducts', 'transparent' => false])
@php
    $esimPlans = $esimProducts->flatMap(fn ($p) => $p->variants->where('is_active', true)->map(fn ($v) => ['p' => $p, 'v' => $v]))->take(8);
    $cardClass = $transparent ? 'border border-app' : 'surface border border-app shadow-sm';
@endphp
@if ($esimPlans->count())
<section class="mx-auto mt-16 max-w-none px-4 sm:px-6" x-data="{ scroll(d) { $refs.esimRow.scrollBy({ left: d * $refs.esimRow.clientWidth * 0.8, behavior: 'smooth' }) } }">
    <div class="flex items-end justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-strong sm:text-3xl">{{ cms('cms_home_esim_title', __('Global travel eSIMs')) }}</h2>
            <p class="mt-1 max-w-2xl text-sm text-muted sm:text-base">{{ cms('cms_home_esim_subtitle', __('Get a data eSIM for your next trip, installed in minutes, no physical SIM. Choose a plan below.')) }}</p>
        </div>
        <div class="flex shrink-0 items-center gap-2">
            <button type="button" @click="scroll(-1)" aria-label="{{ __('Previous') }}" class="grid h-9 w-9 place-items-center rounded-full border border-app surface text-muted transition hover:text-strong"><x-icon name="chevron-right" class="h-4 w-4 rotate-180" /></button>
            <button type="button" @click="scroll(1)" aria-label="{{ __('Next') }}" class="grid h-9 w-9 place-items-center rounded-full border border-app surface text-muted transition hover:text-strong"><x-icon name="chevron-right" class="h-4 w-4" /></button>
            <a href="{{ \Illuminate\Support\Facades\Route::has('esim.index') ? route('esim.index') : route('shop.category', 'esims') }}" class="ml-1 text-sm font-semibold text-brand-500 hover:text-brand-600">{{ __('See all') }} →</a>
        </div>
    </div>
    <div x-ref="esimRow" class="no-scrollbar mt-6 flex snap-x gap-4 overflow-x-auto pb-2">
        @foreach ($esimPlans as $plan)
            @php $p = $plan['p']; $v = $plan['v']; @endphp
            <div class="flex w-60 shrink-0 snap-start flex-col rounded-2xl {{ $cardClass }} p-5 transition duration-300 hover:-translate-y-1 hover:shadow-lg sm:w-64">
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="inline-flex items-center gap-1.5 rounded-full surface-2 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-body ring-1 ring-app"><span class="h-1.5 w-1.5 rounded-full bg-brand-500"></span> {{ $p->region ?: 'eSIM' }}</span>
                    <span class="inline-flex items-center gap-1.5 rounded-full surface-2 px-2.5 py-1 text-[11px] font-bold uppercase tracking-wide text-body ring-1 ring-app"><span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> {{ __('Data') }}</span>
                </div>
                <h3 class="mt-3 text-lg font-extrabold text-strong">{{ $v->name }}</h3>
                <div class="my-3 border-t border-app"></div>
                <ul class="space-y-2.5 text-sm text-body">
                    <li class="flex items-center gap-2.5"><x-icon name="signal" class="h-4 w-4 shrink-0 text-muted" /> {{ $v->data_amount ?: $v->name }} {{ __('of data') }}</li>
                    @if ($v->validity_days)<li class="flex items-center gap-2.5"><x-icon name="clock" class="h-4 w-4 shrink-0 text-muted" /> {{ $v->validity_days }} {{ __('day validity') }}</li>@endif
                    <li class="flex items-center gap-2.5"><x-icon name="globe" class="h-4 w-4 shrink-0 text-muted" /> {{ $p->region ?: __('Global') }} {{ __('coverage') }}</li>
                    <li class="flex items-center gap-2.5"><x-icon name="check" class="h-4 w-4 shrink-0 text-muted" /> {{ __('Instant eSIM delivery') }}</li>
                </ul>
                <div class="mt-auto flex items-end justify-between pt-4">
                    <a href="{{ route('shop.show', $p) }}" class="text-sm font-semibold text-brand-600 hover:text-brand-700">{{ __('See more') }}</a>
                    <span class="text-xl font-extrabold text-strong">{{ disp($v->price) }}</span>
                </div>
            </div>
        @endforeach
    </div>
</section>
@endif
