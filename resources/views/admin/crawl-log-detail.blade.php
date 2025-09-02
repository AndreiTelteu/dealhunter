<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Crawl Log Details') }}
            </h2>
            <a href="{{ route('admin.crawl-logs') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                Back to Logs
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Basic Information -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Basic Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Crawl ID</label>
                                <div class="text-sm text-gray-900">{{ $crawlLog->id }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Type</label>
                                <div class="text-sm text-gray-900">{{ ucfirst(str_replace('_', ' ', $crawlLog->type)) }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Status</label>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($crawlLog->status === 'completed' && $crawlLog->total_errors === 0) bg-green-100 text-green-800
                                    @elseif($crawlLog->status === 'completed') bg-yellow-100 text-yellow-800
                                    @elseif($crawlLog->status === 'failed') bg-red-100 text-red-800
                                    @elseif($crawlLog->status === 'partial') bg-yellow-100 text-yellow-800
                                    @else bg-blue-100 text-blue-800
                                    @endif">
                                    {{ ucfirst($crawlLog->status) }}
                                </span>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Triggered By</label>
                                <div class="text-sm text-gray-900">
                                    {{ ucfirst($crawlLog->triggered_by) }}
                                    @if($crawlLog->user)
                                        ({{ $crawlLog->user->name }})
                                    @endif
                                </div>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Started At</label>
                                <div class="text-sm text-gray-900">{{ $crawlLog->started_at->format('M j, Y H:i:s') }}</div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Completed At</label>
                                <div class="text-sm text-gray-900">
                                    {{ $crawlLog->completed_at ? $crawlLog->completed_at->format('M j, Y H:i:s') : 'Not completed' }}
                                </div>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Duration</label>
                                <div class="text-sm text-gray-900">{{ $crawlLog->formatted_duration }}</div>
                            </div>
                            @if($crawlLog->notes)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Notes</label>
                                    <div class="text-sm text-gray-900">{{ $crawlLog->notes }}</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Statistics</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <div class="bg-blue-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-blue-600">{{ $crawlLog->hunted_deals_processed }}</div>
                            <div class="text-sm text-blue-700">Hunted Deals Processed</div>
                            @if($crawlLog->hunted_deals_failed > 0)
                                <div class="text-xs text-red-600 mt-1">{{ $crawlLog->hunted_deals_failed }} failed</div>
                            @endif
                        </div>
                        <div class="bg-green-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-green-600">{{ number_format($crawlLog->total_listings_found) }}</div>
                            <div class="text-sm text-green-700">Listings Found</div>
                            @if($crawlLog->listings_per_second)
                                <div class="text-xs text-gray-600 mt-1">{{ $crawlLog->listings_per_second }}/sec</div>
                            @endif
                        </div>
                        <div class="bg-purple-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-purple-600">{{ number_format($crawlLog->new_deals_created) }}</div>
                            <div class="text-sm text-purple-700">New Deals Created</div>
                            @if($crawlLog->deals_updated > 0)
                                <div class="text-xs text-gray-600 mt-1">{{ number_format($crawlLog->deals_updated) }} updated</div>
                            @endif
                        </div>
                        <div class="bg-yellow-50 p-4 rounded-lg text-center">
                            <div class="text-2xl font-bold text-yellow-600">{{ number_format($crawlLog->snapshots_created) }}</div>
                            <div class="text-sm text-yellow-700">Snapshots Created</div>
                        </div>
                    </div>
                    
                    @if($crawlLog->success_rate !== null)
                        <div class="mt-4 text-center">
                            <div class="text-lg font-semibold text-gray-700">Success Rate: {{ $crawlLog->success_rate }}%</div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Configuration -->
            @if($crawlLog->configuration)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Configuration at Time of Crawl</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($crawlLog->configuration as $key => $value)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">{{ ucfirst(str_replace('_', ' ', $key)) }}</label>
                                    <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                        @if(is_bool($value))
                                            {{ $value ? 'Enabled' : 'Disabled' }}
                                        @elseif(is_array($value))
                                            {{ json_encode($value) }}
                                        @else
                                            {{ $value ?: 'Not set' }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Errors -->
            @if($crawlLog->total_errors > 0 && $crawlLog->errors)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Errors ({{ $crawlLog->total_errors }})</h3>
                        <div class="space-y-2">
                            @foreach($crawlLog->errors as $index => $error)
                                <div class="p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <div class="text-sm text-red-800">
                                        <span class="font-medium">Error {{ $index + 1 }}:</span> {{ $error }}
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Performance Metrics -->
            @if($crawlLog->duration_ms && $crawlLog->total_listings_found > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Performance Metrics</h3>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="text-2xl font-bold text-gray-700">{{ $crawlLog->formatted_duration }}</div>
                                <div class="text-sm text-gray-600">Total Duration</div>
                            </div>
                            @if($crawlLog->listings_per_second)
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-700">{{ $crawlLog->listings_per_second }}</div>
                                    <div class="text-sm text-gray-600">Listings per Second</div>
                                </div>
                            @endif
                            @if($crawlLog->hunted_deals_processed > 0)
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-gray-700">
                                        {{ round($crawlLog->duration_ms / $crawlLog->hunted_deals_processed) }}ms
                                    </div>
                                    <div class="text-sm text-gray-600">Avg Time per Hunted Deal</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

        </div>
    </div>
</x-app-layout>