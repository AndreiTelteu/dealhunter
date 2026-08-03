<x-guest-layout>
    <h1 class="font-sans font-bold text-2xl tracking-tight">Autentificare</h1>
    <p class="mt-2 text-sm text-dim">Intră în cont ca să vezi anunțurile urmărite.</p>

    <!-- Session Status -->
    <x-auth-session-status class="mt-6" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="mt-6">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" class="block mt-2 w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-5">
            <x-input-label for="password" value="Parolă" />

            <x-text-input id="password" class="block mt-2 w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="block mt-5">
            <label for="remember_me" class="inline-flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" class="rounded-sm border-hairline bg-[#06080a] text-[#59e3ff] shadow-none focus:ring-2 focus:ring-[#59e3ff]/30 focus:ring-offset-0" name="remember">
                <span class="text-sm text-dim">Ține-mă minte</span>
            </label>
        </div>

        <div class="mt-7">
            <x-primary-button class="w-full py-3">
                Autentificare
            </x-primary-button>
        </div>

        <div class="mt-6 flex flex-col sm:flex-row items-center justify-between gap-3 border-t border-hairline pt-5">
            @if (Route::has('password.request'))
                <a class="text-sm text-dim hover:text-beam transition-colors focus-ring rounded-sm" href="{{ route('password.request') }}">
                    Ai uitat parola?
                </a>
            @endif
            <a class="text-sm text-dim hover:text-beam transition-colors focus-ring rounded-sm" href="{{ route('register') }}">
                Nu ai cont? <span class="text-beam">Creează unul</span>
            </a>
        </div>
    </form>
</x-guest-layout>
