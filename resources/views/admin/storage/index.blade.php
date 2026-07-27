@extends('layouts.admin')
@section('page-title', 'Storage & files')

@section('content')
<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold text-strong">Storage & files</h1>
        <p class="text-sm text-muted">Real usage for each local filesystem disk, computed live from disk. S3 is configured but unused, so it's not shown here.</p>
    </div>

    <div class="grid grid-cols-2 gap-3 lg:grid-cols-3">
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #64748B"><x-icon name="doc" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Server disk used</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $serverUsedPct !== null ? $serverUsedPct.'%' : 'Data unavailable' }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #10B981"><x-icon name="check-circle" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Free space</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $serverFreeGb !== null ? $serverFreeGb.' GB' : 'Data unavailable' }}</p>
        </div>
        <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg text-white" style="background: #64748B"><x-icon name="gauge" class="h-4 w-4" /></span>
                <p class="truncate text-[11px] text-faint">Total capacity</p>
            </div>
            <p class="mt-2 text-lg font-bold text-strong">{{ $serverTotalGb !== null ? $serverTotalGb.' GB' : 'Data unavailable' }}</p>
        </div>
    </div>

    <section>
        <h3 class="mb-3 font-semibold text-strong">Disks</h3>
        <div class="grid gap-4 lg:grid-cols-3">
            @foreach ($disks as $disk)
                <div class="card-solid rounded-2xl border border-app p-5 shadow-sm">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-500/15 text-brand-600">
                            <x-icon name="doc" class="h-5 w-5" />
                        </span>
                        <div class="min-w-0">
                            <p class="font-semibold capitalize text-strong">{{ $disk['name'] }}</p>
                            <p class="truncate font-mono text-xs text-faint">{{ $disk['root'] }}</p>
                        </div>
                    </div>
                    <dl class="mt-4 grid grid-cols-2 gap-3 border-t border-app pt-4 text-sm">
                        <div><dt class="text-faint">Size</dt><dd class="text-body">{{ number_format($disk['bytes'] / 1048576, 1) }} MB</dd></div>
                        <div><dt class="text-faint">Files</dt><dd class="text-body">{{ number_format($disk['files']) }}</dd></div>
                    </dl>
                    @if ($sharedRoots->contains($disk['name']) && $sharedRoots->count() > 1)
                        <p class="mt-3 text-xs text-faint">Shares a physical folder with: {{ $sharedRoots->reject(fn ($n) => $n === $disk['name'])->implode(', ') }}</p>
                    @endif
                </div>
            @endforeach
        </div>
    </section>
</div>
@endsection
