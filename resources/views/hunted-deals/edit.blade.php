<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Edit Hunted Deal') }}
            </h2>
            <div class="flex space-x-3">
                <a href="{{ route('hunted-deals.show', $huntedDeal) }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    {{ __('View Details') }}
                </a>
                <a href="{{ route('hunted-deals.index') }}" class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    {{ __('Back to List') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6">
                    <form method="POST" action="{{ route('hunted-deals.update', $huntedDeal) }}" class="space-y-6">
                        @csrf
                        @method('PUT')
                        
                        <!-- Search Term -->
                        <div>
                            <label for="search_term" class="block text-sm font-medium text-gray-700 mb-2">
                                Search Term <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   name="search_term" 
                                   id="search_term"
                                   value="{{ old('search_term', $huntedDeal->search_term) }}"
                                   required
                                   placeholder="e.g., iPhone 13, laptop gaming, apartament 2 camere"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('search_term') border-red-300 @enderror">
                            @error('search_term')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                This is the search term that will be used to find deals on OLX Romania. Be specific for better results.
                            </p>
                        </div>

                        <!-- Active Status -->
                        <div>
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       name="is_active" 
                                       id="is_active"
                                       value="1"
                                       {{ old('is_active', $huntedDeal->is_active) ? 'checked' : '' }}
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <label for="is_active" class="ml-2 block text-sm text-gray-700">
                                    Active (enable automatic crawling)
                                </label>
                            </div>
                            @error('is_active')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                When active, this hunted deal will be included in automatic crawling operations.
                            </p>
                        </div>

                        <!-- Notes -->
                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 mb-2">
                                Notes
                            </label>
                            <textarea name="notes" 
                                      id="notes"
                                      rows="4"
                                      placeholder="Optional notes about what you're looking for, price range, specific requirements, etc."
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 @error('notes') border-red-300 @enderror">{{ old('notes', $huntedDeal->notes) }}</textarea>
                            @error('notes')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-sm text-gray-500">
                                Add any additional information about what you're looking for. This is for your reference only.
                            </p>
                        </div>

                        <!-- Form Actions -->
                        <div class="flex justify-between pt-6 border-t border-gray-200">
                            <div>
                                <button type="button" 
                                        onclick="confirmDelete()"
                                        class="inline-flex items-center px-4 py-2 border border-red-300 rounded-md text-sm font-medium text-red-700 bg-white hover:bg-red-50 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete Hunted Deal
                                </button>
                            </div>
                            
                            <div class="flex space-x-3">
                                <a href="{{ route('hunted-deals.show', $huntedDeal) }}" 
                                   class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                                    Cancel
                                </a>
                                <button type="submit" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                    </svg>
                                    Update Hunted Deal
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Metadata Section -->
            <div class="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Hunted Deal Information</h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-medium text-gray-700">Created:</span>
                        <span class="text-gray-600">{{ $huntedDeal->created_at->format('M j, Y \a\t g:i A') }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Last Updated:</span>
                        <span class="text-gray-600">{{ $huntedDeal->updated_at->format('M j, Y \a\t g:i A') }}</span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Last Crawled:</span>
                        <span class="text-gray-600">
                            @if($huntedDeal->last_crawled_at)
                                {{ $huntedDeal->last_crawled_at->format('M j, Y \a\t g:i A') }}
                            @else
                                Never
                            @endif
                        </span>
                    </div>
                    <div>
                        <span class="font-medium text-gray-700">Total Deals Found:</span>
                        <span class="text-gray-600">{{ $huntedDeal->deals()->count() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-medium text-gray-900 mt-4">Delete Hunted Deal</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Are you sure you want to delete "{{ $huntedDeal->search_term }}"? This will also delete all associated deals and snapshots. This action cannot be undone.
                    </p>
                </div>
                <div class="items-center px-4 py-3">
                    <form method="POST" action="{{ route('hunted-deals.destroy', $huntedDeal) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 bg-red-600 text-white text-base font-medium rounded-md w-24 mr-2 hover:bg-red-700 transition-colors">
                            Delete
                        </button>
                    </form>
                    <button onclick="closeDeleteModal()" class="px-4 py-2 bg-gray-300 text-gray-800 text-base font-medium rounded-md w-24 hover:bg-gray-400 transition-colors">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function confirmDelete() {
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>
</x-app-layout>