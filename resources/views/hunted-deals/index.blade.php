<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Hunted Deals') }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    {{ $huntedDeals->total() }} {{ Str::plural('hunted deal', $huntedDeals->total()) }}
                </p>
            </div>
            <a href="{{ route('hunted-deals.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                {{ __('Add Hunted Deal') }}
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Filters and Search -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="GET" action="{{ route('hunted-deals.index') }}" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <!-- Search -->
                            <div>
                                <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Search</label>
                                <input type="text" 
                                       name="search" 
                                       id="search"
                                       value="{{ request('search') }}"
                                       placeholder="Search terms or notes..."
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </div>
                            
                            <!-- Filter -->
                            <div>
                                <label for="filter" class="block text-sm font-medium text-gray-700 mb-1">Filter</label>
                                <select name="filter" id="filter" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="">All hunted deals</option>
                                    <option value="active" {{ request('filter') === 'active' ? 'selected' : '' }}>Active only</option>
                                    <option value="inactive" {{ request('filter') === 'inactive' ? 'selected' : '' }}>Inactive only</option>
                                    <option value="never_crawled" {{ request('filter') === 'never_crawled' ? 'selected' : '' }}>Never crawled</option>
                                    <option value="recently_crawled" {{ request('filter') === 'recently_crawled' ? 'selected' : '' }}>Recently crawled (24h)</option>
                                </select>
                            </div>
                            
                            <!-- Sort -->
                            <div>
                                <label for="sort" class="block text-sm font-medium text-gray-700 mb-1">Sort by</label>
                                <select name="sort" id="sort" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="updated_at" {{ request('sort') === 'updated_at' ? 'selected' : '' }}>Last updated</option>
                                    <option value="created_at" {{ request('sort') === 'created_at' ? 'selected' : '' }}>Date created</option>
                                    <option value="search_term" {{ request('sort') === 'search_term' ? 'selected' : '' }}>Search term</option>
                                    <option value="last_crawled_at" {{ request('sort') === 'last_crawled_at' ? 'selected' : '' }}>Last crawled</option>
                                    <option value="deals_count" {{ request('sort') === 'deals_count' ? 'selected' : '' }}>Deals found</option>
                                </select>
                            </div>
                            
                            <!-- Direction -->
                            <div>
                                <label for="direction" class="block text-sm font-medium text-gray-700 mb-1">Order</label>
                                <select name="direction" id="direction" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    <option value="desc" {{ request('direction') === 'desc' ? 'selected' : '' }}>Descending</option>
                                    <option value="asc" {{ request('direction') === 'asc' ? 'selected' : '' }}>Ascending</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="flex space-x-3">
                            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Apply Filters
                            </button>
                            <a href="{{ route('hunted-deals.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-md text-sm font-medium transition-colors">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Hunted Deals List -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    @if($huntedDeals->count() > 0)
                        <div class="space-y-4">
                            @foreach($huntedDeals as $huntedDeal)
                                <div class="border border-gray-200 rounded-lg p-6 hover:bg-gray-50 transition-colors">
                                    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start space-y-4 lg:space-y-0">
                                        <div class="flex-1">
                                            <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-3 mb-3">
                                                <h3 class="text-lg font-semibold text-gray-900">{{ $huntedDeal->search_term }}</h3>
                                                <div class="flex space-x-2">
                                                    @if($huntedDeal->is_active)
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Active
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd"></path>
                                                            </svg>
                                                            Inactive
                                                        </span>
                                                    @endif
                                                    
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                        {{ $huntedDeal->deals_count }} {{ Str::plural('deal', $huntedDeal->deals_count) }}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            @if($huntedDeal->notes)
                                                <p class="text-gray-600 mb-3">{{ Str::limit($huntedDeal->notes, 150) }}</p>
                                            @endif
                                            
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-gray-500">
                                                <div>
                                                    <span class="font-medium">Created:</span>
                                                    {{ $huntedDeal->created_at->format('M j, Y') }}
                                                </div>
                                                <div>
                                                    <span class="font-medium">Last updated:</span>
                                                    {{ $huntedDeal->updated_at->diffForHumans() }}
                                                </div>
                                                <div>
                                                    <span class="font-medium">Last crawled:</span>
                                                    @if($huntedDeal->last_crawled_at)
                                                        {{ $huntedDeal->last_crawled_at->diffForHumans() }}
                                                    @else
                                                        <span class="text-yellow-600">Never</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 lg:ml-6">
                                            <a href="{{ route('hunted-deals.show', $huntedDeal) }}" 
                                               class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View
                                            </a>
                                            <a href="{{ route('hunted-deals.edit', $huntedDeal) }}" 
                                               class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Edit
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Pagination -->
                        <div class="mt-6">
                            {{ $huntedDeals->links() }}
                        </div>
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">
                                @if(request()->hasAny(['search', 'filter']))
                                    No hunted deals match your criteria
                                @else
                                    No hunted deals yet
                                @endif
                            </h3>
                            <p class="mt-1 text-sm text-gray-500">
                                @if(request()->hasAny(['search', 'filter']))
                                    Try adjusting your search or filter criteria.
                                @else
                                    Get started by creating your first hunted deal to track OLX listings.
                                @endif
                            </p>
                            <div class="mt-6">
                                @if(request()->hasAny(['search', 'filter']))
                                    <a href="{{ route('hunted-deals.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Clear filters
                                    </a>
                                @else
                                    <a href="{{ route('hunted-deals.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Add your first hunted deal
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
