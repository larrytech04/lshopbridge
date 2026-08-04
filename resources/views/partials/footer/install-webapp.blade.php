{{--
    Compact "Install as a web app" box — hidden entirely once the site is
    already running standalone (installed), and on browsers that will never
    support install (desktop Firefox, etc. just never set canInstall/isIOS/isAndroid).
--}}
<div x-data="installApp()" x-show="show" x-cloak style="display:none" class="relative flex items-center gap-3 rounded-2xl border border-app surface px-4 py-3">
    <span class="grid h-9 w-9 shrink-0 place-items-center overflow-hidden rounded-xl bg-white">
        <img src="{{ asset('icons/icon-192.png') }}" alt="" class="h-full w-full object-contain">
    </span>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-semibold text-strong">{{ __('Install WebApp') }}</p>
        <p class="text-xs text-muted">{{ __('Add LshopBridge to your home screen for quick access.') }}</p>
    </div>
    <button type="button" @click="promptInstall()" class="btn btn-ghost shrink-0 !px-3 !py-1.5 text-xs">{{ __('Install') }}</button>

    {{-- No native prompt available (iOS always, Android sometimes) — manual steps for whichever platform this is. --}}
    <div x-show="showSteps" x-cloak @click.outside="showSteps = false" style="display:none"
         class="glass-strong absolute bottom-full left-0 z-20 mb-2 w-72 rounded-2xl p-4 text-left shadow-2xl">
        <template x-if="isIOS">
            <div>
                <p class="text-sm font-semibold text-strong">{{ __('Install on iPhone / iPad') }}</p>
                <ol class="mt-2 space-y-1.5 text-xs text-muted">
                    <li>1. {{ __('Tap the Share button in Safari\'s toolbar.') }}</li>
                    <li>2. {{ __('Scroll down and tap "Add to Home Screen".') }}</li>
                    <li>3. {{ __('Tap "Add" to confirm.') }}</li>
                </ol>
            </div>
        </template>
        <template x-if="isAndroid && !isIOS">
            <div>
                <p class="text-sm font-semibold text-strong">{{ __('Install on Android') }}</p>
                <ol class="mt-2 space-y-1.5 text-xs text-muted">
                    <li>1. {{ __('Tap the ⋮ menu in Chrome\'s toolbar.') }}</li>
                    <li>2. {{ __('Tap "Add to Home screen" or "Install app".') }}</li>
                    <li>3. {{ __('Confirm to add it to your home screen.') }}</li>
                </ol>
            </div>
        </template>
    </div>
</div>
