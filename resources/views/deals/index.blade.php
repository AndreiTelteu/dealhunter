<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('All Deals') }}
            </h2>
            <div class="text-sm text-gray-600">
                {{ $deals->total() }} {{ Str::plural('deal', $deals->total()) }} found
            </div>
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Search and Filters -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <form method="GET" action="{{ route('deals.index') }}" class="space-y-4">
                        <!-- Search Bar -->
                        <div class="flex flex-col sm:flex-row gap-4">
                            <div class="flex-1">
                                <label for="search" class="sr-only">Search deals</label>
                                <input 
                                    type="text" 
                                    name="search" 
                                    id="search"
                                    value="{{ request('search') }}"
                                    placeholder="Search by title or description..."
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                >
                            </div>
                            <button 
                                type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                            >
                                Search
                            </button>
                        </div>

                        <!-- Filter Checkboxes -->
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="price_drops" 
                                    value="1"
                                    {{ request('price_drops') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    onchange="this.form.submit()"
                                >
                                <span class="text-sm text-gray-700">
                                    Price Drops
                                    <span class="text-gray-500">({{ $filterCounts['price_drops'] }})</span>
                                </span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="new_items" 
                                    value="1"
                                    {{ request('new_items') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    onchange="this.form.submit()"
                                >
                                <span class="text-sm text-gray-700">
                                    New (24h)
                                    <span class="text-gray-500">({{ $filterCounts['new_items'] }})</span>
                                </span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="matches_intent" 
                                    value="1"
                                    {{ request('matches_intent') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    onchange="this.form.submit()"
                                >
                                <span class="text-sm text-gray-700">
                                    Matches Intent
                                    <span class="text-gray-500">({{ $filterCounts['matches_intent'] }})</span>
                                </span>
                            </label>

                            <label class="flex items-center space-x-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="likely_working" 
                                    value="1"
                                    {{ request('likely_working') ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    onchange="this.form.submit()"
                                >
                                <span class="text-sm text-gray-700">
                                    Likely Working
                                    <span class="text-gray-500">({{ $filterCounts['likely_working'] }})</span>
                                </span>
                            </label>
                        </div>

                        <!-- Hidden inputs to preserve other parameters -->
                        <input type="hidden" name="sort" value="{{ request('sort', 'last_seen_at') }}">
                        <input type="hidden" name="direction" value="{{ request('direction', 'desc') }}">
                    </form>
                </div>
            </div>

            <!-- Sorting Controls -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex flex-wrap items-center gap-4">
                        <span class="text-sm font-medium text-gray-700">Sort by:</span>
                        
                        @php
                            $sortOptions = [
                                'last_seen_at' => 'Last Seen',
                                'created_at' => 'Date Added',
                                'title' => 'Title',
                                'price_amount' => 'Price',
                                'location' => 'Location'
                            ];
                            $currentSort = request('sort', 'last_seen_at');
                            $currentDirection = request('direction', 'desc');
                        @endphp

                        @foreach($sortOptions as $key => $label)
                            <a 
                                href="{{ request()->fullUrlWithQuery(['sort' => $key, 'direction' => ($currentSort === $key && $currentDirection === 'asc') ? 'desc' : 'asc']) }}"
                                class="flex items-center space-x-1 px-3 py-1 rounded-md text-sm {{ $currentSort === $key ? 'bg-blue-100 text-blue-800' : 'text-gray-600 hover:bg-gray-100' }}"
                            >
                                <span>{{ $label }}</span>
                                @if($currentSort === $key)
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        @if($currentDirection === 'asc')
                                            <path fill-rule="evenodd" d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z" clip-rule="evenodd" />
                                        @else
                                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                        @endif
                                    </svg>
                                @endif
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Deals List -->
            @if($deals->count() > 0)
                <div class="space-y-4">
                    @foreach($deals as $deal)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow">
                            <div class="p-6">
                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                    <!-- Deal Info -->
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900 truncate pr-4">
                                                <a 
                                                    href="{{ route('deals.show', $deal) }}" 
                                                    class="hover:text-blue-600 transition-colors"
                                                >
                                                    {{ $deal->title }}
                                                </a>
                                            </h3>
                                            
                                            <!-- Status Indicators -->
                                            <div class="flex flex-wrap gap-1">
                                                @if($deal->created_at >= now()->subDay())
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        New
                                                    </span>
                                                @endif
                                                
                                                @if($deal->matches_intent)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        Matches Intent
                                                    </span>
                                                @endif
                                                
                                                @if($deal->likely_working)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        Likely Working
                                                    </span>
                                                @endif
                                                
                                                @if($deal->snapshots_count > 1)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                        {{ $deal->snapshots_count }} Changes
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <!-- Description Preview -->
                                        @if($deal->description)
                                            <p class="text-gray-600 text-sm mb-3">
                                                {{ Str::limit($deal->description, 150) }}
                                            </p>
                                        @endif

                                        <!-- Meta Information -->
                                        <div class="flex flex-wrap items-center gap-4 text-sm text-gray-500">
                                            @if($deal->location)
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M5.05 4.05a7 7 0 119.9 9.9L10 18.9l-4.95-4.95a7 7 0 010-9.9zM10 11a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd" />
                                                    </svg>
                                                    {{ $deal->location }}
                                                </span>
                                            @endif
                                            
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                </svg>
                                                Last seen {{ $deal->last_seen_at->diffForHumans() }}
                                            </span>
                                            
                                            <span class="flex items-center">
                                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z" clip-rule="evenodd" />
                                                </svg>
                                                {{ $deal->huntedDeal->search_term }}
                                            </span>
                                        </div>
                                    </div>

                                    <!-- Price and Actions -->
                                    <div class="flex flex-col items-end space-y-2">
                                        @if($deal->price_amount)
                                            <div class="text-right">
                                                <div class="text-2xl font-bold text-gray-900">
                                                    {{ number_format($deal->price_amount, 0) }} {{ $deal->price_currency }}
                                                </div>
                                                @if($deal->confidence)
                                                    <div class="text-xs text-gray-500">
                                                        {{ round($deal->confidence * 100) }}% confidence
                                                    </div>
                                                @endif
                                            </div>
                                        @else
                                            <div class="text-gray-500 text-sm">Price not available</div>
                                        @endif

                                        <div class="flex space-x-2">
                                            <a 
                                                href="{{ route('deals.show', $deal) }}"
                                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                            >
                                                View Details
                                            </a>
                                            
                                            @if($deal->url)
                                                <a 
                                                    href="{{ $deal->url }}" 
                                                    target="_blank"
                                                    class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                >
                                                    View on OLX
                                                    <svg class="ml-1 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10.293 3.293a1 1 0 011.414 0l6 6a1 1 0 010 1.414l-6 6a1 1 0 01-1.414-1.414L14.586 11H3a1 1 0 110-2h11.586l-4.293-4.293a1 1 0 010-1.414z" clip-rule="evenodd" />
                                                    </svg>
                                                </a>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Pagination -->
                <div class="mt-6">
                    {{ $deals->links() }}
                </div>
            @else
                <!-- Empty State -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-gray-900">No deals found</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            @if(request()->hasAny(['search', 'price_drops', 'new_items', 'matches_intent', 'likely_working']))
                                Try adjusting your filters or search terms.
                            @else
                                Get started by creating a hunted deal to track listings.
                            @endif
                        </p>
                        <div class="mt-6">
                            @if(request()->hasAny(['search', 'price_drops', 'new_items', 'matches_intent', 'likely_working']))
                                <a 
                                    href="{{ route('deals.index') }}"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Clear Filters
                                </a>
                            @else
                                <a 
                                    href="{{ route('hunted-deals.create') }}"
                                    class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    Create Hunted Deal
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>