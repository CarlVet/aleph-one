<div
    class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8"
    x-data="{
        editing: {
            outcome_discrete: false,
            outcome_quant: false,
            date_tested: false,
        }
    }"
    x-on:start-edit.window="editing[$event.detail.field] = true"
    x-on:save-edit.window="editing[$event.detail.field] = false"
    x-on:cancel-edit.window="editing[$event.detail.field] = false"
    x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success!', text: $event.detail.message, timer: 2000, showConfirmButton: false }); }"
    x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error!', text: $event.detail.message, confirmButtonColor: '#d33' }); }"
    wire:ignore.self
>
    <div class="px-4 py-6 sm:px-0">
        @if(!$canView)
            <!-- Unauthorized Access Message -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-2xl p-8 shadow-lg">
                <div class="flex items-center justify-center">
                    <div class="text-center max-w-md">
                        <div class="bg-red-100 p-4 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6 shadow-inner">
                            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-red-900 mb-3">Access Denied</h2>
                        <p class="text-red-700 text-lg mb-6 leading-relaxed">{{ $unauthorizedMessage }}</p>
                        <a href="/experiments/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Experiments List
                        </a>
                    </div>
                </div>
            </div>
        @else
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-blue-900 to-blue-800 rounded-t-xl shadow-lg">
            <div class="px-6 py-8">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="bg-white/20 p-3 rounded-lg">
                                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                    </path>
                                </svg>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">Experiment Profile</h1>
                                <p class="text-blue-100 text-lg">Code: {{ $experiment->code }}</p>
                                @if(optional($experiment->subProjectAssignment?->subProject)->code)
                                    <span class="mt-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                        Sub-project: {{ $experiment->subProjectAssignment->subProject->code }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center space-x-4">
                            <span class="text-blue-100 text-sm">
                                Performed on
                                {{ $experiment->date_tested ? \Carbon\Carbon::parse($experiment->date_tested)->format('M d, Y') : 'N/A' }}
                            </span>
                            @if(!$canEdit)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                    </svg>
                                    View Only
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <a href="/experiments/list"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to List
                        </a>
                        <button wire:click="exportExperiment"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Export
                        </button>
                        @if($canEdit)
                            <button wire:click="deleteExperiment"
                                    wire:confirm="Are you sure you want to delete this experiment? This action cannot be undone."
                                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                    Delete
                                </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white shadow-lg rounded-b-xl">
            <div class="p-8">
                <!-- Centered Photo Section -->
                <div class="max-w-3xl mx-auto mb-10">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center justify-between gap-4 mb-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="bg-pink-100 p-3 rounded-lg shrink-0">
                                <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                                <div class="min-w-0">
                                    <h2 class="text-xl font-semibold text-gray-900 leading-tight">Photo</h2>
                                    <p class="mt-0.5 text-xs text-gray-500 leading-snug">
                                        Max file size: 50MB <br> Formats: JPG, PNG, WEBP, TIFF, PDF
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center justify-center gap-2 mb-4">
                            @if(!$photo)
                                <label
                                    for="photo-upload"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200 cursor-pointer {{ $canEdit ? '' : 'opacity-60 cursor-not-allowed' }}"
                                >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        Select photo
                                    <input type="file"
                                        id="photo-upload"
                                        class="hidden"
                                        accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf"
                                        wire:model="photo"
                                        @if(!$canEdit) disabled @endif
                                        wire:loading.attr="disabled"
                                        x-data
                                        x-on:photo-uploaded.window="$el.value = ''"
                                        x-on:photo-cancelled.window="$el.value = ''">
                                </label>
                            @else
                                <div class="flex gap-2">
                                    <button wire:click="uploadPhoto"
                                        @if(!$canEdit) disabled @endif
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200 disabled:opacity-60 disabled:cursor-not-allowed">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Upload
                                    </button>
                                    <button wire:click="cancelPhotoSelection"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Cancel
                                    </button>
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

                        <x-upload-progress wireModel="photo" class="mt-3" />

                        @php
                            $photoPath = $experiment->photo_path;
                            $photoUrl = $photoPath ? Storage::url($photoPath) : null;
                            $photoExt = $photoPath ? strtolower(pathinfo($photoPath, PATHINFO_EXTENSION)) : null;
                            $photoIsPreviewable = in_array($photoExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                        @endphp
                        @if ($photoUrl)
                            <div class="relative group">
                                <a href="{{ $photoUrl }}" target="_blank" class="block">
                                    @if($photoIsPreviewable)
                                        <img src="{{ $photoUrl }}" alt="Experiment photo"
                                            class="w-full h-auto rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-200">
                                    @else
                                        <div class="w-full rounded-lg border border-gray-200 bg-white p-8 text-center text-sm text-gray-700 shadow-sm hover:shadow transition-shadow duration-200">
                                            File uploaded ({{ strtoupper((string) $photoExt) }}) — click to open
                                        </div>
                                    @endif
                                </a>

                                @if($canEdit)
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <button wire:click="deletePhoto"
                                            wire:confirm="Are you sure you want to delete this photo?"
                                            class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-full shadow-lg transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600">No photo uploaded yet</p>
                                    <p class="text-xs text-gray-500 mt-1">Click “Select Photo” to add an image</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                <!-- Left Column - Main Details -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Experiment Information Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Experiment Information</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Date tested</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="date_tested_{{ $experiment->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('date_tested')" @endif
                                              x-show="!editing.date_tested">
                                            {{ $experiment->date_tested ? \Carbon\Carbon::parse($experiment->date_tested)->format('Y-m-d') : 'N/A' }}
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.date_tested" class="inline-flex items-center space-x-2">
                                                <input type="date"
                                                       wire:model="editingValues.date_tested"
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                       x-ref="date_tested_input"
                                                       x-init="$nextTick(() => $refs.date_tested_input?.focus())">
                                                <button wire:click="saveEdit('date_tested')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('date_tested')"
                                                        class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Protocol</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    @if ($experiment->protocols)
                                        <a href="/protocols/{{ $experiment->protocols->code }}"
                                            class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $experiment->protocols->name }}
                                        </a>
                                    @else
                                        N/A
                                    @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Protocol Type</dt>
                                <dd class="text-sm text-gray-900">
                                    <span
                                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        {{ $experiment->protocols->techniques->type ?? 'N/A' }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Pathogen</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $experiment->pathogens->species ?? 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Outcome (Categorical)</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="outcome_discrete_{{ $experiment->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('outcome_discrete')" @endif
                                              x-show="!editing.outcome_discrete">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $experiment->outcome_discrete === 'Positive'
                                                    ? 'bg-green-100 text-green-800'
                                                    : ($experiment->outcome_discrete === 'Negative'
                                                        ? 'bg-red-100 text-red-800'
                                                        : 'bg-gray-100 text-gray-800') }}">
                                                {{ $experiment->outcome_discrete ?? 'N/A' }}
                                            </span>
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.outcome_discrete" class="inline-flex items-center space-x-2">
                                                <input type="text"
                                                       wire:model="editingValues.outcome_discrete"
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                       placeholder="{{ $experiment->outcome_discrete ?? 'Outcome' }}"
                                                       x-ref="outcome_discrete_input"
                                                       x-init="$nextTick(() => $refs.outcome_discrete_input?.focus())">
                                                <button wire:click="saveEdit('outcome_discrete')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('outcome_discrete')"
                                                        class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        @endif
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Outcome (Quantitative)</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="outcome_quant_{{ $experiment->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('outcome_quant')" @endif
                                              x-show="!editing.outcome_quant">
                                            {{ $experiment->outcome_quant ?? 'N/A' }}
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.outcome_quant" class="inline-flex items-center space-x-2">
                                                <input type="number"
                                                       wire:model="editingValues.outcome_quant"
                                                       step="0.01"
                                                       min="0"
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-28"
                                                       placeholder="{{ $experiment->outcome_quant ?? '0.00' }}"
                                                       x-ref="outcome_quant_input"
                                                       x-init="$nextTick(() => $refs.outcome_quant_input?.focus())">
                                                <button wire:click="saveEdit('outcome_quant')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('outcome_quant')"
                                                        class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

                    <!-- Sample Details Section -->
                    @if($experiment->experiments_content)
                    <div class="bg-gray-50 rounded-xl p-6">
                        @if($experiment->experiments_content_type === 'App\Models\AnimalSamples')
                            <div class="flex items-center mb-6">
                                <div class="bg-orange-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-paw text-2xl text-orange-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Animal Sample Details</h2>
                            </div>
                            @php
                                $animalSample = $experiment->experiments_content->load(['animals.animal_species', 'sample_types', 'people', 'sampling_sites']);
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Content Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/samples/animals/{{ $experiment->experiments_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $experiment->experiments_content->code }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Animal Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/animals/{{ $animalSample->animals->code ?? '' }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $animalSample->animals->code ?? 'N/A' }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Animal Species</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $animalSample->animals->animal_species->name_common ?? $animalSample->animals->animal_species->name_scientific ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sample Type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $animalSample->sample_types->name ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Date Collected</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $animalSample->date_collected ? \Carbon\Carbon::parse($animalSample->date_collected)->format('M d, Y') : 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Collection Site</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $animalSample->sampling_sites->name ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Collected By</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <x-people-logo :person="$animalSample->people" width="24" />
                                            <a href="/profile/{{ $animalSample->people->id ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                {{ $animalSample->people->title . ' ' . $animalSample->people->first_name . ' ' . $animalSample->people->last_name ?? 'N/A' }}
                                            </a>
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        @elseif($experiment->experiments_content_type === 'App\Models\HumanSamples')
                            <div class="flex items-center mb-6">
                                <div class="bg-pink-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-person text-2xl text-pink-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Human Sample Details</h2>
                            </div>
                            @php
                                $humanSample = $experiment->experiments_content->load(['humans', 'sample_types', 'people', 'sampling_sites']);
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Content Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/samples/humans/{{ $experiment->experiments_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $experiment->experiments_content->code }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Human Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/humans/{{ $humanSample->humans->code ?? '' }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $humanSample->humans->code ?? 'N/A' }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sample Type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $humanSample->sample_types->name ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Date Collected</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $humanSample->date_collected ? \Carbon\Carbon::parse($humanSample->date_collected)->format('M d, Y') : 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Collection Site</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $humanSample->sampling_sites->name ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Collected By</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <x-people-logo :person="$humanSample->people" width="24" />
                                            <a href="/profile/{{ $humanSample->people->id ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                {{ $humanSample->people->title . ' ' . $humanSample->people->first_name . ' ' . $humanSample->people->last_name ?? 'N/A' }}
                                            </a>
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        @elseif($experiment->experiments_content_type === 'App\Models\ParasiteSamples')
                            <div class="flex items-center mb-6">
                                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-spider text-2xl text-purple-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Parasite Sample Details</h2>
                            </div>
                            @php
                                $parasiteSample = $experiment->experiments_content->load(['parasites.parasite_species', 'parasite_sample_types', 'people']);
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Content Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/samples/parasites/{{ $experiment->experiments_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $experiment->experiments_content->code }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Parasite Species</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $parasiteSample->parasites->parasite_species->name_scientific ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sample Type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $parasiteSample->parasite_sample_types->name ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Date Processed</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $parasiteSample->date_processed ? \Carbon\Carbon::parse($parasiteSample->date_processed)->format('M d, Y') : 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Processed By</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <x-people-logo :person="$parasiteSample->people" width="24" />
                                            <a href="/profile/{{ $parasiteSample->people->id ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                {{ $parasiteSample->people->title . ' ' . $parasiteSample->people->first_name . ' ' . $parasiteSample->people->last_name ?? 'N/A' }}
                                            </a>
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        @elseif($experiment->experiments_content_type === 'App\Models\NucleicAcids')
                            <div class="flex items-center mb-6">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-dna text-2xl text-blue-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Nucleic Acid Details</h2>
                            </div>
                            @php
                                $nucleicAcid = $experiment->experiments_content->load(['people']);
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Content Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/samples/nucleic/{{ $experiment->experiments_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $experiment->experiments_content->code }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Nucleic Acid Type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $nucleicAcid->type ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Date Extracted</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $nucleicAcid->date_extracted ? \Carbon\Carbon::parse($nucleicAcid->date_extracted)->format('M d, Y') : 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Volume</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $nucleicAcid->volume ?? 'N/A' }} μL
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Extracted By</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <x-people-logo :person="$nucleicAcid->people" width="24" />
                                            <a href="/profile/{{ $nucleicAcid->people->id ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                {{ $nucleicAcid->people->title . ' ' . $nucleicAcid->people->first_name . ' ' . $nucleicAcid->people->last_name ?? 'N/A' }}
                                            </a>
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        @elseif($experiment->experiments_content_type === 'App\Models\EnvironmentSamples')
                            <div class="flex items-center mb-6">
                                <div class="bg-green-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-leaf text-2xl text-green-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Environmental Sample Details</h2>
                            </div>
                            @php
                                $environmentSample = $experiment->experiments_content->load(['environment_sample_types', 'people', 'sampling_sites']);
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Content Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/samples/environment/{{ $experiment->experiments_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $experiment->experiments_content->code }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sample Type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $environmentSample->environment_sample_types->name ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Date Collected</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $environmentSample->date_collected ? \Carbon\Carbon::parse($environmentSample->date_collected)->format('M d, Y') : 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Collection Site</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $environmentSample->sampling_sites->name ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Collected By</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <x-people-logo :person="$environmentSample->people" width="24" />
                                            <a href="/profile/{{ $environmentSample->people->id ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                {{ $environmentSample->people->title . ' ' . $environmentSample->people->first_name . ' ' . $environmentSample->people->last_name ?? 'N/A' }}
                                            </a>
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        @elseif($experiment->experiments_content_type === 'App\Models\Cultures')
                            <div class="flex items-center mb-6">
                                <div class="bg-orange-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-bacteria text-2xl text-orange-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Culture Details</h2>
                            </div>
                            @php
                                $culture = $experiment->experiments_content->load(['people']);
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Content Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/samples/cultures/{{ $experiment->experiments_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $experiment->experiments_content->code }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Culture Type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $culture->type ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Date Created</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $culture->date_created ? \Carbon\Carbon::parse($culture->date_created)->format('M d, Y') : 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Created By</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <x-people-logo :person="$culture->people" width="24" />
                                            <a href="/profile/{{ $culture->people->id ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                {{ $culture->people->title . ' ' . $culture->people->first_name . ' ' . $culture->people->last_name ?? 'N/A' }}
                                            </a>
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        @elseif($experiment->experiments_content_type === 'App\Models\Pools')
                            <div class="flex items-center mb-6">
                                <div class="bg-cyan-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-layer-group text-2xl text-cyan-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Pool Details</h2>
                            </div>
                            @php
                                $pool = $experiment->experiments_content->load(['people']);
                            @endphp
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Content Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/samples/pools/{{ $experiment->experiments_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $experiment->experiments_content->code }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Pool Type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $pool->type ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Date Created</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $pool->date_created ? \Carbon\Carbon::parse($pool->date_created)->format('M d, Y') : 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Created By</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="flex items-center space-x-2">
                                            <x-people-logo :person="$pool->people" width="24" />
                                            <a href="/profile/{{ $pool->people->id ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                {{ $pool->people->title . ' ' . $pool->people->first_name . ' ' . $pool->people->last_name ?? 'N/A' }}
                                            </a>
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        @endif
                    </div>
                    @endif

                </div>

                <!-- Right Column - Sidebar -->
                <div class="space-y-6">

                    <!-- Personnel Information -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900">Personnel</h2>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-2">Performed By</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="flex items-center space-x-3 bg-white p-3 rounded-lg border">
                                        <x-people-logo :person="$experiment->people" width="40" />
                                        <div>
                                            <a href="/profile/{{ $experiment->people->id }}"
                                                class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                {{ $experiment->people->title . ' ' . $experiment->people->first_name . ' ' . $experiment->people->last_name ?? 'N/A' }}
                                            </a>
                                            @if ($experiment->people->email)
                                                <p class="text-xs text-gray-500">{{ $experiment->people->email }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-2">Performed At</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="bg-white p-3 rounded-lg border">
                                        <div class="font-medium">{{ $experiment->laboratories->name ?? 'N/A' }}</div>
                                        @if ($experiment->laboratories->countries->name)
                                            <div class="text-xs text-gray-500">{{ $experiment->laboratories->countries->name }}
                                            </div>
                                        @endif
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endif
