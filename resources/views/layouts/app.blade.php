<!DOCTYPE html>
<html lang="ro">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title . ' - ' : '' }}{{ config('app.name', 'OLX Deal Hunter') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css2?family=Archivo:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-[#06080a] text-[#eaf4f6]">
        <div class="min-h-screen flex flex-col">
            @include('layouts.navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-hairline bg-bench">
                    <div class="max-w-7xl mx-auto py-5 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Flash Messages -->
            @if (session('success') || session('error') || session('info') || session('warning'))
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4">
                    @if (session('success'))
                        <div class="flash-line flash-green mb-3" role="alert">
                            <span class="spec-line inline-block w-[2px] self-stretch bg-[#7dffa8] text-[#7dffa8]" aria-hidden="true"></span>
                            <span class="block sm:inline">{{ session('success') }}</span>
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="flash-line flash-red mb-3" role="alert">
                            <span class="spec-line inline-block w-[2px] self-stretch bg-[#ff5d5d] text-[#ff5d5d]" aria-hidden="true"></span>
                            <span class="block sm:inline">{{ session('error') }}</span>
                        </div>
                    @endif
                    @if (session('info'))
                        <div class="flash-line flash-beam mb-3" role="alert">
                            <span class="spec-line inline-block w-[2px] self-stretch bg-[#59e3ff] text-[#59e3ff]" aria-hidden="true"></span>
                            <span class="block sm:inline">{{ session('info') }}</span>
                        </div>
                    @endif
                    @if (session('warning'))
                        <div class="flash-line flash-amber mb-3" role="alert">
                            <span class="spec-line inline-block w-[2px] self-stretch bg-[#ffc46b] text-[#ffc46b]" aria-hidden="true"></span>
                            <span class="block sm:inline">{{ session('warning') }}</span>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Page Content -->
            <main class="flex-1">
                {{ $slot }}
            </main>

            <!-- ============ FOOTER ============ -->
            <footer class="border-t border-hairline">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
                    <p class="font-mono text-[0.65rem] uppercase text-dim/50">
                        OLX·Deal Hunter &mdash; camera de analiză &middot; &copy; {{ date('Y') }}
                    </p>
                </div>
            </footer>
        </div>
    </body>
</html>
