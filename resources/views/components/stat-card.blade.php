@props(['title', 'value', 'icon', 'color' => 'blue', 'href' => null])

@php
$colorClasses = [
    'blue' => 'bg-blue-100 text-blue-600',
    'green' => 'bg-green-100 text-green-600',
    'purple' => 'bg-purple-100 text-purple-600',
    'yellow' => 'bg-yellow-100 text-yellow-600',
];
@endphp

<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow' . ($href ? ' cursor-pointer' : '')]) }}
     @if($href) onclick="window.location.href='{{ $href }}'" @endif>
    <div class="p-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 {{ $colorClasses[$color] ?? $colorClasses['blue'] }} rounded-full flex items-center justify-center">
                    {!! $icon !!}
                </div>
            </div>
            <div class="ml-4">
                <p class="text-sm font-medium text-gray-500">{{ $title }}</p>
                <p class="text-2xl font-semibold text-gray-900">{{ $value }}</p>
            </div>
        </div>
    </div>
</div>