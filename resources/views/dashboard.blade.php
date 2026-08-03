<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Dashboard') }}
            </h2>
            <div class="flex space-x-3" x-data="{ refreshing: false }">
                <button 
                    @click="toggleAutoRefresh()"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center space-x-2"
                    :class="{ 'bg-green-100 text-green-700': autoRefresh }"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span x-text="autoRefresh ? 'Auto-refresh ON' : 'Auto-refresh OFF'"></span>
                </button>
                <button 
                    @click="refreshing = true; setTimeout(() => refreshing = false, 1000); window.location.reload()"
                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors flex items-center space-x-2"
                    :class="{ 'opacity-50 cursor-not-allowed': refreshing }"
                    :disabled="refreshing"
                >
                    <svg class="w-4 h-4" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span x-text="refreshing ? 'Refreshing...' : 'Refresh'"></span>
                </button>
                <a href="{{ route('hunted-deals.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    {{ __('Add Hunted Deal') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12" x-data="{ 
        autoRefresh: false,
        refreshInterval: null,
        toggleAutoRefresh() {
            this.autoRefresh = !this.autoRefresh;
            if (this.autoRefresh) {
                this.refreshInterval = setInterval(() => {
                    window.location.reload();
                }, 300000); // 5 minutes
            } else {
                clearInterval(this.refreshInterval);
            }
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <!-- Welcome Message -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-2">Welcome back, {{ Auth::user()->name }}!</h3>
                    <p class="text-gray-600">Track your favorite OLX searches and never miss a great deal.</p>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                <x-stat-card 
                    title="Hunted Deals" 
                    :value="$huntedDealsCount" 
                    color="blue"
                    :href="route('hunted-deals.index')"
                    :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z\'></path></svg>'" 
                />
                
                <x-stat-card 
                    title="Active" 
                    :value="$activeHuntedDealsCount" 
                    color="green"
                    :href="route('hunted-deals.index') . '?filter=active'"
                    :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z\'></path></svg>'" 
                />
                
                <x-stat-card 
                    title="Total Deals" 
                    :value="$totalDealsCount" 
                    color="purple"
                    :href="route('deals.index')"
                    :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z\'></path></svg>'" 
                />
                
                <x-stat-card 
                    title="New (24h)" 
                    :value="$newDealsCount" 
                    color="yellow"
                    :href="route('deals.index') . '?new_items=1'"
                    :icon="'<svg class=\'w-5 h-5\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z\'></path></svg>'" 
                />
            </div>

            <!-- Hunted Deals Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Your Hunted Deals</h3>
                        <a href="{{ route('hunted-deals.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All →
                        </a>
                    </div>
                    
                    @if($huntedDeals->count() > 0)
                        <div class="space-y-4">
                            @foreach($huntedDeals as $huntedDeal)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start space-y-3 sm:space-y-0">
                                        <div class="flex-1">
                                            <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-3">
                                                <h4 class="font-medium text-gray-900">{{ $huntedDeal->search_term }}</h4>
                                                @if($huntedDeal->is_active)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 w-fit">
                                                        Active
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 w-fit">
                                                        Inactive
                                                    </span>
                                                @endif
                                            </div>
                                            @if($huntedDeal->notes)
                                                <p class="text-sm text-gray-600 mt-1">{{ Str::limit($huntedDeal->notes, 100) }}</p>
                                            @endif
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 mt-2 text-sm text-gray-500 space-y-1 sm:space-y-0">
                                                <span>{{ $huntedDeal->deals_count ?? 0 }} deals found</span>
                                                @if($huntedDeal->last_crawled_at)
                                                    <span>Last crawled {{ $huntedDeal->last_crawled_at->diffForHumans() }}</span>
                                                @else
                                                    <span>Never crawled</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="flex space-x-2 sm:ml-4">
                                            <a href="{{ route('hunted-deals.show', $huntedDeal) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                                View
                                            </a>
                                            <a href="{{ route('hunted-deals.edit', $huntedDeal) }}" class="text-gray-600 hover:text-gray-800 text-sm">
                                                Edit
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No hunted deals</h3>
                            <p class="mt-1 text-sm text-gray-500">Get started by creating your first hunted deal.</p>
                            <div class="mt-6">
                                <a href="{{ route('hunted-deals.create') }}" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    Add Hunted Deal
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Recent Activity</h3>
                        <a href="{{ route('deals.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            View All Deals →
                        </a>
                    </div>
                    
                    @if($recentDeals->count() > 0)
                        <div class="space-y-4">
                            @foreach($recentDeals as $deal)
                                <div class="border border-gray-200 rounded-lg p-4 hover:bg-gray-50 transition-colors">
                                    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-start space-y-3 sm:space-y-0">
                                        <div class="flex-1">
                                            <div class="flex flex-col sm:flex-row sm:items-center space-y-2 sm:space-y-0 sm:space-x-2">
                                                <h4 class="font-medium text-gray-900">{{ Str::limit($deal->title, 60) }}</h4>
                                                <div class="flex space-x-2">
                                                    @if($deal->matches_intent)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 w-fit">
                                                            Match
                                                        </span>
                                                    @endif
                                                    @if($deal->likely_working)
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 w-fit">
                                                            Working
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-4 mt-1 text-sm text-gray-500 space-y-1 sm:space-y-0">
                                                @if($deal->price_amount)
                                                    <span class="font-medium text-gray-900">{{ number_format($deal->price_amount, 0) }} {{ $deal->price_currency }}</span>
                                                @endif
                                                @if($deal->location)
                                                    <span>{{ $deal->location }}</span>
                                                @endif
                                                <span>{{ $deal->created_at->diffForHumans() }}</span>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1">Search: {{ $deal->huntedDeal->search_term }}</p>
                                        </div>
                                        <div class="flex space-x-2 sm:ml-4">
                                            <a href="{{ route('deals.show', $deal) }}" class="text-blue-600 hover:text-blue-800 text-sm">
                                                View
                                            </a>
                                            <a href="{{ $deal->url }}" target="_blank" class="text-gray-600 hover:text-gray-800 text-sm">
                                                OLX ↗
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900">No deals found yet</h3>
                            <p class="mt-1 text-sm text-gray-500">Create some hunted deals and wait for the crawler to find listings.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
