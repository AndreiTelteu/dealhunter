<x-guest-layout>
    <h1 class="font-sans font-bold text-2xl tracking-tight">Alege o parolă nouă</h1>
    <p class="mt-2 text-sm text-dim">Introdu adresa de email și noua parolă.</p>

    <form method="POST" action="{{ route('password.store') }}" class="mt-6">
        @csrf

        <!-- Password Reset Token -->
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <x-input-label for="password" value="Parolă nouă" />
            <x-text-input id="password" class="block mt-2 w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-5">
            <x-input-label for="password_confirmation" value="Confirmă parola" />

            <x-text-input id="password_confirmation" class="block mt-2 w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="mt-7">
            <x-primary-button class="w-full py-3">
                Resetează parola
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
