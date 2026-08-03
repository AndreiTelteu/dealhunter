<!DOCTYPE html>
<html lang="ro">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'OLX Deal Hunter') }}</title>

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
                        <a href="/" class="flex items-center gap-3 focus-ring rounded-sm">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full rounded-full bg-[#59e3ff] beam-idle"
                                      style="box-shadow: 0 2px 4px rgba(0,0,0,0.7), 0 0 10px 1px #59e3ff;"></span>
                            </span>
                            <span class="font-mono font-bold uppercase tracking-[0.18em] text-sm">
                                OLX<span class="text-beam">·</span>Deal&nbsp;Hunter
                            </span>
                        </a>
                        <a href="/" class="placard text-xs hover:text-beam transition-colors focus-ring rounded-sm px-1 py-1">
                            &larr; Prima pagină
                        </a>
                    </div>
                </div>
            </header>

            <!-- ============ ACCESS PANEL ============ -->
            <main class="flex-1 flex flex-col items-center justify-center px-5 sm:px-8 py-12 sm:py-16">
                <div class="w-full sm:max-w-md">
                    <div class="relative border border-hairline bg-bench px-6 sm:px-8 py-8 sm:py-10 overflow-hidden">
                        {{-- parked beam at the panel's entry edge --}}
                        <div class="beam-core beam-idle absolute left-0 top-0 bottom-0 w-[2px]" aria-hidden="true"></div>

                        <div class="relative">
                            {{ $slot }}
                        </div>
                    </div>

                    <p class="mt-6 text-center font-mono text-[0.65rem] uppercase tracking-[0.16em] text-dim/50">
                        OLX·Deal Hunter &mdash; urmărim OLX-ul ca tu să nu o faci
                    </p>
                </div>
            </main>

            <!-- ============ FOOTER ============ -->
            <footer class="border-t border-hairline bg-[#06080a]">
                <div class="max-w-6xl mx-auto px-5 sm:px-8 py-6">
                    <p class="font-mono text-xs text-dim/50 tracking-wide text-center">&copy; {{ date('Y') }} OLX·Deal Hunter</p>
                </div>
            </footer>
        </div>
    </body>
</html>
