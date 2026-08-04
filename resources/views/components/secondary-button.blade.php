<button {{ $attributes->merge(['type' => 'button', 'class' => 'beamkey focus-ring rounded-sm px-4 py-2 text-xs disabled:opacity-40 disabled:pointer-events-none']) }}>
    {{ $slot }}
</button>
