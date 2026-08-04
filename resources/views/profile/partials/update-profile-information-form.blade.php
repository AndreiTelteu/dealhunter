<section>
    <header>
        <p class="placard text-[0.6rem]">Date personale</p>
        <h2 class="mt-2 text-lg font-bold text-[#eaf4f6] sm:text-xl">
            Datele tale
        </h2>

        <p class="mt-1.5 max-w-xl text-sm text-dim">
            Numele și adresa de email folosite pentru cont.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="Nume" />
            <x-text-input id="name" name="name" type="text" class="mt-2 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input id="email" name="email" type="email" class="mt-2 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 border border-[#59e3ff]/25 bg-[#06080a] px-4 py-3">
                    <p class="text-sm text-dim">
                        Adresa ta de email nu este verificată.

                        <button form="send-verification" class="ml-1 text-beam underline decoration-[#59e3ff]/40 underline-offset-4 transition-colors hover:text-[#eaf4f6] focus-ring rounded-sm">
                            Trimite din nou emailul de verificare.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 flex items-center gap-2 text-sm font-medium text-em-green">
                            <span class="spec-line inline-block h-3 w-px bg-[#7dffa8] text-[#7dffa8]" aria-hidden="true"></span>
                            Un nou link de verificare a fost trimis.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="flex items-center gap-4">
            <x-primary-button>Salvează datele</x-primary-button>

            @if (session('status') === 'profile-updated')
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
