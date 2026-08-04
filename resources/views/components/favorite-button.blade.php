@props(['deal'])

<button
    x-data="{
        favorited: {{ $deal->is_favorite ? 'true' : 'false' }},
        busy: false,
        toggle() {
            if (this.busy) { return; }
            this.busy = true;
            fetch(@js(route('deals.favorite.toggle', $deal)), {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name=\'csrf-token\']').content,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            })
                .then((response) => response.json())
                .then((data) => {
                    this.favorited = data.favorited;
                    document.dispatchEvent(new CustomEvent('favorites:updated', { detail: { count: data.count } }));
                })
                .finally(() => { this.busy = false; });
        },
    }"
    @click="toggle"
    type="button"
    :aria-pressed="favorited ? 'true' : 'false'"
    aria-label="Adaugă la favorite"
    class="focus-ring inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-sm border border-hairline bg-bench transition duration-150 ease-in-out hover:border-[#ff5d5d]/50"
>
    <svg class="h-4 w-4 transition-colors" :class="favorited ? 'fill-[#ff5d5d] stroke-[#ff5d5d]' : 'fill-transparent stroke-dim'"
        style="transition: transform 0.15s ease, color 0.15s ease;" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"
        stroke-width="1.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
    </svg>
</button>
