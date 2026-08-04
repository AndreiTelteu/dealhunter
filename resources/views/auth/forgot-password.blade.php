<x-guest-layout>
    <h1 class="font-sans font-bold text-2xl">Ai uitat parola?</h1>
    <p class="mt-2 text-sm text-dim leading-relaxed">Scrie adresa ta de email și îți trimitem un link cu care îți alegi o parolă nouă.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="mt-6">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-7">
            <x-primary-button class="w-full py-3">
                Trimite linkul de resetare
            </x-primary-button>
        </div>

        <div class="mt-6 border-t border-hairline pt-5 text-center sm:text-left">
            <a class="text-sm text-dim hover:text-beam transition-colors focus-ring rounded-sm" href="{{ route('login') }}">
                &larr; Înapoi la autentificare
            </a>
        </div>
    </form>
</x-guest-layout>
