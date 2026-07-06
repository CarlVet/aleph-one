<div>
@if(!$canView)
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
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
                        <a href="/samples/parasites/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Parasites List
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
@else
<div data-profile-tables class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8"
    x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success!', text: $event.detail.message, timer: 2000, showConfirmButton: false }); }"
    x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error!', text: $event.detail.message, confirmButtonColor: '#d33' }); }">
    <div class="px-4 py-6 sm:px-0">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-purple-900 to-purple-800 rounded-t-xl shadow-lg">
            <div class="px-6 py-8">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="bg-white/20 p-3 rounded-lg">
                                <i class="fa-solid fa-spider text-2xl text-white"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">Parasite Details</h1>
                                <p class="text-purple-100 text-lg">Code: {{ $parasite->code }}</p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center space-x-4">
                            <span class="text-purple-100 text-sm">
                                {{ $parasite->parasite_species->name_scientific ?? 'N/A' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <a href="/samples/parasites/list"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to List
                        </a>
                        @if(!$canEdit)
                            <span class="inline-flex items-center px-3 py-2 bg-yellow-100 text-yellow-800 text-sm font-medium rounded-lg">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                </svg>
                                View Only
                            </span>
                        @endif
                        @if($canEdit)
                            <button wire:click="deleteParasite"
                                    wire:confirm="Are you sure you want to delete this parasite? This action cannot be undone."
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

                    <!-- Parasite Information Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                <i class="fa-solid fa-spider text-2xl text-purple-600"></i>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Parasite Information</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Species</dt>
                                <dd class="text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        {{ $parasite->parasite_species->name_scientific ?? 'N/A' }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Family</dt>
                                <dd class="text-sm text-gray-900">
                                    <span class="inline-flex items-center text-xs font-medium">
                                        {{ $parasite->parasite_species->family ?? 'N/A' }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Order</dt>
                                <dd class="text-sm text-gray-900">
                                    <span class="inline-flex items-center text-xs font-medium">
                                        {{ $parasite->parasite_species->order ?? 'N/A' }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Stage</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="stage_{{ $parasite->id }}">
                                        @if($editingField !== 'stage')
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('stage')" @endif>
                                            {{ $parasite->stage ?? 'N/A' }}
                                        </span>
                                        @elseif($canEdit)
                                            <div class="inline-flex items-center space-x-2">
                                                <select wire:model="editingValues.stage"
                                                        class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                                    <option value="">—</option>
                                                    @foreach ($stageOptions as $option)
                                                        <option value="{{ $option }}">{{ $option === 'NA' ? 'N/A' : $option }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" wire:click="saveEdit('stage')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button type="button" wire:click="cancelEdit('stage')"
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Sex</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="sex_{{ $parasite->id }}">
                                        @if($editingField !== 'sex')
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('sex')" @endif>
                                            {{ $parasite->sex ?? 'N/A' }}
                                        </span>
                                        @elseif($canEdit)
                                            <div class="inline-flex items-center space-x-2">
                                                <select wire:model="editingValues.sex"
                                                        class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                                    <option value="">—</option>
                                                    @foreach ($sexOptions as $option)
                                                        <option value="{{ $option }}">{{ $option === 'NA' ? 'N/A' : $option }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" wire:click="saveEdit('sex')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button type="button" wire:click="cancelEdit('sex')"
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">State</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="state_{{ $parasite->id }}">
                                        @if($editingField !== 'state')
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('state')" @endif>
                                            {{ $parasite->state ?? 'N/A' }}
                                        </span>
                                        @elseif($canEdit)
                                            <div class="inline-flex items-center space-x-2">
                                                <select wire:model="editingValues.state"
                                                        class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                                    <option value="">—</option>
                                                    @foreach ($stateOptions as $option)
                                                        <option value="{{ $option }}">{{ $option === 'NA' ? 'N/A' : $option }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" wire:click="saveEdit('state')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button type="button" wire:click="cancelEdit('state')"
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Status</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="status_{{ $parasite->id }}">
                                        @if($editingField !== 'status')
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('status')" @endif>
                                            <x-parasites.status-badge :status="$parasite->status" />
                                        </span>
                                        @elseif($canEdit)
                                            <div class="inline-flex items-center space-x-2">
                                                <select wire:model="editingValues.status"
                                                        class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                                    @foreach (($statusOptions ?? []) as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="button" wire:click="saveEdit('status')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button type="button" wire:click="cancelEdit('status')"
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Date identified</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="date_identified_{{ $parasite->id }}">
                                        @if($editingField !== 'date_identified')
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('date_identified')" @endif>
                                            {{ $parasite->date_identified ? ($parasite->date_identified instanceof \DateTimeInterface ? $parasite->date_identified->format('Y-m-d') : \Carbon\Carbon::parse($parasite->date_identified)->format('Y-m-d')) : 'N/A' }}
                                        </span>
                                        @elseif($canEdit)
                                            <div class="inline-flex items-center space-x-2">
                                                <input type="date" wire:model="editingValues.date_identified"
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                                <button type="button" wire:click="saveEdit('date_identified')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button type="button" wire:click="cancelEdit('date_identified')"
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Collected from</dt>
                                <dd class="text-sm text-gray-900">
                                    <span class="inline-flex items-center text-xs font-medium">
                                        @if($parasite->parasites_origin_type === 'App\Models\HumanSamples')
                                        <a href="/samples/humans/{{ $parasite->parasites_origin->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $parasite->parasites_origin->code . ' - ' . $parasite->parasites_origin->humans->first_name . ' ' . $parasite->parasites_origin->humans->last_name  }}
                                        </a>
                                        @elseif($parasite->parasites_origin_type === 'App\Models\AnimalSamples')
                                        <a href="/samples/animals/{{ $parasite->parasites_origin->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                        {{ $parasite->parasites_origin?->code ?? 'N/A' }} - {{ $parasite->parasites_origin?->animals?->animal_species?->name_common ?? 'N/A' }}
                                        </a>
                                        @elseif($parasite->parasites_origin_type === 'App\Models\EnvironmentSamples')
                                        <a href="/samples/environment/{{ $parasite->parasites_origin->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                        {{ $parasite->parasites_origin->code . ' - ' . $parasite->parasites_origin->environment_sample_types->name  }}
                                        </a>
                                        @endif
                                    </span>
                                </dd>
                            </div>

                        </div>
                    </div>

                    <!-- Related Samples Section -->
                    @if($parasite->parasite_samples->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-vial text-2xl text-blue-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Related Samples ({{ $parasite->parasite_samples->count() }})</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Sample Code
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Sample Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date Processed
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Processed By
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Laboratory
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Associated Tubes
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($parasite->parasite_samples as $sample)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="/samples/parasites/{{ $sample->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                {{ $sample->code }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                @php
                                                    // Avoid lazy loading + avoid "Undefined array key" when not loaded
                                                    $sampleTypeRel = $sample->getRelations()['parasite_sample_types'] ?? null;
                                                @endphp
                                                {{ data_get($sampleTypeRel, 'name') ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $sample->date_processed ? $sample->date_processed->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @php
                                                $processor = $sample->getRelations()['people'] ?? null;
                                                $processorName = trim(implode(' ', array_filter([
                                                    data_get($processor, 'title'),
                                                    data_get($processor, 'first_name'),
                                                    data_get($processor, 'last_name'),
                                                ])));
                                            @endphp
                                            {{ $processorName !== '' ? $processorName : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ data_get($sample->getRelations()['laboratories'] ?? null, 'name') ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            @php
                                                $sampleTubes = $sample->getRelations()['tubes'] ?? collect();
                                            @endphp
                                            @if($sampleTubes->count() > 0)
                                                <ul class="space-y-2">
                                                    @foreach($sampleTubes as $tube)
                                                        <li>
                                                            <div>
                                                                <a href="/bank/tubes/{{ $tube->code }}"
                                                                    class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                                    {{ $tube->code ?? 'N/A' }}
                                                                </a>
                                                                @if($isGuestMode && $tube->projects?->code)
                                                                    <span class="text-gray-500">
                                                                        (
                                                                        <a href="{{ route('projects.profile', $tube->projects->code) }}"
                                                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                                            {{ $tube->projects->code }}
                                                                        </a>
                                                                        )
                                                                    </span>
                                                                @endif
                                                            </div>
                                                            @if($tube->alias_code)
                                                                <span class="inline-block text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded mt-1">
                                                                    Alias: {{ $tube->alias_code }}
                                                                </span>
                                                            @endif
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <span class="text-gray-500 italic">No tubes associated</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Parasite Origin Characteristics Section -->
                    @if($parasite->parasites_origin)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Origin Characteristics</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }"
                                 fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            @if($parasite->parasites_origin_type === 'App\Models\AnimalSamples')
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Origin Type</dt>
                                    <dd class="text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            Animal Sample
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Animal Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/samples/animals/{{ $parasite->parasites_origin->code ?? '' }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $parasite->parasites_origin->code ?? 'N/A' }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Animal Species</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ data_get($parasite->parasites_origin, 'animals.animal_species.name_common')
                                            ?? data_get($parasite->parasites_origin, 'animals.animal_species.name')
                                            ?? 'N/A' }}
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sample Type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $parasite->parasites_origin->sample_types->name ?? 'N/A' }}
                                    </dd>
                                </div>
                            @elseif($parasite->parasites_origin_type === 'App\Models\HumanSamples')
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Origin Type</dt>
                                    <dd class="text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                            Human Sample
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Human Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/samples/humans/{{ $parasite->parasites_origin->code ?? '' }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $parasite->parasites_origin->code ?? 'N/A' }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sample Type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $parasite->parasites_origin->sample_types->name ?? 'N/A' }}
                                    </dd>
                                </div>
                            @elseif($parasite->parasites_origin_type === 'App\Models\EnvironmentSamples')
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Origin Type</dt>
                                    <dd class="text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            Environment Sample
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Environment Code</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/samples/environment/{{ $parasite->parasites_origin->code ?? '' }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $parasite->parasites_origin->code ?? 'N/A' }}
                                        </a>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sample Type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        @if($parasite->parasites_origin_type === 'App\Models\EnvironmentSamples')
                                            {{ $parasite->parasites_origin->environment_sample_types->name ?? 'N/A' }}
                                        @elseif($parasite->parasites_origin_type === 'App\Models\AnimalSamples')
                                            {{ $parasite->parasites_origin->sample_types->name ?? 'N/A' }}
                                        @elseif($parasite->parasites_origin_type === 'App\Models\HumanSamples')
                                            {{ $parasite->parasites_origin->sample_types->name ?? 'N/A' }}
                                        @else
                                            N/A
                                        @endif
                                    </dd>
                                </div>
                            @endif

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Collection Site</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $parasite->parasites_origin->sampling_sites->name ?? 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Date Collected</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $parasite->parasites_origin->date_collected ? \Carbon\Carbon::parse($parasite->parasites_origin->date_collected)->format('M d, Y') : 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Collected By</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="flex items-center space-x-2">
                                        <x-people-logo :person="$parasite->parasites_origin->people" width="24" />
                                        <a href="/profile/{{ $parasite->parasites_origin->people->id ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                            {{ $parasite->parasites_origin->people->title . ' ' . $parasite->parasites_origin->people->first_name . ' ' . $parasite->parasites_origin->people->last_name ?? 'N/A' }}
                                        </a>
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Experiments Results Section -->
                    @if($sampleExperiments->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button type="button" @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-flask text-2xl text-blue-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Experiments results ({{ $sampleExperiments->count() }})</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition>
                            <p class="text-xs text-gray-500 mb-3">All experiments conducted on this parasite, its samples and on samples derived from them.</p>
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
                                        @foreach($sampleExperiments as $experiment)
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
                                <dt class="text-sm font-medium text-gray-500 mb-2">Identified by</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="flex items-center space-x-3 bg-white p-3 rounded-lg border">
                                        <x-people-logo :person="$parasite->people" width="40" />
                                        <div>
                                            <a href="/profile/{{ $parasite->people->id }}"
                                                class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                {{ $parasite->people->title . ' ' . $parasite->people->first_name . ' ' . $parasite->people->last_name ?? 'N/A' }}
                                            </a>
                                            @if ($parasite->people->email)
                                                <p class="text-xs text-gray-500">{{ $parasite->people->email }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-2">Identified at</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="bg-white p-3 rounded-lg border">
                                        <div class="font-medium">{{ data_get($parasite->getRelations()['laboratories'] ?? null, 'name') ?? 'N/A' }}</div>
                                        @if (data_get($parasite->getRelations()['laboratories'] ?? null, 'countries.name'))
                                            <div class="text-xs text-gray-500">{{ data_get($parasite->getRelations()['laboratories'] ?? null, 'countries.name') }}
                                            </div>
                                        @endif
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

            <div class="px-8 pb-8 border-t border-gray-100">
                @include('partials.parasite-observation-gallery', [
                    'owner' => $parasite,
                    'observations' => $galleryObservations,
                    'legacyPhotoPath' => $parasite->photo_path,
                ])
            </div>
        </div>
    </div>
</div>
@endif
</div>