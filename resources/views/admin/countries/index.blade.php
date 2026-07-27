@extends('layouts.admin')
@section('page-title', 'Countries & Regions')

@php
    $summaryCards = [
        ['Total', $summary['total'], 'globe', 'slate'],
        ['Active', $summary['active'], 'check-circle', 'emerald'],
        ['Coming soon', $summary['coming_soon'], 'clock', 'sky'],
        ['Restricted', $summary['restricted'], 'alert', 'amber'],
        ['Disabled', $summary['disabled'], 'ban', 'rose'],
    ];
@endphp

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-strong">Countries & Regions</h1>
        <p class="text-sm text-muted">Control which countries are launched, coming soon, restricted, or disabled. No delete: countries are permanent reference data used by users and transactions.</p>
    </div>

    <div class="no-scrollbar grid grid-flow-col auto-cols-[minmax(9rem,1fr)] gap-3 overflow-x-auto pb-1 lg:grid-flow-row lg:auto-cols-auto lg:grid-cols-5">
        @foreach ($summaryCards as [$label, $value, $icon, $tint])
            <div class="card-solid rounded-2xl border border-app p-3.5 shadow-sm">
                <div class="flex items-center gap-2">
                    <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-{{ $tint }}-500/15 text-{{ $tint }}-600"><x-icon :name="$icon" class="h-4 w-4" /></span>
                    <p class="truncate text-[11px] text-faint">{{ $label }}</p>
                </div>
                <p class="mt-2 text-lg font-bold text-strong">{{ $value }}</p>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        <div class="lg:col-span-2">
            <x-glass-card padding="p-0">
                <div class="overflow-x-auto"><table class="w-full text-left text-sm">
                    <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Country</th><th class="px-5 py-3">ISO</th><th class="px-5 py-3">Currency</th><th class="px-5 py-3">Status</th><th class="px-5 py-3"></th></tr></thead>
                    <tbody class="divide-y divide-app">
                        @foreach ($countries as $c)
                            <tr x-data="{ edit: false }">
                                <td class="px-5 py-3 text-strong">{{ $c->flag_emoji }} {{ $c->name }}</td>
                                <td class="px-5 py-3 text-body">{{ $c->iso2 }}</td>
                                <td class="px-5 py-3 text-body">{{ $c->currency_code }}</td>
                                <td class="px-5 py-3"><span class="pill {{ $c->launch_status->color() }} text-[10px]">{{ $c->launch_status->label() }}</span></td>
                                <td class="px-5 py-3 text-right">
                                    <button @click="edit=!edit" class="text-brand-600">Edit</button>
                                    <div x-show="edit" x-collapse style="display:none" class="mt-2">
                                        <form method="POST" action="{{ route('admin.countries.update', $c) }}" class="grid grid-cols-2 gap-2 text-left">@csrf @method('PUT')
                                            <input name="name" value="{{ $c->name }}" class="field" required>
                                            <input name="iso2" value="{{ $c->iso2 }}" maxlength="2" class="field uppercase" required>
                                            <input name="currency_code" value="{{ $c->currency_code }}" maxlength="3" class="field uppercase">
                                            <input name="dial_code" value="{{ $c->dial_code }}" class="field">
                                            <input name="flag_emoji" value="{{ $c->flag_emoji }}" class="field">
                                            <select name="launch_status" class="field">
                                                @foreach ($statuses as $s)<option value="{{ $s->value }}" @selected($c->launch_status === $s)>{{ $s->label() }}</option>@endforeach
                                            </select>
                                            <textarea name="admin_notes" class="field col-span-2" rows="2" placeholder="Admin notes">{{ $c->admin_notes }}</textarea>
                                            <button class="btn btn-primary col-span-2 text-xs">Save</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table></div>
            </x-glass-card>
        </div>
        <div>
            <x-glass-card>
                <h3 class="font-semibold text-strong">Add country</h3>
                <form method="POST" action="{{ route('admin.countries.store') }}" class="mt-4 space-y-3">@csrf
                    <input name="name" class="field" placeholder="Country name" required>
                    <div class="grid grid-cols-2 gap-2">
                        <input name="iso2" class="field uppercase" placeholder="ISO2" maxlength="2" required>
                        <input name="currency_code" class="field uppercase" placeholder="CCY" maxlength="3">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input name="dial_code" class="field" placeholder="+237">
                        <input name="flag_emoji" class="field" placeholder="🇨🇲">
                    </div>
                    <select name="launch_status" class="field">
                        @foreach ($statuses as $s)<option value="{{ $s->value }}" @selected($s->value === 'coming_soon')>{{ $s->label() }}</option>@endforeach
                    </select>
                    <button class="btn btn-primary w-full">Add</button>
                </form>
            </x-glass-card>
        </div>
    </div>
</div>
@endsection
