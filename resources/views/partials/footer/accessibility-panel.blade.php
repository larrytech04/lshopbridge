{{--
    Compact Accessibility control — one trigger, real functional toggles (see
    accessibilityPanel() in app.js + CSS in app.css). Pass triggerClass to
    restyle the trigger for context (a plain text link in the legal bar vs.
    a pill button elsewhere); defaults to the pill style.
--}}
@php $triggerClass = $triggerClass ?? 'footer-region-trigger'; @endphp
<div x-data="accessibilityPanel()" @keydown.escape.window="open = false">
    <button type="button" @click="open = true" class="{{ $triggerClass }}">
        @if (! isset($plainTrigger))<x-icon name="user-circle" class="h-4 w-4" />@endif
        <span>{{ __('Accessibility') }}</span>
    </button>

    <x-sheet max-width="sm:max-w-sm">
        <div class="mb-4 flex items-center justify-between">
            <h3 class="font-bold text-strong">{{ __('Accessibility') }}</h3>
            <button type="button" @click="open = false" aria-label="{{ __('Close') }}" class="grid h-8 w-8 place-items-center rounded-full text-muted hover:surface-2"><x-icon name="x" class="h-4 w-4" /></button>
        </div>

        <div class="space-y-4 text-sm">
            <div>
                <p class="label mb-2">{{ __('Text size') }}</p>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" @click="setTextSize('md')" :aria-pressed="(textSize === 'md').toString()" class="footer-a11y-option" :class="textSize === 'md' ? 'footer-a11y-option-active' : ''">{{ __('Default') }}</button>
                    <button type="button" @click="setTextSize('lg')" :aria-pressed="(textSize === 'lg').toString()" class="footer-a11y-option" :class="textSize === 'lg' ? 'footer-a11y-option-active' : ''">{{ __('Large') }}</button>
                    <button type="button" @click="setTextSize('xl')" :aria-pressed="(textSize === 'xl').toString()" class="footer-a11y-option" :class="textSize === 'xl' ? 'footer-a11y-option-active' : ''">{{ __('Larger') }}</button>
                </div>
            </div>

            <label class="flex items-center justify-between rounded-xl border border-app p-3">
                <span class="text-body">{{ __('High contrast') }}</span>
                <input type="checkbox" :checked="contrast" @change="toggleContrast()" class="h-5 w-9 rounded-full surface-2 text-brand-500">
            </label>
            <label class="flex items-center justify-between rounded-xl border border-app p-3">
                <span class="text-body">{{ __('Underline links') }}</span>
                <input type="checkbox" :checked="underlineLinks" @change="toggleUnderlineLinks()" class="h-5 w-9 rounded-full surface-2 text-brand-500">
            </label>
            <label class="flex items-center justify-between rounded-xl border border-app p-3">
                <span class="text-body">{{ __('Reduce motion') }}</span>
                <input type="checkbox" :checked="reducedMotion" @change="toggleReducedMotion()" class="h-5 w-9 rounded-full surface-2 text-brand-500">
            </label>

            <button type="button" @click="reset()" class="btn btn-ghost w-full text-xs">{{ __('Reset to defaults') }}</button>
        </div>
    </x-sheet>
</div>
