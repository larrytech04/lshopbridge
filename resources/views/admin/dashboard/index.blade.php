@extends('layouts.admin')
@section('page-title', 'Admin Command Center')

@section('content')
<div x-data="commandCenter(@js($hiddenSections))" x-init="init()" @keydown.window="onKey($event)" class="mx-auto max-w-[1800px] space-y-5">

    @include('admin.dashboard._header')
    @include('admin.dashboard._customize')
    @include('admin.dashboard._drawer')

    <div class="space-y-5" x-show="!hidden.includes('attention')">@include('admin.dashboard._attention')</div>
    <div class="space-y-5" x-show="!hidden.includes('kpis')">@include('admin.dashboard._kpis')</div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2" x-show="!hidden.includes('financial')">@include('admin.dashboard._financial')</div>
        <div class="space-y-5" x-show="!hidden.includes('reconciliation')">@include('admin.dashboard._reconciliation')</div>
    </div>

    <div class="grid grid-cols-1 gap-5 xl:grid-cols-3">
        <div class="space-y-5 xl:col-span-2" x-show="!hidden.includes('geo')">@include('admin.dashboard._geo')</div>
        <div class="space-y-5" x-show="!hidden.includes('insights')">@include('admin.dashboard._insights')</div>
    </div>

    <div class="space-y-5" x-show="!hidden.includes('transactions')">@include('admin.dashboard._transactions')</div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2" x-show="!hidden.includes('customer') || !hidden.includes('compliance')">
        <div class="space-y-5" x-show="!hidden.includes('customer')">@include('admin.dashboard._customer')</div>
        <div class="space-y-5" x-show="!hidden.includes('compliance')">@include('admin.dashboard._compliance')</div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2" x-show="!hidden.includes('marketplace') || !hidden.includes('agents')">
        <div class="space-y-5" x-show="!hidden.includes('marketplace')">@include('admin.dashboard._marketplace')</div>
        <div class="space-y-5" x-show="!hidden.includes('agents')">@include('admin.dashboard._agents')</div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2" x-show="!hidden.includes('providers') || !hidden.includes('system')">
        <div class="space-y-5" x-show="!hidden.includes('providers')">@include('admin.dashboard._providers')</div>
        <div class="space-y-5" x-show="!hidden.includes('system')" id="system">@include('admin.dashboard._system')</div>
    </div>

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2" x-show="!hidden.includes('support') || !hidden.includes('activity')">
        <div class="space-y-5" x-show="!hidden.includes('support')">@include('admin.dashboard._support')</div>
        <div class="space-y-5" x-show="!hidden.includes('activity')">@include('admin.dashboard._activity')</div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function commandCenter(initialHidden) {
    return {
        hidden: initialHidden || [],
        customizeOpen: false,
        drawerOpen: false,
        drawerLoading: false,
        drawerData: null,
        init() {
            if (window.ShortcutManager) {
                window.ShortcutManager.registerAction('dash-daterange', () => this.$refs.periodSelect?.focus());
                window.ShortcutManager.registerAction('dash-refresh', () => window.location.reload());
                window.ShortcutManager.registerAction('dash-export', () => { window.location = '{{ route('admin.dashboard.export') }}?' + new URLSearchParams(window.location.search); });
                window.ShortcutManager.registerAction('dash-attention', () => document.getElementById('attention')?.scrollIntoView({ behavior: 'smooth' }));
                window.ShortcutManager.registerAction('dash-transactions', () => document.getElementById('transactions')?.scrollIntoView({ behavior: 'smooth' }));
            }
        },
        toggleHidden(key) {
            this.hidden = this.hidden.includes(key) ? this.hidden.filter(k => k !== key) : [...this.hidden, key];
        },
        async saveLayout() {
            await fetch('{{ route('admin.dashboard.widgets') }}', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                body: JSON.stringify({ hidden: this.hidden }),
            });
            this.customizeOpen = false;
        },
        async openTransaction(type, id) {
            this.drawerOpen = true;
            this.drawerLoading = true;
            this.drawerData = null;
            try {
                const res = await fetch(`/admin/dashboard/transaction/${type}/${id}`);
                this.drawerData = await res.json();
            } catch (e) { this.drawerData = { error: true }; }
            this.drawerLoading = false;
        },
        onKey(e) {
            if (e.key === 'Escape') { this.drawerOpen = false; this.customizeOpen = false; }
        },
    };
}
</script>
@endpush
