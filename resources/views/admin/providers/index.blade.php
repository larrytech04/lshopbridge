@extends('layouts.admin')
@section('page-title', 'Payment providers')

@section('content')
<div class="space-y-5">
    <div class="rounded-2xl border border-sky-400/30 bg-sky-500/10 p-4 text-sm text-sky-100">
        <x-icon name="lock" class="mr-1 inline h-4 w-4" /> API secrets live in your <code class="rounded surface-2 px-1">.env</code> file, never in the database. Toggle availability and sandbox/live mode here.
    </div>

    <div class="grid gap-4 sm:grid-cols-2">
        @foreach ($providers as $p)
            <x-glass-card>
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="font-semibold text-strong">{{ $p->name }}</h3>
                        <p class="text-xs text-faint">{{ $p->code }} · {{ ucfirst($p->kind) }}</p>
                    </div>
                    <span class="pill {{ $p->mode==='live' ? 'bg-rose-500/15 text-rose-300' : 'bg-amber-500/15 text-amber-300' }}">{{ ucfirst($p->mode) }}</span>
                </div>
                <form method="POST" action="{{ route('admin.providers.update', $p) }}" class="mt-4 flex items-end gap-3">
                    @csrf @method('PUT')
                    <div class="flex-1"><label class="label">Mode</label><select name="mode" class="field"><option value="sandbox" @selected($p->mode==='sandbox')>Sandbox</option><option value="live" @selected($p->mode==='live')>Live</option></select></div>
                    <label class="flex items-center gap-2 pb-2.5 text-sm text-body"><input type="checkbox" name="is_active" value="1" @checked($p->is_active) class="rounded border-app surface-2 text-brand-500"> Active</label>
                    <button class="btn btn-primary">Save</button>
                </form>
            </x-glass-card>
        @endforeach
    </div>
</div>
@endsection
