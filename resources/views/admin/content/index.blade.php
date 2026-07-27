@extends('layouts.admin')
@section('page-title', 'Page content')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">
    <div>
        <h2 class="text-xl font-bold text-strong">Page content</h2>
        <p class="text-sm text-muted">Edit the text, testimonials, and process steps shown on the public website — no Blade file edits required.</p>
    </div>

    @if (session('success'))
        <div class="rounded-xl border border-emerald-400/30 bg-emerald-500/10 px-4 py-3 text-sm font-medium text-emerald-500">{{ session('success') }}</div>
    @endif

    {{-- ============ NAMED TEXT BLOCKS ============ --}}
    <form method="POST" action="{{ route('admin.content.update') }}" class="space-y-6">
        @csrf @method('PUT')
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
        <div class="flex justify-end"><button class="btn btn-primary px-6"><x-icon name="check" class="h-4 w-4" /> Save text blocks</button></div>
    </form>

    {{-- ============ TESTIMONIALS ============ --}}
    <x-glass-card>
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-strong">Testimonials</h3>
                <p class="text-xs text-faint">Shown in the homepage reviews carousel.</p>
            </div>
        </div>
        <div class="mt-4 divide-y divide-app">
            @forelse ($testimonials as $t)
                <div x-data="{ edit: false }" class="py-3">
                    <div class="flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-strong">{{ $t->name }} <span class="text-xs font-normal text-faint">· {{ ucfirst($t->source) }} · {{ $t->rating }}★</span></p>
                            <p class="truncate text-xs text-muted">{{ $t->text }}</p>
                        </div>
                        <div class="flex shrink-0 items-center gap-3">
                            @unless($t->is_active)<span class="pill bg-slate-400/15 text-body text-[10px]">Inactive</span>@endunless
                            <button type="button" @click="edit = !edit" class="text-xs text-brand-600">Edit</button>
                            <form method="POST" action="{{ route('admin.content.testimonials.destroy', $t) }}" onsubmit="return confirm('Remove this testimonial?')">@csrf @method('DELETE')<button class="text-rose-600"><x-icon name="x" class="h-4 w-4" /></button></form>
                        </div>
                    </div>
                    <div x-show="edit" x-collapse style="display:none" class="mt-3">
                        <form method="POST" action="{{ route('admin.content.testimonials.update', $t) }}" class="grid gap-2 sm:grid-cols-2">
                            @csrf @method('PUT')
                            <input name="name" value="{{ $t->name }}" class="field" placeholder="Name" required>
                            <select name="source" class="field"><option value="trustpilot" @selected($t->source==='trustpilot')>Trustpilot</option><option value="google" @selected($t->source==='google')>Google</option><option value="other" @selected($t->source==='other')>Other</option></select>
                            <input name="rating" type="number" step="0.1" min="1" max="5" value="{{ $t->rating }}" class="field" placeholder="Rating">
                            <input name="review_date" type="date" value="{{ $t->review_date?->format('Y-m-d') }}" class="field">
                            <textarea name="text" rows="2" class="field sm:col-span-2" placeholder="Review text">{{ $t->text }}</textarea>
                            <input name="sort" type="number" value="{{ $t->sort }}" class="field" placeholder="Sort">
                            <div class="flex items-center gap-4">
                                <label class="flex items-center gap-2 text-xs text-body"><input type="checkbox" name="verified" value="1" @checked($t->verified) class="rounded surface-2"> Verified</label>
                                <label class="flex items-center gap-2 text-xs text-body"><input type="checkbox" name="is_active" value="1" @checked($t->is_active) class="rounded surface-2"> Active</label>
                            </div>
                            <button class="btn btn-primary sm:col-span-2 text-xs">Save</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="py-4 text-center text-sm text-faint">No testimonials yet.</p>
            @endforelse
        </div>
        <details class="mt-4">
            <summary class="cursor-pointer text-sm font-semibold text-brand-600">+ Add testimonial</summary>
            <form method="POST" action="{{ route('admin.content.testimonials.store') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                @csrf
                <input name="name" class="field" placeholder="Name" required>
                <select name="source" class="field"><option value="trustpilot">Trustpilot</option><option value="google">Google</option><option value="other">Other</option></select>
                <input name="rating" type="number" step="0.1" min="1" max="5" value="5" class="field" placeholder="Rating">
                <input name="review_date" type="date" class="field">
                <textarea name="text" rows="2" class="field sm:col-span-2" placeholder="Review text" required></textarea>
                <label class="flex items-center gap-2 text-xs text-body"><input type="checkbox" name="verified" value="1" checked class="rounded surface-2"> Verified</label>
                <label class="flex items-center gap-2 text-xs text-body"><input type="checkbox" name="is_active" value="1" checked class="rounded surface-2"> Active</label>
                <button class="btn btn-primary sm:col-span-2 text-xs">Add testimonial</button>
            </form>
        </details>
    </x-glass-card>

    {{-- ============ HOW IT WORKS STEPS ============ --}}
    @foreach ([['fund_step', 'How It Works: Funding a China wallet', $fundSteps], ['shop_step', 'How It Works: Shopping gift cards & eSIMs', $shopSteps], ['promise', 'How It Works: Why it just works', $promises]] as [$group, $label, $rows])
        <x-glass-card>
            <h3 class="font-semibold text-strong">{{ $label }}</h3>
            <p class="text-xs text-faint">{{ $group === 'promise' ? 'Icon = an icon name from the design system (e.g. shield, chart, heart).' : 'Icon = an asset filename (e.g. Money-Wallet-1--Streamline-Ultimate.png).' }}</p>
            <div class="mt-4 divide-y divide-app">
                @forelse ($rows as $step)
                    <div x-data="{ edit: false }" class="py-3">
                        <div class="flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="truncate text-sm font-semibold text-strong">{{ $step->sort + 1 }}. {{ $step->title }}</p>
                                <p class="truncate text-xs text-muted">{{ $step->body }}</p>
                            </div>
                            <div class="flex shrink-0 items-center gap-3">
                                @unless($step->is_active)<span class="pill bg-slate-400/15 text-body text-[10px]">Inactive</span>@endunless
                                <button type="button" @click="edit = !edit" class="text-xs text-brand-600">Edit</button>
                                <form method="POST" action="{{ route('admin.content.steps.destroy', $step) }}" onsubmit="return confirm('Remove this step?')">@csrf @method('DELETE')<button class="text-rose-600"><x-icon name="x" class="h-4 w-4" /></button></form>
                            </div>
                        </div>
                        <div x-show="edit" x-collapse style="display:none" class="mt-3">
                            <form method="POST" action="{{ route('admin.content.steps.update', $step) }}" class="grid gap-2 sm:grid-cols-2">
                                @csrf @method('PUT')
                                <input type="hidden" name="group" value="{{ $group }}">
                                <input name="icon" value="{{ $step->icon }}" class="field" placeholder="Icon" required>
                                <input name="sort" type="number" value="{{ $step->sort }}" class="field" placeholder="Sort">
                                <input name="title" value="{{ $step->title }}" class="field sm:col-span-2" placeholder="Title" required>
                                <textarea name="body" rows="2" class="field sm:col-span-2" placeholder="Description" required>{{ $step->body }}</textarea>
                                <label class="flex items-center gap-2 text-xs text-body"><input type="checkbox" name="is_active" value="1" @checked($step->is_active) class="rounded surface-2"> Active</label>
                                <button class="btn btn-primary sm:col-span-2 text-xs">Save</button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="py-4 text-center text-sm text-faint">No steps yet.</p>
                @endforelse
            </div>
            <details class="mt-4">
                <summary class="cursor-pointer text-sm font-semibold text-brand-600">+ Add step</summary>
                <form method="POST" action="{{ route('admin.content.steps.store') }}" class="mt-3 grid gap-2 sm:grid-cols-2">
                    @csrf
                    <input type="hidden" name="group" value="{{ $group }}">
                    <input name="icon" class="field" placeholder="Icon" required>
                    <input name="sort" type="number" value="{{ $rows->count() }}" class="field" placeholder="Sort">
                    <input name="title" class="field sm:col-span-2" placeholder="Title" required>
                    <textarea name="body" rows="2" class="field sm:col-span-2" placeholder="Description" required></textarea>
                    <label class="flex items-center gap-2 text-xs text-body sm:col-span-2"><input type="checkbox" name="is_active" value="1" checked class="rounded surface-2"> Active</label>
                    <button class="btn btn-primary sm:col-span-2 text-xs">Add step</button>
                </form>
            </details>
        </x-glass-card>
    @endforeach
</div>
@endsection
