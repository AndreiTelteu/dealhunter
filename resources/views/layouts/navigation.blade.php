<nav x-data="{ open: false }" class="bg-rail border-b border-hairline">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex h-16 items-center justify-between gap-3">
            <!-- Wordmark -->
            <div class="relative z-10 flex shrink-0 items-center">
                <a href="{{ route('dashboard') }}" class="brand-mark focus-ring flex shrink-0 items-center rounded-sm" aria-label="Deal Hunter - Panou">
                    <x-application-logo />
                </a>
            </div>

            <!-- Navigation Links -->
            <div class="hidden min-w-0 flex-1 items-stretch justify-center gap-4 sm:-my-px sm:flex lg:gap-7">
                <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                    {{ __('Panou') }}
                </x-nav-link>
                <x-nav-link :href="route('hunted-deals.index')" :active="request()->routeIs('hunted-deals.*')">
                    {{ __('Urmărite') }}
                </x-nav-link>
                <x-nav-link :href="route('deals.index')" :active="request()->routeIs('deals.*')">
                    {{ __('Anunțuri') }}
                </x-nav-link>
                <x-nav-link :href="route('ai-classification.index')" :active="request()->routeIs('ai-classification.*')">
                    {{ __('Testare AI') }}
                </x-nav-link>
                @if((bool) Auth::user()?->getAttribute('is_admin'))
                    <x-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                        {{ __('Admin') }}
                    </x-nav-link>
                @endif
            </div>

            <!-- Favorites Badge -->
            <div class="hidden shrink-0 sm:flex sm:items-center">
                <a href="{{ route('favorites.index') }}"
                    x-data="{ count: {{ $favoritesCount }} }"
                    @favorites:updated.window="count = $event.detail.count"
                    class="focus-ring inline-flex h-9 items-center gap-2 border border-hairline bg-bench px-3 font-mono text-xs text-dim transition duration-150 ease-in-out hover:border-[#ff5d5d]/50 hover:text-[#ff5d5d] rounded-sm"
                    aria-label="Favorite">
                    <svg class="h-4 w-4 stroke-[#ff5d5d]" :class="count > 0 ? 'fill-[#ff5d5d]' : 'fill-transparent'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                    </svg>
                    <span class="tabular-nums" :class="{ 'hidden': count === 0 }" x-text="count">{{ $favoritesCount }}</span>
                </a>
            </div>

            <!-- Settings Dropdown -->
            <div class="hidden shrink-0 sm:flex sm:items-center">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex h-9 items-center gap-2 border border-hairline bg-bench px-2 text-xs font-mono text-dim transition duration-150 ease-in-out hover:border-[rgba(89,227,255,0.35)] hover:text-beam focus-ring rounded-sm xl:px-3" aria-label="Meniu cont">
                            <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#7dffa8]" style="box-shadow: 0 0 8px rgba(125,255,168,0.6);" aria-hidden="true"></span>
                            <span class="grid h-5 w-5 place-items-center border border-hairline bg-rail text-[0.65rem] text-[#eaf4f6] xl:hidden" aria-hidden="true">{{ mb_substr(Auth::user()->name, 0, 1) }}</span>
                            <span class="hidden max-w-[12rem] truncate xl:inline">{{ Auth::user()->name }}</span>

                            <svg class="h-3.5 w-3.5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                            </svg>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profil') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Deconectare') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <!-- Favorites Badge (Mobile) -->
            <div class="flex shrink-0 items-center sm:hidden">
                <a href="{{ route('favorites.index') }}"
                    x-data="{ count: {{ $favoritesCount }} }"
                    @favorites:updated.window="count = $event.detail.count"
                    class="focus-ring inline-flex h-9 items-center gap-2 border border-hairline bg-bench px-2.5 font-mono text-xs text-dim transition duration-150 ease-in-out hover:border-[#ff5d5d]/50 hover:text-[#ff5d5d] rounded-sm"
                    aria-label="Favorite">
                    <svg class="h-4 w-4 stroke-[#ff5d5d]" :class="count > 0 ? 'fill-[#ff5d5d]' : 'fill-transparent'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" stroke-width="1.5" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />
                    </svg>
                    <span class="tabular-nums" :class="{ 'hidden': count === 0 }" x-text="count">{{ $favoritesCount }}</span>
                </a>
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex shrink-0 items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 border border-hairline rounded-sm text-dim hover:text-beam hover:border-[rgba(89,227,255,0.35)] focus-ring transition duration-150 ease-in-out" aria-label="Meniu">
                    <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-hairline bg-[#06080a] sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Panou') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('hunted-deals.index')" :active="request()->routeIs('hunted-deals.*')">
                {{ __('Urmărite') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('deals.index')" :active="request()->routeIs('deals.*')">
                {{ __('Anunțuri') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('ai-classification.index')" :active="request()->routeIs('ai-classification.*')">
                {{ __('Testare AI') }}
            </x-responsive-nav-link>
            @if((bool) Auth::user()?->getAttribute('is_admin'))
                <x-responsive-nav-link :href="route('admin.dashboard')" :active="request()->routeIs('admin.*')">
                    {{ __('Admin') }}
                </x-responsive-nav-link>
            @endif
        </div>

        <!-- Responsive Settings Options -->
        <div class="pt-4 pb-2 border-t border-hairline">
            <div class="px-4 flex items-center gap-2.5">
                <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#7dffa8]" style="box-shadow: 0 0 8px rgba(125,255,168,0.6);" aria-hidden="true"></span>
                <div>
                    <div class="font-medium text-sm text-[#eaf4f6]">{{ Auth::user()->name }}</div>
                    <div class="font-mono text-xs text-dim">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profil') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Deconectare') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
