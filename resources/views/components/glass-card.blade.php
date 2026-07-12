@props(['hover' => false, 'padding' => 'p-6'])

<div {{ $attributes->merge(['class' => 'glass rounded-2xl '.$padding.($hover ? ' glass-hover' : '')]) }}>
    {{ $slot }}
</div>
