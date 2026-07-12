@extends('layouts.admin')
@section('page-title', 'Page content')

@section('content')
<form method="POST" action="{{ route('admin.content.update') }}" class="mx-auto max-w-4xl space-y-6">
    @csrf @method('PUT')

    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-strong">Page content</h2>
            <p class="text-sm text-muted">Edit the text shown on the public website. Leave a field blank to use the built-in default.</p>
        </div>
        <button class="btn btn-primary"><x-icon name="check" class="h-4 w-4" /> Save changes</button>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-500">{{ session('success') }}</div>
    @endif

    @foreach ($groups as $group => $blocks)
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ $group }}</h3>
            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @foreach ($blocks as [$key, $label, $input, $default])
                    <div class="{{ $input === 'textarea' ? 'sm:col-span-2' : '' }}">
                        <label class="label text-xs">{{ $label }}</label>
                        @if ($input === 'textarea')
                            <textarea name="{{ $key }}" rows="2" class="field" placeholder="{{ $default }}">{{ setting($key) }}</textarea>
                        @else
                            <input name="{{ $key }}" value="{{ setting($key) }}" class="field" placeholder="{{ $default }}">
                        @endif
                        <p class="mt-1 text-[11px] text-faint">Default: {{ \Illuminate\Support\Str::limit($default, 90) }}</p>
                    </div>
                @endforeach
            </div>
        </x-glass-card>
    @endforeach

    <div class="flex justify-end"><button class="btn btn-primary px-6">Save changes</button></div>
</form>
@endsection
