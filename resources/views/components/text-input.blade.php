@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'rounded-sm border-hairline bg-[#06080a] text-[#eaf4f6] placeholder:text-[#8fa8b0]/50 shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30 disabled:opacity-50 disabled:cursor-not-allowed']) }}>
