<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="placard text-[0.6rem] mb-1.5">Căutare nouă</p>
                <h2 class="font-sans font-bold text-xl sm:text-2xl text-[#eaf4f6] leading-tight">
                    Adaugă o căutare urmărită
                </h2>
            </div>
            <a href="{{ route('hunted-deals.index') }}" class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem] shrink-0">
                &larr; Toate căutările
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('hunted-deals.store') }}" class="border border-hairline bg-bench px-5 py-6 sm:px-7 sm:py-7 space-y-7">
                @csrf

                <!-- Search Term -->
                <div>
                    <x-input-label for="search_term" value="Termen căutat *" />
                    <x-text-input id="search_term" name="search_term" type="text" class="block mt-2 w-full"
                        :value="old('search_term')" required autofocus
                        placeholder="ex. iPhone 13, laptop gaming, apartament 2 camere" />
                    <x-input-error :messages="$errors->get('search_term')" class="mt-2" />
                    <p class="mt-2 text-sm text-dim" style="max-width:60ch">
                        Acesta este termenul pe care îl căutăm automat pe OLX România. Cu cât e mai specific, cu atât rezultatele sunt mai bune.
                    </p>
                </div>

                <!-- Active Status -->
                <div>
                    <div class="flex items-center gap-2.5">
                        <input id="is_active" name="is_active" type="checkbox" value="1"
                            @checked(old('is_active', true))
                            class="rounded-sm border-hairline bg-[#06080a] text-[#59e3ff] shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30">
                        <label for="is_active" class="font-mono text-[0.7rem] uppercase text-[#eaf4f6]">
                            Activă (pornește verificarea automată)
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('is_active')" class="mt-2" />
                    <p class="mt-2 text-sm text-dim" style="max-width:60ch">
                        Când e activă, căutarea este inclusă în verificările automate de anunțuri.
                    </p>
                </div>

                <!-- Notes -->
                <div>
                    <x-input-label for="notes" value="Notițe" />
                    <textarea id="notes" name="notes" rows="4"
                        placeholder="Opțional: ce cauți exact, buget, cerințe specifice…"
                        class="block mt-2 w-full rounded-sm border-hairline bg-[#06080a] text-[#eaf4f6] placeholder:text-[#8fa8b0]/50 shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30">{{ old('notes') }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    <p class="mt-2 text-sm text-dim" style="max-width:60ch">
                        Doar pentru referința ta — nu influențează căutarea.
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-2.5 pt-6 border-t border-hairline">
                    <a href="{{ route('hunted-deals.index') }}" class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                        Anulează
                    </a>
                    <x-primary-button class="text-[0.65rem]">
                        + Creează căutarea
                    </x-primary-button>
                </div>
            </form>

            <!-- Tips -->
            <div class="mt-6 border border-hairline bg-bench px-5 py-5">
                <h3 class="placard text-[0.65rem] mb-4">Sfaturi pentru rezultate mai bune</h3>
                <ul class="space-y-3">
                    <li class="flex items-start gap-3">
                        <span class="mt-[0.4rem] inline-block h-1 w-1 shrink-0 rounded-full bg-[#59e3ff]" style="box-shadow: 0 0 6px rgba(89,227,255,0.6);" aria-hidden="true"></span>
                        <span class="text-sm text-dim">Folosește termeni specifici, precum „iPhone 13 Pro", nu doar „telefon".</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-[0.4rem] inline-block h-1 w-1 shrink-0 rounded-full bg-[#59e3ff]" style="box-shadow: 0 0 6px rgba(89,227,255,0.6);" aria-hidden="true"></span>
                        <span class="text-sm text-dim">Include marca și modelul atunci când le știi.</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-[0.4rem] inline-block h-1 w-1 shrink-0 rounded-full bg-[#59e3ff]" style="box-shadow: 0 0 6px rgba(89,227,255,0.6);" aria-hidden="true"></span>
                        <span class="text-sm text-dim">Folosește termenii din piața românească (de ex. „apartament", nu „apartment").</span>
                    </li>
                    <li class="flex items-start gap-3">
                        <span class="mt-[0.4rem] inline-block h-1 w-1 shrink-0 rounded-full bg-[#59e3ff]" style="box-shadow: 0 0 6px rgba(89,227,255,0.6);" aria-hidden="true"></span>
                        <span class="text-sm text-dim">Verificarea automată caută anunțuri noi o dată pe oră.</span>
                    </li>
                </ul>
            </div>

        </div>
    </div>
</x-app-layout>
