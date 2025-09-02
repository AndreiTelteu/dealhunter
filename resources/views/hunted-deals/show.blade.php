<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ $huntedDeal->search_term }}
                </h2>
                <div class="flex items-center space-x-3 mt-2">
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
                    <span class="text-sm text-gray-500">
                        Last crawled: 
                        @if($huntedDeal->last_crawled_at)
                            {{ $huntedDeal->last_crawled_at->diffForHumans() }}
                        @else
                            Never
                        @endif
                    </span>
                </div>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('hunted-deals.edit', $huntedDeal) }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('hunted-deals.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    {{ __('Back to List') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                <x-stat-card 
                    title="Total Deals" 
                    :value="$stats['total_deals']" 
                    color="blue"
                    :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z\'></path></svg>'" 
                />
                
                <x-stat-card 
                    title="New (24h)" 
                    :value="$stats['new_deals_24h']" 
                    color="green"
                    :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z\'></path></svg>'" 
                />
                
                <x-stat-card 
                    title="Matching Intent" 
                    :value="$stats['matching_intent']" 
                    color="purple"
                    :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\'></path></svg>'" 
                />
                
                <x-stat-card 
                    title="Likely Working" 
                    :value="$stats['likely_working']" 
                    color="green"
                    :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M5 13l4 4L19 7\'></path></svg>'" 
                />
                
                <x-stat-card 
                    title="Price Changes" 
                    :value="$stats['price_drops']" 
                    color="yellow"
                    :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6\'></path></svg>'" 
                />
            </div>

            <!-- Notes Section -->
            @if($huntedDeal->notes)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Notes</h3>
                        <p class="text-gray-700 whitespace-pre-wrap">{{ $huntedDeal->notes }}</p>
                    </div>
                </div>
            @endif

            <!-- Associated Deals -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h3 class="text-lg font-semibold text-gray-900">Associated Deals</h3>
                        @if($deals->count() > 0)
                            <a href="{{ route('deals.index', ['hunted_deal' => $huntedDeal->id]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                View All in Deals Section →
                            </a>
                        @endif
                    </div>
                    
                    @if($deals->count() > 0)
                        <div class="space-y-4">
                            @foreach($deals as $deal)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex flex-col lg:flex-row lg:justify-between lg:items-start space-y-3 lg:space-y-0">
                                        <div class="flex-1">
                                            <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-2 mb-2">
                                                <h4 class="font-medium text-gray-900">{{ Str::limit($deal->title, 80) }}</h4>
                                                <div class="flex space-x-2">
                                                    @if($deal->matches_intent)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                                            Match
                                                        </span>
                                                    @endif
                                                    @if($deal->likely_working)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                            Working
                                                        </span>
                                                    @endif
                                                    @if($deal->created_at >= now()->subDay())
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                            New
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            
                                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm text-gray-500">
                                                <div>
                                                    @if($deal->price_amount)
                                                        <span class="font-medium text-gray-900">{{ number_format($deal->price_amount, 0) }} {{ $deal->price_currency }}</span>
                                                    @else
                                                        <span class="text-gray-400">Price not available</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    @if($deal->location)
                                                        <span>{{ $deal->location }}</span>
                                                    @else
                                                        <span class="text-gray-400">Location not available</span>
                                                    @endif
                                                </div>
                                                <div>
                                                    <span>{{ $deal->created_at->diffForHumans() }}</span>
                                                </div>
                                            </div>
                                            
                                            @if($deal->description)
                                                <p class="text-sm text-gray-600 mt-2">{{ Str::limit($deal->description, 120) }}</p>
                                            @endif
                                        </div>
                                        
                                        <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-3 lg:ml-6">
                                            <a href="{{ route('deals.show', $deal) }}" 
                                               class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                View
                                            </a>
                                            <a href="{{ $deal->url }}" 
                                               target="_blank"
                                               class="inline-flex items-center justify-center px-3 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                                </svg>
                                                OLX
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        
                        <!-- Pagination -->
                        @if($deals->hasPages())
                            <div class="mt-6">
                                {{ $deals->links() }}
                            </div>
                        @endif
                    @else
                        <div class="text-center py-12">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No deals found yet</h3>
                            <p class="mt-1 text-sm text-gray-500">
                                @if($huntedDeal->is_active)
                                    The crawler will search for deals matching "{{ $huntedDeal->search_term }}" during the next scheduled run.
                                @else
                                    This hunted deal is inactive. Enable it to start finding deals.
                                @endif
                            </p>
                            @if(!$huntedDeal->is_active)
                                <div class="mt-6">
                                    <a href="{{ route('hunted-deals.edit', $huntedDeal) }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        Activate Hunted Deal
                                    </a>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Metadata -->
            <div class="bg-gray-50 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Hunted Deal Information</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-gray-700">Created:</span>
                            <div class="text-gray-600">{{ $huntedDeal->created_at->format('M j, Y') }}</div>
                            <div class="text-gray-500">{{ $huntedDeal->created_at->format('g:i A') }}</div>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Last Updated:</span>
                            <div class="text-gray-600">{{ $huntedDeal->updated_at->format('M j, Y') }}</div>
                            <div class="text-gray-500">{{ $huntedDeal->updated_at->format('g:i A') }}</div>
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Last Crawled:</span>
                            @if($huntedDeal->last_crawled_at)
                                <div class="text-gray-600">{{ $huntedDeal->last_crawled_at->format('M j, Y') }}</div>
                                <div class="text-gray-500">{{ $huntedDeal->last_crawled_at->format('g:i A') }}</div>
                            @else
                                <div class="text-yellow-600">Never crawled</div>
                            @endif
                        </div>
                        <div>
                            <span class="font-medium text-gray-700">Status:</span>
                            <div class="text-gray-600">
                                @if($huntedDeal->is_active)
                                    <span class="text-green-600">Active</span>
                                @else
                                    <span class="text-gray-500">Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>