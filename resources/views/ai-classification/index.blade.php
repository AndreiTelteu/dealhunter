<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('AI Classification Testing') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Connection Status -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">AI Provider Status</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Provider</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $current_provider }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Model</label>
                            <p class="mt-1 text-sm text-gray-900">{{ $current_model }}</p>
                        </div>
                    </div>
                    
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <div class="mt-1 flex items-center">
                            @if($ai_enabled && $connection_test['success'])
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    ✅ Connected
                                </span>
                            @elseif($ai_enabled)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    ❌ Connection Failed
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    ⚠️ Disabled
                                </span>
                            @endif
                        </div>
                        @if(!$connection_test['success'])
                            <p class="mt-1 text-sm text-red-600">{{ $connection_test['error'] ?? 'Unknown error' }}</p>
                        @endif
                    </div>
                    
                    <button 
                        id="test-connection-btn"
                        class="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                    >
                        Test Connection
                    </button>
                </div>
            </div>

            <!-- Classification Testing -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <h3 class="text-lg font-medium mb-4">Test Classification</h3>
                    
                    <form id="classification-form" class="space-y-4">
                        <div>
                            <label for="search_term" class="block text-sm font-medium text-gray-700">Search Term</label>
                            <input 
                                type="text" 
                                id="search_term" 
                                name="search_term" 
                                value="laptop"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Listing Title</label>
                            <input 
                                type="text" 
                                id="title" 
                                name="title" 
                                value="Laptop Dell Latitude E7450"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea 
                                id="description" 
                                name="description" 
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Laptop functional, stare foarte buna, fara probleme..."
                            >Laptop functional, stare foarte buna, fara probleme. Procesor Intel i5, 8GB RAM, SSD 256GB. Testat si merge perfect.</textarea>
                        </div>
                        
                        <button 
                            type="submit"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <span id="classify-btn-text">Classify</span>
                            <svg id="classify-spinner" class="hidden animate-spin -mr-1 ml-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </button>
                    </form>
                    
                    <!-- Results -->
                    <div id="results" class="hidden mt-6">
                        <h4 class="text-md font-medium mb-3">Classification Results</h4>
                        
                        <!-- AI Results -->
                        <div class="bg-blue-50 p-4 rounded-lg mb-4">
                            <h5 class="font-medium text-blue-900 mb-2">AI Classification</h5>
                            <div id="ai-results" class="space-y-2 text-sm"></div>
                        </div>
                        
                        <!-- Keyword Results -->
                        <div class="bg-gray-50 p-4 rounded-lg mb-4">
                            <h5 class="font-medium text-gray-900 mb-2">Keyword-Based Classification</h5>
                            <div id="keyword-results" class="space-y-2 text-sm"></div>
                        </div>
                        
                        <!-- Comparison -->
                        <div class="bg-green-50 p-4 rounded-lg">
                            <h5 class="font-medium text-green-900 mb-2">Comparison</h5>
                            <div id="comparison-results" class="space-y-2 text-sm"></div>
                        </div>
                    </div>
                    
                    <!-- Error Display -->
                    <div id="error-display" class="hidden mt-6 bg-red-50 border border-red-200 rounded-md p-4">
                        <div class="flex">
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Error</h3>
                                <div id="error-message" class="mt-2 text-sm text-red-700"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Test connection
        document.getElementById('test-connection-btn').addEventListener('click', async function() {
            const btn = this;
            const originalText = btn.textContent;
            btn.textContent = 'Testing...';
            btn.disabled = true;
            
            try {
                const response = await fetch('/ai-classification/test-connection', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const result = await response.json();
                
                if (result.success) {
                    alert('✅ Connection successful!');
                    location.reload();
                } else {
                    alert('❌ Connection failed: ' + result.error);
                }
            } catch (error) {
                alert('❌ Request failed: ' + error.message);
            } finally {
                btn.textContent = originalText;
                btn.disabled = false;
            }
        });
        
        // Classification form
        document.getElementById('classification-form').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const form = this;
            const formData = new FormData(form);
            const btnText = document.getElementById('classify-btn-text');
            const spinner = document.getElementById('classify-spinner');
            const results = document.getElementById('results');
            const errorDisplay = document.getElementById('error-display');
            
            // Show loading state
            btnText.textContent = 'Classifying...';
            spinner.classList.remove('hidden');
            results.classList.add('hidden');
            errorDisplay.classList.add('hidden');
            
            try {
                const response = await fetch('/ai-classification/test', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    displayResults(result);
                    results.classList.remove('hidden');
                } else {
                    displayError(result.error);
                }
            } catch (error) {
                displayError('Request failed: ' + error.message);
            } finally {
                btnText.textContent = 'Classify';
                spinner.classList.add('hidden');
            }
        });
        
        function displayResults(result) {
            // AI Results
            const aiResults = document.getElementById('ai-results');
            aiResults.innerHTML = `
                <div><strong>Intent Match:</strong> ${result.ai_result.matches_intent ? '✅ Yes' : '❌ No'}</div>
                <div><strong>Working Condition:</strong> ${getWorkingConditionDisplay(result.ai_result.likely_working)}</div>
                <div><strong>Overall Confidence:</strong> ${(result.ai_result.confidence * 100).toFixed(1)}%</div>
                <div><strong>Intent Confidence:</strong> ${(result.ai_result.intent_confidence * 100).toFixed(1)}%</div>
                <div><strong>Working Confidence:</strong> ${(result.ai_result.working_confidence * 100).toFixed(1)}%</div>
                <div><strong>Reasoning:</strong> ${result.ai_result.reasoning}</div>
            `;
            
            // Keyword Results
            const keywordResults = document.getElementById('keyword-results');
            keywordResults.innerHTML = `
                <div><strong>Intent Match:</strong> ${result.keyword_result.matches_intent ? '✅ Yes' : '❌ No'}</div>
                <div><strong>Working Condition:</strong> ${getWorkingConditionDisplay(result.keyword_result.likely_working)}</div>
                <div><strong>Confidence:</strong> ${(result.keyword_result.confidence * 100).toFixed(1)}%</div>
                <div><strong>Reasoning:</strong> ${result.keyword_result.reasoning}</div>
            `;
            
            // Comparison
            const comparisonResults = document.getElementById('comparison-results');
            comparisonResults.innerHTML = `
                <div><strong>Intent Agreement:</strong> ${result.comparison.intent_match ? '✅ Match' : '❌ Differ'}</div>
                <div><strong>Working Condition Agreement:</strong> ${result.comparison.working_condition_match ? '✅ Match' : '❌ Differ'}</div>
            `;
        }
        
        function displayError(error) {
            const errorDisplay = document.getElementById('error-display');
            const errorMessage = document.getElementById('error-message');
            errorMessage.textContent = error;
            errorDisplay.classList.remove('hidden');
        }
        
        function getWorkingConditionDisplay(working) {
            if (working === true) return '✅ Working';
            if (working === false) return '❌ Broken';
            return '❓ Uncertain';
        }
    </script>
</x-app-layout>