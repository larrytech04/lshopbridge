@extends('layouts.admin')
@section('page-title', 'Countries')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="lg:col-span-2">
        <x-glass-card padding="p-0">
            <div class="overflow-x-auto"><table class="w-ful
            left text-sm">
                <thead class="border-b border-app text-muted"><tr><th class="px-5 py-3">Country</th><th class="px-5 py-3">ISO</th><th class="px-5 py-3">Currency</th><th class="px-5 py-3">State</th><th class="px-5 py-3"></th></tr></thead>
                <tbody class="divide-y divide-app">
                    @foreach ($countries as $c)
                        <tr x-data="{ edit: false }">
                            <td class="px-5 py-3 text-strong">{{ $c->flag_emoji }} {{ $c->name }}</td>
                            <td class="px-5 py-3 text-body">{{ $c->iso2 }}</td>
                            <td class="px-5 py-3 text-body">{{ $c->currency_code }}</td>
                            <td class="px-5 py-3">@if($c->is_blocked)<span class="pill bg-rose-500/15 text-rose-300">Blocked</span>@elseif($c->is_active)<span class="pill bg-emerald-500/15 text-emerald-300">Active</span>@else<span class="pill bg-slate-400/15 text-body">Off</span>@endif</td>
                            <td class="px-5 py-3 text-right">
                                <button @click="edit=!edit" class="text-brand-300">Edit</button>
                                <div x-show="edit" x-collapse style="display:none" class="mt-2">
                                    <form method="POST" action="{{ route('admin.countries.update', $c) }}" class="grid grid-cols-2 gap-2 text-left">@csrf @method('PUT')
                                        <input name="name" value="{{ $c->name }}" class="field" required>
                                        <input name="iso2" value="{{ $c->iso2 }}" maxlength="2" class="field uppercase" required>
                                        <input name="currency_code" value="{{ $c->currency_code }}" maxlength="3" class="field uppercase">
                                        <input name="flag_emoji" value="{{ $c->flag_emoji }}" class="field">
                                        <label class="flex items-center gap-2 text-xs text-body"><input type="checkbox" name="is_active" value="1" @checked($c->is_active) class="rounded surface-2"> Active</label>
                                        <label class="flex items-center gap-2 text-xs text-body"><input type="checkbox" name="is_blocked" value="1" @checked($c->is_blocked) class="rounded surface-2"> Blocked</label>
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
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded surface-2"> Active</label>
                <button class="btn btn-primary w-full">Add</button>
            </form>
        </x-glass-card>
    </div>
</div>
@endsection
