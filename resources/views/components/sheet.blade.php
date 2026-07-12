@props(['maxWidth' => 'sm:max-w-md'])
{{-- Responsive popup shell: a bottom sheet that slides up on mobile, a wider centered modal on desktop. Expects an ancestor with x-data exposing `open`. --}}
<div x-show="open" x-cloak class="fixed inset-0 z-[100] flex flex-col justify-end sm:items-center sm:justify-center sm:p-4" x-transition.opacity>
    <div class="absolute inset-0 bg-black/60" style="backdrop-filter: blur(3px); -webkit-backdrop-filter: blur(3px);"></div>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-full opacity-100 sm:translate-y-4 sm:opacity-0 sm:scale-95"
         x-transition:enter-end="translate-y-0 opacity-100 sm:translate-y-0 sm:opacity-100 sm:scale-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0 opacity-100 sm:scale-100 sm:opacity-100"
         x-transition:leave-end="translate-y-full opacity-100 sm:translate-y-4 sm:opacity-0 sm:scale-95"
         {{ $attributes->merge(['class' => "glass-strong relative w-full max-h-[92vh] overflow-y-auto rounded-t-3xl p-6 shadow-2xl sm:max-h-none sm:overflow-visible sm:rounded-2xl $maxWidth"]) }}>
        {{ $slot }}
    </div>
</div>
