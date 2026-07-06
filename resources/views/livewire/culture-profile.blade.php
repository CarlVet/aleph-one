<div
    data-profile-tables
    class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8"
    x-data="{
        editing: {
            medium: false,
            type: false,
            incubation_temp: false,
            athmosphere: false,
            date_cultured: false,
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
                        <a href="/samples/cultures/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Cultures List
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-orange-900 to-orange-800 rounded-t-xl shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="bg-white/20 p-3 rounded-lg">
                                    <i class="fa-solid fa-bacteria text-2xl text-white"></i>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-white">Culture Profile</h1>
                                    <p class="text-orange-100 text-lg">Code: {{ $culture->code }}</p>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="flex items-center space-x-4 flex-wrap gap-2">
                                <span class="text-orange-100 text-sm">
                                    Source: {{ class_basename($culture->cultures_content_type) }}
                                </span>
                                @if($culture->is_discarded)
                                    <span class="inline-flex items-center rounded-full bg-red-500/90 px-3 py-1 text-xs font-semibold text-white">
                                        Discarded{{ $culture->date_discarded ? ' · '.$culture->date_discarded->format('Y-m-d') : '' }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-green-500/80 px-3 py-1 text-xs font-semibold text-white">
                                        Active
                                    </span>
                                @endif
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
                            <a href="/samples/cultures/list"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to List
                            </a>
                            @if($canEdit)
                                <button wire:click="deleteCulture"
                                    wire:confirm="Are you sure you want to delete this culture? This action cannot be undone."
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

                        <!-- Culture Information Section -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-orange-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-bacteria text-2xl text-orange-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Culture Information</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Source code</dt>
                                    <dd class="text-sm text-gray-900">
                                        @if($culture->cultures_content_type === 'App\Models\AnimalSamples')
                                                <a href="/samples/animals/{{ $culture->cultures_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $culture->cultures_content->code }} - {{ $culture->cultures_content->animals->animal_species->name_common ?? 'N/A' }}
                                                </a>
                                            @elseif($culture->cultures_content_type === 'App\Models\HumanSamples')
                                                <a href="/samples/humans/{{ $culture->cultures_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $culture->cultures_content->code }}
                                                </a>
                                            @elseif($culture->cultures_content_type === 'App\Models\EnvironmentSamples')
                                                <a href="/samples/environment/{{ $culture->cultures_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $culture->cultures_content->code }}
                                                </a>
                                            @elseif($culture->cultures_content_type === 'App\Models\ParasiteSamples')
                                                <a href="/samples/parasites/{{ $culture->cultures_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $culture->cultures_content->code }}
                                                </a>
                                            @elseif($culture->cultures_content_type === 'App\Models\Pools')
                                                <a href="/samples/pools/{{ $culture->cultures_content->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $culture->cultures_content->code }}
                                                </a>
                                            @endif
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Medium</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="inline-edit" wire:key="medium_{{ $culture->id }}">
                                            <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 transition-colors' : '' }}" 
                                                  @if($canEdit) wire:click="startEdit('medium')" @endif
                                                  x-show="!editing.medium">
                                                {{ $culture->medium ?? 'N/A' }}
                                            </span>
                                            @if($canEdit)
                                                <div x-show="editing.medium" class="inline-flex items-center space-x-2">
                                                    <div class="relative min-w-[200px]">
                                                        <input type="text" wire:model="editingValues.medium" 
                                                            x-ref="medium_input"
                                                            x-init="$nextTick(() => $refs.medium_input.focus())"
                                                            value="{{ $culture->medium }}"
                                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                                            autocomplete="off"
                                                            list="medium-list">
                                                        <datalist id="medium-list">
                                                            @foreach($existingMediums as $medium)
                                                                <option value="{{ $medium }}">
                                                                    {{ $medium }}
                                                                </option>
                                                            @endforeach
                                                        </datalist>
                                                        <div
                                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                            <i class="fas fa-search text-gray-400"></i>
                                                        </div>
                                                    </div>
                                                    <button wire:click="saveEdit('medium')" 
                                                            class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="cancelEdit('medium')" 
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
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Culture Type</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="inline-edit" wire:key="type_{{ $culture->id }}">
                                            <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 transition-colors' : '' }}" 
                                                  @if($canEdit) wire:click="startEdit('type')" @endif
                                                  x-show="!editing.type">
                                                {{ $culture->type ?? 'N/A' }}
                                            </span>
                                            @if($canEdit)
                                                <div x-show="editing.type" class="inline-flex items-center space-x-2">
                                                    <div class="relative min-w-[150px]">
                                                        <input type="text" wire:model="editingValues.type" 
                                                            x-ref="type_input"
                                                            x-init="$nextTick(() => $refs.type_input.focus())"
                                                            value="{{ $culture->type }}"
                                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                                            autocomplete="off"
                                                            list="type-list">
                                                        <datalist id="type-list">
                                                            @foreach($existingTypes as $type)
                                                                <option value="{{ $type }}">
                                                                    {{ $type }}
                                                                </option>
                                                            @endforeach
                                                        </datalist>
                                                        <div
                                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                            <i class="fas fa-search text-gray-400"></i>
                                                        </div>
                                                    </div>
                                                    <button wire:click="saveEdit('type')" 
                                                            class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="cancelEdit('type')" 
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
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Incubation Temperature</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="inline-edit" wire:key="incubation_temp_{{ $culture->id }}">
                                            <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 transition-colors' : '' }}" 
                                                  @if($canEdit) wire:click="startEdit('incubation_temp')" @endif
                                                  x-show="!editing.incubation_temp">
                                                {{ $culture->incubation_temp ? $culture->incubation_temp . '°C' : 'N/A' }}
                                            </span>
                                            @if($canEdit)
                                                <div x-show="editing.incubation_temp" class="inline-flex items-center space-x-2">
                                                    <input type="number" 
                                                           wire:model="editingValues.incubation_temp" 
                                                           class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-20"
                                                           placeholder="Temp"
                                                           min="0"
                                                           max="100"
                                                           x-ref="incubation_temp_input"
                                                           x-init="$nextTick(() => $refs.incubation_temp_input.focus())">
                                                    <span class="text-gray-500">°C</span>
                                                    <button wire:click="saveEdit('incubation_temp')" 
                                                            class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="cancelEdit('incubation_temp')" 
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
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Atmosphere</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="inline-edit" wire:key="athmosphere_{{ $culture->id }}">
                                            <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 transition-colors' : '' }}" 
                                                  @if($canEdit) wire:click="startEdit('athmosphere')" @endif
                                                  x-show="!editing.athmosphere">
                                                {{ $culture->athmosphere ?? 'N/A' }}
                                            </span>
                                            @if($canEdit)
                                                <div x-show="editing.athmosphere" class="inline-flex items-center space-x-2">
                                                    <div class="relative min-w-[150px]">
                                                        <input type="text" wire:model="editingValues.athmosphere" 
                                                            x-ref="athmosphere_input"
                                                            x-init="$nextTick(() => $refs.athmosphere_input.focus())"
                                                            value="{{ $culture->athmosphere }}"
                                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                                            autocomplete="off"
                                                            list="athmosphere-list">
                                                        <datalist id="athmosphere-list">
                                                            @foreach($existingAtmospheres as $atmosphere)
                                                                <option value="{{ $atmosphere }}">
                                                                    {{ $atmosphere }}
                                                                </option>
                                                            @endforeach
                                                        </datalist>
                                                        <div
                                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                            <i class="fas fa-search text-gray-400"></i>
                                                        </div>
                                                    </div>
                                                    <button wire:click="saveEdit('athmosphere')" 
                                                            class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="cancelEdit('athmosphere')" 
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
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Date Cultured</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="inline-edit" wire:key="date_cultured_{{ $culture->id }}">
                                            <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 transition-colors' : '' }}" 
                                                  @if($canEdit) wire:click="startEdit('date_cultured')" @endif
                                                  x-show="!editing.date_cultured">
                                                {{ $culture->date_cultured ? $culture->date_cultured->format('M d, Y') : 'N/A' }}
                                            </span>
                                            @if($canEdit)
                                                <div x-show="editing.date_cultured" class="inline-flex items-center space-x-2">
                                                    <input type="date" 
                                                           wire:model="editingValues.date_cultured" 
                                                           class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                           x-ref="date_cultured_input"
                                                           x-init="$nextTick(() => $refs.date_cultured_input.focus())">
                                                    <button wire:click="saveEdit('date_cultured')" 
                                                            class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="cancelEdit('date_cultured')" 
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
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Discard status</dt>
                                    <dd class="text-sm text-gray-900">
                                        @if($canEdit)
                                            <div class="flex flex-wrap items-center gap-3">
                                                <label class="inline-flex items-center gap-2">
                                                    <input type="checkbox"
                                                        @checked($culture->is_discarded)
                                                        wire:change="updateDiscarded($event.target.checked, @js($culture->date_discarded?->format('Y-m-d') ?? now()->toDateString()))"
                                                        wire:confirm="Are you sure you want to change the discard status?"
                                                        class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                                    <span>Culture discarded</span>
                                                </label>
                                                @if($culture->is_discarded)
                                                    <input type="date"
                                                        value="{{ $culture->date_discarded ? $culture->date_discarded->format('Y-m-d') : now()->toDateString() }}"
                                                        wire:change="updateDiscarded(true, $event.target.value)"
                                                        wire:confirm="Are you sure you want to edit the discard date?"
                                                        class="rounded-lg border border-gray-200 px-3 py-1.5 text-sm">
                                                @endif
                                            </div>
                                        @else
                                            @if($culture->is_discarded)
                                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">
                                                    Discarded{{ $culture->date_discarded ? ' on '.$culture->date_discarded->format('M d, Y') : '' }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Active</span>
                                            @endif
                                        @endif
                                    </dd>
                                </div>

                            </div>
                        </div>

                        <!-- Related Nucleic Acids Section -->
                        @if($culture->nucleic_acids->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-teal-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-dna text-2xl text-teal-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Related Nucleic Acids</h2>
                            </div>

                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Code
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Concentration
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date Extracted
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Status
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($culture->nucleic_acids as $na)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="/samples/nucleic/{{ $na->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $na->code }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    {{ $na->type === 'DNA' ? 'bg-blue-100 text-blue-800' : 
                                                       ($na->type === 'RNA' ? 'bg-green-100 text-green-800' : 
                                                       'bg-gray-100 text-gray-800') }}">
                                                    {{ $na->type }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $na->concentration ?? 'N/A' }} ng/μL
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $na->date_extracted ? $na->date_extracted->format('M d, Y') : 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    Available
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        @endif

                        <!-- Experiments Section -->
                        @if($culture->experiments->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6 mb-6" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-4 bg-white rounded-lg border hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-center">
                                    <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                        <i class="fa-solid fa-flask text-lg text-blue-600"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Experiments ({{ $culture->experiments->count() }})</h3>
                                </div>
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="open" x-transition class="mt-4">
                                <div class="bg-white rounded-lg border overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocol</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pathogen</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Tested</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outcome</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($culture->experiments as $experiment)
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <a href="/experiments/{{ $experiment->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                            {{ $experiment->code }}
                                                        </a>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $experiment->protocols->name ?? 'N/A' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $experiment->pathogens->species ?? 'N/A' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {{ $experiment->date_tested ? \Carbon\Carbon::parse($experiment->date_tested)->format('M d, Y') : 'N/A' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                            {{ $experiment->outcome_discrete === 'Strong positive'
                                                                ? 'bg-red-700 text-white'
                                                                : ($experiment->outcome_discrete === 'Positive'
                                                                    ? 'bg-orange-100 text-orange-800'
                                                                    : ($experiment->outcome_discrete === 'Suspect'
                                                                        ? 'bg-yellow-100 text-yellow-800'
                                                                        : ($experiment->outcome_discrete === 'Negative'
                                                                            ? 'bg-green-100 text-green-800'
                                                                            : 'bg-gray-100 text-gray-800'))) }}">
                                                            {{ $experiment->outcome_discrete ?? 'N/A' }}
                                                        </span>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Tubes Section -->
                        @if($culture->tubes->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6 mb-6" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-4 bg-white rounded-lg border hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-center">
                                    <div class="bg-gray-100 p-2 rounded-lg mr-3">
                                        <i class="fa-solid fa-vial text-lg text-gray-600"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Tubes ({{ $culture->tubes->count() }})</h3>
                                </div>
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="open" x-transition class="mt-4">
                                <div class="bg-white rounded-lg border overflow-hidden">
                                    <div class="overflow-x-auto">
                                        <table class="min-w-full divide-y divide-gray-200">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">State</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Box</th>
                                                </tr>
                                            </thead>
                                                <tbody class="bg-white divide-y divide-gray-200">
                                                    @foreach($culture->tubes as $tube)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <a href="/bank/tubes/{{ $tube->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                                {{ $tube->code }}
                                                            </a>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            {{ $tube->purpose ?? 'N/A' }}
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap">
                                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                                {{ $tube->preservant === 'Available' ? 'bg-green-100 text-green-800' : 
                                                                ($tube->preservant === 'Used' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800') }}">
                                                                {{ $tube->preservant ?? 'N/A' }}
                                                            </span>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                            @if($tube->tube_positions && $tube->tube_positions->first() && $tube->tube_positions->first()->boxes)
                                                            <a href="/bank/boxes/{{ $tube->tube_positions->first()->boxes->id }}/contents" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                                {{ $tube->tube_positions->first()->boxes->code }}
                                                            </a>
                                                            @else
                                                                No box assigned
                                                            @endif
                                                        </td>
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($culture->nucleic_acids->count() === 0 && $culture->experiments->count() === 0 && $culture->tubes->count() === 0)
                            <div class="bg-gray-50 rounded-xl p-6">
                                <div class="text-center py-8 text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                    </svg>
                                    <p class="mt-2">No related data available yet</p>
                                </div>
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
                                    <dt class="text-sm font-medium text-gray-500 mb-2">Cultured By</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="flex items-center space-x-3 bg-white p-3 rounded-lg border">
                                            <x-people-logo :person="$culture->people" width="40" />
                                            <div>
                                                <a href="/profile/{{ $culture->people->id }}"
                                                    class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $culture->people->title . ' ' . $culture->people->first_name . ' ' . $culture->people->last_name ?? 'N/A' }}
                                                </a>
                                                @if ($culture->people->email)
                                                    <p class="text-xs text-gray-500">{{ $culture->people->email }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-2">Cultured At</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="bg-white p-3 rounded-lg border">
                                            <div class="font-medium">{{ $culture->laboratories->name ?? 'N/A' }}</div>
                                            @if ($culture->laboratories->countries->name)
                                                <div class="text-xs text-gray-500">{{ $culture->laboratories->countries->name }}
                                                </div>
                                            @endif
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- Full-width Photo Gallery -->
                <div class="px-8 pb-8 border-t border-gray-100">
                    <div class="bg-gradient-to-br from-gray-50 via-white to-orange-50 rounded-2xl p-8 shadow-inner border border-gray-100">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div class="bg-orange-100 p-3 rounded-xl shadow-sm">
                                    <svg class="w-7 h-7 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">Observation Photos</h2>
                                    <p class="text-sm text-gray-500">Browse culture images with observation dates, notes, and comments.</p>
                                </div>
                            </div>
                            <div class="flex flex-col items-start gap-2 lg:items-end">
                                @php
                                    $cultureObservations = $culture->observations ?? collect();
                                    $photoTotal = $cultureObservations->count() ?: ($culture->photo_path ? 1 : 0);
                                @endphp
                                @if($photoTotal > 0)
                                    <span class="inline-flex items-center rounded-full bg-orange-100 px-4 py-1.5 text-sm font-semibold text-orange-800">
                                        {{ $photoTotal }} {{ Str::plural('photo', $photoTotal) }}
                                    </span>
                                @endif
                                @if($canEdit)
                                    @if(!$photo)
                                        <div class="text-left lg:text-right">
                                            <label for="photo-upload"
                                                class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700">
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                                                </svg>
                                                Add observation photo
                                            </label>
                                            <input type="file" id="photo-upload" class="hidden"
                                                accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf"
                                                wire:model="photo" wire:loading.attr="disabled"
                                                x-on:photo-uploaded.window="$el.value = ''"
                                                x-on:photo-cancelled.window="$el.value = ''">
                                            <p class="mt-1 text-xs text-gray-500">Max 50MB · JPG, PNG, WEBP, TIFF, PDF</p>
                                        </div>
                                    @else
                                        <div class="w-full min-w-[280px] rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                                            <div class="grid grid-cols-1 gap-3">
                                                @if($canEditPhotoDates)
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Observed by</label>
                                                        <select wire:model="photoObserverPeopleId"
                                                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                                            <option value="">Current user (default)</option>
                                                            @foreach($observerPeople as $person)
                                                                <option value="{{ $person->id }}">
                                                                    {{ trim(($person->title ?? '').' '.($person->first_name ?? '').' '.($person->last_name ?? '')) }}
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div>
                                                        <label class="mb-1 block text-xs font-semibold text-gray-600">Date of observation</label>
                                                        <input type="date" wire:model="photoObservedAt"
                                                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                                    </div>
                                                @endif
                                                <div>
                                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Notes</label>
                                                    <textarea wire:model="photoNotes" rows="2"
                                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                                        placeholder="Observation notes (optional)"></textarea>
                                                </div>
                                            </div>
                                            <div class="mt-3 flex flex-wrap gap-2">
                                                <button wire:click="uploadPhoto"
                                                    class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-green-700">
                                                    Upload photo
                                                </button>
                                                <button wire:click="cancelPhotoSelection"
                                                    class="inline-flex items-center gap-2 rounded-xl bg-gray-700 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-gray-800">
                                                    Cancel
                                                </button>
                                            </div>
                                        </div>
                                    @endif
                                @endif
                            </div>
                        </div>

                        @php
                            $activeObservation = $cultureObservations[$activePhotoIndex] ?? $cultureObservations->first();
                            $activePhoto = $activeObservation?->photo;
                            $activePhotoPath = $activePhoto?->photo_path ?: $culture->photo_path;
                            $activePhotoUrl = $activePhotoPath ? Storage::url($activePhotoPath) : null;
                            $activePhotoExt = $activePhotoPath ? strtolower(pathinfo($activePhotoPath, PATHINFO_EXTENSION)) : null;
                            $activePhotoIsPreviewable = in_array($activePhotoExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                        @endphp

                        @if($activePhotoUrl)
                            <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-orange-950 shadow-2xl ring-1 ring-black/10">
                                <div class="relative aspect-[16/9] min-h-[320px] sm:min-h-[420px] lg:min-h-[520px]">
                                    @if($activePhotoIsPreviewable)
                                        <img wire:key="culture-gallery-{{ $activePhotoIndex }}-{{ $activeObservation?->id ?? 'legacy' }}"
                                            src="{{ $activePhotoUrl }}"
                                            alt="Culture observation photo"
                                            class="absolute inset-0 h-full w-full object-contain transition-all duration-500 ease-out">
                                    @else
                                        <div class="absolute inset-0 flex items-center justify-center p-8">
                                            <a href="{{ $activePhotoUrl }}" target="_blank"
                                                class="rounded-xl border border-white/20 bg-white/10 px-6 py-4 text-center text-white backdrop-blur-sm hover:bg-white/20 transition-colors">
                                                File uploaded ({{ strtoupper((string) $activePhotoExt) }}) — click to open
                                            </a>
                                        </div>
                                    @endif

                                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 pt-16">
                                        <div class="flex flex-col gap-3 text-white sm:flex-row sm:items-end sm:justify-between">
                                            <div class="pointer-events-auto">
                                                @if($canEditPhotoDates && $activeObservation)
                                                    <label class="text-xs font-semibold uppercase tracking-wider text-orange-200">Observed</label>
                                                    <input type="date"
                                                        wire:model.blur="editingPhotoObservedAt"
                                                        class="mt-1 block rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-sm text-white backdrop-blur-sm focus:border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-400">
                                                    <label class="mt-3 block text-xs font-semibold uppercase tracking-wider text-orange-200">Observed by</label>
                                                    <select wire:model.live="editingObserverPeopleId"
                                                        class="mt-1 block w-full max-w-md rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-sm text-white backdrop-blur-sm focus:border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-400">
                                                        <option value="">Unknown</option>
                                                        @foreach($observerPeople as $person)
                                                            <option value="{{ $person->id }}" class="text-gray-900">
                                                                {{ trim(($person->title ?? '').' '.($person->first_name ?? '').' '.($person->last_name ?? '')) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                @elseif($activeObservation?->observed_at)
                                                    <p class="text-xs font-semibold uppercase tracking-wider text-orange-200">Observed</p>
                                                    <p class="text-lg font-semibold">{{ $activeObservation->observed_at->format('M d, Y') }}</p>
                                                    @if($activeObservation->people)
                                                        <p class="mt-1 text-sm text-white/80">
                                                            by {{ trim(($activeObservation->people->title ?? '').' '.($activeObservation->people->first_name ?? '').' '.($activeObservation->people->last_name ?? '')) }}
                                                        </p>
                                                    @endif
                                                @endif
                                                @if($canEditPhotoDates && $activeObservation)
                                                    <label class="mt-3 block text-xs font-semibold uppercase tracking-wider text-orange-200">Notes</label>
                                                    <textarea
                                                        rows="2"
                                                        placeholder="Observation notes (optional)"
                                                        wire:model.blur="editingPhotoNotes"
                                                        class="mt-1 block w-full max-w-2xl rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm text-white placeholder-white/50 backdrop-blur-sm focus:border-orange-300 focus:outline-none focus:ring-2 focus:ring-orange-400"></textarea>
                                                @elseif($activeObservation?->notes)
                                                    <p class="mt-2 max-w-2xl text-sm text-white/90">{{ $activeObservation->notes }}</p>
                                                @endif
                                            </div>
                                            @if($cultureObservations->count() > 1)
                                                <p class="text-sm font-medium text-white/80">
                                                    {{ $activePhotoIndex + 1 }} / {{ $cultureObservations->count() }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>

                                    @if($cultureObservations->count() > 1)
                                        <button type="button" wire:click="previousPhoto"
                                            class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-3 text-white backdrop-blur-sm transition hover:bg-black/70 hover:scale-105">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                            </svg>
                                        </button>
                                        <button type="button" wire:click="nextPhoto"
                                            class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-3 text-white backdrop-blur-sm transition hover:bg-black/70 hover:scale-105">
                                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                            </svg>
                                        </button>
                                    @endif

                                    @if($canEdit && $activeObservation)
                                        <button type="button" wire:click="deleteObservation({{ $activeObservation->id }})"
                                            wire:confirm="Are you sure you want to delete this observation?"
                                            class="absolute right-4 top-4 rounded-full bg-red-600/90 p-2.5 text-white shadow-lg transition hover:bg-red-700">
                                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if($cultureObservations->count() > 1)
                                <div class="mt-5 flex gap-3 overflow-x-auto pb-2">
                                    @foreach($cultureObservations as $index => $thumbObservation)
                                        @php
                                            $thumbPhoto = $thumbObservation->photo;
                                            $thumbUrl = $thumbPhoto ? Storage::url($thumbPhoto->photo_path) : null;
                                        @endphp
                                        @if($thumbUrl)
                                        <button type="button" wire:click="showPhotoAt({{ $index }})"
                                            class="group relative flex-shrink-0 overflow-hidden rounded-xl transition-all duration-200 {{ $activePhotoIndex === $index ? 'ring-2 ring-orange-500 ring-offset-2 scale-105' : 'opacity-70 hover:opacity-100 hover:scale-105' }}">
                                            <img src="{{ $thumbUrl }}" alt="Thumbnail {{ $index + 1 }}"
                                                class="h-20 w-28 object-cover sm:h-24 sm:w-32">
                                            @if($thumbObservation->observed_at)
                                                <span class="absolute inset-x-0 bottom-0 bg-black/60 px-1 py-0.5 text-[10px] text-white">
                                                    {{ $thumbObservation->observed_at->format('Y-m-d') }}
                                                </span>
                                            @endif
                                        </button>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            @if($activeObservation && $activeObservation->id)
                                @php
                                    $photoCommentCount = $activeObservation->comments->sum(fn ($comment) => 1 + $comment->replies->count());
                                @endphp
                                <div class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                                    <div class="flex items-center justify-between gap-3 mb-4">
                                        <h3 class="text-sm font-semibold text-gray-900">Observation comments</h3>
                                        <span class="text-xs text-gray-500">{{ $photoCommentCount }} comment{{ $photoCommentCount === 1 ? '' : 's' }}</span>
                                    </div>

                                    <div class="space-y-3 max-h-80 overflow-y-auto">
                                        @forelse($activeObservation->comments as $comment)
                                            @php
                                                $commentPerson = $comment->user?->people;
                                                $commentName = $commentPerson
                                                    ? trim(($commentPerson->title ?? '').' '.($commentPerson->first_name ?? '').' '.($commentPerson->last_name ?? ''))
                                                    : ($comment->user?->email ?? 'User');
                                            @endphp
                                            <div wire:key="photo-comment-{{ $comment->id }}" class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                                                <div class="flex items-center justify-between gap-2">
                                                    <span class="text-sm font-medium text-gray-900">{{ $commentName }}</span>
                                                    <span class="text-xs text-gray-500">{{ $comment->created_at?->diffForHumans() }}</span>
                                                </div>
                                                <p class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $comment->body }}</p>
                                                @if($canCommentOnPhotos)
                                                    <button type="button" wire:click="toggleReplyForm({{ $comment->id }})"
                                                        class="mt-2 text-xs font-semibold text-blue-600 hover:text-blue-800">
                                                        Reply
                                                    </button>
                                                @endif

                                                @if($comment->replies->isNotEmpty())
                                                    <div class="mt-3 space-y-2 border-l-2 border-gray-200 pl-3">
                                                        @foreach($comment->replies as $reply)
                                                            @php
                                                                $replyPerson = $reply->user?->people;
                                                                $replyName = $replyPerson
                                                                    ? trim(($replyPerson->title ?? '').' '.($replyPerson->first_name ?? '').' '.($replyPerson->last_name ?? ''))
                                                                    : ($reply->user?->email ?? 'User');
                                                            @endphp
                                                            <div wire:key="photo-reply-{{ $reply->id }}" class="rounded-md bg-white p-2">
                                                                <div class="flex items-center justify-between gap-2">
                                                                    <span class="text-xs font-medium text-gray-900">{{ $replyName }}</span>
                                                                    <span class="text-[11px] text-gray-500">{{ $reply->created_at?->diffForHumans() }}</span>
                                                                </div>
                                                                <p class="mt-1 whitespace-pre-wrap text-sm text-gray-700">{{ $reply->body }}</p>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                @if($canCommentOnPhotos && ($showReplyForm[$comment->id] ?? false))
                                                    <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50/50 p-3">
                                                        <textarea
                                                            wire:model.defer="replyPhotoComments.{{ $comment->id }}"
                                                            rows="2"
                                                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                                            placeholder="Write a reply..."></textarea>
                                                        <div class="mt-2 flex gap-2">
                                                            <button type="button" wire:click="addObservationComment({{ $activeObservation->id }}, {{ $comment->id }})"
                                                                class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">
                                                                Post reply
                                                            </button>
                                                            <button type="button" wire:click="toggleReplyForm({{ $comment->id }})"
                                                                class="inline-flex items-center rounded-lg bg-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-300">
                                                                Cancel
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <p class="text-sm text-gray-500">No comments yet for this photo.</p>
                                        @endforelse
                                    </div>

                                    @if($canCommentOnPhotos)
                                        <div class="mt-4 border-t border-gray-100 pt-4">
                                            <label class="mb-1 block text-xs font-semibold text-gray-600">Add a comment</label>
                                            <textarea
                                                wire:model.defer="newPhotoComments.{{ $activeObservation->id }}"
                                                rows="3"
                                                class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                                placeholder="Write a comment about this observation photo..."></textarea>
                                            <button type="button" wire:click="addObservationComment({{ $activeObservation->id }})"
                                                class="mt-2 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                                                Post comment
                                            </button>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @else
                            <div class="flex aspect-[16/9] min-h-[280px] items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-white/80">
                                <div class="text-center">
                                    <svg class="mx-auto h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    <p class="mt-3 text-sm font-medium text-gray-600">No photos uploaded yet</p>
                                </div>
                            </div>
                        @endif

                        @if($uploadError)
                            <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $uploadError }}</div>
                        @endif

                        <x-upload-progress wireModel="photo" class="mt-4" />
                    </div>
                </div>
            </div>
        @endif
    </div>
</div> 