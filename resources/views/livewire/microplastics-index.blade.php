<div class="text-center mt-2">
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public microplastics from all projects</p>
                </div>
                <a href="/my-projects"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif

    <div class="mt-2 mb-6 flex items-center justify-center w-full relative">
        <div class="flex flex-col">
            @if (! $isGuestMode)
                <a href="/samples/microplastics"
                    class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                    <i class="fas fa-arrow-left text-2xl mr-2"></i>
                    <span class="text-sm font-medium">Back to Microplastics Home</span>
                </a>
            @endif
            <h2 class="text-xl font-bold mb-4 text-gray-700">Select content type:</h2>
            <div class="flex flex-wrap items-center justify-center gap-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                @foreach ([
                    ['table' => 'microplastics_table', 'icon' => 'fa-recycle', 'label' => 'All', 'active' => 'bg-blue-100 ring-2 ring-blue-500', 'inactive' => 'bg-gray-50 group-hover:bg-gray-100', 'activeText' => 'text-blue-600'],
                    ['table' => 'microplastics_human_table', 'icon' => 'fa-person', 'label' => 'Humans', 'active' => 'bg-rose-100 ring-2 ring-rose-500', 'inactive' => 'bg-gray-50 group-hover:bg-gray-100', 'activeText' => 'text-rose-600'],
                    ['table' => 'microplastics_animal_table', 'icon' => 'fa-paw', 'label' => 'Animals', 'active' => 'bg-orange-100 ring-2 ring-orange-500', 'inactive' => 'bg-gray-50 group-hover:bg-gray-100', 'activeText' => 'text-orange-600'],
                    ['table' => 'microplastics_environment_table', 'icon' => 'fa-leaf', 'label' => 'Environment', 'active' => 'bg-emerald-100 ring-2 ring-emerald-500', 'inactive' => 'bg-gray-50 group-hover:bg-gray-100', 'activeText' => 'text-emerald-600'],
                    ['table' => 'microplastics_parasite_table', 'icon' => 'fa-spider', 'label' => 'Parasites', 'active' => 'bg-purple-100 ring-2 ring-purple-500', 'inactive' => 'bg-gray-50 group-hover:bg-gray-100', 'activeText' => 'text-purple-600'],
                    ['table' => 'microplastics_pool_table', 'icon' => 'fa-layer-group', 'label' => 'Pools', 'active' => 'bg-cyan-100 ring-2 ring-cyan-500', 'inactive' => 'bg-gray-50 group-hover:bg-gray-100', 'activeText' => 'text-cyan-600'],
                ] as $config)
                    <button wire:click="$set('selectedTable', '{{ $config['table'] }}')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                        type="button">
                        <div class="flex flex-col items-center">
                            <div
                                class="p-3 rounded-full {{ $selectedTable === $config['table'] ? $config['active'] : $config['inactive'] }} transition-all duration-300">
                                <i
                                    class="fa-solid {{ $config['icon'] }} text-3xl {{ $selectedTable === $config['table'] ? $config['activeText'] : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span
                                class="mt-2 text-xs font-medium {{ $selectedTable === $config['table'] ? $config['activeText'] : 'text-gray-500 group-hover:text-gray-600' }}">{{ $config['label'] }}</span>
                        </div>
                    </button>
                @endforeach
            </div>
        </div>
    </div>

    <div class="text-center flex justify-center space-x-4 mt-6">
        @if (! $isGuestMode)
            @if (! $canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/samples/microplastics/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @endif
        @else
            <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                <i class="fas fa-lock mr-2"></i>
                Create (Project Mode)
            </div>
        @endif

        @if ($isEditing)
            <button type="button" wire:click="toggleEditMode"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                <i class="fas fa-eye mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                Stop Editing
            </button>
        @else
            @if (! $canEdit || $isGuestMode)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Edit ({{ $isGuestMode ? 'Guest Mode' : 'Viewer' }})
                </div>
            @else
                <button type="button" wire:click="toggleEditMode"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                    <i class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Edit
                </button>
            @endif
        @endif

        <a href="/samples/microplastics/dashboard"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
            Dashboard
        </a>
    </div>

    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        <div class="flex flex-col items-center w-full p-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                @if ($isGuestMode)
                    <i class="fas fa-eye text-purple-600 mr-2"></i>
                    Public Microplastics
                @else
                    {{ $isEditing ? 'Edit Microplastics' : $selectedTableLabel }}
                @endif
            </h2>

            <button type="button" wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>

            @if ($showBulkActions)
                <div class="mt-3 flex items-center gap-3">
                    <button type="button" wire:click="deleteSelected"
                        wire:confirm="Are you sure you want to delete the selected microplastics records?"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 transition-colors duration-200 hover:bg-red-200"
                        title="Delete selected microplastics records">
                        <i class="fas fa-trash"></i>
                    </button>
                    <span class="text-sm text-gray-600">
                        {{ count(array_filter($selectedMicroplastics ?? [])) }} selected
                    </span>
                </div>
            @endif
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $records])


        <div class="index-table-container overflow-x-auto">
        <table class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <datalist id="microplastics-types-list">
                @foreach ($availableTypes as $type)
                    <option value="{{ $type }}">{{ $type }}</option>
                @endforeach
            </datalist>

            <datalist id="microplastics-protocols-list">
                @foreach ($availableProtocols as $protocol)
                    <option value="{{ $protocol }}">{{ $protocol }}</option>
                @endforeach
            </datalist>

            <datalist id="microplastics-laboratories-list">
                @foreach ($availableLaboratories as $laboratory)
                    <option value="{{ $laboratory }}">{{ $laboratory }}</option>
                @endforeach
            </datalist>

            <datalist id="microplastics-identifiers-list">
                @foreach ($availableIdentifiers as $identifier)
                    <option value="{{ $identifier }}">{{ $identifier }}</option>
                @endforeach
            </datalist>

            <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                    @if ($showBulkActions)
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Select</th>
                    @endif
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[180px]"><x-sort-button field="code" :active="$sortField" :direction="$sortDirection">Code</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[150px]">Source type</th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[170px]">Source code</th>
                    @foreach ($sourceSpecificColumns as $column)
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 {{ $column['minWidth'] }}">
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[140px]"><x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">Sub-project</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[220px]"><x-sort-button field="type" :active="$sortField" :direction="$sortDirection">MPS type</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[240px]"><x-sort-button field="protocol" :active="$sortField" :direction="$sortDirection">Protocol</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[220px]"><x-sort-button field="laboratory" :active="$sortField" :direction="$sortDirection">Laboratory</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[210px]"><x-sort-button field="identifier" :active="$sortField" :direction="$sortDirection">Identified by</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[170px]"><x-sort-button field="identification_date" :active="$sortField" :direction="$sortDirection">Identification date</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[130px]"><x-sort-button field="sample_weight" :active="$sortField" :direction="$sortDirection">Weight of samples (g)</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[130px]"><x-sort-button field="r_coeff" :active="$sortField" :direction="$sortDirection">Pearson r</x-sort-button></th>
                    <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[130px]"><x-sort-button field="m_feret" :active="$sortField" :direction="$sortDirection">Feret (um)</x-sort-button></th>
                    @if ($isEditing)
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 min-w-[110px]">Delete</th>
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
                        <input type="text" wire:model.live.debounce.300ms="codeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="sourceFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3"></th>
                    @foreach ($sourceSpecificFilterDefinitions as $filter)
                        <th class="px-6 py-3">
                            @if (($filter['type'] ?? 'text') === 'date_range')
                                <div class="flex flex-col gap-2 min-w-[160px]">
                                    <input type="date"
                                        wire:model.live.debounce.300ms="sourceSpecificFilters.{{ $filter['startKey'] }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        title="Start date">
                                    <input type="date"
                                        wire:model.live.debounce.300ms="sourceSpecificFilters.{{ $filter['endKey'] }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        title="End date">
                                </div>
                            @else
                                <input type="text"
                                    wire:model.live.debounce.300ms="sourceSpecificFilters.{{ $filter['key'] }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="{{ $filter['placeholder'] }}">
                            @endif
                        </th>
                    @endforeach
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="subProjectFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="typeFilter" list="microplastics-types-list"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="protocolFilter" list="microplastics-protocols-list"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="laboratoryFilter" list="microplastics-laboratories-list"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="identifierFilter" list="microplastics-identifiers-list"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3"></th>
                    <th class="px-6 py-3"></th>
                    <th class="px-6 py-3"></th>
                    <th class="px-6 py-3"></th>
                    @if ($isEditing)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($records as $record)
                    @php
                        $sourceTypeLabel = match (class_basename((string) $record->microplastics_content_type)) {
                            'HumanSamples' => 'Human Sample',
                            'AnimalSamples' => 'Animal Sample',
                            'EnvironmentSamples' => 'Environmental Sample',
                            'ParasiteSamples' => 'Parasite Sample',
                            'Pools' => 'Pool',
                            default => class_basename((string) $record->microplastics_content_type),
                        };
                        $sourceTypeBadgeClasses = match ($sourceTypeLabel) {
                            'Human Sample' => 'bg-gradient-to-r from-rose-100 to-rose-200 text-rose-800',
                            'Animal Sample' => 'bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800',
                            'Environmental Sample' => 'bg-gradient-to-r from-emerald-100 to-emerald-200 text-emerald-800',
                            'Parasite Sample' => 'bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800',
                            'Pool' => 'bg-gradient-to-r from-cyan-100 to-cyan-200 text-cyan-800',
                            default => 'bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800',
                        };
                        $sourceSpecificCells = $sourceSpecificCellsByRecord[$record->id] ?? [];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        @if ($showBulkActions)
                            <td class="px-6 py-4 text-center">
                                <input type="checkbox" wire:model.live="selectedMicroplastics.{{ $record->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    title="Select this microplastics record">
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap text-center min-w-[180px]">
                            <a href="/samples/microplastics/{{ $record->code }}"
                                class="font-semibold text-blue-700 hover:text-blue-900 hover:underline">
                                {{ $record->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700 min-w-[150px]">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm {{ $sourceTypeBadgeClasses }}">
                                {{ $sourceTypeLabel }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700 min-w-[170px]">
                            @php($sourceProfileUrl = $this->sourceProfileUrl($record))
                            @if ($sourceProfileUrl)
                                <a href="{{ $sourceProfileUrl }}" class="text-blue-700 hover:text-blue-900 hover:underline">
                                    {{ $record->microplastics_content?->code }}
                                </a>
                            @else
                                {{ $record->microplastics_content?->code ?? 'N/A' }}
                            @endif
                        </td>
                        @foreach ($sourceSpecificCells as $index => $cell)
                            <td class="px-6 py-4 text-center text-sm text-gray-700 align-top {{ $sourceSpecificColumns[$index]['minWidth'] ?? '' }}">
                                @if ($cell['html'] ?? false)
                                    {!! $cell['value'] !!}
                                @else
                                    <span class="text-gray-900 font-medium">{{ $cell['value'] }}</span>
                                @endif
                            </td>
                        @endforeach
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700 min-w-[140px]">
                            @if ($record->subProject?->code)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                    {{ $record->subProject->code }}
                                </span>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700 min-w-[220px]">
                            @if ($isEditing && $canEdit)
                                <input type="text" value="{{ $record->mps_types?->name }}"
                                    list="microplastics-types-list"
                                    wire:change="updateField({{ $record->id }}, 'mps_type', $event.target.value)"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span class="text-gray-900 font-medium">{{ $record->mps_types?->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700 min-w-[240px]">
                            @if ($isEditing && $canEdit)
                                <input type="text" value="{{ $record->protocols?->name }}"
                                    list="microplastics-protocols-list"
                                    wire:change="updateField({{ $record->id }}, 'protocol', $event.target.value)"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span class="text-gray-900 font-medium">{{ $record->protocols?->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700 min-w-[220px]">
                            @if ($isEditing && $canEdit)
                                <input type="text" value="{{ $record->laboratories?->name }}"
                                    list="microplastics-laboratories-list"
                                    wire:change="updateField({{ $record->id }}, 'laboratory', $event.target.value)"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span class="text-gray-900 font-medium">{{ $record->laboratories?->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 min-w-[210px]">
                            <div class="flex items-center justify-center space-x-3">
                                <x-people-logo :person="$record->people" width="30" class="rounded-full ring-2 ring-gray-100" />
                                @if ($record->people)
                                    <a href="/profile/{{ $record->people->id }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200 font-medium">
                                        {{ trim(($record->people->title ?? '').' '.($record->people->first_name ?? '').' '.($record->people->last_name ?? '')) ?: 'N/A' }}
                                    </a>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700 min-w-[170px]">
                            @if ($isEditing && $canEdit)
                                <input type="date"
                                    value="{{ $record->identification_date ? $record->identification_date->format('Y-m-d') : '' }}"
                                    wire:change="updateField({{ $record->id }}, 'identification_date', $event.target.value)"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span class="text-gray-900 font-medium">{{ $record->identification_date ? $record->identification_date->format('Y-m-d') : 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700 min-w-[130px]">
                            @if ($isEditing && $canEdit)
                                <input type="number" step="any" value="{{ $record->sample_weight }}"
                                    wire:change="updateField({{ $record->id }}, 'sample_weight', $event.target.value)"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span class="text-gray-900 font-medium">{{ $record->sample_weight ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700 min-w-[130px]">
                            @if ($isEditing && $canEdit)
                                <input type="number" step="any" value="{{ $record->r_coeff }}"
                                    wire:change="updateField({{ $record->id }}, 'r_coeff', $event.target.value)"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span class="text-gray-900 font-medium">{{ $record->r_coeff ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-700 min-w-[130px]">
                            @if ($isEditing && $canEdit)
                                <input type="number" step="any" value="{{ $record->m_feret }}"
                                    wire:change="updateField({{ $record->id }}, 'm_feret', $event.target.value)"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span class="text-gray-900 font-medium">{{ $record->m_feret ?? 'N/A' }}</span>
                            @endif
                        </td>
                        @if ($isEditing)
                            <td class="px-6 py-4 text-center">
                                <button type="button" wire:click="delete({{ $record->id }})"
                                    class="inline-flex items-center justify-center h-10 w-10 rounded-xl bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200"
                                    title="Delete record">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($showBulkActions ? 1 : 0) + count($sourceSpecificColumns) + ($isEditing ? 13 : 12) }}" class="px-6 py-10 text-center text-sm text-gray-500">
                            No microplastics records found for the current filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
    </div>

    <div class="mt-6">
        @include('livewire.partials.index-pagination-bar', ['paginator' => $records])
    </div>
</div>
