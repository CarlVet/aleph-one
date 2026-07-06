<div
    class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8"
    x-data="{
        editing: {
            title: false,
            doi: false,
            publication_year: false,
            study_design: false,
            risk_bias: false,
            abstract: false,
            sampling_strategy: false,
        },
        showAbstract: false,
        showSampling: false,
        showProtocols: true,
        showLiterature: true,
    }"
    x-on:start-edit.window="editing[$event.detail.field] = true"
    x-on:save-edit.window="editing[$event.detail.field] = false"
    x-on:cancel-edit.window="editing[$event.detail.field] = false"
    x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success!', text: $event.detail.message, timer: 2000, showConfirmButton: false }); }"
    x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error!', text: $event.detail.message, confirmButtonColor: '#d33' }); }"
    wire:ignore.self
>
    <div class="px-4 py-6 sm:px-0">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-purple-900 to-purple-800 rounded-t-xl shadow-lg">
            <div class="px-6 py-8">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="bg-white/20 p-3 rounded-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"></path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">Study Details</h1>
                                <p class="text-purple-100 text-lg">Reference Key: {{ $study->ref_key }}</p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center space-x-4">
                            <span class="text-purple-100 text-sm">
                                Published in {{ $study->publication_year ?? 'N/A' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <a href="/meta/list/animal"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to List
                        </a>
                        <button wire:click="exportStudy"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Export
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white shadow-lg rounded-b-xl">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 p-8">

                <!-- Left Column - Study Information (1/3 width) -->
                <div class="space-y-6">

                    <!-- Study Information Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Study Information</h2>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Title</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="study_title_{{ $study->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('title')" @endif
                                              x-show="!editing.title">
                                            {{ $study->title ?? 'N/A' }}
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.title" class="flex items-start gap-2">
                                                <textarea wire:model="editingValues.title"
                                                          class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                                                          rows="2"
                                                          x-ref="title_input"
                                                          x-init="$nextTick(() => $refs.title_input?.focus())"></textarea>
                                                <button wire:click="saveEdit('title')"
                                                        class="mt-1 w-8 h-8 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('title')"
                                                        class="mt-1 w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">DOI</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="study_doi_{{ $study->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('doi')" @endif
                                              x-show="!editing.doi">
                                            @if($study->doi)
                                                <a href="https://doi.org/{{ $study->doi }}" target="_blank"
                                                   class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $study->doi }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.doi" class="inline-flex items-center space-x-2">
                                                <input type="text"
                                                       wire:model="editingValues.doi"
                                                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent w-72"
                                                       placeholder="10.xxxx/xxxxx"
                                                       x-ref="doi_input"
                                                       x-init="$nextTick(() => $refs.doi_input?.focus())">
                                                <button wire:click="saveEdit('doi')"
                                                        class="w-8 h-8 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('doi')"
                                                        class="w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Publication Year</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="study_publication_year_{{ $study->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('publication_year')" @endif
                                              x-show="!editing.publication_year">
                                            {{ $study->publication_year ?? 'N/A' }}
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.publication_year" class="inline-flex items-center space-x-2">
                                                <input type="number"
                                                       wire:model="editingValues.publication_year"
                                                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent w-28"
                                                       min="1500" max="2200"
                                                       x-ref="publication_year_input"
                                                       x-init="$nextTick(() => $refs.publication_year_input?.focus())">
                                                <button wire:click="saveEdit('publication_year')"
                                                        class="w-8 h-8 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('publication_year')"
                                                        class="w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Study Design</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="study_design_{{ $study->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('study_design')" @endif
                                              x-show="!editing.study_design">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $study->study_design ?? 'N/A' }}
                                            </span>
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.study_design" class="inline-flex items-center space-x-2">
                                                <input type="text"
                                                       wire:model="editingValues.study_design"
                                                       class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent w-64"
                                                       x-ref="study_design_input"
                                                       x-init="$nextTick(() => $refs.study_design_input?.focus())">
                                                <button wire:click="saveEdit('study_design')"
                                                        class="w-8 h-8 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('study_design')"
                                                        class="w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Risk of Bias</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="risk_bias_{{ $study->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('risk_bias')" @endif
                                              x-show="!editing.risk_bias">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $study->risk_bias === 'Low' ? 'bg-green-100 text-green-800' : 
                                                   ($study->risk_bias === 'Medium' ? 'bg-yellow-100 text-yellow-800' : 
                                                   ($study->risk_bias === 'High' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                                {{ $study->risk_bias ?? 'N/A' }}
                                            </span>
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.risk_bias" class="inline-flex items-center space-x-2">
                                                <select wire:model="editingValues.risk_bias"
                                                        class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white"
                                                        x-ref="risk_bias_input"
                                                        x-init="$nextTick(() => $refs.risk_bias_input?.focus())">
                                                    <option value="">N/A</option>
                                                    <option value="Low">Low</option>
                                                    <option value="Medium">Medium</option>
                                                    <option value="High">High</option>
                                                </select>
                                                <button wire:click="saveEdit('risk_bias')"
                                                        class="w-8 h-8 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('risk_bias')"
                                                        class="w-8 h-8 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column - PDF Document Section (2/3 width) -->
                <div class="lg:col-span-2">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="bg-red-100 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-xl font-semibold text-gray-900 leading-tight">Manuscript</h2>
                                    <div class="mt-1 text-xs text-gray-500 leading-snug">
                                        Max file size: 50MB • Formats: PDF, DOC, DOCX
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Upload Button -->
                            @if(!$document && $canManageStudy)
                                <label
                                    for="document-upload"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200 cursor-pointer"
                                >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        Select document
                                    <input type="file" 
                                        id="document-upload" 
                                        class="hidden" 
                                        accept=".pdf,.doc,.docx"
                                        wire:model="document"
                                        wire:loading.attr="disabled"
                                        x-data
                                        x-on:document-uploaded.window="$el.value = ''"
                                        x-on:document-cancelled.window="$el.value = ''">
                                </label>
                            @elseif($document && $canManageStudy)
                                <!-- Action Buttons when document is selected -->
                                <div class="flex flex-wrap gap-2">
                                    <button wire:click="uploadDocument" 
                                        class="inline-flex items-center gap-2 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Upload
                                    </button>
                                    <button wire:click="cancelDocumentSelection" 
                                        class="inline-flex items-center gap-2 px-3 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Cancel
                                    </button>
                                </div>
                            @endif

                            @if(! $canManageStudy)
                                <div class="text-xs text-gray-500">
                                    You can view and download this manuscript, but only the user who registered this study can upload or replace it.
                                </div>
                            @endif
                        </div>

                        @if($uploadError)
                            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-red-800 break-words">{{ $uploadError }}</span>
                                </div>
                            </div>
                        @endif

                        <x-upload-progress wireModel="document" class="mt-3" />

                        <!-- Document Display -->
                        @if ($study->pdf_path)
                            <div class="relative group">
                                @php
                                    $fileExtension = pathinfo($study->pdf_path, PATHINFO_EXTENSION);
                                    $isPdf = strtolower($fileExtension) === 'pdf';
                                    $isWord = in_array(strtolower($fileExtension), ['doc', 'docx']);
                                @endphp

                                <div class="bg-white rounded-lg shadow overflow-hidden">
                                    @if($isPdf)
                                        <iframe src="{{ Storage::url($study->pdf_path) }}" 
                                                class="w-full h-96" 
                                                frameborder="0">
                                        </iframe>
                                    @elseif($isWord)
                                        <div class="flex items-center justify-center p-8 bg-white rounded-lg shadow">
                                            <div class="text-center">
                                                <i class="fas fa-file-word text-6xl text-blue-600 mb-4"></i>
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">Word Document</h3>
                                                <p class="text-sm text-gray-600 mb-4">This is a Word document that can be downloaded and viewed</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                
                                <!-- Delete Button (appears on hover) -->
                                @if($canManageStudy)
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <button wire:click="deleteDocument" 
                                            wire:confirm="Are you sure you want to delete this document?"
                                            class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-full shadow-lg transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endif
                                
                                <div class="mt-4">
                                    <a href="{{ Storage::url($study->pdf_path) }}" 
                                       download
                                       class="w-full flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        Download {{ $isPdf ? 'PDF' : 'Document' }}
                                    </a>
                                </div>
                            </div>
                        @else
                            <!-- No Document Placeholder -->
                            <div class="text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600">No document uploaded yet</p>
                                    <p class="text-xs text-gray-500 mt-1">Click "Select Document" to add a PDF or Word file</p>
                                </div>
                            </div>
                        @endif
                    </div>

                </div>
            </div>

            <!-- Full-width details + literature data -->
            <div class="px-8 pb-8 space-y-8">
                <!-- Associated protocols -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-file-lines text-blue-700"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Associated protocols</h2>
                                <div class="text-sm text-gray-600 mt-1">Protocols linked to this study.</div>
                            </div>
                        </div>
                        <button type="button"
                                @click="showProtocols = !showProtocols"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors">
                            <i class="fas" :class="showProtocols ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            <span x-text="showProtocols ? 'Collapse' : 'Expand'"></span>
                        </button>
                    </div>

                    <div class="mt-6" x-show="showProtocols" x-collapse>
                        <div class="bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                            @if($study->protocols && $study->protocols->count() > 0)
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Code</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Title</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Technique</th>
                                                <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Technique type</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-100 bg-white">
                                            @foreach($study->protocols as $protocol)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-4 py-3 text-sm">
                                                        <a href="/protocols/{{ $protocol->code }}" class="font-medium text-blue-700 hover:underline">
                                                            {{ $protocol->code }}
                                                        </a>
                                                    </td>
                                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $protocol->name ?? 'N/A' }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $protocol->techniques?->name ?? 'N/A' }}</td>
                                                    <td class="px-4 py-3 text-sm text-gray-900">{{ $protocol->techniques?->type ?? 'N/A' }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="px-4 py-6 text-sm text-gray-600">
                                    No associated protocols.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Associated literature data (full width, paginated + column filters) -->
                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div class="flex items-center">
                            <div class="bg-emerald-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-chart-column text-emerald-700"></i>
                            </div>
                            <div>
                                <h2 class="text-xl font-semibold text-gray-900">Associated literature data</h2>
                                <div class="text-sm text-gray-600 mt-1">Paginated. Filter each column from the header row.</div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button"
                                    @click="showLiterature = !showLiterature"
                                    class="inline-flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-100 transition-colors">
                                <i class="fas" :class="showLiterature ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                <span x-text="showLiterature ? 'Collapse' : 'Expand'"></span>
                            </button>
                            <span class="text-xs text-gray-600">Rows:</span>
                            <select wire:model.live="metaPerPage"
                                    class="px-3 py-2 text-sm border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-emerald-200 focus:border-emerald-300">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-6" x-show="showLiterature" x-collapse>
                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        @php
                            $tabCards = [
                                'humans' => ['label' => 'Humans', 'icon' => 'fa-solid fa-user'],
                                'animals' => ['label' => 'Animals', 'icon' => 'fa-solid fa-paw'],
                                'environments' => ['label' => 'Environment', 'icon' => 'fa-solid fa-seedling'],
                                'parasites' => ['label' => 'Parasites', 'icon' => 'fa-solid fa-spider'],
                            ];
                        @endphp
                        @foreach($tabCards as $key => $tab)
                            @php
                                $s = $metaStats[$key] ?? [
                                    'count' => 0,
                                    'tested' => 0,
                                    'pos' => 0,
                                    'rate' => null,
                                    'countries' => 0,
                                    'pathogens' => 0,
                                ];
                            @endphp
                            <button type="button"
                                    wire:click="$set('metaTab','{{ $key }}')"
                                    class="text-left bg-white border rounded-xl p-4 shadow-sm hover:shadow transition-all duration-200 min-w-0"
                                    @if($metaTab === $key) style="border-color: rgb(52 211 153);" @endif>
                                <div class="flex items-center justify-between gap-3">
                                    <div class="flex items-center gap-3 min-w-0">
                                        <div class="w-10 h-10 rounded-lg flex items-center justify-center bg-gray-100 text-gray-700">
                                            <i class="{{ $tab['icon'] }}"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-sm font-semibold text-gray-900 truncate">{{ $tab['label'] }}</div>
                                            <div class="text-xs text-gray-500">{{ $s['count'] }} record{{ $s['count'] === 1 ? '' : 's' }}</div>
                                        </div>
                                    </div>
                                    <span class="shrink-0 text-xs font-semibold px-2 py-1 rounded-full bg-emerald-100 text-emerald-800">
                                        {{ $s['rate'] !== null ? $s['rate'].'%' : '—' }}
                                    </span>
                                </div>

                                <div class="mt-3 flex flex-wrap gap-3 text-xs text-gray-600">
                                    <div class="min-w-[72px]">
                                        <div class="font-semibold text-gray-900 leading-tight">{{ $s['tested'] }}</div>
                                        <div class="leading-tight">tested</div>
                                    </div>
                                    <div class="min-w-[72px]">
                                        <div class="font-semibold text-gray-900 leading-tight">{{ $s['pos'] }}</div>
                                        <div class="leading-tight">positive</div>
                                    </div>
                                    <div class="min-w-[72px]">
                                        <div class="font-semibold text-gray-900 leading-tight">{{ $s['countries'] }}</div>
                                        <div class="leading-tight">countries</div>
                                    </div>
                                </div>
                            </button>
                        @endforeach
                    </div>

                    <div class="mt-6 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    @if($metaTab === 'animals')
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Country</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Location</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Species</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pathogen</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sample type</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Technique</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Tested</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Pos</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Prev</th>
                                        </tr>
                                        <tr class="bg-white">
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.country" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.location" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.species" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.pathogen" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.sample_type" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.technique" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="number" wire:model.live.debounce.400ms="metaFilters.tested_min" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="≥"></th>
                                            <th class="px-4 py-2"><input type="number" wire:model.live.debounce.400ms="metaFilters.pos_min" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="≥"></th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    @elseif($metaTab === 'humans')
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Country</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Location</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pathogen</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sample type</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Technique</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Tested</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Pos</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Prev</th>
                                        </tr>
                                        <tr class="bg-white">
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.country" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.location" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.pathogen" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.sample_type" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.technique" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="number" wire:model.live.debounce.400ms="metaFilters.tested_min" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="≥"></th>
                                            <th class="px-4 py-2"><input type="number" wire:model.live.debounce.400ms="metaFilters.pos_min" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="≥"></th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    @elseif($metaTab === 'environments')
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Country</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Location</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sample type</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pathogen</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Technique</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Tested</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Pos</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Prev</th>
                                        </tr>
                                        <tr class="bg-white">
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.country" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.location" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.sample_type" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.pathogen" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.technique" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="number" wire:model.live.debounce.400ms="metaFilters.tested_min" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="≥"></th>
                                            <th class="px-4 py-2"><input type="number" wire:model.live.debounce.400ms="metaFilters.pos_min" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="≥"></th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    @else
                                        <tr>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Country</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Location</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Species</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Sample type</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Pathogen</th>
                                            <th class="px-4 py-2 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Technique</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Tested</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Pos</th>
                                            <th class="px-4 py-2 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Prev</th>
                                        </tr>
                                        <tr class="bg-white">
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.country" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.location" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.sample_type" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.pathogen" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="text" wire:model.live.debounce.400ms="metaFilters.technique" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="Filter"></th>
                                            <th class="px-4 py-2"><input type="number" wire:model.live.debounce.400ms="metaFilters.tested_min" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="≥"></th>
                                            <th class="px-4 py-2"><input type="number" wire:model.live.debounce.400ms="metaFilters.pos_min" class="w-full px-2 py-1 text-sm border border-gray-200 rounded" placeholder="≥"></th>
                                            <th class="px-4 py-2"></th>
                                        </tr>
                                    @endif
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($metaRows as $row)
                                        @php
                                            $tested = (int) ($row->tested_n ?? 0);
                                            $pos = (int) ($row->pos_n ?? 0);
                                            $prev = $tested > 0 ? round(($pos / $tested) * 100, 1) : null;
                                        @endphp
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $row->countries?->name ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $row->location ?? 'N/A' }}</td>
                                            @if($metaTab === 'animals')
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->animal_species?->name_common ?? $row->animal_species?->name ?? 'N/A' }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->pathogens?->species ?? 'N/A' }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->sample_types?->name ?? 'N/A' }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->techniques?->name ?? $row->techniques?->type ?? 'N/A' }}</td>
                                            @elseif($metaTab === 'humans')
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->pathogens?->species ?? 'N/A' }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->sample_types?->name ?? 'N/A' }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->techniques?->name ?? $row->techniques?->type ?? 'N/A' }}</td>
                                            @elseif($metaTab === 'environments')
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->environment_sample_types?->name ?? 'N/A' }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->pathogens?->species ?? 'N/A' }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->techniques?->name ?? $row->techniques?->type ?? 'N/A' }}</td>
                                            @else
                                                <td class="px-4 py-3 text-sm text-gray-900">{!! $row->parasite_species?->name_scientific ? '<i>'.e($row->parasite_species->name_scientific).'</i>' : 'N/A' !!}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->parasite_sample_types?->name ?? 'N/A' }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->pathogens?->species ?? 'N/A' }}</td>
                                                <td class="px-4 py-3 text-sm text-gray-900">{{ $row->techniques?->name ?? $row->techniques?->type ?? 'N/A' }}</td>
                                            @endif
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $row->tested_n ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $row->pos_n ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ $prev !== null ? $prev.'%' : '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="px-4 py-6 text-sm text-gray-600">
                                                No records match the current filters.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="px-4 py-3 border-t border-gray-200">
                            {{ $metaRows->links() }}
                        </div>
                    </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 