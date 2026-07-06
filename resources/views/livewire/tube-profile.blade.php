<div
    class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8"
    x-data="{
        editing: {
            alias_code: false,
            tube_type: false,
            preservant: false,
            purpose: false,
            date_processed: false,
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
                        <a href="/bank/tubes/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Tubes List
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-cyan-900 to-cyan-800 rounded-t-xl shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="bg-white/20 p-3 rounded-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                        </path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-white">Tube Details</h1>
                                    <p class="text-cyan-100 text-lg">Code: {{ $tube->code }}</p>
                                </div>
                            </div>

                            @if ($tube->alias_code)
                                <!-- Status Badge -->
                                <div class="flex items-center space-x-4">
                                    <span class="text-cyan-100 text-sm">
                                        Alias code: {{ $tube->alias_code ?? 'N/A' }}
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
                            @else
                                <div class="flex items-center space-x-4">
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
                            @endif
                        </div>

                        <div class="flex space-x-3">
                            <a href="/bank/tubes/list"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to List
                            </a>
                        @if($canEdit)
                            <button wire:click="deleteTube"
                                wire:confirm="Are you sure you want to delete this tube? This action cannot be undone."
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
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 p-8">

                    <!-- Left Column - Main Details -->
                    <div class="lg:col-span-2 space-y-8">

                        <!-- Tube Information Section -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-cyan-100 p-2 rounded-lg mr-3">
                                    <svg class="w-6 h-6 text-cyan-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Tube Information</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Content Type</dt>
                                <dd class="text-sm text-gray-900">
                                    @php
                                    $type = $tube->tubes_content_type;
                                    $code = $tube->tubes_content->code;
                                    $route = null;
                                    $badge = null;
                                    if ($type === 'App\\Models\\HumanSamples') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-pink-100 to-pink-200 text-pink-800 shadow-sm">Human Sample</span>';
                                        $route = "/samples/humans/{$code}";
                                    } elseif ($type === 'App\\Models\\AnimalSamples') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800 shadow-sm">Animal Sample</span>';
                                        $route = "/samples/animals/{$code}";
                                    } elseif ($type === 'App\\Models\\EnvironmentSamples') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-green-100 to-green-200 text-green-800 shadow-sm">Environmental Sample</span>';
                                        $route = "/samples/environments/{$code}";
                                    } elseif ($type === 'App\\Models\\ParasiteSamples') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm">Parasite Sample</span>';
                                        $route = "/samples/parasites/{$code}";
                                    } elseif ($type === 'App\\Models\\Experiments') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-900 to-blue-800 text-blue-100 shadow-sm">Experiments</span>';
                                        $route = "/experiments/{$code}";
                                    } elseif ($type === 'App\\Models\\Cultures') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-sm">Culture</span>';
                                        $route = "/samples/cultures/{$code}";
                                    } elseif ($type === 'App\\Models\\Pools') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-cyan-100 to-cyan-200 text-cyan-800 shadow-sm">Pool</span>';
                                        $route = "/samples/pools/{$code}";
                                    } elseif ($type === 'App\\Models\\NucleicAcids') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 shadow-sm">Nucleic Acid</span>';
                                        $route = "/samples/nucleic/{$code}";
                                    }
                                @endphp
                                {!! $badge !!}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Content Code</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    @if ($route && $code !== 'N/A')
                                    <a href="{{ $route }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">{{ $code }}</a>
                                @endif
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Tube type</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="tube_type_{{ $tube->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('tube_type')" @endif
                                              x-show="!editing.tube_type">
                                            {{ $tube->tube_type ?? 'N/A' }}
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.tube_type" class="inline-flex items-center space-x-2">
                                                <input type="text"
                                                       wire:model="editingValues.tube_type"
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                                                       x-ref="tube_type_input"
                                                       x-init="$nextTick(() => $refs.tube_type_input?.focus())">
                                                <button wire:click="saveEdit('tube_type')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('tube_type')"
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Preservant</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="preservant_{{ $tube->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('preservant')" @endif
                                              x-show="!editing.preservant">
                                            {{ $tube->preservant ?? 'N/A' }}
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.preservant" class="inline-flex items-center space-x-2">
                                                <input type="text"
                                                       wire:model="editingValues.preservant"
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                                                       x-ref="preservant_input"
                                                       x-init="$nextTick(() => $refs.preservant_input?.focus())">
                                                <button wire:click="saveEdit('preservant')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('preservant')"
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Purpose of processing</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="purpose_{{ $tube->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('purpose')" @endif
                                              x-show="!editing.purpose">
                                            {{ $tube->purpose ?? 'N/A' }}
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.purpose" class="inline-flex items-center space-x-2">
                                                <input type="text"
                                                       wire:model="editingValues.purpose"
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                                                       x-ref="purpose_input"
                                                       x-init="$nextTick(() => $refs.purpose_input?.focus())">
                                                <button wire:click="saveEdit('purpose')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('purpose')"
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Date processed</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="date_processed_{{ $tube->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('date_processed')" @endif
                                              x-show="!editing.date_processed">
                                            {{ $tube->date_processed ? \Carbon\Carbon::parse($tube->date_processed)->format('Y-m-d') : 'N/A' }}
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.date_processed" class="inline-flex items-center space-x-2">
                                                <input type="date"
                                                       wire:model="editingValues.date_processed"
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                                                       x-ref="date_processed_input"
                                                       x-init="$nextTick(() => $refs.date_processed_input?.focus())">
                                                <button wire:click="saveEdit('date_processed')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('date_processed')"
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Alias code</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="alias_code_{{ $tube->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('alias_code')" @endif
                                              x-show="!editing.alias_code">
                                            {{ $tube->alias_code ?? 'N/A' }}
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.alias_code" class="inline-flex items-center space-x-2">
                                                <input type="text"
                                                       wire:model="editingValues.alias_code"
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent"
                                                       x-ref="alias_code_input"
                                                       x-init="$nextTick(() => $refs.alias_code_input?.focus())">
                                                <button wire:click="saveEdit('alias_code')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('alias_code')"
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

                    <!-- Current Position Grid -->
                    @php
                        $currentBox = null;
                        $currentLocation = null;
                        if ($tube->tube_positions->count() > 0) {
                            $latestPosition = $tube->tube_positions->sortByDesc('date_moved')->first();
                            $currentBox = $latestPosition->boxes;
                            $boxPositions = $currentBox->box_positions->sortByDesc('date_moved');
                            $currentLocation = $boxPositions->first();
                        }
                    @endphp

                    @if ($tube->tube_positions->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                        </path>
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-gray-900">Current Position</h2>
                            </div>

                            <!-- Box Grid -->
                            <div class="mb-6">
                                <div class="flex items-center justify-between mb-3">
                                    <h3 class="text-sm font-medium text-gray-700">
                                        Box:
                                        <a href="/bank/boxes/{{ $currentBox->id }}/contents"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200"
                                            title="Open box grid">
                                            {{ $currentBox->code }}
                                        </a>
                                    </h3>
                                    <span
                                        class="text-xs text-gray-500">{{ $currentBox->n_rows }}×{{ $currentBox->n_columns }}
                                        grid</span>
                                </div>

                                <div class="bg-white rounded-lg border-2 border-gray-200 p-2">
                                    <div class="grid gap-1"
                                        style="grid-template-columns: repeat({{ $currentBox->n_columns }}, 1fr);">
                                        @for ($row = 1; $row <= $currentBox->n_rows; $row++)
                                            @for ($col = 1; $col <= $currentBox->n_columns; $col++)
                                                @php
                                                    $latestPosition = $tube->tube_positions
                                                        ->sortByDesc('date_moved')
                                                        ->first();
                                                @endphp
                                                <div
                                                    class="aspect-square border border-gray-200 rounded flex items-center justify-center text-xs font-mono
                            {{ $row == $latestPosition->position_y && $col == $latestPosition->position_x
                                ? 'bg-cyan-500 text-white border-cyan-600'
                                : 'bg-gray-50 text-gray-400' }}">
                                                    @if ($row == $latestPosition->position_y && $col == $latestPosition->position_x)
                                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                                                            <path
                                                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                                        </svg>
                                                    @else
                                                        {{ $row }},{{ $col }}
                                                    @endif
                                                </div>
                                            @endfor
                                        @endfor
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2 text-center">
                                    Tube located at position ({{ $latestPosition->position_x }},
                                    {{ $latestPosition->position_y }})
                                </p>
                            </div>


                        </div>
                    @else
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-gray-100 p-2 rounded-lg mr-3">
                                    <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                        </path>
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-gray-900">Current Position</h2>
                            </div>

                            <div class="text-center py-8">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900">No position data</h3>
                                <p class="mt-1 text-sm text-gray-500">This tube has not been positioned in any box yet.
                                </p>
                            </div>
                        </div>
                    @endif




                </div>

                <!-- Right Column - Sidebar -->
                <div class="space-y-6">

                    <!-- Position History Section -->
                    @if ($tube->tube_positions->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-1.447-.894L15 4m0 13V4m-6 3l6-3">
                                        </path>
                                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Position History</h2>
                            </div>

                            <div class="bg-white rounded-lg shadow overflow-hidden">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Box</th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Position</th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date Moved</th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Moved By</th>
                                            <th
                                                class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach ($tube->tube_positions as $position)
                                            <tr class="hover:bg-gray-50">
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                    @if ($position->boxes)
                                                        <a href="/bank/boxes/{{ $position->boxes->id }}/contents"
                                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200"
                                                            title="Open box grid">
                                                            {{ $position->boxes->code }}
                                                        </a>
                                                    @else
                                                        N/A
                                                    @endif
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                    ({{ $position->position_x }}, {{ $position->position_y }})
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $position->date_moved }}
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                    <div class="flex items-center space-x-2">
                                                        <x-people-logo :person="$position->people" width="24" />
                                                        <a href="/profile/{{ $position->people->id }}"
                                                            class="text-blue-600 hover:text-blue-800 hover:underline">
                                                            {{ $position->people->title . ' ' . $position->people->first_name . ' ' . $position->people->last_name }}
                                                        </a>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">
                                                    {{ $position->reason ?? 'N/A' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif


                    <!-- Storage Information -->
                    <div class="space-y-4">
                        <h3 class="text-sm font-medium text-gray-700">Storage Information</h3>

                        <div class="bg-white rounded-lg p-4 space-y-3">
                            @if ($currentBox)
                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Box Code:</span>
                                    <span class="text-sm font-medium text-gray-900">{{ $currentBox->code }}</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Box Name:</span>
                                    <span
                                        class="text-sm font-medium text-gray-900">{{ $currentBox->name ?? 'N/A' }}</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Content Type:</span>
                                    <span
                                        class="text-sm font-medium text-gray-900">{{ $currentBox->content_type ?? 'N/A' }}</span>
                                </div>

                                <div class="flex justify-between items-center">
                                    <span class="text-sm text-gray-600">Content State:</span>
                                    <span
                                        class="text-sm font-medium text-gray-900">{{ $currentBox->content_state ?? 'N/A' }}</span>
                                </div>

                                @if ($currentLocation)
                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Location:</span>
                                        <span
                                            class="text-sm font-medium text-gray-900">{{ $currentLocation->locations->name ?? 'N/A' }}</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Location Type:</span>
                                        <span
                                            class="text-sm font-medium text-gray-900">{{ $currentLocation->locations->type ?? 'N/A' }}</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Room:</span>
                                        <span
                                            class="text-sm font-medium text-gray-900">{{ $currentLocation->locations->room ?? 'N/A' }}</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Laboratory:</span>
                                        <span
                                            class="text-sm font-medium text-gray-900">{{ $currentLocation->locations->laboratories->name ?? 'N/A' }}</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-sm text-gray-600">Last Moved:</span>
                                        <span
                                            class="text-sm font-medium text-gray-900">{{ $currentLocation->date_moved }}</span>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <span class="text-sm text-gray-500">No location information available</span>
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-4">
                                    <span class="text-sm text-gray-500">No storage information available</span>
                                </div>
                                @if($canEdit)
                                    <form action="/bank/tubes/create">
                                        <button type="submit"
                                    class="group relative
                                        inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all
                                        duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400
                                        to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border
                                        border-amber-500">
                                        <i
                                            class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                        Create New Tube Position
                                    </button>
                                    </form>
                                @endif
                                
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
@endif
