{{--
    Wraps a non-urgent, non-blocking notice (a nudge, a status FYI — never
    something the user must act on before continuing) with a small close
    button and permanent per-browser dismissal. The wrapper only owns the
    dismissed/dismiss() state; where the actual X button sits, and the rest
    of the banner's markup, is entirely up to the caller via the slot — call
    `dismiss()` from anywhere inside it (e.g. `@click="dismiss()"`), Alpine
    scope reaches through Blade's slot boundary fine.

    `id` must be stable and unique per distinct notice (include the record's
    own id/state in it if the message can change, e.g. an agent's status —
    see agent/dashboard.blade.php — so a stale dismissal never suppresses a
    genuinely new, different message reusing the same banner).

    Never wrap anything the user actually needs to see before proceeding
    (KYC blocks, suspension notices that gate a real action, payment
    failures) — those stay permanently visible, this component is only for
    the "would be nice, not required" tier.
--}}
@props(['id'])
<div x-data="{
        dismissed: localStorage.getItem('pb-dismissed-{{ $id }}') === '1',
        dismiss() { this.dismissed = true; localStorage.setItem('pb-dismissed-{{ $id }}', '1'); },
     }" x-show="!dismissed" x-cloak>
    {{ $slot }}
</div>
