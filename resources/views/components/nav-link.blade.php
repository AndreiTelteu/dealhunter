@props(['active'])

@php
$classes = ($active ?? false)
            ? 'rail-link rail-link-active inline-flex items-center px-1 pt-1 border-b-2 text-xs leading-5 focus-ring transition duration-150 ease-in-out'
            : 'rail-link inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-xs leading-5 focus-ring transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
