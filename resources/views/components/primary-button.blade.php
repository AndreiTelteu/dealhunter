<button {{ $attributes->merge(['type' => 'submit', 'class' => 'beamkey beamkey-armed focus-ring rounded-sm px-5 py-2.5 text-xs']) }}>
    {{ $slot }}
</button>
