@props(['hover' => false, 'padding' => 'p-6', 'solid' => false])

<div {{ $attributes->merge(['class' => $solid
        ? 'card-solid rounded-3xl border border-app shadow-sm '.$padding
        : 'glass rounded-2xl '.$padding.($hover ? ' glass-hover' : '')]) }}>
    {{ $slot }}
</div>
