@props(['value'])

<label {{ $attributes->merge(['class' => 'block placard text-[0.65rem]']) }}>
    {{ $value ?? $slot }}
</label>
