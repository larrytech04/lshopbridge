<div class="mt-10 flex flex-col items-center justify-between gap-3 pt-5 text-[11px] text-faint sm:flex-row">
    <div class="flex items-center gap-4">
        <a href="{{ route('legal.show', 'privacy') }}" class="hover:text-body">{{ __('Privacy') }}</a>
        <a href="{{ route('legal.show', 'terms') }}" class="hover:text-body">{{ __('Terms') }}</a>
        <a href="{{ route('legal.index') }}" class="hover:text-body">{{ __('Legal Center') }}</a>
    </div>
    <span>© {{ date('Y') }} {{ setting('site_name', config('platform.name')) }}</span>
</div>
