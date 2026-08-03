<x-guest-layout>
    <h1 class="font-sans font-bold text-2xl tracking-tight">Verifică-ți adresa de email</h1>
    <p class="mt-2 text-sm text-dim leading-relaxed">Ți-am trimis un email cu un link de confirmare. Apasă pe link ca să îți activezi contul. Dacă nu l-ai primit, îți trimitem altul.</p>

    @if (session('status') == 'verification-link-sent')
        <div class="mt-6 border border-hairline border-l-2 border-l-[#7dffa8] bg-[#06080a] px-4 py-3 font-medium text-sm text-em-green">
            Ți-am trimis un nou link de confirmare pe adresa de email din cont.
        </div>
    @endif

    <div class="mt-7 flex flex-col sm:flex-row items-center justify-between gap-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <x-primary-button class="w-full sm:w-auto">
                Retrimite emailul
            </x-primary-button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="text-sm text-dim hover:text-beam transition-colors focus-ring rounded-sm">
                Deconectare
            </button>
        </form>
    </div>
</x-guest-layout>
