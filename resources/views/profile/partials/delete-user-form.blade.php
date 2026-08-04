<section class="space-y-6">
    <header>
        <p class="font-mono text-[0.6rem] uppercase text-em-red">Zonă ireversibilă</p>
        <h2 class="mt-2 text-lg font-bold text-[#eaf4f6] sm:text-xl">
            Șterge contul
        </h2>

        <p class="mt-1.5 max-w-xl text-sm text-dim">
            Toate datele contului vor fi șterse definitiv. Păstrează înainte informațiile de care ai nevoie.
        </p>
    </header>

    <x-danger-button
        x-data=""
        x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')"
    >Șterge contul</x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-5 sm:p-7">
            @csrf
            @method('delete')

            <p class="font-mono text-[0.6rem] uppercase text-em-red">Confirmare</p>
            <h2 class="mt-2 text-lg font-bold text-[#eaf4f6] sm:text-xl">
                Ștergi definitiv contul?
            </h2>

            <p class="mt-1.5 max-w-xl text-sm text-dim">
                Această acțiune nu poate fi anulată. Introdu parola pentru confirmare.
            </p>

            <div class="mt-6">
                <x-input-label for="password" value="Parolă" class="sr-only" />

                <x-text-input
                    id="password"
                    name="password"
                    type="password"
                    class="mt-1 block w-full sm:w-3/4"
                    placeholder="Parolă"
                />

                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <x-secondary-button x-on:click="$dispatch('close')">
                    Renunță
                </x-secondary-button>

                <x-danger-button>
                    Șterge definitiv
                </x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
