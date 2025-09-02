<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-start">
            <div class="min-w-0 flex-1">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight truncate">
                    {{ $deal->title }}
                </h2>
                <p class="mt-1 text-sm text-gray-600">
                    From hunted deal: <span class="font-medium">{{ $deal->huntedDeal->search_term }}</span>
                </p>
            </div>
            <div class="ml-4 flex space-x-2">
                <a 
                    href="{{ route('deals.index') }}"
                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                >
                    ← Back to Deals
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
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Current Information -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Current Information</h3>
                            
                            <!-- Status Indicators -->
                            <div class="flex flex-wrap gap-2 mb-4">
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
                                
                                @if($deal->confidence)
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ round($deal->confidence * 100) }}% Confidence
                                    </span>
                                @endif
                            </div>

                            <!-- Description -->
                            @if($deal->description)
                                <div class="mb-4">
                                    <h4 class="text-sm font-medium text-gray-700 mb-2">Description</h4>
                                    <p class="text-gray-600 whitespace-pre-line">{{ $deal->description }}</p>
                                </div>
                            @endif

                            <!-- Meta Information -->
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                                @if($deal->location)
                                    <div>
                                        <span class="font-medium text-gray-700">Location:</span>
                                        <span class="text-gray-600">{{ $deal->location }}</span>
                                    </div>
                                @endif
                                
                                @if($deal->seller_name)
                                    <div>
                                        <span class="font-medium text-gray-700">Seller:</span>
                                        <span class="text-gray-600">{{ $deal->seller_name }}</span>
                                    </div>
                                @endif
                                
                                @if($deal->posted_at)
                                    <div>
                                        <span class="font-medium text-gray-700">Posted:</span>
                                        <span class="text-gray-600">{{ $deal->posted_at->format('M j, Y g:i A') }}</span>
                                    </div>
                                @endif
                                
                                <div>
                                    <span class="font-medium text-gray-700">Last Seen:</span>
                                    <span class="text-gray-600">{{ $deal->last_seen_at->format('M j, Y g:i A') }}</span>
                                </div>
                                
                                <div>
                                    <span class="font-medium text-gray-700">First Found:</span>
                                    <span class="text-gray-600">{{ $deal->created_at->format('M j, Y g:i A') }}</span>
                                </div>
                                
                                <div>
                                    <span class="font-medium text-gray-700">Total Changes:</span>
                                    <span class="text-gray-600">{{ $deal->snapshots->count() }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Price History Chart -->
                    @if($deal->snapshots->where('price_amount', '!=', null)->count() > 1)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Price History</h3>
                                
                                @php
                                    $priceSnapshots = $deal->snapshots->where('price_amount', '!=', null)->sortBy('captured_at');
                                    $minPrice = $priceSnapshots->min('price_amount');
                                    $maxPrice = $priceSnapshots->max('price_amount');
                                    $priceRange = max($maxPrice - $minPrice, 1); // Avoid division by zero
                                    $chartWidth = 700;
                                    $chartHeight = 250;
                                    $padding = 50;
                                    $bottomPadding = 80;
                                    
                                    // Calculate price statistics
                                    $currentPrice = $priceSnapshots->last()->price_amount;
                                    $firstPrice = $priceSnapshots->first()->price_amount;
                                    $priceChange = $currentPrice - $firstPrice;
                                    $priceChangePercent = $firstPrice > 0 ? (($priceChange / $firstPrice) * 100) : 0;
                                    $lowestPrice = $minPrice;
                                    $highestPrice = $maxPrice;
                                @endphp
                                
                                <!-- Price Statistics -->
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6 p-4 bg-gray-50 rounded-lg">
                                    <div class="text-center">
                                        <div class="text-sm font-medium text-gray-500">Current</div>
                                        <div class="text-lg font-bold text-gray-900">{{ number_format($currentPrice, 0) }} {{ $deal->price_currency }}</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-sm font-medium text-gray-500">Change</div>
                                        <div class="text-lg font-bold {{ $priceChange >= 0 ? 'text-red-600' : 'text-green-600' }}">
                                            {{ $priceChange >= 0 ? '+' : '' }}{{ number_format($priceChange, 0) }} {{ $deal->price_currency }}
                                            <div class="text-xs">({{ $priceChange >= 0 ? '+' : '' }}{{ number_format($priceChangePercent, 1) }}%)</div>
                                        </div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-sm font-medium text-gray-500">Lowest</div>
                                        <div class="text-lg font-bold text-green-600">{{ number_format($lowestPrice, 0) }} {{ $deal->price_currency }}</div>
                                    </div>
                                    <div class="text-center">
                                        <div class="text-sm font-medium text-gray-500">Highest</div>
                                        <div class="text-lg font-bold text-red-600">{{ number_format($highestPrice, 0) }} {{ $deal->price_currency }}</div>
                                    </div>
                                </div>
                                
                                <!-- Enhanced SVG Chart -->
                                <div class="overflow-x-auto">
                                    <svg width="{{ $chartWidth }}" height="{{ $chartHeight + $padding + $bottomPadding }}" class="border rounded bg-white">
                                        <!-- Background -->
                                        <rect width="100%" height="100%" fill="#fafafa"/>
                                        
                                        <!-- Horizontal grid lines -->
                                        @for($i = 0; $i <= 5; $i++)
                                            @php
                                                $y = $padding + ($i * $chartHeight / 5);
                                                $price = $maxPrice - ($i * $priceRange / 5);
                                            @endphp
                                            <line x1="{{ $padding }}" y1="{{ $y }}" x2="{{ $chartWidth - $padding }}" y2="{{ $y }}" stroke="#e5e7eb" stroke-width="1" stroke-dasharray="2,2"/>
                                            <text x="{{ $padding - 10 }}" y="{{ $y + 4 }}" text-anchor="end" class="text-xs fill-gray-600" font-family="system-ui">
                                                {{ number_format($price, 0) }}
                                            </text>
                                        @endfor
                                        
                                        <!-- Vertical grid lines and date labels -->
                                        @foreach($priceSnapshots as $index => $snapshot)
                                            @if($index % max(1, floor($priceSnapshots->count() / 6)) === 0 || $index === $priceSnapshots->count() - 1)
                                                @php
                                                    $x = $padding + ($index * ($chartWidth - $padding * 2) / ($priceSnapshots->count() - 1));
                                                @endphp
                                                <line x1="{{ $x }}" y1="{{ $padding }}" x2="{{ $x }}" y2="{{ $padding + $chartHeight }}" stroke="#f3f4f6" stroke-width="1"/>
                                                <text x="{{ $x }}" y="{{ $padding + $chartHeight + 20 }}" text-anchor="middle" class="text-xs fill-gray-600" font-family="system-ui">
                                                    {{ $snapshot->captured_at->format('M j') }}
                                                </text>
                                                <text x="{{ $x }}" y="{{ $padding + $chartHeight + 35 }}" text-anchor="middle" class="text-xs fill-gray-500" font-family="system-ui">
                                                    {{ $snapshot->captured_at->format('Y') }}
                                                </text>
                                            @endif
                                        @endforeach
                                        
                                        <!-- Price area fill -->
                                        @php
                                            $areaPoints = [];
                                            foreach($priceSnapshots as $index => $snapshot) {
                                                $x = $padding + ($index * ($chartWidth - $padding * 2) / ($priceSnapshots->count() - 1));
                                                $y = $padding + (($maxPrice - $snapshot->price_amount) / $priceRange) * $chartHeight;
                                                $areaPoints[] = "$x,$y";
                                            }
                                            // Close the area
                                            $lastX = $padding + (($priceSnapshots->count() - 1) * ($chartWidth - $padding * 2) / ($priceSnapshots->count() - 1));
                                            $firstX = $padding;
                                            $bottomY = $padding + $chartHeight;
                                            $areaPath = 'M ' . implode(' L ', $areaPoints) . " L $lastX,$bottomY L $firstX,$bottomY Z";
                                        @endphp
                                        <path d="{{ $areaPath }}" fill="url(#priceGradient)" opacity="0.3"/>
                                        
                                        <!-- Gradient definition -->
                                        <defs>
                                            <linearGradient id="priceGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                                                <stop offset="0%" style="stop-color:#3b82f6;stop-opacity:0.8" />
                                                <stop offset="100%" style="stop-color:#3b82f6;stop-opacity:0.1" />
                                            </linearGradient>
                                        </defs>
                                        
                                        <!-- Price line -->
                                        @php
                                            $pathData = 'M ' . implode(' L ', $areaPoints);
                                        @endphp
                                        <path d="{{ $pathData }}" fill="none" stroke="#3b82f6" stroke-width="3"/>
                                        
                                        <!-- Data points with hover effects -->
                                        @foreach($priceSnapshots as $index => $snapshot)
                                            @php
                                                $x = $padding + ($index * ($chartWidth - $padding * 2) / ($priceSnapshots->count() - 1));
                                                $y = $padding + (($maxPrice - $snapshot->price_amount) / $priceRange) * $chartHeight;
                                                $isLowest = $snapshot->price_amount == $lowestPrice;
                                                $isHighest = $snapshot->price_amount == $highestPrice;
                                                $isFirst = $index === 0;
                                                $isLast = $index === $priceSnapshots->count() - 1;
                                            @endphp
                                            
                                            <!-- Highlight special points -->
                                            @if($isLowest || $isHighest)
                                                <circle cx="{{ $x }}" cy="{{ $y }}" r="8" fill="{{ $isLowest ? '#10b981' : '#ef4444' }}" opacity="0.2"/>
                                            @endif
                                            
                                            <circle cx="{{ $x }}" cy="{{ $y }}" r="5" fill="#ffffff" stroke="#3b82f6" stroke-width="2"/>
                                            <circle cx="{{ $x }}" cy="{{ $y }}" r="3" fill="{{ $isLowest ? '#10b981' : ($isHighest ? '#ef4444' : '#3b82f6') }}"/>
                                            
                                            <!-- Tooltip -->
                                            <title>{{ number_format($snapshot->price_amount, 0) }} {{ $snapshot->price_currency }} - {{ $snapshot->captured_at->format('M j, Y g:i A') }}</title>
                                        @endforeach
                                        
                                        <!-- Chart title -->
                                        <text x="{{ $chartWidth / 2 }}" y="25" text-anchor="middle" class="text-sm font-medium fill-gray-700" font-family="system-ui">
                                            Price History ({{ $priceSnapshots->count() }} data points)
                                        </text>
                                    </svg>
                                </div>
                                
                                <!-- Chart Legend -->
                                <div class="flex justify-center mt-4 space-x-6 text-sm">
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-blue-500 rounded-full mr-2"></div>
                                        <span class="text-gray-600">Price Points</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-green-500 rounded-full mr-2"></div>
                                        <span class="text-gray-600">Lowest Price</span>
                                    </div>
                                    <div class="flex items-center">
                                        <div class="w-3 h-3 bg-red-500 rounded-full mr-2"></div>
                                        <span class="text-gray-600">Highest Price</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Change Timeline -->
                    @if($deal->snapshots->count() > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    Change Timeline
                                    <span class="text-sm font-normal text-gray-500">({{ $deal->snapshots->count() }} snapshots)</span>
                                </h3>
                                
                                <div class="flow-root">
                                    <ul class="-mb-8">
                                        @foreach($deal->snapshots as $index => $snapshot)
                                            @php
                                                $previousSnapshot = $index < $deal->snapshots->count() - 1 ? $deal->snapshots[$index + 1] : null;
                                            @endphp
                                            <li>
                                                <div class="relative flex space-x-3">
                                                        <div>
                                                            <span class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center ring-8 ring-white">
                                                                <svg class="h-4 w-4 text-white" fill="currentColor" viewBox="0 0 20 20">
                                                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd" />
                                                                </svg>
                                                            </span>
                                                        </div>
                                                        
                                                        <div class="min-w-0 flex-1 pt-1.5">
                                                            <div class="text-sm text-gray-500">
                                                                <span class="font-medium text-gray-900">
                                                                    {{ $snapshot->captured_at->format('M j, Y g:i A') }}
                                                                </span>
                                                                @if($index === 0)
                                                                    <span class="text-green-600">(Latest)</span>
                                                                @endif
                                                            </div>
                                                            
                                                            <div class="mt-2 text-sm text-gray-700">
                                                                @if($snapshot->price_amount)
                                                                    <div class="mb-1">
                                                                        <span class="font-medium">Price:</span> 
                                                                        {{ number_format($snapshot->price_amount, 0) }} {{ $snapshot->price_currency }}
                                                                        
                                                                        @if($index < $deal->snapshots->count() - 1)
                                                                            @php
                                                                                $previousSnapshot = $deal->snapshots[$index + 1];
                                                                                if($previousSnapshot->price_amount && $snapshot->price_amount != $previousSnapshot->price_amount) {
                                                                                    $priceDiff = $snapshot->price_amount - $previousSnapshot->price_amount;
                                                                                    $isIncrease = $priceDiff > 0;
                                                                                }
                                                                            @endphp
                                                                            
                                                                            @if(isset($priceDiff))
                                                                                <span class="ml-2 text-xs {{ $isIncrease ? 'text-red-600' : 'text-green-600' }}">
                                                                                    {{ $isIncrease ? '+' : '' }}{{ number_format($priceDiff, 0) }} {{ $snapshot->price_currency }}
                                                                                </span>
                                                                            @endif
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                                
                                                                @if($snapshot->title !== $deal->title)
                                                                    <div class="mb-1">
                                                                        <span class="font-medium">Title:</span> {{ $snapshot->title }}
                                                                    </div>
                                                                @endif
                                                                
                                                                @if($snapshot->description && $snapshot->description !== $deal->description)
                                                                    <div class="mb-1">
                                                                        <span class="font-medium">Description changed</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                            </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Current Price -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Current Price</h3>
                            @if($deal->price_amount)
                                <div class="text-3xl font-bold text-gray-900">
                                    {{ number_format($deal->price_amount, 0) }} {{ $deal->price_currency }}
                                </div>
                                @if($deal->price_raw && $deal->price_raw !== $deal->price_amount . ' ' . $deal->price_currency)
                                    <div class="text-sm text-gray-500 mt-1">
                                        Raw: {{ $deal->price_raw }}
                                    </div>
                                @endif
                            @else
                                <div class="text-gray-500">Price not available</div>
                            @endif
                        </div>
                    </div>

                    <!-- Image Gallery -->
                    @if($deal->latestSnapshot && $deal->latestSnapshot->image_urls)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">Images</h3>
                                <div class="grid grid-cols-2 gap-2">
                                    @foreach(array_slice($deal->latestSnapshot->image_urls, 0, 6) as $imageUrl)
                                        <div class="aspect-square bg-gray-100 rounded-lg overflow-hidden">
                                            <img 
                                                src="{{ $imageUrl }}" 
                                                alt="Deal image"
                                                class="w-full h-full object-cover hover:scale-105 transition-transform cursor-pointer"
                                                onclick="window.open('{{ $imageUrl }}', '_blank')"
                                                onerror="this.parentElement.innerHTML='<div class=\'w-full h-full flex items-center justify-center text-gray-400 text-xs\'>Image not available</div>'"
                                            >
                                        </div>
                                    @endforeach
                                </div>
                                @if(count($deal->latestSnapshot->image_urls) > 6)
                                    <p class="text-sm text-gray-500 mt-2">
                                        +{{ count($deal->latestSnapshot->image_urls) - 6 }} more images
                                    </p>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- AI Classification -->
                    @if($deal->matches_intent !== null || $deal->likely_working !== null)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">AI Classification</h3>
                                
                                <div class="space-y-3">
                                    @if($deal->matches_intent !== null)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-700">Matches Intent</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $deal->matches_intent ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $deal->matches_intent ? 'Yes' : 'No' }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    @if($deal->likely_working !== null)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-700">Likely Working</span>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $deal->likely_working ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $deal->likely_working ? 'Yes' : 'No' }}
                                            </span>
                                        </div>
                                    @endif
                                    
                                    @if($deal->confidence)
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-700">Confidence</span>
                                            <span class="text-sm text-gray-600">{{ round($deal->confidence * 100) }}%</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Related Hunted Deal -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Related Hunted Deal</h3>
                            
                            <div class="space-y-2">
                                <div>
                                    <span class="text-sm font-medium text-gray-700">Search Term:</span>
                                    <span class="text-sm text-gray-600">{{ $deal->huntedDeal->search_term }}</span>
                                </div>
                                
                                <div>
                                    <span class="text-sm font-medium text-gray-700">Status:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium {{ $deal->huntedDeal->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                        {{ $deal->huntedDeal->is_active ? 'Active' : 'Inactive' }}
                                    </span>
                                </div>
                                
                                @if($deal->huntedDeal->last_crawled_at)
                                    <div>
                                        <span class="text-sm font-medium text-gray-700">Last Crawled:</span>
                                        <span class="text-sm text-gray-600">{{ $deal->huntedDeal->last_crawled_at->diffForHumans() }}</span>
                                    </div>
                                @endif
                            </div>
                            
                            <div class="mt-4">
                                <a 
                                    href="{{ route('hunted-deals.show', $deal->huntedDeal) }}"
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                >
                                    View Hunted Deal
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>