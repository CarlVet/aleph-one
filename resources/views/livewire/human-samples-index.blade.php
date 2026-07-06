<div class="text-center mt-2">
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing human samples with public tubes from all projects</p>
                </div>
                <a href="/my-projects" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif

    <!-- Create, Edit, Dashboard (Centered) -->
    <div class="text-center flex justify-center space-x-4 mt-6">
        @if (!$isGuestMode)
        @if(!$canEdit)
        <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
            <i class="fas fa-lock mr-2"></i>
            Create (Viewer)
        </div>
        @else
            <a href="/samples/humans/create"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                Create
            </a>
        @endif
            @if ($isEditing)
                <a href="/samples/humans/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            @else
                @if(!$canEdit)
                    <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                        <i class="fas fa-lock mr-2"></i>
                        Edit (Viewer)
                    </div>
                @else
                    <button wire:click="toggleEditMode"
                        class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                        <i class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Edit
                    </button>
                @endif
            @endif
        @else
            <div class="flex items-center space-x-4">
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Project Mode)
                </div>
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Edit (Project Mode)
                </div>
            </div>
        @endif
        <a href="/samples/humans/dashboard"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
            Dashboard
        </a>
        @if ($isGuestMode)
            <a href="/tube-requests"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-indigo-600">
                <i class="fas fa-handshake mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                My Requests
            </a>
        @endif
    </div>

    <!-- Table Section -->
    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        @php
            $showBulkActions = !$isGuestMode && $canEdit;
            $selectedItemsCount = collect($selectedHumanSamples ?? [])
                ->filter(fn($checked) => (bool) $checked)
                ->count();
        @endphp
        <div class="flex flex-col items-center w-full p-4">
            <!-- Index Title (Centered) -->
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                @if ($isGuestMode)
                    <i class="fas fa-eye text-purple-600 mr-2"></i>
                    Public Human Samples
                @else
                    {{ $isEditing ? 'Edit Human Samples' : 'List of Human Samples' }}
                @endif
            </h2>

            <!-- Export Button (Centered) -->
            <button wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>

            @if ($showBulkActions)
                <div class="mt-2 w-full flex items-center justify-center gap-2">
                    <button type="button"
                        x-on:click.prevent.stop="
                            if (!confirm('Are you sure you want to delete all selected samples?')) { return; }
                            $wire.deleteSelected();
                        "
                        class="inline-flex items-center justify-center h-9 w-9 rounded-md border border-red-400 bg-red-600 text-white hover:bg-red-700 transition-colors"
                        title="Delete checked samples">
                        <i class="fas fa-trash"></i>
                    </button>
                    <span class="text-xs font-medium text-gray-600">
                        {{ $selectedItemsCount }} selected
                    </span>
                </div>
            @endif
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $samples])


        <div class="index-table-container overflow-x-auto">
        <table id="human_samples_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                    @if ($showBulkActions)
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Select</th>
                    @endif
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200"><x-sort-button field="code" :active="$sortField" :direction="$sortDirection">Sample Code</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200"><x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">Sub-project</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Associated tubes</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200"><x-sort-button field="patient" :active="$sortField" :direction="$sortDirection">Patient Code</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200"><x-sort-button field="sample_type" :active="$sortField" :direction="$sortDirection">Sample Type</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200"><x-sort-button field="collection_date" :active="$sortField" :direction="$sortDirection">Collection Date</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 index-people-cell"><x-sort-button field="collector" :active="$sortField" :direction="$sortDirection">Collector</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200"><x-sort-button field="sampling_site" :active="$sortField" :direction="$sortDirection">Sampling Site</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Area</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Coordinates</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200"><x-sort-button field="purpose" :active="$sortField" :direction="$sortDirection">Purpose</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200"><x-sort-button field="storage_state" :active="$sortField" :direction="$sortDirection">Storage State</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200"><x-sort-button field="processed" :active="$sortField" :direction="$sortDirection">Processed</x-sort-button></th>
                    @if ($isEditing && $canEdit)
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Photo</th>
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Delete</th>
                    @endif
                </tr>
            </thead>
            <thead class="bg-gradient-to-r from-gray-100 to-gray-50">
                <tr>
                    @if ($showBulkActions)
                        <th class="px-6 py-3 text-center">
                            <label class="inline-flex items-center gap-2 text-[11px] font-semibold text-gray-600">
                                <input type="checkbox" wire:model.live="selectAllFiltered"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    title="Select all filtered rows">
                                <span>All filtered</span>
                            </label>
                        </th>
                    @endif
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="sampleIdFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="subProjectCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="tubeCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="patientFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="sampleTypeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <div class="flex items-center space-x-2">
                            <input type="date" wire:model.live.debounce.300ms="collectionDateStart"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Start Date">
                            <span class="text-gray-500 font-medium">to</span>
                            <input type="date" wire:model.live.debounce.300ms="collectionDateEnd"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="End Date">
                        </div>
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="collectorFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="samplingSiteFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="areaFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3"></th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="purposeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="storageStateFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <select wire:model.live.debounce.300ms="processedFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All</option>
                            <option value="true">Yes</option>
                            <option value="false">No</option>
                        </select>
                    </th>
                    @if ($isEditing && $canEdit)
                        <th class="px-6 py-3"></th>
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php($currentPeopleId = (int) ($this->currentPeopleId() ?? 0))
                @if (session()->has('error'))
                    <tr>
                        <td colspan="20" class="px-6 py-4 text-red-500 text-center bg-red-50 border-l-4 border-red-500">
                            {{ session('error') }}
                        </td>
                    </tr>
                @endif
                @if (session()->has('message'))
                    <tr>
                        <td colspan="20" class="px-6 py-4 text-green-500 text-center bg-green-50 border-l-4 border-green-500">
                            {{ session('message') }}
                        </td>
                    </tr>
                @endif
                @forelse ($samples as $sample)
                    <tr wire:key="{{ $sample->id }}" class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @php($canEditRow = $canEdit && (int) ($sample->people_id ?? 0) === $currentPeopleId)
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($canEditRow)
                                    <input type="checkbox" wire:model.live="selectedHumanSamples.{{ $sample->id }}"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        title="Select this sample">
                                @endif
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isGuestMode)
                                <span class="text-gray-900 font-medium">{{ $sample->code }}</span>
                            @else
                                <a href="/samples/humans/{{ $sample->code }}" class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                    {{ $sample->code }}
                                </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(optional($sample->subProjectAssignment?->subProject)->code)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                    {{ $sample->subProjectAssignment->subProject->code }}
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <!-- Existing Tubes List -->
                                <div class="space-y-2 min-w-[200px]">
                                    @foreach($sample->tubes as $tube)
                                        <div class="flex items-center space-x-2">
                                            <div class="flex flex-col">
                                                <div>
                                                @if ($isGuestMode)
                                                    <span class="text-gray-900 font-medium">{{ $tube->code ?? 'N/A' }}</span>
                                                @else
                                                    <a href="/bank/tubes/{{ $tube->code }}"
                                                       class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                        {{ $tube->code ?? 'N/A' }}
                                                    </a>
                                                @endif
                                            
                                                <span class="text-sm text-gray-500">
                                                    (
                                                    @if ($tube->projects?->code)
                                                        <a href="{{ route('projects.profile', $tube->projects->code) }}"
                                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                            {{ $tube->projects->code }}
                                                        </a>
                                                    @else
                                                        N/A
                                                    @endif
                                                    )
                                                </span>
                                                </div>
                                            
                                                @if ($tube->alias_code)
                                                    <span class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded mt-1">
                                                        Alias: {{ $tube->alias_code }}
                                                    </span>
                                                @endif
                                            </div>
                                            <button 
                                                type="button"
                                                wire:click="removeTube({{ $tube->id }})"
                                                wire:confirm="Are you sure you want to remove this tube?"
                                                class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                    @endforeach
                                    
                                    @if($sample->tubes->count() === 0)
                                        <span class="text-sm text-gray-500 italic">No tubes associated</span>
                                    @endif
                                </div>
                            @else
                                <ul>
                                    @foreach($sample->tubes as $tube)
                                        <li class="flex items-center space-x-2 mb-1">
                                            <div class="flex flex-col">
                                                <div>
                                                @if ($isGuestMode)
                                                    <span class="text-gray-900 font-medium">{{ $tube->code ?? 'N/A' }}</span>
                                                @else
                                                    <a href="/bank/tubes/{{ $tube->code }}"
                                                       class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                        {{ $tube->code ?? 'N/A' }}
                                                    </a>
                                                @endif
                                            
                                                <span class="text-sm text-gray-500">
                                                    (
                                                    @if ($tube->projects?->code)
                                                        <a href="{{ route('projects.profile', $tube->projects->code) }}"
                                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                            {{ $tube->projects->code }}
                                                        </a>
                                                    @else
                                                        N/A
                                                    @endif
                                                    )
                                                </span>
                                                </div>
                                            
                                                @if ($tube->alias_code)
                                                    <span class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded mt-1">
                                                        Alias: {{ $tube->alias_code }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($isGuestMode)
                                                <!-- DEBUG: Guest mode is active -->
                                                <button 
                                                    type="button"
                                                    wire:key="tube-request-{{ $tube->id }}"
                                                    wire:click="openTubeRequestModal({{ $tube->id }})"
                                                    onclick="console.log('Button clicked for tube {{ $tube->id }}')"
                                                    class="text-indigo-500 hover:text-indigo-700 transition-colors duration-200"
                                                    title="Request access to this tube">
                                                    <i class="fas fa-handshake text-sm"></i>
                                                </button>
                                            @else
                                                <!-- DEBUG: Not in guest mode -->
                                            @endif
                                        </li>
                                    @endforeach
                                    @if($sample->tubes->count() === 0)
                                        <span class="text-gray-500 italic">No tubes associated</span>
                                    @endif
                                </ul>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isGuestMode)
                                <span class="text-gray-900 font-medium">{{ $sample->humans->code }}</span>
                            @else
                                <a href="/humans/{{ $sample->humans->code }}" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                    {{ $sample->humans->code }}
                                </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="sample_type" name="sample_type"
                                    wire:change="updateField({{ $sample->id }}, 'sample_type', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Sample Type?"
                                    class="w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($sampleTypes as $type)
                                        <option value="{{ $type->name }}"
                                            {{ $type->name === ($sample->sample_types->name ?? '') ? 'selected' : '' }}>
                                            {{ $type->name }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            @else
                                <span class="text-gray-900 font-medium">{{ $sample->sample_types->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="date" value="{{ $sample->date_collected ? $sample->date_collected->format('Y-m-d') : '' }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $sample->id }}, 'date_collected', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Collection Date?">
                            @else
                                <span class="text-gray-900 font-medium">{{ $sample->date_collected ? $sample->date_collected->format('Y-m-d') : '' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap index-people-cell">
                            <div class="flex items-center space-x-3">
                                <x-people-logo :person="$sample->people" width="30" class="rounded-full ring-2 ring-gray-100" />
                                <a href="/profile/{{$sample->people->id}}" class="text-blue-600 hover:text-blue-800 transition-colors duration-200 font-medium">
                                    {{ $sample->people->title }} {{ $sample->people->first_name }} {{ $sample->people->last_name }}
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="sampling_site" name="sampling_site"
                                    wire:change="updateField({{ $sample->id }}, 'sampling_site', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Sampling Site?"
                                    class="w-[180px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($sampling_sites as $sampling_site)
                                        <option value="{{ $sampling_site->name }}"
                                            {{ $sampling_site->name === ($sample->sampling_sites->name ?? '') ? 'selected' : '' }}>
                                            {{ $sampling_site->name }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            @else
                                <span class="text-gray-900 font-medium">{{ $sample->sampling_sites->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-gray-900 font-medium">{{ $sample->area ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-gray-900 font-medium">
                                @if($sample->latitude && $sample->longitude)
                                    {{ $sample->latitude }}, {{ $sample->longitude }}
                                @else
                                    N/A
                                @endif
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="sample_purpose" name="sample_purpose"
                                    wire:change="updateField({{ $sample->id }}, 'sample_purpose', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Sample Purpose?"
                                    class="w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="diagnostic" {{ $sample->sample_purpose === 'diagnostic' ? 'selected' : '' }}>Diagnostic</option>
                                    <option value="research" {{ $sample->sample_purpose === 'research' ? 'selected' : '' }}>Research</option>
                                    <option value="surveillance" {{ $sample->sample_purpose === 'surveillance' ? 'selected' : '' }}>Surveillance</option>
                                </x-forms.select-input>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm
                                    {{ $sample->sample_purpose === 'diagnostic' ? 'bg-gradient-to-r from-red-100 to-red-200 text-red-800' : 
                                       ($sample->sample_purpose === 'research' ? 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800' : 
                                       'bg-gradient-to-r from-green-100 to-green-200 text-green-800') }}">
                                    {{ ucfirst($sample->sample_purpose) ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="storage_state" name="storage_state"
                                    wire:change="updateField({{ $sample->id }}, 'storage_state', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Storage State?"
                                    class="w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="frozen" {{ $sample->storage_state === 'frozen' ? 'selected' : '' }}>Frozen</option>
                                    <option value="refrigerated" {{ $sample->storage_state === 'refrigerated' ? 'selected' : '' }}>Refrigerated</option>
                                    <option value="room_temperature" {{ $sample->storage_state === 'room_temperature' ? 'selected' : '' }}>Room Temperature</option>
                                </x-forms.select-input>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm
                                    {{ $sample->storage_state === 'frozen' ? 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800' : 
                                       ($sample->storage_state === 'refrigerated' ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800' : 
                                       'bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800') }}">
                                    {{ ucfirst(str_replace('_', ' ', $sample->storage_state)) ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="processed" name="processed"
                                    wire:change="updateField({{ $sample->id }}, 'processed', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Processed status?"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="true" {{ $sample->processed ? 'selected' : '' }}>Yes</option>
                                    <option value="false" {{ !$sample->processed ? 'selected' : '' }}>No</option>
                                </x-forms.select-input>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm
                                    {{ $sample->processed ? 'bg-gradient-to-r from-green-100 to-green-200 text-green-800' : 
                                       'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800' }}">
                                    {{ $sample->processed ? 'Yes' : 'No' }}
                                </span>
                            @endif
                        </td>
                        
                        @if ($isEditing && $canEditRow)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center space-x-3">
                                    <label for="photo-upload-{{ $sample->id }}" class="cursor-pointer group">
                                        <i class="fas fa-camera text-blue-500 group-hover:text-blue-600 text-xl transition-all duration-200 transform group-hover:scale-110"></i>
                                        <input type="file" 
                                            id="photo-upload-{{ $sample->id }}" 
                                            class="hidden" 
                                            accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf"
                                            wire:model.live="photo"
                                            wire:loading.attr="disabled"
                                            wire:change="uploadPhoto({{ $sample->id }})"
                                            x-data
                                            x-init="$watch('$wire.photo', value => {
                                                if (value && $wire.currentPhotoId === {{ $sample->id }}) {
                                                    $wire.uploadPhoto({{ $sample->id }});
                                                }
                                            })"
                                            x-on:change="
                                                if ($el.files[0] && $el.files[0].size > 52428800) {
                                                    alert('File size exceeds 50MB limit');
                                                    $el.value = '';
                                                    return;
                                                }
                                            "
                                            x-on:photo-uploaded.window="
                                                if ($wire.currentPhotoId === {{ $sample->id }}) {
                                                    $el.value = '';
                                                }
                                            ">
                                    </label>
                                    @if($uploadingPhotoId === $sample->id)
                                        <div wire:loading wire:target="photo" class="text-sm text-gray-500">
                                            <i class="fas fa-spinner fa-spin"></i> Uploading...
                                        </div>
                                    @endif
                                    @if(isset($uploadErrors[$sample->id]))
                                        <div class="text-sm text-red-500">
                                            {{ $uploadErrors[$sample->id] }}
                                        </div>
                                    @endif
                                    @if($sample->photo_path)
                                        <a href="{{ Storage::url($sample->photo_path) }}" 
                                           target="_blank" 
                                           class="text-green-500 hover:text-green-600 transition-all duration-200 transform hover:scale-110">
                                            <i class="fas fa-image text-xl"></i>
                                        </a>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    class="text-red-500 hover:text-red-600 transition-all duration-200 transform hover:scale-110"
                                    type="button" wire:click="delete({{ $sample->id }})"
                                    wire:confirm="Are you sure you want to delete this sample?">
                                    <i class="fas fa-trash text-xl"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="100" class="px-6 py-12 text-center">
                            <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                <span class="text-sm text-gray-600">No human samples found.</span>
                                @if (! $isGuestMode)
                                    <a href="/samples/humans/create"
                                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                                        <i class="fas fa-plus-circle"></i>
                                        Register human sample
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        
        @include('livewire.partials.index-pagination-bar', ['paginator' => $samples])

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
</div>

<!-- Tube Request Modal -->
@if ($isGuestMode)
    @if($showTubeRequestModal)
        <div wire:key="tube-request-modal-{{ $selectedTubeId }}" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="modal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Request Tube Access</h3>
                        <button wire:click="closeTubeRequestModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    @if($selectedTube)
                        <div class="mb-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Tube Details</h4>
                                <p class="text-sm text-gray-600"><strong>Code:</strong> {{ $selectedTube->code }}</p>
                                <p class="text-sm text-gray-600"><strong>Type:</strong> {{ $selectedTube->tube_type }}</p>
                                <p class="text-sm text-gray-600"><strong>Source Project:</strong> {{ $sourceProject->code ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <form>
                            <div class="mb-4">
                                <label for="targetProjectId" class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Target Project *
                                </label>
                                <select 
                                    wire:model="targetProjectId"
                                    id="targetProjectId"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    required
                                >
                                    <option value="">Choose a project...</option>
                                    @foreach($userProjects as $project)
                                        <option value="{{ $project->id }}">
                                            {{ $project->code }} - {{ $project->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('targetProjectId') 
                                    <span class="text-red-500 text-xs">{{ $message }}</span> 
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="requestMessage" class="block text-sm font-medium text-gray-700 mb-2">
                                    Request Message (Optional)
                                </label>
                                <textarea 
                                    wire:model="requestMessage"
                                    id="requestMessage"
                                    rows="3"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Explain why you need access to this tube..."
                                ></textarea>
                                @error('requestMessage') 
                                    <span class="text-red-500 text-xs">{{ $message }}</span> 
                                @enderror
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button 
                                    type="button"
                                    wire:click="closeTubeRequestModal"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="button"
                                    wire:click="submitTubeRequest"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-200"
                                >
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center">
                            <p class="text-gray-500">Tube not found.</p>
                            <button 
                                wire:click="closeTubeRequestModal"
                                class="mt-3 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200"
                            >
                                Close
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
@endif 