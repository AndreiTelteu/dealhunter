<section>
    <header>
        <p class="placard text-[0.6rem]">Acces</p>
        <h2 class="mt-2 text-lg font-bold text-[#eaf4f6] sm:text-xl">
            Schimbă parola
        </h2>

        <p class="mt-1.5 max-w-xl text-sm text-dim">
            Alege o parolă lungă, unică și greu de ghicit.
        </p>
    </header>

    <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('put')

        <div>
            <x-input-label for="update_password_current_password" value="Parola actuală" />
            <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-2 block w-full" autocomplete="current-password" />
            <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password" value="Parola nouă" />
            <x-text-input id="update_password_password" name="password" type="password" class="mt-2 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="update_password_password_confirmation" value="Confirmă parola nouă" />
            <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-2 block w-full" autocomplete="new-password" />
            <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Actualizează parola</x-primary-button>

            @if (session('status') === 'password-updated')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2000)"
                    class="flex items-center gap-2 text-sm text-em-green"
                ><span class="spec-line inline-block h-3 w-px bg-[#7dffa8] text-[#7dffa8]" aria-hidden="true"></span>Salvat.</p>
            @endif
        </div>
    </form>
</section>
