<x-guest-layout>
    <h1 class="font-sans font-bold text-2xl tracking-tight">Confirmă parola</h1>
    <p class="mt-2 text-sm text-dim leading-relaxed">Aceasta este o zonă securizată. Confirmă parola înainte de a continua.</p>

    <form method="POST" action="{{ route('password.confirm') }}" class="mt-6">
        @csrf

        <!-- Password -->
        <div>
            <x-input-label for="password" value="Parolă" />

            <x-text-input id="password" class="block mt-2 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-7">
            <x-primary-button class="w-full py-3">
                Confirmă
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
