{{--
    Sticky "send feedback" edge tab + scroll-to-top button. Feedback links to
    the existing public Contact page, no separate feedback pipeline exists yet.
--}}
<a href="{{ route('contact') }}"
   class="fixed left-0 top-1/2 z-40 hidden -translate-y-1/2 flex-col items-center gap-1 rounded-r-lg bg-brand-600 px-1.5 py-2.5 text-white shadow-md transition hover:bg-brand-700 sm:flex"
   aria-label="{{ __('Send feedback') }}">
    <x-icon name="mail" class="h-3.5 w-3.5 shrink-0" />
    <span class="text-[10px] font-bold tracking-wide [writing-mode:vertical-rl]">{{ __('Feedback') }}</span>
</a>

<button type="button"
        x-data="{ show: false }"
        x-init="show = window.scrollY > 400; window.addEventListener('scroll', () => show = window.scrollY > 400)"
        x-show="show" x-cloak x-transition.opacity
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        style="display:none"
        class="fixed bottom-6 left-4 z-40 grid h-8 w-8 place-items-center rounded-full bg-brand-600 text-white shadow-md transition hover:bg-brand-700 sm:left-6"
        aria-label="{{ __('Scroll to top') }}">
    <x-icon name="arrow-up" class="h-3.5 w-3.5" />
</button>
