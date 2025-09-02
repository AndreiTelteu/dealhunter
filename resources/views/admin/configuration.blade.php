<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Configuration Management') }}
            </h2>
            <a href="{{ route('admin.dashboard') }}" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded text-sm">
                Back to Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            
            <!-- Configuration Notice -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Configuration Information</h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <p>Configuration values are read from environment variables and config files. To modify these settings, update your <code>.env</code> file or the corresponding config files in the <code>config/</code> directory.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Crawler Configuration -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Crawler Configuration</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Max Pages per Search</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                    {{ $config['crawler']['max_pages_per_search'] }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>CRAWLER_MAX_PAGES_PER_SEARCH</code></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Request Delay (ms)</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                    {{ $config['crawler']['request_delay_ms'] }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>CRAWLER_REQUEST_DELAY_MS</code></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Max Listings per Run</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                    {{ $config['crawler']['max_listings_per_run'] }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>CRAWLER_MAX_LISTINGS_PER_RUN</code></p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">MCP Playwright Endpoint</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm break-all">
                                    {{ $config['crawler']['mcp_playwright_endpoint'] ?: 'Not configured' }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>MCP_PLAYWRIGHT_ENDPOINT</code></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">User Agent</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm break-all">
                                    {{ $config['crawler']['user_agent'] ?: 'Default browser user agent' }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>CRAWLER_USER_AGENT</code></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Flags -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Feature Flags</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">AI Classification</label>
                                <div class="mt-1 p-2 rounded-md text-sm 
                                    @if($config['features']['ai_classification_enabled']) bg-green-50 text-green-800 @else bg-red-50 text-red-800 @endif">
                                    {{ $config['features']['ai_classification_enabled'] ? 'Enabled' : 'Disabled' }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>AI_CLASSIFICATION_ENABLED</code></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Detail Page Crawling</label>
                                <div class="mt-1 p-2 rounded-md text-sm 
                                    @if($config['features']['detail_page_crawling']) bg-green-50 text-green-800 @else bg-red-50 text-red-800 @endif">
                                    {{ $config['features']['detail_page_crawling'] ? 'Enabled' : 'Disabled' }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>DETAIL_PAGE_CRAWLING</code></p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Image URL Extraction</label>
                                <div class="mt-1 p-2 rounded-md text-sm 
                                    @if($config['features']['image_url_extraction']) bg-green-50 text-green-800 @else bg-red-50 text-red-800 @endif">
                                    {{ $config['features']['image_url_extraction'] ? 'Enabled' : 'Disabled' }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>IMAGE_URL_EXTRACTION</code></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Seller Info Extraction</label>
                                <div class="mt-1 p-2 rounded-md text-sm 
                                    @if($config['features']['seller_info_extraction']) bg-green-50 text-green-800 @else bg-red-50 text-red-800 @endif">
                                    {{ $config['features']['seller_info_extraction'] ? 'Enabled' : 'Disabled' }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>SELLER_INFO_EXTRACTION</code></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Configuration -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">AI Configuration</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">AI Provider</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                    {{ $config['ai']['provider'] ?: 'Not configured' }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>AI_PROVIDER</code></p>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">AI Model</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                    {{ $config['ai']['model'] ?: 'Not configured' }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>AI_MODEL</code></p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Confidence Threshold</label>
                                <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                    {{ $config['ai']['confidence_threshold'] }}
                                </div>
                                <p class="text-xs text-gray-500 mt-1">Environment: <code>AI_CONFIDENCE_THRESHOLD</code></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Currency Configuration -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Currency Configuration</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Default Currency</label>
                            <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                {{ $config['currency']['default_currency'] }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Environment: <code>DEFAULT_CURRENCY</code></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">EUR to RON Rate</label>
                            <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                {{ $config['currency']['eur_to_ron_rate'] }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Environment: <code>EUR_TO_RON_RATE</code></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">USD to RON Rate</label>
                            <div class="mt-1 p-2 bg-gray-50 rounded-md text-sm">
                                {{ $config['currency']['usd_to_ron_rate'] }}
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Environment: <code>USD_TO_RON_RATE</code></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Configuration Files Reference -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-semibold mb-4">Configuration Files Reference</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Config Files</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li><code>config/crawler.php</code> - Crawler settings</li>
                                <li><code>config/features.php</code> - Feature flags</li>
                                <li><code>config/ai.php</code> - AI classification settings</li>
                                <li><code>config/currency.php</code> - Currency conversion rates</li>
                            </ul>
                        </div>
                        <div>
                            <h4 class="font-medium text-gray-700 mb-2">Environment File</h4>
                            <ul class="text-sm text-gray-600 space-y-1">
                                <li><code>.env</code> - Main environment configuration</li>
                                <li><code>.env.example</code> - Example configuration template</li>
                            </ul>
                        </div>
                    </div>
                    
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800">Important Note</h3>
                                <div class="mt-2 text-sm text-yellow-700">
                                    <p>After modifying configuration values, you may need to restart the application and clear the config cache using <code>php artisan config:clear</code> for changes to take effect.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>