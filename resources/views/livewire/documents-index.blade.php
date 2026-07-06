<div class="bg-white">
    <!-- Header Section -->
    <div class="bg-gradient-to-r from-green-600 to-emerald-700 py-12">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
            <div class="text-center">
                <h1 class="text-3xl font-bold text-white mb-4">Project Documents</h1>
                <p class="text-green-100 text-lg">All documents and resources for the project</p>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="bg-white py-8 border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold">{{ $documents->count() }}</div>
                            <div class="text-green-100 text-sm">Total Documents</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-pdf text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold">{{ $documents->where('type', 'pdf')->count() }}</div>
                            <div class="text-blue-100 text-sm">PDF Files</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-purple-500 to-purple-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-word text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold">{{ $documents->where('type', 'word')->count() }}</div>
                            <div class="text-purple-100 text-sm">Word Files</div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-xl p-6 text-white shadow-lg">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-calendar-alt text-2xl"></i>
                        </div>
                        <div class="ml-4">
                            <div class="text-2xl font-bold">{{ $documents->where('created_at', '>=', now()->subDays(30))->count() }}</div>
                            <div class="text-orange-100 text-sm">This Month</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Documents Section -->
    <div class="py-12">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
            <div class="mb-8 rounded-2xl border border-slate-200/80 bg-white/90 p-3 shadow-sm">
                <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                    <i class="fas fa-magnifying-glass text-[10px]"></i>
                    Search documents
                </div>
                <label class="block">
                    <span class="mb-1 block text-xs font-medium text-slate-600">Title</span>
                    <input type="search"
                        wire:model.live.debounce.200ms="search"
                        placeholder="Filter by document title…"
                        class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-green-500 focus:ring-green-500">
                </label>
                @if($search !== '')
                    <p class="mt-2 text-xs text-slate-500">
                        Showing documents whose title matches your search.
                    </p>
                @endif
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div class="flex items-center space-x-4">
                    <button wire:click="showUploadModal" 
                            class="bg-gradient-to-r from-green-600 to-green-700 text-white font-medium py-3 px-6 rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-upload mr-2"></i>
                        Upload Document
                    </button>
                    @include('livewire.partials.export-buttons')
                    <button wire:click="deleteSelected"
                            wire:confirm="Are you sure you want to delete the selected documents?"
                            class="bg-gradient-to-r from-red-600 to-red-700 text-white font-medium py-3 px-6 rounded-lg hover:from-red-700 hover:to-red-800 transition-all duration-200 transform hover:scale-105 shadow-lg">
                        <i class="fas fa-trash mr-2"></i>
                        Delete Selected
                    </button>
                    <span class="text-sm text-gray-600">{{ count(array_filter($selectedDocuments ?? [])) }} selected</span>
                </div>

                <div class="flex items-center space-x-4">
                    <select wire:model.live="filterType" 
                            class="border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 focus:border-transparent">
                        <option value="">All Types</option>
                        <option value="pdf">PDF</option>
                        <option value="word">Word</option>
                        <option value="excel">Excel</option>
                        <option value="other">Other</option>
                    </select>
                </div>
            </div>

            @if($documents->isEmpty())
                <!-- Empty State -->
                <div class="text-center py-16">
                    <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i class="fas fa-file-alt text-3xl text-gray-400"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">No Documents Yet</h3>
                    <p class="text-gray-600 max-w-md mx-auto mb-6">
                        Upload your first document to get started. Supported formats include PDF, Word, Excel, and more.
                    </p>
                    <button wire:click="showUploadModal" 
                            class="bg-gradient-to-r from-green-600 to-green-700 text-white font-medium py-3 px-6 rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 transform hover:scale-105">
                        <i class="fas fa-upload mr-2"></i>
                        Upload First Document
                    </button>
                </div>
            @else
                <!-- Documents Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($documents as $document)
                        <div class="bg-white border border-gray-200 rounded-xl shadow-sm hover:shadow-lg transition-all duration-200 transform hover:scale-[1.02]">
                            <!-- Document Header -->
                            <div class="p-6 border-b border-gray-100">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center flex-1">
                                        <div class="flex-shrink-0">
                                            @if($document->type === 'pdf')
                                                <div class="w-12 h-12 bg-red-100 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-file-pdf text-red-600 text-xl"></i>
                                                </div>
                                            @elseif($document->type === 'word')
                                                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-file-word text-blue-600 text-xl"></i>
                                                </div>
                                            @elseif($document->type === 'excel')
                                                <div class="w-12 h-12 bg-green-100 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-file-excel text-green-600 text-xl"></i>
                                                </div>
                                            @else
                                                <div class="w-12 h-12 bg-gray-100 rounded-lg flex items-center justify-center">
                                                    <i class="fas fa-file text-gray-600 text-xl"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="ml-4 flex-1 min-w-0">
                                            <h3 class="text-lg font-semibold text-gray-900 truncate">{{ $document->title }}</h3>
                                            <p class="text-sm text-gray-500">{{ $document->type ?? 'Unknown' }} • {{ $document->size_formatted }}</p>
                                        </div>
                                    </div>
                                    <div class="flex-shrink-0 ml-2">
                                        <div class="relative flex items-center gap-2" x-data="{ open: false }">
                                            <input type="checkbox" wire:model.live="selectedDocuments.{{ $document->id }}"
                                                   class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500"
                                                   title="Select document">
                                            <button @click="open = !open" 
                                                    class="text-gray-400 hover:text-gray-600 p-1 rounded-full hover:bg-gray-100">
                                                <i class="fas fa-ellipsis-v"></i>
                                            </button>
                                            <div x-show="open" 
                                                 @click.away="open = false"
                                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border border-gray-200 z-10">
                                                <div class="py-1">
                                                    <a href="{{ Storage::url($document->file_path) }}" 
                                                       download
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-download mr-2"></i>
                                                        Download
                                                    </a>
                                                    <a href="{{ Storage::url($document->file_path) }}" 
                                                       target="_blank"
                                                       class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                        <i class="fas fa-external-link-alt mr-2"></i>
                                                        Open
                                                    </a>
                                                    <button wire:click="deleteDocument({{ $document->id }})"
                                                            wire:confirm="Are you sure you want to delete this document?"
                                                            class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                        <i class="fas fa-trash mr-2"></i>
                                                        Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Document Details -->
                            <div class="p-6 space-y-4">
                                @if($document->description)
                                    <div>
                                        <p class="text-sm text-gray-500 mb-1">Description</p>
                                        <p class="text-sm text-gray-900 line-clamp-2">{{ $document->description }}</p>
                                    </div>
                                @endif

                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-user text-blue-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-500">Uploaded by</p>
                                        <p class="text-sm font-medium text-gray-900 truncate">
                                            {{ $document->uploaded_by ? $document->uploaded_by->first_name . ' ' . $document->uploaded_by->last_name : 'Unknown' }}
                                        </p>
                                    </div>
                                </div>

                                <div class="flex items-center">
                                    <div class="flex-shrink-0 w-8 h-8 bg-green-100 rounded-full flex items-center justify-center mr-3">
                                        <i class="fas fa-calendar text-green-600 text-sm"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm text-gray-500">Uploaded</p>
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ \Carbon\Carbon::parse($document->created_at)->format('M d, Y') }}
                                        </p>
                                    </div>
                                </div>

                                @if($document->tags)
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-tags text-purple-600 text-sm"></i>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-sm text-gray-500">Tags</p>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach(explode(',', $document->tags) as $tag)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                        {{ trim($tag) }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Action Buttons -->
                            <div class="px-6 pb-6">
                                <div class="flex space-x-3">
                                    <a href="{{ Storage::url($document->file_path) }}" 
                                       download
                                       class="flex-1 bg-gradient-to-r from-green-600 to-green-700 text-white font-medium py-3 px-4 rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200 text-center transform hover:scale-[1.02]">
                                        <i class="fas fa-download mr-2"></i>
                                        Download
                                    </a>
                                    <a href="{{ Storage::url($document->file_path) }}" 
                                       target="_blank"
                                       class="flex-1 bg-gradient-to-r from-blue-600 to-blue-700 text-white font-medium py-3 px-4 rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all duration-200 text-center transform hover:scale-[1.02]">
                                        <i class="fas fa-external-link-alt mr-2"></i>
                                        Open
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Upload Modal -->
    @if($showUploadModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" 
             x-data="{ open: @entangle('showUploadModal') }"
             x-show="open"
             @click.away="open = false">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-xl bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-900">Upload Document</h3>
                        <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <form wire:submit.prevent="uploadDocument">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Document Title</label>
                                <input type="text" 
                                       wire:model="uploadTitle" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Enter document title">
                                @error('uploadTitle') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description (Optional)</label>
                                <textarea wire:model="uploadDescription" 
                                          rows="3"
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                          placeholder="Enter document description"></textarea>
                                @error('uploadDescription') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Tags (Optional)</label>
                                <input type="text" 
                                       wire:model="uploadTags" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                       placeholder="Enter tags separated by commas">
                                @error('uploadTags') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">File</label>
                                <input type="file" 
                                       wire:model="uploadFile" 
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                @error('uploadFile') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>
                            
                            <div class="flex justify-end space-x-3 pt-4">
                                <button type="button" 
                                        @click="open = false"
                                        class="px-4 py-2 text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all duration-200">
                                    <i class="fas fa-upload mr-2"></i>
                                    Upload
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    <!-- Flash Messages -->
    <div x-data="{ show: true }" 
         x-show="show" 
         x-init="setTimeout(() => show = false, 5000)"
         class="fixed bottom-4 right-4 z-50">
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
        @endif
    </div>
</div> 