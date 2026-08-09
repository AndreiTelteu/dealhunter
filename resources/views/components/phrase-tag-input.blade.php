@props([
    'name',
    'label',
    'description',
    'placeholder' => 'Scrie o expresie și apasă Enter',
    'values' => [],
    'tone' => 'beam',
])

@php
    $initialValues = old($name, $values);

    if (! is_array($initialValues)) {
        $initialValues = filled($initialValues) ? preg_split('/\s*,\s*/', $initialValues) : [];
    }

    $initialValues = array_values(array_filter($initialValues, fn ($value) => filled($value)));
    $accentClasses = $tone === 'amber'
        ? 'border-[#ffc46b]/35 bg-[#ffc46b]/[0.08] text-[#ffe0a8] hover:border-[#ffc46b]/65 hover:text-[#fff0d2] focus-visible:outline-[#ffc46b]'
        : 'border-[#59e3ff]/35 bg-[#59e3ff]/[0.08] text-[#b7f4ff] hover:border-[#59e3ff]/65 hover:text-[#eafcff] focus-visible:outline-[#59e3ff]';
@endphp

<div
    x-data="{
        tags: @js($initialValues),
        draft: '',
        add() {
            const phrases = this.draft.split(',').map((phrase) => phrase.trim()).filter(Boolean);

            phrases.forEach((phrase) => {
                if (! this.tags.some((tag) => tag.toLocaleLowerCase() === phrase.toLocaleLowerCase())) {
                    this.tags.push(phrase);
                }
            });

            this.draft = '';
        },
        remove(index) {
            this.tags.splice(index, 1);
        }
    }"
>
    <label for="{{ $name }}_input" class="placard text-[0.6rem]">{{ $label }}</label>

    <div class="mt-2 border border-hairline bg-[#06080a] p-2.5 transition-colors focus-within:border-[#59e3ff]/60 focus-within:ring-2 focus-within:ring-[#59e3ff]/20">
        <div class="flex flex-wrap items-center gap-2" aria-live="polite" aria-relevant="additions removals">
            <template x-for="(tag, index) in tags" :key="`${tag}-${index}`">
                <span class="inline-flex max-w-full items-center gap-1.5 border px-2 py-1 font-mono text-[0.68rem] leading-5 {{ $accentClasses }}">
                    <span class="min-w-0 break-words" x-text="tag"></span>
                    <button
                        type="button"
                        @click="remove(index)"
                        class="-mr-0.5 inline-flex h-4 w-4 shrink-0 items-center justify-center rounded-sm text-current/70 transition-colors hover:bg-white/10 hover:text-current focus-ring"
                        :aria-label="`Elimină expresia ${tag}`"
                    >
                        <span aria-hidden="true">×</span>
                    </button>
                    <input type="hidden" name="{{ $name }}[]" :value="tag">
                </span>
            </template>

            <input
                id="{{ $name }}_input"
                type="text"
                x-model="draft"
                @keydown.enter.prevent="add()"
                @keydown.,.prevent="add()"
                @keydown.backspace="if (! draft && tags.length) remove(tags.length - 1)"
                @blur="add()"
                placeholder="{{ $placeholder }}"
                class="min-w-40 flex-1 border-0 bg-transparent px-1 py-1 font-mono text-sm text-[#eaf4f6] placeholder:text-[#8fa8b0]/50 focus:ring-0"
                aria-describedby="{{ $name }}_hint"
            >
        </div>
    </div>

    <x-input-error :messages="$errors->get($name)" class="mt-2" />
    <p id="{{ $name }}_hint" class="mt-2 text-sm text-dim" style="max-width:60ch">{{ $description }}</p>
</div>
