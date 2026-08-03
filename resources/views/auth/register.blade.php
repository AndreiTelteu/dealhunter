<x-guest-layout>
    <h1 class="font-sans font-bold text-2xl tracking-tight">Creează cont</h1>
    <p class="mt-2 text-sm text-dim">Îți faci cont în două minute și salvezi prima căutare.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-6">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" value="Nume" />
            <x-text-input id="name" class="block mt-2 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-5">
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <x-input-label for="password" value="Parolă" />

            <x-text-input id="password" class="block mt-2 w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

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
                Creează cont
            </x-primary-button>
        </div>

        <div class="mt-6 border-t border-hairline pt-5 text-center sm:text-left">
            <a class="text-sm text-dim hover:text-beam transition-colors focus-ring rounded-sm" href="{{ route('login') }}">
                Ai deja cont? <span class="text-beam">Autentifică-te</span>
            </a>
        </div>
    </form>
</x-guest-layout>
