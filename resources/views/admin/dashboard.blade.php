<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Admin Dashboard') }}
            </h2>
            <div class="flex space-x-2">
                <a href="{{ route('admin.system-health') }}" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                    System Health
                </a>
                <a href="{{ route('admin.crawl-logs') }}" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded text-sm">
                    Crawl Logs
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- System Health Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">System Health Overview</h3>
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        @foreach($systemHealth['components'] as $component => $health)
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <h4 class="font-medium capitalize">{{ $component }}</h4>
                                    @if($health)
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            @if($health->status === 'healthy') bg-green-100 text-green-800
                                            @elseif($health->status === 'warning') bg-yellow-100 text-yellow-800
                                            @elseif($health->status === 'critical') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800
                                            @endif">
                                            {{ ucfirst($health->status) }}
                                        </span>
                                    @else
                                        <span class="px-2 py-1 text-xs rounded-full bg-gray-100 text-gray-800">
                                            Unknown
                                        </span>
                                    @endif
                                </div>
                                @if($health)
                                    <p class="text-sm text-gray-600 mt-2">{{ $health->message }}</p>
                                    @if($health->response_time_ms)
                                        <p class="text-xs text-gray-500 mt-1">{{ $health->response_time_ms }}ms</p>
                                    @endif
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-4 flex justify-between items-center">
                        <p class="text-sm text-gray-600">
                            Last check: {{ $systemHealth['last_check'] ? $systemHealth['last_check']->diffForHumans() : 'Never' }}
                        </p>
                        <form action="{{ route('admin.run-health-check') }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-1 px-3 rounded text-sm">
                                Run Health Check
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-500">Crawls (24h)</h3>
                                <p class="text-2xl font-bold">{{ $crawlStats['total_crawls'] }}</p>
                                <p class="text-sm text-green-600">{{ $crawlStats['successful_crawls'] }} successful</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-500">Listings Found</h3>
                                <p class="text-2xl font-bold">{{ number_format($crawlStats['total_listings_found']) }}</p>
                                <p class="text-sm text-blue-600">{{ number_format($crawlStats['total_deals_created']) }} new deals</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-500">Active Hunted Deals</h3>
                                <p class="text-2xl font-bold">{{ $activeHuntedDeals }}</p>
                                <p class="text-sm text-gray-600">{{ number_format($totalDeals) }} total deals</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <div class="flex items-center">
                            <div class="flex-1">
                                <h3 class="text-sm font-medium text-gray-500">Success Rate</h3>
                                <p class="text-2xl font-bold">{{ number_format($crawlStats['average_success_rate'] ?? 0, 1) }}%</p>
                                <p class="text-sm text-gray-600">Last 24 hours</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Manual Crawl Trigger -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Manual Crawl Trigger</h3>
                    <form action="{{ route('admin.trigger-crawl') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="hunted_deal_id" class="block text-sm font-medium text-gray-700">Specific Hunted Deal (Optional)</label>
                                <select name="hunted_deal_id" id="hunted_deal_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <option value="">All active hunted deals</option>
                                    @foreach(\App\Models\HuntedDeal::where('is_active', true)->with('user')->get() as $huntedDeal)
                                        <option value="{{ $huntedDeal->id }}">
                                            {{ $huntedDeal->search_term }} ({{ $huntedDeal->user->name ?? 'Unknown' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="notes" class="block text-sm font-medium text-gray-700">Notes (Optional)</label>
                                <input type="text" name="notes" id="notes" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Reason for manual crawl">
                            </div>
                            <div class="flex items-end space-x-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="dry_run" value="1" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                    <span class="ml-2 text-sm text-gray-600">Dry Run</span>
                                </label>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
                                Trigger Crawl
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Recent Crawl Logs -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Recent Crawl Logs</h3>
                        <a href="{{ route('admin.crawl-logs') }}" class="text-blue-600 hover:text-blue-800 text-sm">View All</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Started</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Duration</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Results</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Triggered By</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($recentCrawls as $crawl)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $crawl->started_at->format('M j, H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ ucfirst(str_replace('_', ' ', $crawl->type)) }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                @if($crawl->status === 'completed' && $crawl->total_errors === 0) bg-green-100 text-green-800
                                                @elseif($crawl->status === 'completed') bg-yellow-100 text-yellow-800
                                                @elseif($crawl->status === 'failed') bg-red-100 text-red-800
                                                @else bg-blue-100 text-blue-800
                                                @endif">
                                                {{ ucfirst($crawl->status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $crawl->formatted_duration }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $crawl->total_listings_found }} found, {{ $crawl->new_deals_created }} new
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ ucfirst($crawl->triggered_by) }}
                                            @if($crawl->user)
                                                ({{ $crawl->user->name }})
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 text-center">
                                            No recent crawl logs found
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Configuration Overview -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-lg font-semibold">Current Configuration</h3>
                        <a href="{{ route('admin.configuration') }}" class="text-blue-600 hover:text-blue-800 text-sm">View Details</a>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Crawler Settings</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>Max pages per search: {{ $crawlerConfig['max_pages_per_search'] }}</li>
                                <li>Request delay: {{ $crawlerConfig['request_delay_ms'] }}ms</li>
                                <li>Max listings per run: {{ $crawlerConfig['max_listings_per_run'] }}</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Features</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li>AI Classification: 
                                    <span class="@if($crawlerConfig['ai_classification_enabled']) text-green-600 @else text-red-600 @endif">
                                        {{ $crawlerConfig['ai_classification_enabled'] ? 'Enabled' : 'Disabled' }}
                                    </span>
                                </li>
                                <li>MCP Endpoint: {{ $crawlerConfig['mcp_endpoint'] ? 'Configured' : 'Not configured' }}</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>