@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'border border-hairline border-l-2 border-l-[#7dffa8] bg-[#06080a] px-4 py-3 font-medium text-sm text-em-green']) }}>
        {{ $status }}
    </div>
@endif
