<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('System Health Monitoring') }}
            </h2>
            <div class="flex space-x-2">
                <form action="{{ route('admin.run-health-check') }}" method="POST" class="inline">
                    @csrf
                    <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded text-sm">
                        Run Health Check
                    </button>
                </form>
                <a href="{{ route('admin.dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                    Back to Dashboard
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Overall Health Status -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold">Overall System Health</h3>
                        <span class="px-4 py-2 text-sm font-medium rounded-full 
                            @if($overallHealth['overall_status'] === 'healthy') bg-green-100 text-green-800
                            @elseif($overallHealth['overall_status'] === 'warning') bg-yellow-100 text-yellow-800
                            @elseif($overallHealth['overall_status'] === 'critical') bg-red-100 text-red-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($overallHealth['overall_status']) }}
                        </span>
                    </div>
                    
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                        <div class="bg-green-50 p-3 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ $overallHealth['summary']['healthy'] }}</div>
                            <div class="text-sm text-green-700">Healthy</div>
                        </div>
                        <div class="bg-yellow-50 p-3 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600">{{ $overallHealth['summary']['warning'] }}</div>
                            <div class="text-sm text-yellow-700">Warning</div>
                        </div>
                        <div class="bg-red-50 p-3 rounded-lg">
                            <div class="text-2xl font-bold text-red-600">{{ $overallHealth['summary']['critical'] }}</div>
                            <div class="text-sm text-red-700">Critical</div>
                        </div>
                        <div class="bg-gray-50 p-3 rounded-lg">
                            <div class="text-2xl font-bold text-gray-600">{{ $overallHealth['summary']['unknown'] }}</div>
                            <div class="text-sm text-gray-700">Unknown</div>
                        </div>
                    </div>
                    
                    <p class="text-sm text-gray-600 mt-4">
                        Last check: {{ $overallHealth['last_check'] ? $overallHealth['last_check']->diffForHumans() : 'Never' }}
                    </p>
                </div>
            </div>

            <!-- Component Health Details -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                @foreach($healthResults as $component => $health)
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold capitalize">{{ $component }}</h3>
                                <span class="px-3 py-1 text-sm font-medium rounded-full 
                                    @if($health->status === 'healthy') bg-green-100 text-green-800
                                    @elseif($health->status === 'warning') bg-yellow-100 text-yellow-800
                                    @elseif($health->status === 'critical') bg-red-100 text-red-800
                                    @else bg-gray-100 text-gray-800
                                    @endif">
                                    {{ ucfirst($health->status) }}
                                </span>
                            </div>
                            
                            <p class="text-gray-700 mb-3">{{ $health->message }}</p>
                            
                            @if($health->response_time_ms)
                                <div class="text-sm text-gray-600 mb-2">
                                    Response Time: <span class="font-medium">{{ $health->response_time_ms }}ms</span>
                                </div>
                            @endif
                            
                            <div class="text-xs text-gray-500">
                                Checked: {{ $health->checked_at->diffForHumans() }}
                            </div>
                            
                            @if($health->details && count($health->details) > 0)
                                <div class="mt-4 p-3 bg-gray-50 rounded-lg">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Details</h4>
                                    <div class="text-xs text-gray-600 space-y-1">
                                        @foreach($health->details as $key => $value)
                                            <div class="flex justify-between">
                                                <span class="font-medium">{{ ucfirst(str_replace('_', ' ', $key)) }}:</span>
                                                <span>{{ is_array($value) ? json_encode($value) : $value }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Health History Charts -->
            @if(count($healthHistory) > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6 text-gray-900">
                        <h3 class="text-lg font-semibold mb-4">Health History (Last 24 Hours)</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @foreach($healthHistory as $component => $checks)
                                @if($checks->count() > 1)
                                    <div>
                                        <h4 class="font-medium text-gray-700 mb-2 capitalize">{{ $component }} Response Times</h4>
                                        <div class="h-32 bg-gray-50 rounded-lg flex items-end justify-center p-2">
                                            <div class="flex items-end space-x-1 h-full">
                                                @foreach($checks->take(20)->reverse() as $check)
                                                    @if($check->response_time_ms)
                                                        @php
                                                            $maxTime = $checks->max('response_time_ms');
                                                            $height = $maxTime > 0 ? ($check->response_time_ms / $maxTime) * 100 : 0;
                                                            $color = $check->status === 'healthy' ? 'bg-green-400' : 
                                                                    ($check->status === 'warning' ? 'bg-yellow-400' : 'bg-red-400');
                                                        @endphp
                                                        <div class="w-2 {{ $color }} rounded-t" 
                                                             style="height: {{ max($height, 5) }}%"
                                                             title="{{ $check->checked_at->format('H:i') }}: {{ $check->response_time_ms }}ms">
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-1 text-center">
                                            Latest: {{ $checks->first()->response_time_ms ?? 'N/A' }}ms
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- Log Cleanup -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Log Cleanup</h3>
                    <form action="{{ route('admin.cleanup-logs') }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="crawl_logs_days" class="block text-sm font-medium text-gray-700">Keep Crawl Logs (Days)</label>
                                <input type="number" name="crawl_logs_days" id="crawl_logs_days" value="30" min="1" max="365" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">Crawl logs older than this will be deleted</p>
                            </div>
                            <div>
                                <label for="health_logs_days" class="block text-sm font-medium text-gray-700">Keep Health Logs (Days)</label>
                                <input type="number" name="health_logs_days" id="health_logs_days" value="7" min="1" max="90" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <p class="text-xs text-gray-500 mt-1">Health check logs older than this will be deleted</p>
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded" 
                                    onclick="return confirm('Are you sure you want to delete old logs? This action cannot be undone.')">
                                Cleanup Old Logs
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>