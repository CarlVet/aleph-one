@php
    /**
     * Expected:
     * - $subtitle, $tableId
     * - $tubes (LengthAwarePaginator)
     * - $extraColumns: array<int, array{
     *     label: string,
     *     valuePath?: string,
     *     value?: callable,
     *     html?: bool,
     *     filterModel?: string,
     *     filterPlaceholder?: string,
     *     filterType?: string,
     *     filterModelStart?: string,
     *     filterModelEnd?: string,
     * }>
     * - $canEdit (bool)
     * - $selectedTable (string)
     */

    $extraColumns = $extraColumns ?? [];
    $canEdit = (bool) ($canEdit ?? true);
    $selectedTable = (string) ($selectedTable ?? 'tubes_table');

    $isAllTubes = $selectedTable === 'tubes_table' || $selectedTable === '';
    $showBulkActions = ! $isGuestMode && $canEdit;
    $columnCount = 9 + ($isAllTubes ? 2 : 0) + count($extraColumns) + ($showBulkActions ? 1 : 0) + ($isEditing && ! $isGuestMode && $canEdit ? 1 : 0);
@endphp

<div>
    <!-- Create, Edit, Tubes storage (Centered) -->
    <div class="text-center flex justify-center space-x-4 mt-6">
        @if (!$isGuestMode)
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Process (Viewer)
                </div>
            @else
                <a href="/samples/process"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Process
                </a>
            @endif
        @else
            <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                <i class="fas fa-lock mr-2"></i>
                Process (Project Mode)
            </div>
        @endif

        @if ($isEditing)
            <a href="/samples/process/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
        @else
            @if (!$isGuestMode && $canEdit)
                <button wire:click="toggleEditMode"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                    <i class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Edit
                </button>
            @else
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Edit ({{ $isGuestMode ? 'Guest Mode' : 'Viewer' }})
                </div>
            @endif
        @endif

        <a href="/bank/tubes"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-vial mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
            Tubes Storage
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
        <div class="flex flex-col items-center w-full p-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                @if ($isGuestMode)
                    <i class="fas fa-eye text-purple-600 mr-2"></i>
                    {{ $isAllTubes ? 'Public Tubes' : 'Public Tubes' }}
                @else
                    {{ $isEditing ? 'Edit Tubes' : 'List of Tubes' }}
                @endif
            </h2>
            @if (!empty($subtitle))
                <h3 class="text-lg text-gray-600 mb-2">{{ $subtitle }}</h3>
            @endif

            <button wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>

            @if ($showBulkActions)
                <div class="mt-3 flex items-center gap-3">
                    <button type="button" wire:click="deleteSelected"
                        wire:confirm="Are you sure you want to delete the selected tubes?"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200"
                        title="Delete selected tubes">
                        <i class="fas fa-trash"></i>
                    </button>
                    <span class="text-sm text-gray-600">
                        {{ count(array_filter($selectedTubes ?? [])) }} selected
                    </span>
                </div>
            @endif
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $tubes])


        <div class="index-table-container overflow-x-auto">
        <table id="{{ $tableId }}"
            wire:key="tubes-table-{{ $tableId }}-{{ $isEditing ? 'editing' : 'viewing' }}"
            class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                    @if ($showBulkActions)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Select</th>
                    @endif
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="tube_code" :active="$sortField" :direction="$sortDirection">Tube code</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="alias_code" :active="$sortField" :direction="$sortDirection">Alias code</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">Sub-project</x-sort-button></th>

                    @if ($isAllTubes)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="content_type" :active="$sortField" :direction="$sortDirection">Content type</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Content code</th>
                    @endif

                    @foreach ($extraColumns as $column)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            {{ $column['label'] }}
                        </th>
                    @endforeach

                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="tube_type" :active="$sortField" :direction="$sortDirection">Tube type</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="purpose" :active="$sortField" :direction="$sortDirection">Purpose</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="preservant" :active="$sortField" :direction="$sortDirection">Preservant</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="amount" :active="$sortField" :direction="$sortDirection">Amount</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="date_processed" :active="$sortField" :direction="$sortDirection">Date processed</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="project" :active="$sortField" :direction="$sortDirection">Project</x-sort-button></th>
                    @if ($isEditing && !$isGuestMode && $canEdit)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Delete</th>
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
                        <input type="text" wire:model.live.debounce.300ms="tubeCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="aliasCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="subProjectCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>

                    @if ($isAllTubes)
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="contentTypeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3"></th>
                    @endif

                    @foreach ($extraColumns as $column)
                        <th class="px-6 py-3">
                            @if (($column['filterType'] ?? 'text') === 'date_range')
                                <div class="flex items-center space-x-2">
                                    <input type="date"
                                        wire:model.live.debounce.300ms="{{ $column['filterModelStart'] }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <span class="text-gray-500 font-medium">to</span>
                                    <input type="date"
                                        wire:model.live.debounce.300ms="{{ $column['filterModelEnd'] }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </div>
                            @elseif (($column['filterType'] ?? 'text') === 'date')
                                <input type="date" wire:model.live.debounce.300ms="{{ $column['filterModel'] }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @elseif (!empty($column['filterModel']))
                                <input type="text" wire:model.live.debounce.300ms="{{ $column['filterModel'] }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="{{ $column['filterPlaceholder'] ?? 'Filter' }}">
                            @endif
                        </th>
                    @endforeach

                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="tubeTypeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="purposeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="preservantFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3"></th>
                    <th class="px-6 py-3">
                        <div class="flex items-center space-x-2">
                            <input type="date" wire:model.live.debounce.300ms="startDate"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <span class="text-gray-500 font-medium">to</span>
                            <input type="date" wire:model.live.debounce.300ms="endDate"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        </div>
                    </th>
                    <th class="px-6 py-3"></th>
                    @if ($isEditing && !$isGuestMode && $canEdit)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($tubes as $tube)
                    <tr wire:key="tube-row-{{ $tableId }}-{{ $tube->id }}"
                        class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <input type="checkbox" wire:model.live="selectedTubes.{{ $tube->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-2">
                                @if ($isGuestMode)
                                    <span class="text-gray-900 font-medium">{{ $tube->code }}</span>
                                @else
                                    <a href="/bank/tubes/{{ $tube->code }}"
                                        class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                        {{ $tube->code }}
                                    </a>
                                @endif
                                @if ($isGuestMode)
                                    <button type="button" wire:click="openTubeRequestModal({{ $tube->id }})"
                                        class="text-indigo-500 hover:text-indigo-700 transition-colors duration-200"
                                        title="Request this tube">
                                        <i class="fas fa-handshake text-sm"></i>
                                    </button>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEdit)
                                <input type="text" value="{{ $tube->alias_code ?? '' }}"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $tube->id }}, 'alias_code', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Alias Code?">
                            @else
                                <span class="text-gray-900 font-medium">{{ $tube->alias_code ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if (optional($tube->subProjectAssignment?->subProject)->code)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                    {{ $tube->subProjectAssignment->subProject->code }}
                                </span>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </td>

                        @if ($isAllTubes)
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $type = (string) ($tube->tubes_content_type ?? '');
                                    $typeColorClass = match (class_basename($type)) {
                                        'HumanSamples' => 'bg-rose-100 text-rose-800',
                                        'AnimalSamples' => 'bg-orange-100 text-orange-800',
                                        'EnvironmentSamples' => 'bg-emerald-100 text-emerald-800',
                                        'ParasiteSamples' => 'bg-purple-100 text-purple-800',
                                        'NucleicAcids' => 'bg-blue-100 text-blue-800',
                                        'Cultures' => 'bg-amber-100 text-amber-800',
                                        'Pools' => 'bg-cyan-100 text-cyan-800',
                                        default => 'bg-gray-100 text-gray-700',
                                    };
                                @endphp
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm {{ $typeColorClass }}">
                                    {{ class_basename($type) ?: 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                {!! $this->linkToContent($tube) !!}
                            </td>
                        @endif

                        @foreach ($extraColumns as $column)
                            @php
                                $isPerson = !empty($column['personPath']);
                                $person = $isPerson ? data_get($tube, $column['personPath']) : null;
                                $raw = null;

                                if (!$isPerson) {
                                    $raw = isset($column['value'])
                                        ? call_user_func($column['value'], $tube)
                                        : data_get($tube, $column['valuePath'] ?? '');
                                    $raw = $raw === null || $raw === '' ? 'N/A' : $raw;
                                }
                            @endphp
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isPerson)
                                    @if ($person)
                                        <div class="flex items-center space-x-3">
                                            <x-people-logo :person="$person" width="30"
                                                class="ring-2 ring-gray-100" />
                                            @if ($isGuestMode)
                                                <span class="text-gray-900 font-medium">
                                                    {{ trim(($person->title ?? '') . ' ' . ($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) ?: 'N/A' }}
                                                </span>
                                            @else
                                                <a href="/profile/{{ $person->id }}"
                                                    class="text-blue-600 hover:text-blue-800 transition-colors duration-200 font-medium">
                                                    {{ trim(($person->title ?? '') . ' ' . ($person->first_name ?? '') . ' ' . ($person->last_name ?? '')) ?: 'N/A' }}
                                                </a>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-gray-500">N/A</span>
                                    @endif
                                @elseif (!empty($column['html']))
                                    {!! $raw !!}
                                @else
                                    <span class="text-gray-900 font-medium">{{ $raw }}</span>
                                @endif
                            </td>
                        @endforeach

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEdit)
                                <input type="text" value="{{ $tube->tube_type ?? '' }}"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $tube->id }}, 'tube_type', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Tube Type?">
                            @else
                                <span class="text-gray-900 font-medium">{{ $tube->tube_type ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEdit)
                                <input type="text" value="{{ $tube->purpose ?? '' }}"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $tube->id }}, 'purpose', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Purpose?">
                            @else
                                <span class="text-gray-900 font-medium">{{ $tube->purpose ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEdit)
                                <input type="text" value="{{ $tube->preservant ?? '' }}"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $tube->id }}, 'preservant', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Preservant?">
                            @else
                                <span class="text-gray-900 font-medium">{{ $tube->preservant ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEdit)
                                <div class="flex space-x-2">
                                    <input type="number" value="{{ $tube->amount ?? '' }}" step="0.01"
                                        class="w-20 px-2 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $tube->id }}, 'amount', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Amount?">
                                    <input type="text" value="{{ $tube->amount_unit ?? '' }}"
                                        class="w-16 px-2 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $tube->id }}, 'amount_unit', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Amount Unit?">
                                </div>
                            @else
                                <span class="text-gray-900 font-medium">
                                    {{ $tube->amount ? $tube->amount . ' ' . ($tube->amount_unit ?? '') : 'N/A' }}
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEdit)
                                <input type="date"
                                    value="{{ $tube->date_processed ? \Carbon\Carbon::parse($tube->date_processed)->format('Y-m-d') : '' }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $tube->id }}, 'date_processed', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Date Processed?">
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $tube->date_processed ? \Carbon\Carbon::parse($tube->date_processed)->format('Y-m-d') : 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($tube->projects?->code)
                                <a href="{{ route('projects.profile', $tube->projects->code) }}"
                                    class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                    {{ $tube->projects->code }}
                                </a>
                            @else
                                <span class="text-gray-900 font-medium">N/A</span>
                            @endif
                        </td>

                        @if ($isEditing && !$isGuestMode && $canEdit)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    class="text-red-500 hover:text-red-600 transition-all duration-200 transform hover:scale-110"
                                    type="button" wire:click="delete({{ $tube->id }})"
                                    wire:confirm="Are you sure you want to delete this tube?">
                                    <i class="fas fa-trash text-xl"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $columnCount }}" class="px-6 py-12 text-center">
                            <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                <span class="text-sm text-gray-600">No tubes found.</span>
                                @if (! $isGuestMode)
                                    <a href="/samples/process"
                                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                                        <i class="fas fa-plus-circle"></i>
                                        Register tube
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @include('livewire.partials.index-pagination-bar', ['paginator' => $tubes])
    </div>
</div>
