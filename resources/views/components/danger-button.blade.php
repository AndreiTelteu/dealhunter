<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center gap-2 px-4 py-2 rounded-sm border border-[rgba(255,93,93,0.45)] bg-[rgba(255,93,93,0.08)] font-mono uppercase text-xs text-[#ff5d5d] hover:bg-[rgba(255,93,93,0.16)] active:translate-y-[1px] focus-ring disabled:opacity-40 disabled:pointer-events-none transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>
