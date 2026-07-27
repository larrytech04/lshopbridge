@extends('layouts.admin')
@section('page-title', 'Suppliers')

@section('content')
<div class="grid gap-6 lg:grid-cols-3">
    <div class="space-y-3 lg:col-span-2">
        <h1 class="text-2xl font-bold text-strong">Suppliers</h1>
        <p class="-mt-2 text-sm text-muted">Contacts for the suppliers behind your imported and dropshipped products.</p>
        @forelse ($suppliers as $s)
            <div class="glass rounded-2xl p-4" x-data="{ edit: false }">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="font-semibold text-strong">{{ $s->name }} @unless($s->is_active)<span class="pill bg-gray-400/15 text-body text-[10px]">Inactive</span>@endunless</p>
                        <p class="text-xs text-faint">{{ $s->code }} · {{ $s->products_count }} product(s) · {{ $s->contact_email ?? 'no contact email' }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button @click="edit=!edit" class="text-sm text-brand-400">Edit</button>
                        <form method="POST" action="{{ route('admin.shop.suppliers.destroy', $s) }}" onsubmit="return confirm('Remove this supplier?')">@csrf @method('DELETE')<button class="text-rose-400"><x-icon name="x" class="h-4 w-4" /></button></form>
                    </div>
                </div>
                <div x-show="edit" x-collapse style="display:none">
                    <form method="POST" action="{{ route('admin.shop.suppliers.update', $s) }}" class="mt-3 grid gap-2 border-t border-app pt-3 sm:grid-cols-2">
                        @csrf @method('PUT')
                        <input name="name" value="{{ $s->name }}" class="field" required>
                        <input name="code" value="{{ $s->code }}" class="field" required>
                        <input name="contact_email" value="{{ $s->contact_email }}" class="field" placeholder="Contact email">
                        <input name="contact_phone" value="{{ $s->contact_phone }}" class="field" placeholder="Contact phone">
                        <input name="website" value="{{ $s->website }}" class="field sm:col-span-2" placeholder="Website">
                        <textarea name="notes" class="field sm:col-span-2" placeholder="Notes">{{ $s->notes }}</textarea>
                        <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" @checked($s->is_active) class="rounded"> Active</label>
                        <div class="sm:col-span-2"><button class="btn btn-primary text-sm">Save</button></div>
                    </form>
                </div>
            </div>
        @empty
            <x-empty icon="truck" title="No suppliers yet" message="Add a supplier to track who fulfills your imported products." />
        @endforelse
    </div>
    <div>
        <x-glass-card>
            <h3 class="font-semibold text-strong">Add supplier</h3>
            <form method="POST" action="{{ route('admin.shop.suppliers.store') }}" class="mt-4 space-y-3">
                @csrf
                <input name="name" class="field" placeholder="Name" required>
                <input name="code" class="field" placeholder="Unique code (e.g. cj-dropship)" required>
                <input name="contact_email" type="email" class="field" placeholder="Contact email">
                <input name="contact_phone" class="field" placeholder="Contact phone">
                <input name="website" class="field" placeholder="Website">
                <textarea name="notes" class="field" placeholder="Notes"></textarea>
                <label class="flex items-center gap-2 text-sm text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded"> Active</label>
                <button class="btn btn-primary w-full">Add supplier</button>
            </form>
        </x-glass-card>
    </div>
</div>
@endsection
