@extends('layouts.admin')
@section('page-title', 'API Health')

@section('content')
<div class="space-y-5">
    <div>
        <h1 class="text-2xl font-bold text-strong">API Health</h1>
        <p class="text-sm text-muted">The same honest health data shown on the dashboard — derived from real webhook history and real infrastructure checks. No API response-time or uptime figure is fabricated; where nothing measures a thing, it says so instead of inventing a number.</p>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @include('admin.dashboard._providers')
        @include('admin.dashboard._system')
    </div>
</div>
@endsection
