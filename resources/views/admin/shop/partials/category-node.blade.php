@php $cat = $node['category']; @endphp
<div class="rounded-xl border border-app" :class="{ 'ring-2 ring-brand-400': selectedId === {{ $cat->id }} }">
    <div class="flex items-center gap-2 px-3 py-2.5 hover:surface cursor-pointer" @click="select({{ $cat->id }}, '{{ $cat->slug }}')" style="padding-left: {{ 1 + $depth * 1.25 }}rem">
        @if (count($node['children']))
            <button type="button" class="shrink-0 text-faint" @click.stop="toggle({{ $cat->id }})">
                <x-icon name="chevron-right" class="h-3.5 w-3.5 transition-transform" x-bind:class="expanded.includes({{ $cat->id }}) ? 'rotate-90' : ''" />
            </button>
        @else
            <span class="w-3.5 shrink-0"></span>
        @endif
        <span class="grid h-7 w-7 shrink-0 place-items-center rounded-lg surface-2 text-brand-400"><x-icon :name="$cat->icon" class="h-3.5 w-3.5" /></span>
        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium text-strong">{{ $cat->name }}</p>
            <p class="truncate text-[11px] text-faint">{{ $cat->products_count }} product(s) @if($cat->children_count) · {{ $cat->children_count }} sub{{ $cat->children_count === 1 ? '' : 's' }} @endif</p>
        </div>
        @unless ($cat->is_active)
            <span class="pill bg-gray-400/15 text-body text-[10px]">Hidden</span>
        @endunless
        @if ($cat->featured)
            <span class="pill bg-amber-500/15 text-amber-600 text-[10px]">Featured</span>
        @endif
        <div class="flex shrink-0 items-center gap-0.5" @click.stop>
            <button type="button" class="rounded p-1 text-faint hover:surface-2" @click="moveUp({{ $cat->id }})" title="Move up"><x-icon name="chevron-up" class="h-3.5 w-3.5" /></button>
            <button type="button" class="rounded p-1 text-faint hover:surface-2" @click="moveDown({{ $cat->id }})" title="Move down"><x-icon name="chevron-down" class="h-3.5 w-3.5" /></button>
            <button type="button" class="rounded p-1 text-faint hover:surface-2" @click="openAdd({{ $cat->id }})" title="Add subcategory"><x-icon name="plus" class="h-3.5 w-3.5" /></button>
        </div>
    </div>
    @if (count($node['children']))
        <div x-show="expanded.includes({{ $cat->id }})" x-collapse class="space-y-1 border-t border-app p-1.5">
            @foreach ($node['children'] as $child)
                @include('admin.shop.partials.category-node', ['node' => $child, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
