<!DOCTYPE html>
<html lang="ro">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'OLX Deal Hunter') }}</title>

        <!--
            DIRECTION CONTRACT — The Spectrograph
            THESIS: one beam decomposes the market's light; verdicts read as emission lines on a spectrum — refuses the SaaS neon hero and our former gauge cluster.
            OWN-WORLD: chamber black #06080a, beam cyan #59e3ff, emission green/amber/red lines, engraved graticule, Archivo voice + mono measurement.
            STORY: visitor watches a listing pass through the beam and be understood — then arms their own beam.
            FIRST VIEWPORT: optical axis across the screen — specimen source left, live beam center, spectrum resolving right; headline under the axis; primary action as armed beam-key.
            FORM: spectral laboratory bench, grounded candidate #3 of 7, seed key 273feecc.
        -->

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css2?family=Archivo:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-[#06080a] text-[#eaf4f6]">
        <div class="min-h-screen flex flex-col">

            <!-- ============ INSTRUMENT RAIL ============ -->
            <header class="border-b border-hairline bg-rail">
                <div class="max-w-6xl mx-auto px-5 sm:px-8">
                    <div class="flex items-center justify-between h-16">
                        <a href="/" class="brand-mark focus-ring flex items-center rounded-sm" aria-label="Deal Hunter - Prima pagină">
                            <x-application-logo />
                        </a>

                        @if (Route::has('login'))
                            <nav class="flex items-center gap-3">
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="beamkey focus-ring rounded-sm px-4 py-2 text-xs">
                                        Panoul meu
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="beamkey focus-ring rounded-sm px-4 py-2 text-xs">
                                        Autentificare
                                    </a>
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2 text-xs">
                                            Creează cont
                                        </a>
                                    @endif
                                @endauth
                            </nav>
                        @endif
                    </div>
                </div>
            </header>

            <main class="flex-1">

                <!-- ============ FIRST VIEWPORT: THE OPTICAL AXIS ============ -->
                <section class="relative overflow-hidden" aria-labelledby="hero-heading">
                    <div class="relative max-w-6xl mx-auto px-5 sm:px-8 pt-14 pb-16 sm:pt-20 sm:pb-24">

                        <p class="placard text-xs mb-10">Urmărește automat anunțuri pe OLX România · demo live</p>

                        <!-- ===== the bench: source → beam → spectrum ===== -->
                        <div class="relative graticule border-y border-hairline h-36 sm:h-56 mb-10 sm:mb-12" aria-hidden="true">

                            <!-- specimen source: a listing entering the beam -->
                            <div class="absolute left-[3%] top-1/2 -translate-y-1/2 w-[17%] sm:w-[15%] min-w-[4.5rem] sm:min-w-[5.5rem]">
                                <div class="border border-hairline bg-bench px-2 sm:px-3 py-2 sm:py-2.5">
                                    <p class="font-mono text-[0.55rem] sm:text-[0.6rem] uppercase text-dim mb-1 sm:mb-1.5">Anunț nou</p>
                                    <p class="font-mono text-[0.6rem] sm:text-[0.65rem] leading-snug text-[#eaf4f6]">iPhone 13<br>1.850 lei</p>
                                </div>
                                <div class="mt-2 h-px bg-gradient-to-r from-transparent via-[#59e3ff]/40 to-transparent"></div>
                            </div>

                            <!-- absorption trace: the raw signal the beam reads through -->
                            <svg class="absolute left-[21%] right-[46%] top-1/2 -translate-y-1/2 h-12 sm:h-16 w-auto" style="width:33%" viewBox="0 0 200 40" preserveAspectRatio="none" aria-hidden="true">
                                <path d="M0 20 L18 20 L24 12 L32 27 L40 20 L66 20 L73 8 L82 30 L90 20 L128 20 L135 14 L142 25 L150 20 L200 20"
                                      fill="none" stroke="#59e3ff" stroke-width="1.2" opacity="0.5"
                                      stroke-dasharray="4 3" class="trace-live"/>
                            </svg>

                            <!-- the traveling beam -->
                            <div class="beam-core beam-travel absolute top-0 bottom-0 w-[3px]"></div>

                            <!-- spectrum resolving on the right -->
                            <div class="absolute right-[3%] top-0 bottom-0 w-[40%] sm:w-[38%]">
                                <!-- baseline -->
                                <div class="absolute bottom-[26%] inset-x-0 h-px bg-[#1c242a]"></div>
                                <!-- emission lines, igniting as the beam passes -->
                                <div class="absolute bottom-[26%] left-[8%]  w-[2px] spec-line line-ignite" style="height:42%; background:#7dffa8; color:#7dffa8; animation-delay:1.6s,1.6s;"></div>
                                <div class="absolute bottom-[26%] left-[24%] w-[2px] spec-line line-ignite" style="height:60%; background:#7dffa8; color:#7dffa8; animation-delay:1.75s,1.75s;"></div>
                                <div class="absolute bottom-[26%] left-[41%] w-[2px] spec-line line-ignite" style="height:26%; background:#ffc46b; color:#ffc46b; animation-delay:1.9s,1.9s;"></div>
                                <div class="absolute bottom-[26%] left-[59%] w-[2px] spec-line line-ignite" style="height:72%; background:#7dffa8; color:#7dffa8; animation-delay:2.05s,2.05s;"></div>
                                <div class="absolute bottom-[26%] left-[76%] w-[2px] spec-line line-ignite alert-live" style="height:84%; background:#ff5d5d; color:#ff5d5d; animation-delay:2.2s,2.2s;"></div>
                                <div class="absolute bottom-[26%] left-[91%] w-[2px] spec-line line-ignite" style="height:20%; background:#7dffa8; color:#7dffa8; animation-delay:2.35s,2.35s;"></div>
                                <!-- wavelength scale -->
                                <div class="absolute bottom-[8%] inset-x-0 flex justify-between font-mono text-[0.55rem] text-dim/60">
                                    <span>380</span><span class="hidden sm:inline">nm</span><span>750</span>
                                </div>
                            </div>
                        </div>

                        <h1 id="hero-heading" class="max-w-3xl font-sans font-extrabold leading-[1.04] text-[2rem] sm:text-5xl md:text-[4.2rem] text-balance break-words">
                            Nu rata niciun chilipir<br>
                            <span class="text-beam">de pe OLX.</span>
                        </h1>

                        <p class="mt-6 text-base sm:text-lg leading-relaxed text-dim" style="max-width:62ch">
                            Salvezi căutarea — de exemplu „iPhone 13 sub 2.000 lei" — și OLX Deal Hunter urmărește OLX România non-stop pentru tine. Primești anunțurile noi, vezi cum se mișcă prețurile și afli care oferte merită deschise.
                        </p>

                        <div class="mt-9 flex flex-wrap items-center gap-4">
                            @auth
                                <a href="{{ route('dashboard') }}" class="beamkey beamkey-armed focus-ring rounded-sm px-7 py-3.5 text-sm">
                                    Deschide panoul
                                </a>
                            @else
                                <a href="{{ route('register') }}" class="beamkey beamkey-armed focus-ring rounded-sm px-7 py-3.5 text-sm">
                                    Creează cont gratuit
                                </a>
                                <a href="{{ route('login') }}" class="beamkey focus-ring rounded-sm px-7 py-3.5 text-sm">
                                    Am deja cont
                                </a>
                            @endauth
                        </div>
                    </div>
                </section>

                <!-- ============ DEMONSTRATION SPECTRUM ============ -->
                <section class="border-t border-hairline bg-bench" aria-labelledby="spectrum-heading">
                    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-16 sm:py-24">

                        <div class="flex items-end justify-between flex-wrap gap-4 mb-10">
                            <div>
                                <h2 id="spectrum-heading" class="font-sans font-bold text-2xl sm:text-3xl">Ce afli despre un anunț, dintr-o privire</h2>
                                <p class="mt-2 text-dim text-sm sm:text-base" style="max-width:56ch">Fiecare anunț nou e analizat automat. Exemplul de mai jos arată ce primești pentru o căutare urmărită.</p>
                            </div>
                            <p class="placard text-[0.65rem] sm:text-right leading-relaxed">Exemplu demonstrativ<br><span class="text-dim/50">valori fictive</span></p>
                        </div>

                        <div class="relative border border-hairline bg-[#06080a]">
                            {{-- the chamber: all six lines on one baseline --}}
                            <div class="relative h-56 sm:h-64 graticule" aria-hidden="true">
                                <div class="absolute inset-x-0 bottom-0 grid grid-cols-6 h-full">
                                    <x-spectrum-line :height="0.92" state="green" :delay="1.2" />
                                    <x-spectrum-line :height="0.62" state="green" :delay="1.35" />
                                    <x-spectrum-line :height="0.34" state="green" :delay="1.5" />
                                    <x-spectrum-line :height="0.61" state="amber" :delay="1.65" />
                                    <x-spectrum-line :height="0.48" state="green" :delay="1.8" />
                                    <x-spectrum-line :height="0.96" state="red" :delay="1.95" />
                                </div>
                                {{-- wavelength ruler --}}
                                <div class="absolute inset-x-5 sm:inset-x-8 bottom-0 h-px bg-[#1c242a]"></div>
                                <div class="absolute inset-x-5 sm:inset-x-8 bottom-0 flex justify-between font-mono text-[0.55rem] text-dim/50 pb-1.5 translate-y-full pt-1">
                                    <span>potrivire</span><span>stare</span><span>preț</span><span>alertă</span>
                                </div>
                            </div>

                            {{-- the ledger under the plate --}}
                            <dl class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-x-6 gap-y-8 px-5 sm:px-8 py-8">
                                <div>
                                    <dd class="font-mono font-bold tabular-nums text-xl sm:text-2xl leading-none text-em-green" style="text-shadow:0 0 16px currentColor">92<span class="text-dim text-sm font-normal">&thinsp;%</span></dd>
                                    <dt class="placard text-[0.65rem] mt-2">Potrivire</dt>
                                    <dd class="mt-1 text-xs font-mono text-dim/70">corespunde căutării tale</dd>
                                </div>
                                <div>
                                    <dd class="font-mono font-bold tabular-nums text-xl sm:text-2xl leading-none text-em-green" style="text-shadow:0 0 16px currentColor">Da</dd>
                                    <dt class="placard text-[0.65rem] mt-2">Pare funcțional</dt>
                                    <dd class="mt-1 text-xs font-mono text-dim/70">după descriere și poze</dd>
                                </div>
                                <div>
                                    <dd class="font-mono font-bold tabular-nums text-xl sm:text-2xl leading-none text-em-green" style="text-shadow:0 0 16px currentColor">−4,8<span class="text-dim text-sm font-normal">&thinsp;%</span></dd>
                                    <dt class="placard text-[0.65rem] mt-2">Preț în scădere</dt>
                                    <dd class="mt-1 text-xs font-mono text-dim/70">în ultimele 7 zile</dd>
                                </div>
                                <div>
                                    <dd class="font-mono font-bold tabular-nums text-xl sm:text-2xl leading-none text-em-amber" style="text-shadow:0 0 16px currentColor">61<span class="text-dim text-sm font-normal">&thinsp;%</span></dd>
                                    <dt class="placard text-[0.65rem] mt-2">Vânzător</dt>
                                    <dd class="mt-1 text-xs font-mono text-dim/70">istoric incomplet</dd>
                                </div>
                                <div>
                                    <dd class="font-mono font-bold tabular-nums text-xl sm:text-2xl leading-none text-em-green" style="text-shadow:0 0 16px currentColor">3<span class="text-dim text-sm font-normal">&thinsp;h</span></dd>
                                    <dt class="placard text-[0.65rem] mt-2">Prospețime</dt>
                                    <dd class="mt-1 text-xs font-mono text-dim/70">publicat acum 3 ore</dd>
                                </div>
                                <div>
                                    <dd class="font-mono font-bold tabular-nums text-xl sm:text-2xl leading-none text-em-red" style="text-shadow:0 0 16px currentColor">−150<span class="text-dim text-sm font-normal">&thinsp;lei</span></dd>
                                    <dt class="placard text-[0.65rem] mt-2">Alertă preț</dt>
                                    <dd class="mt-1 text-xs font-mono text-dim/70">sub pragul tău</dd>
                                </div>
                            </dl>
                        </div>

                        <p class="sr-only">
                            Exemplu demonstrativ cu valori fictive. Potrivire cu căutarea: 92 la sută. Pare funcțional: da.
                            Preț în scădere cu 4,8 la sută în ultimele 7 zile. Vânzător: 61 la sută, istoric incomplet.
                            Prospețime: publicat acum 3 ore. Alertă de preț: cu 150 de lei sub pragul tău.
                        </p>
                    </div>
                </section>

                <!-- ============ HOW THE BENCH WORKS ============ -->
                <section class="border-t border-hairline" aria-labelledby="how-heading">
                    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-16 sm:py-24">
                        <h2 id="how-heading" class="font-sans font-bold text-2xl sm:text-3xl mb-14">Cum funcționează</h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-x-10 gap-y-12">
                            <div class="border-t border-hairline pt-6">
                                <p class="placard text-xs text-beam mb-3">Pasul 1</p>
                                <h3 class="font-semibold text-lg mb-2">Salvezi căutarea</h3>
                                <p class="text-dim leading-relaxed text-sm" style="max-width:38ch">Scrii ce cauți pe OLX — un model, un termen, o limită de preț. De exemplu: „iPhone 13, maximum 2.000 lei".</p>
                            </div>
                            <div class="border-t border-hairline pt-6">
                                <p class="placard text-xs text-beam mb-3">Pasul 2</p>
                                <h3 class="font-semibold text-lg mb-2">Noi urmărim OLX non-stop</h3>
                                <p class="text-dim leading-relaxed text-sm" style="max-width:38ch">Sistemul scanează OLX România încontinuu și salvează fiecare anunț nou și fiecare schimbare de preț.</p>
                            </div>
                            <div class="border-t border-hairline pt-6">
                                <p class="placard text-xs text-beam mb-3">Pasul 3</p>
                                <h3 class="font-semibold text-lg mb-2">Tu vezi doar ce contează</h3>
                                <p class="text-dim leading-relaxed text-sm" style="max-width:38ch">Pentru fiecare anunț afli dacă se potrivește, dacă pare funcțional și când scade prețul sub pragul tău.</p>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- ============ FINAL CALLOUT ============ -->
                <section class="border-t border-hairline bg-bench">
                    <div class="max-w-6xl mx-auto px-5 sm:px-8 py-16 sm:py-24 text-center">
                        <p class="placard text-xs mb-6">Gratuit pentru început</p>
                        <h2 class="font-sans font-extrabold leading-tight text-3xl sm:text-4xl md:text-5xl text-balance max-w-2xl mx-auto">
                            Pune OLX-ul să lucreze pentru tine din seara asta.
                        </h2>
                        <p class="mt-5 text-dim max-w-md mx-auto text-sm sm:text-base" style="max-width:48ch">Îți faci cont în două minute, salvezi prima căutare și primești anunțurile noi direct în panou.</p>
                        @guest
                            <div class="mt-9">
                                <a href="{{ route('register') }}" class="beamkey beamkey-armed focus-ring rounded-sm px-8 py-4 text-sm">
                                    Creează cont gratuit
                                </a>
                            </div>
                        @endguest
                    </div>
                </section>
            </main>

            <!-- ============ FOOTER ============ -->
            <footer class="border-t border-hairline bg-[#06080a]">
                <div class="max-w-6xl mx-auto px-5 sm:px-8 py-8 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <span class="relative flex h-2 w-2">
                            <span class="absolute inline-flex h-full w-full rounded-full bg-[#59e3ff] beam-idle"
                                  style="box-shadow: 0 2px 4px rgba(0,0,0,0.7), 0 0 10px 1px #59e3ff;"></span>
                        </span>
                        <span class="font-mono uppercase text-xs text-dim">OLX·Deal&nbsp;Hunter</span>
                    </div>
                    <p class="font-mono text-xs text-dim/50">&copy; {{ date('Y') }} · urmărim OLX-ul ca tu să nu o faci</p>
                </div>
            </footer>
        </div>
    </body>
</html>
