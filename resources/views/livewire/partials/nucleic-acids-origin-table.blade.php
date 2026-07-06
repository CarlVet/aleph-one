@php
    /**
     * Expected:
     * - $subtitle, $tableId, $originLabel, $originRoutePrefix
     * - $extraColumns: array<int, array{label: string, valuePath?: string, value?: callable, html?: bool, filterModel?: string, filterPlaceholder?: string, filterType?: string, filterModelStart?: string, filterModelEnd?: string}>
     */
    $extraColumns = $extraColumns ?? [];

    $showBulkActions = $isEditing && $canEdit && !$isGuestMode;
    $baseColumnCount = 10; // tube code, content code, extras, elution, nucleic type, protocol, extracted by, extracted at, date extracted, volume
    $colspan = $baseColumnCount + count($extraColumns) + ($showBulkActions ? 1 : 0) + ($isEditing && $canEdit ? 1 : 0);
@endphp

<div>
    <!-- Create, Edit, Dashboard (Centered) -->
    <div class="text-center flex justify-center space-x-4 mt-6">
        @if (!$isGuestMode)
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/samples/nucleic/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
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
            <a href="/samples/nucleic/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
        @else
            @if (!$canEdit || $isGuestMode)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Edit ({{ $isGuestMode ? 'Guest Mode' : 'Viewer' }})
                </div>
            @else
                <button wire:click="toggleEditMode"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                    <i class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Edit
                </button>
            @endif
        @endif
        <a href="/samples/nucleic/dashboard"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
            Dashboard
        </a>
    </div>

    <!-- Table Section -->
    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        <div class="flex flex-col items-center w-full p-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                @if ($isGuestMode)
                    <i class="fas fa-eye text-purple-600 mr-2"></i>
                    Public Nucleic Acids
                @else
                    {{ $isEditing ? 'Edit Nucleic Acids' : 'List of Nucleic Acids' }}
                @endif
            </h2>
            <h3 class="text-lg text-gray-600 mb-2">{{ $subtitle }}</h3>

            <button wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>
            @if ($showBulkActions)
                <div class="mt-3 flex items-center gap-3">
                    <button type="button" wire:click="deleteSelected"
                        wire:confirm="Are you sure you want to delete the selected nucleic tubes?"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200"
                        title="Delete selected tubes">
                        <i class="fas fa-trash"></i>
                    </button>
                    <span class="text-sm text-gray-600">
                        {{ count(array_filter($selectedNucleicTubes ?? [])) }} selected
                    </span>
                </div>
            @endif
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $nucleic_tubes])


        <div class="index-table-container overflow-x-auto">
        <table id="{{ $tableId }}" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <datalist id="nucleic-preservants-list">
                @foreach ($preservants as $preservant)
                    <option value="{{ $preservant }}">{{ $preservant }}</option>
                @endforeach
            </datalist>

            <datalist id="nucleic-types-list">
                @foreach ($nucleic_acids as $nucleic_acid)
                    <option value="{{ $nucleic_acid['type'] }}">{{ $nucleic_acid['type'] }}</option>
                @endforeach
            </datalist>

            <datalist id="nucleic-protocols-list">
                @foreach ($nucleic_methods_available as $protocol)
                    <option value="{{ $protocol->name }}">{{ $protocol->name }}</option>
                @endforeach
            </datalist>

            <datalist id="nucleic-laboratories-list">
                @foreach ($laboratories_available as $laboratory)
                    <option value="{{ $laboratory->name }}">{{ $laboratory->name }}</option>
                @endforeach
            </datalist>

            <datalist id="nucleic-volumes-list">
                @foreach ($nucleic_tubes as $tubeOption)
                    @if (!is_null($tubeOption->tubes_content?->volume) && $tubeOption->tubes_content?->volume !== '')
                        <option value="{{ $tubeOption->tubes_content->volume }}">
                            {{ $tubeOption->tubes_content->volume }}</option>
                    @endif
                @endforeach
            </datalist>

            <datalist id="nucleic-date-extracted-list">
                @foreach ($nucleic_tubes as $tubeOption)
                    @if ($tubeOption->tubes_content?->date_extracted)
                        <option
                            value="{{ \Carbon\Carbon::parse($tubeOption->tubes_content->date_extracted)->format('Y-m-d') }}">
                            {{ \Carbon\Carbon::parse($tubeOption->tubes_content->date_extracted)->format('Y-m-d') }}
                        </option>
                    @endif
                @endforeach
            </datalist>

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
                        {{ $originLabel }}</th>
                    @foreach ($extraColumns as $column)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            {{ $column['label'] }}</th>
                    @endforeach
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="state" :active="$sortField" :direction="$sortDirection">Elution type</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="nucleic_type" :active="$sortField" :direction="$sortDirection">Nucleic type</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="protocol" :active="$sortField" :direction="$sortDirection">Protocol</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="extractor" :active="$sortField" :direction="$sortDirection">Extracted by</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="extracted_at" :active="$sortField" :direction="$sortDirection">Extracted at</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="date_extracted" :active="$sortField" :direction="$sortDirection">Date extracted</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="volume" :active="$sortField" :direction="$sortDirection">Volume</x-sort-button></th>
                    @if ($isEditing && $canEdit)
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
                        <input type="text" wire:model.live.debounce.300ms="tubeIdFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="contentIdFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @foreach ($extraColumns as $column)
                        <th class="px-6 py-3">
                            @if (!empty($column['filterModel']))
                                <input type="text" wire:model.live.debounce.300ms="{{ $column['filterModel'] }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="{{ $column['filterPlaceholder'] ?? 'Filter' }}">
                            @elseif (
                                ($column['filterType'] ?? null) === 'date_range' &&
                                    !empty($column['filterModelStart']) &&
                                    !empty($column['filterModelEnd']))
                                <div class="flex items-center space-x-2">
                                    <input type="date"
                                        wire:model.live.debounce.300ms="{{ $column['filterModelStart'] }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <span class="text-gray-500 font-medium">to</span>
                                    <input type="date"
                                        wire:model.live.debounce.300ms="{{ $column['filterModelEnd'] }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </div>
                            @endif
                        </th>
                    @endforeach
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="stateFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="typeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="protocolFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="extractorFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="extractedAtFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <div class="flex items-center space-x-2">
                            <input type="date" wire:model.live.debounce.300ms="startDate"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <span class="text-gray-500 font-medium">to</span>
                            <input type="date" wire:model.live.debounce.300ms="endDate"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        </div>
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="volumeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @if ($isEditing && $canEdit)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
                @endphp
                @forelse ($nucleic_tubes as $tube)
                    @php
                        $canEditRow = $canEdit && (int) (data_get($tube, 'tubes_content.people_id') ?? 0) === $currentPeopleId;
                    @endphp
                    <tr wire:key="{{ $tube->id }}"
                        class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <input type="checkbox" wire:model.live="selectedNucleicTubes.{{ $tube->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    title="Select this tube">
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col">
                                @if ($isGuestMode)
                                    <span class="text-gray-900 font-medium">{{ $tube->code }}</span>
                                @else
                                    <a href="/bank/tubes/{{ $tube->code }}"
                                        class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                        {{ $tube->code }}
                                    </a>
                                @endif
                                @if ($tube->alias_code)
                                    <span class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded mt-1">
                                        Alias: {{ $tube->alias_code }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isGuestMode)
                                <span class="text-gray-900 font-medium">{{ $tube->tubes_content->nucleic_content->code ?? 'N/A' }}</span>
                            @else
                                <a href="{{ $originRoutePrefix }}/{{ $tube->tubes_content->nucleic_content->code }}"
                                    class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                    {{ $tube->tubes_content->nucleic_content->code ?? 'N/A' }}
                                </a>
                            @endif
                        </td>

                        @foreach ($extraColumns as $column)
                            @php
                                $cellValue = null;
                                $isHtml = (bool) ($column['html'] ?? false);

                                if (isset($column['value']) && is_callable($column['value'])) {
                                    $cellValue = $column['value']($tube);
                                } else {
                                    $cellValue = data_get($tube, $column['valuePath'] ?? null);
                                }
                            @endphp
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-gray-900 font-medium">
                                    @if ($isHtml)
                                        {!! $cellValue ?: 'N/A' !!}
                                    @else
                                        {{ $cellValue ?? 'N/A' }}
                                    @endif
                                </span>
                            </td>
                        @endforeach

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" list="nucleic-preservants-list"
                                    value="{{ $tube->preservant ?? '' }}" x-data="{ original: @js($tube->preservant ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Elution Type?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube->id }}, 'state', $el.value);
                                    "
                                    class="w-full min-w-[160px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                            @else
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 shadow-sm">
                                    {{ $tube->preservant ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" list="nucleic-types-list"
                                    value="{{ $tube->tubes_content->type ?? '' }}" x-data="{ original: @js($tube->tubes_content->type ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the type of Nucleic Acid?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube->id }}, 'nucleic_type', $el.value);
                                    "
                                    class="w-full min-w-[200px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $tube->tubes_content->type ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" list="nucleic-protocols-list"
                                    value="{{ $tube->tubes_content->protocols->name ?? '' }}" x-data="{ original: @js($tube->tubes_content->protocols->name ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Extraction Protocol?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube->id }}, 'protocol', $el.value);
                                    "
                                    class="w-full min-w-[240px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $tube->tubes_content->protocols->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $extractor = $tube->tubes_content->people;
                            @endphp
                            @if ($extractor)
                                <div class="flex items-center space-x-2">
                                    <x-people-logo :person="$extractor" width="28" />
                                    <a href="/profile/{{ $extractor->id }}"
                                        class="text-blue-600 hover:text-blue-800 font-medium hover:underline transition-colors duration-200">
                                        {{ trim(($extractor->title ? $extractor->title . ' ' : '') . $extractor->first_name . ' ' . $extractor->last_name) }}
                                    </a>
                                </div>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" list="nucleic-laboratories-list"
                                    value="{{ $tube->tubes_content->laboratories->name ?? '' }}"
                                    x-data="{ original: @js($tube->tubes_content->laboratories->name ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Extraction Laboratory?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube->id }}, 'extracted_at', $el.value);
                                    "
                                    class="w-full min-w-[220px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                            @else
                                <span class="text-gray-900 font-medium">{{ $tube->tubes_content->laboratories->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="date" list="nucleic-date-extracted-list"
                                    value="{{ $tube->tubes_content->date_extracted ? \Carbon\Carbon::parse($tube->tubes_content->date_extracted)->format('Y-m-d') : '' }}"
                                    x-data="{ original: @js($tube->tubes_content->date_extracted ? \Carbon\Carbon::parse($tube->tubes_content->date_extracted)->format('Y-m-d') : '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Date extracted?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube->id }}, 'date_extracted', $el.value);
                                    "
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span class="text-gray-900 font-medium">
                                    {{ $tube->tubes_content->date_extracted ? \Carbon\Carbon::parse($tube->tubes_content->date_extracted)->format('Y-m-d') : 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" list="nucleic-volumes-list"
                                    value="{{ $tube->tubes_content->volume ?? '' }}" x-data="{ original: @js($tube->tubes_content->volume ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Volume?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube->id }}, 'volume', $el.value);
                                    "
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $tube->tubes_content->volume ?? 'N/A' }}</span>
                            @endif
                        </td>

                        @if ($isEditing && $canEditRow)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="delete({{ $tube->id }})"
                                    wire:confirm="Are you sure you want to delete this tube?"
                                    class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colspan }}" class="px-6 py-10 text-center">
                            <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                <span class="text-sm text-gray-600">No nucleic acid records found.</span>
                                @if (!$isGuestMode)
                                    <a href="/samples/nucleic/create"
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                        Register nucleic acid
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <div class="px-6 py-4">
            @include('livewire.partials.index-pagination-bar', ['paginator' => $nucleic_tubes])
        </div>
    </div>
</div>
