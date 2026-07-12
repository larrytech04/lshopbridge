@props(['icon' => 'sparkles', 'title' => 'Nothing here yet', 'message' => null])

<div class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-app surface px-6 py-12 text-center">
    <span class="grid h-14 w-14 place-items-center rounded-2xl surface-2 text-muted ring-1 ring-app">
        <x-icon :name="$icon" class="h-6 w-6" />
    </span>
    <p class="mt-4 font-semibold text-strong">{{ $title }}</p>
    @if ($message)
        <p class="mt-1 max-w-sm text-sm text-muted">{{ $message }}</p>
    @endif
    @if (isset($action))
        <div class="mt-5">{{ $action }}</div>
    @endif
</div>
