<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="placard text-[0.6rem] mb-1.5">Editează căutarea</p>
                <h2 class="font-sans font-bold text-xl sm:text-2xl text-[#eaf4f6] leading-tight break-words">
                    {{ $huntedDeal->search_term }}
                </h2>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2.5">
                <a href="{{ route('hunted-deals.index') }}" class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    &larr; Toate căutările
                </a>
                <a href="{{ route('hunted-deals.show', $huntedDeal) }}" class="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    Detalii
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">

            <form method="POST" action="{{ route('hunted-deals.update', $huntedDeal) }}" class="border border-hairline bg-bench px-5 py-6 sm:px-7 sm:py-7 space-y-7">
                @csrf
                @method('PUT')

                <!-- Search Term -->
                <div>
                    <x-input-label for="search_term" value="Termen căutat *" />
                    <x-text-input id="search_term" name="search_term" type="text" class="block mt-2 w-full"
                        :value="old('search_term', $huntedDeal->search_term)" required autofocus
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
                            @checked(old('is_active', $huntedDeal->is_active))
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
                        class="block mt-2 w-full rounded-sm border-hairline bg-[#06080a] text-[#eaf4f6] placeholder:text-[#8fa8b0]/50 shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30">{{ old('notes', $huntedDeal->notes) }}</textarea>
                    <x-input-error :messages="$errors->get('notes')" class="mt-2" />
                    <p class="mt-2 text-sm text-dim" style="max-width:60ch">
                        Doar pentru referința ta — nu influențează căutarea.
                    </p>
                </div>

                <!-- Actions -->
                <div class="flex flex-wrap items-center justify-between gap-3 pt-6 border-t border-hairline">
                    <x-danger-button type="button" onclick="confirmDelete()">
                        Șterge căutarea
                    </x-danger-button>
                    <div class="flex items-center gap-2.5">
                        <a href="{{ route('hunted-deals.show', $huntedDeal) }}" class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                            Anulează
                        </a>
                        <x-primary-button class="text-[0.65rem]">
                            Salvează modificările
                        </x-primary-button>
                    </div>
                </div>
            </form>

            <!-- Metadata -->
            <div class="mt-6 border border-hairline bg-bench px-5 py-5">
                <h3 class="placard text-[0.65rem] mb-4">Informații căutare</h3>
                <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-5">
                    <div>
                        <dt class="placard text-[0.58rem]">Creată</dt>
                        <dd class="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{{ $huntedDeal->created_at->format('d M Y, H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="placard text-[0.58rem]">Actualizată</dt>
                        <dd class="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{{ $huntedDeal->updated_at->format('d M Y, H:i') }}</dd>
                    </div>
                    <div>
                        <dt class="placard text-[0.58rem]">Ultima verificare</dt>
                        <dd class="mt-1.5 font-mono text-[0.8rem] tabular-nums {{ $huntedDeal->last_crawled_at ? 'text-[#eaf4f6]' : 'text-em-amber' }}">
                            {{ $huntedDeal->last_crawled_at ? $huntedDeal->last_crawled_at->format('d M Y, H:i') : 'Neverificată' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="placard text-[0.58rem]">Anunțuri găsite</dt>
                        <dd class="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{{ $huntedDeal->deals()->count() }}</dd>
                    </div>
                </dl>
            </div>

        </div>
    </div>

    <!-- ============ DELETE CONFIRMATION MODAL ============ -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-[#06080a]/80 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="delete-title">
        <div class="relative top-20 mx-auto w-[92%] max-w-md border border-hairline bg-bench shadow-[0_20px_50px_-12px_rgba(0,0,0,0.9)]">
            <span class="block h-[2px] w-full bg-[#ff5d5d] spec-line text-[#ff5d5d]" aria-hidden="true"></span>
            <div class="px-6 py-6">
                <h3 id="delete-title" class="font-sans font-bold text-lg text-[#eaf4f6]">Șterge căutarea</h3>
                <p class="mt-3 text-sm text-dim" style="max-width:56ch">
                    Sigur vrei să ștergi „{{ $huntedDeal->search_term }}"? Se șterg și toate anunțurile și instantaneele asociate. Acțiunea nu poate fi anulată.
                </p>
                <div class="mt-6 flex flex-wrap items-center justify-end gap-2.5">
                    <button type="button" onclick="closeDeleteModal()" class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                        Anulează
                    </button>
                    <form method="POST" action="{{ route('hunted-deals.destroy', $huntedDeal) }}">
                        @csrf
                        @method('DELETE')
                        <x-danger-button type="submit">
                            Șterge definitiv
                        </x-danger-button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }
        document.getElementById('deleteModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeDeleteModal();
            }
        });
    </script>
</x-app-layout>
