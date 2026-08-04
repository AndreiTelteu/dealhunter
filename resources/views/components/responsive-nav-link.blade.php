@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-2 border-[#59e3ff] text-start font-mono uppercase text-xs text-[#59e3ff] bg-[rgba(89,227,255,0.06)] focus-ring transition duration-150 ease-in-out'
            : 'block w-full ps-3 pe-4 py-2 border-l-2 border-transparent text-start font-mono uppercase text-xs text-dim hover:text-[#eaf4f6] hover:bg-[#0a0e11] hover:border-[#1c242a] focus-ring transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
