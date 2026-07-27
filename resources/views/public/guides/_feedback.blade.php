{{-- Shared "was this helpful?" widget — included from both the marketing guide page and the dashboard Learning Center. --}}
<div class="mt-10 rounded-2xl border border-app card-solid p-6 text-center" x-data="{ voted: {{ $alreadyVoted ? 'true' : 'false' }}, showReasons: false }">
    <template x-if="!voted">
        <div>
            <p class="font-semibold text-strong">{{ __('Was this guide helpful?') }}</p>
            <form method="POST" action="{{ route('guides.feedback', $guide) }}" x-show="!showReasons" class="mt-3 flex justify-center gap-3">
                @csrf
                <x-honeypot />
                <x-form-timing form-type="guide_feedback" />
                <input type="hidden" name="was_helpful" value="1">
                <button type="submit" class="qa-btn qa-btn-good"><x-icon name="check" class="h-3.5 w-3.5" /> {{ __('Yes') }}</button>
                <button type="button" @click.prevent="showReasons = true" class="qa-btn"><x-icon name="x" class="h-3.5 w-3.5" /> {{ __('No') }}</button>
            </form>
            <form method="POST" action="{{ route('guides.feedback', $guide) }}" x-show="showReasons" x-cloak class="mx-auto mt-3 max-w-sm space-y-3">
                @csrf
                <x-honeypot />
                <x-form-timing form-type="guide_feedback" />
                <input type="hidden" name="was_helpful" value="0">
                <select name="reason" class="field">
                    @foreach (\App\Enums\GuideFeedbackReason::cases() as $r)
                        <option value="{{ $r->value }}">{{ __($r->label()) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="qa-btn w-full">{{ __('Submit feedback') }}</button>
            </form>
        </div>
    </template>
    <p x-show="voted" x-cloak class="font-semibold text-strong">{{ __('Thanks for the feedback!') }}</p>
</div>
