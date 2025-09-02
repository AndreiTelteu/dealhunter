<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'OLX Deal Hunter') }}</title>
        
        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
        
        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-gray-50 font-sans antialiased">
        <div class="min-h-screen flex flex-col">
            <!-- Navigation -->
            <nav class="bg-white shadow-sm border-b border-gray-200">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex items-center">
                            <div class="flex items-center space-x-2">
                                <div class="bg-blue-600 text-white px-3 py-1 rounded-lg font-bold text-lg">
                                    OLX
                                </div>
                                <span class="font-semibold text-gray-800">Deal Hunter</span>
                            </div>
                        </div>
                        
                        @if (Route::has('login'))
                            <div class="flex items-center space-x-4">
                                @auth
                                    <a href="{{ url('/dashboard') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                                        Dashboard
                                    </a>
                                @else
                                    <a href="{{ route('login') }}" class="text-gray-600 hover:text-gray-900 px-3 py-2 rounded-md text-sm font-medium">
                                        Log in
                                    </a>
                                    @if (Route::has('register'))
                                        <a href="{{ route('register') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                                            Register
                                        </a>
                                    @endif
                                @endauth
                            </div>
                        @endif
                    </div>
                </div>
            </nav>

            <!-- Hero Section -->
            <main class="flex-1">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
                    <div class="text-center">
                        <h1 class="text-4xl font-bold text-gray-900 sm:text-5xl md:text-6xl">
                            Never Miss a Great Deal on
                            <span class="text-blue-600">OLX Romania</span>
                        </h1>
                        <p class="mt-3 max-w-md mx-auto text-base text-gray-500 sm:text-lg md:mt-5 md:text-xl md:max-w-3xl">
                            Automatically track your favorite searches on OLX Romania. Get notified of new listings, price drops, and never miss the perfect deal again.
                        </p>
                        <div class="mt-5 max-w-md mx-auto sm:flex sm:justify-center md:mt-8">
                            @auth
                                <div class="rounded-md shadow">
                                    <a href="{{ route('dashboard') }}" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10 transition-colors">
                                        Go to Dashboard
                                    </a>
                                </div>
                            @else
                                <div class="rounded-md shadow">
                                    <a href="{{ route('register') }}" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 md:py-4 md:text-lg md:px-10 transition-colors">
                                        Get Started
                                    </a>
                                </div>
                                <div class="mt-3 rounded-md shadow sm:mt-0 sm:ml-3">
                                    <a href="{{ route('login') }}" class="w-full flex items-center justify-center px-8 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-gray-50 md:py-4 md:text-lg md:px-10 transition-colors">
                                        Sign In
                                    </a>
                                </div>
                            @endauth
                        </div>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="py-12 bg-white">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div class="lg:text-center">
                            <h2 class="text-base text-blue-600 font-semibold tracking-wide uppercase">Features</h2>
                            <p class="mt-2 text-3xl leading-8 font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                                Smart Deal Hunting
                            </p>
                            <p class="mt-4 max-w-2xl text-xl text-gray-500 lg:mx-auto">
                                Our intelligent system monitors OLX Romania 24/7 so you don't have to.
                            </p>
                        </div>

                        <div class="mt-10">
                            <div class="space-y-10 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-8 md:gap-y-10">
                                <div class="relative">
                                    <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </div>
                                    <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Automated Search Monitoring</p>
                                    <p class="mt-2 ml-16 text-base text-gray-500">
                                        Set up your search terms and let our system continuously monitor OLX for new listings that match your criteria.
                                    </p>
                                </div>

                                <div class="relative">
                                    <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"></path>
                                        </svg>
                                    </div>
                                    <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Price History Tracking</p>
                                    <p class="mt-2 ml-16 text-base text-gray-500">
                                        Track price changes over time and get notified when items drop in price or become available again.
                                    </p>
                                </div>

                                <div class="relative">
                                    <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                                        </svg>
                                    </div>
                                    <p class="ml-16 text-lg leading-6 font-medium text-gray-900">AI-Powered Classification</p>
                                    <p class="mt-2 ml-16 text-base text-gray-500">
                                        Our AI analyzes listings to identify relevant matches and assess item condition, helping you find quality deals faster.
                                    </p>
                                </div>

                                <div class="relative">
                                    <div class="absolute flex items-center justify-center h-12 w-12 rounded-md bg-blue-500 text-white">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <p class="ml-16 text-lg leading-6 font-medium text-gray-900">Real-time Updates</p>
                                    <p class="mt-2 ml-16 text-base text-gray-500">
                                        Get hourly updates on your tracked searches with comprehensive historical data and change notifications.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- CTA Section -->
                <div class="bg-blue-600">
                    <div class="max-w-2xl mx-auto text-center py-16 px-4 sm:py-20 sm:px-6 lg:px-8">
                        <h2 class="text-3xl font-extrabold text-white sm:text-4xl">
                            <span class="block">Ready to start hunting deals?</span>
                        </h2>
                        <p class="mt-4 text-lg leading-6 text-blue-200">
                            Join thousands of smart shoppers who never miss a great deal on OLX Romania.
                        </p>
                        @guest
                            <a href="{{ route('register') }}" class="mt-8 w-full inline-flex items-center justify-center px-5 py-3 border border-transparent text-base font-medium rounded-md text-blue-600 bg-white hover:bg-blue-50 sm:w-auto transition-colors">
                                Sign up for free
                            </a>
                        @endguest
                    </div>
                </div>
            </main>

            <!-- Footer -->
            <footer class="bg-white">
                <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 md:flex md:items-center md:justify-between lg:px-8">
                    <div class="flex justify-center space-x-6 md:order-2">
                        <p class="text-center text-sm text-gray-500">
                            &copy; {{ date('Y') }} OLX Deal Hunter. Built for smart shoppers.
                        </p>
                    </div>
                    <div class="mt-8 md:mt-0 md:order-1">
                        <div class="flex items-center justify-center md:justify-start">
                            <div class="bg-blue-600 text-white px-3 py-1 rounded-lg font-bold text-lg">
                                OLX
                            </div>
                            <span class="ml-2 font-semibold text-gray-800">Deal Hunter</span>
                        </div>
                    </div>
                </div>
            </footer>
        </div>
    </body>
</html>