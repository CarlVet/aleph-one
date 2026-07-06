@php
    /**
     * @var array<int, array{
     *   label: string,
     *   valuePath?: string,
     *   value?: callable,
     *   html?: bool
     * }> $extraColumns
     */
    $extraColumns = $extraColumns ?? [];
    $showBulkActions = !$isGuestMode && $canEdit;

    $baseColumnCount = 13; // code, tubes, origin code, photo, species, sex, stage, state, sample type, identification date, processed date, identified by, processed by
    $colspan = $baseColumnCount + count($extraColumns) + ($isEditing && $canEdit ? 1 : 0) + ($showBulkActions ? 1 : 0);

    $originFilterModel = $originFilterModel ?? null;
    $originFilterPlaceholder = $originFilterPlaceholder ?? 'Filter';
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
                <a href="/samples/parasites/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
                <a href="/samples/parasites/dissection/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-purple-500 to-violet-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-purple-600">
                    <i
                        class="fas fa-cut mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Dissect
                </a>
            @endif
            @if ($isEditing)
                <a href="/samples/parasites/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            @else
                @if (!$canEdit)
                    <div
                        class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
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
        <a href="/samples/parasites/dashboard"
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
        <div class="flex flex-col items-center w-full p-4">
            <!-- Index Title (Centered) -->
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                @if ($isGuestMode)
                    <i class="fas fa-eye text-purple-600 mr-2"></i>
                    Public Parasite Samples
                @else
                    {{ $isEditing ? 'Edit Parasite Samples' : 'List of Parasite Samples' }}
                @endif
            </h2>
            <h3 class="text-lg text-gray-600 mb-2">{{ $subtitle }}</h3>

            <!-- Export Button (Centered) -->
            <button wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>

            @if ($showBulkActions)
                @php
                    $selectedItemsCount = collect($selectedParasiteSamples ?? [])
                        ->filter(fn($checked) => (bool) $checked)
                        ->count();
                @endphp
                <div class="mt-2 w-full flex items-center justify-center gap-2">
                    <input id="{{ $tableId }}-bulk-photo-input" type="file" class="hidden" wire:model.live="bulkPhoto"
                        accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf">
                    <label for="{{ $tableId }}-bulk-photo-input"
                        class="inline-flex items-center justify-center h-9 w-9 rounded-md border border-blue-300 bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors cursor-pointer"
                        title="Select and apply one photo to checked samples">
                        <i class="fas fa-images"></i>
                    </label>
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

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $parasite_samples])


        <div class="index-table-container overflow-x-auto">
        <table id="{{ $tableId }}"
            data-sticky-cols="{{ ($showBulkActions ?? false) ? '2,3' : '1,2' }}"
            class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                    @if ($showBulkActions)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Select
                        </th>
                    @endif
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Sample code
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Associated tubes
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        {{ $originLabel }}
                    </th>
                    @foreach ($extraColumns as $column)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            {{ $column['label'] }}
                        </th>
                    @endforeach
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Parasite photo
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Species
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Sex
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Stage
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Repletion state
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Sample type
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Identification date
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Processed date
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 index-people-cell">
                        Identified by
                    </th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 index-people-cell">
                        Processed by
                    </th>
                    @if ($isEditing && $canEdit)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Delete
                        </th>
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
                        <input type="text" wire:model.live.debounce.300ms="tubeCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        @if ($originFilterModel)
                            <input type="text" wire:model.live.debounce.300ms="{{ $originFilterModel }}"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="{{ $originFilterPlaceholder }}">
                        @endif
                    </th>
                    @foreach ($extraColumns as $column)
                        <th class="px-6 py-3">
                            @if (!empty($column['filterModel']))
                                @php
                                    $filterType = $column['filterType'] ?? 'text';
                                @endphp

                                <input type="{{ $filterType === 'date' ? 'date' : 'text' }}"
                                    wire:model.live.debounce.300ms="{{ $column['filterModel'] }}"
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
                        <select wire:model.live="photoFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All</option>
                            <option value="has">Has photo</option>
                            <option value="none">No photo</option>
                            @if ($this->canFilterBrokenPhotos())
                                <option value="broken">Broken link</option>
                            @endif
                        </select>
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="parasiteSpeciesFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="sexFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="stageFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="stateFilter"
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
                            <input type="date" wire:model.live.debounce.300ms="startDate"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Start Date">
                            <span class="text-gray-500 font-medium">to</span>
                            <input type="date" wire:model.live.debounce.300ms="endDate"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="End Date">
                        </div>
                    </th>
                    <th class="px-6 py-3">
                        <div class="flex items-center space-x-2">
                            <input type="date" wire:model.live.debounce.300ms="processedStartDate"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Start Date">
                            <span class="text-gray-500 font-medium">to</span>
                            <input type="date" wire:model.live.debounce.300ms="processedEndDate"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="End Date">
                        </div>
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="scientistFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="processedByFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @if ($isEditing && $canEdit)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @if (session()->has('error'))
                    <tr>
                        <td colspan="{{ $colspan }}"
                            class="px-6 py-4 text-red-500 text-center bg-red-50 border-l-4 border-red-500">
                            {{ session('error') }}
                        </td>
                    </tr>
                @endif
                @if (session()->has('message'))
                    <tr>
                        <td colspan="{{ $colspan }}"
                            class="px-6 py-4 text-green-500 text-center bg-green-50 border-l-4 border-green-500">
                            {{ session('message') }}
                        </td>
                    </tr>
                @endif

                @forelse ($parasite_samples as $sample)
                    @php
                        $canEditRow = $canEdit && $this->canMutateSampleRecord((int) ($sample->people_id ?? 0));
                    @endphp
                    <tr wire:key="{{ $sample->id }}"
                        class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($canEditRow)
                                    <input type="checkbox" wire:model.live="selectedParasiteSamples.{{ $sample->id }}"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        title="Select this sample">
                                @endif
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEdit)
                                <div class="relative min-w-[200px]">
                                    <input type="text" list="sample-codes-list"
                                        wire:change="updateField({{ $sample->id }}, 'code', $event.target.value)"
                                        value="{{ $sample->code }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Search sample code..."
                                        wire:confirm="Are you sure you want to edit the Sample Code?"
                                        autocomplete="off">
                                    <datalist id="sample-codes-list">
                                        @foreach ($parasite_samples as $option)
                                            <option value="{{ $option->code }}">
                                                {{ $option->code }}
                                            </option>
                                        @endforeach
                                    </datalist>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                            @else
                                @if ($isGuestMode)
                                    <span class="text-gray-900 font-medium">{{ $sample->code }}</span>
                                @else
                                    <a href="/samples/parasites/{{ $sample->code }}"
                                        class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                        {{ $sample->code }}
                                    </a>
                                @endif
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEdit)
                                <div class="space-y-2 min-w-[200px]">
                                    @foreach ($sample->tubes as $tube)
                                        <div class="flex items-center space-x-2">
                                            <div class="flex flex-col">
                                                <div>
                                                    <span class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded">
                                                        {{ $tube->code ?? 'N/A' }}
                                                    </span>
                                                    @if ($isGuestMode)
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
                                                    @endif
                                                </div>
                                                @if ($tube->alias_code)
                                                    <span
                                                        class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded mt-1">
                                                        Alias: {{ $tube->alias_code }}
                                                    </span>
                                                @endif
                                            </div>
                                            <button type="button" wire:click="removeTube({{ $tube->id }})"
                                                wire:confirm="Are you sure you want to remove this tube?"
                                                class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                    @endforeach

                                    @if ($sample->tubes->count() === 0)
                                        <span class="text-sm text-gray-500 italic">No tubes associated</span>
                                    @endif
                                </div>
                            @else
                                <ul>
                                    @foreach ($sample->tubes as $tube)
                                        <li class="flex items-center space-x-2 mb-1">
                                            <div class="flex flex-col">
                                                <div>
                                                    <a href="/bank/tubes/{{ $tube->code }}"
                                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                        {{ $tube->code ?? 'N/A' }}
                                                    </a>
                                                    @if ($isGuestMode)
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
                                                    @endif
                                                </div>
                                                @if ($tube->alias_code)
                                                    <span
                                                        class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded mt-1">
                                                        Alias: {{ $tube->alias_code }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($isGuestMode)
                                                <button type="button"
                                                    wire:click="openTubeRequestModal({{ $tube->id }})"
                                                    class="text-indigo-500 hover:text-indigo-700 transition-colors duration-200"
                                                    title="Request this tube">
                                                    <i class="fas fa-handshake text-sm"></i>
                                                </button>
                                            @endif
                                        </li>
                                    @endforeach
                                    @if ($sample->tubes->count() === 0)
                                        <span class="text-gray-500 italic">No tubes associated</span>
                                    @endif
                                </ul>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEdit)
                                <div class="relative min-w-[200px]">
                                    <input type="text" list="{{ $originListId }}"
                                        wire:change="updateField({{ $sample->id }}, '{{ $originEditField }}', $event.target.value)"
                                        value="{{ $sample->parasites->parasites_origin->code }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Search..."
                                        wire:confirm="Are you sure you want to edit the {{ $originLabel }}?"
                                        autocomplete="off">
                                    <datalist id="{{ $originListId }}">
                                        @foreach ($originSamples as $option)
                                            <option value="{{ $option->code }}">
                                                {{ $option->code }}
                                            </option>
                                        @endforeach
                                    </datalist>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                            @else
                                @if ($isGuestMode)
                                    <span class="text-gray-900 font-medium">{{ $sample->parasites->parasites_origin->code ?? 'N/A' }}</span>
                                @else
                                    <a href="{{ $originRoutePrefix }}/{{ $sample->parasites->parasites_origin->code }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ $sample->parasites->parasites_origin->code ?? 'N/A' }}
                                    </a>
                                @endif
                            @endif
                        </td>

                        @foreach ($extraColumns as $column)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-gray-900 font-medium">
                                    @php
                                        $cellValue = null;
                                        $isHtml = (bool) ($column['html'] ?? false);

                                        if (isset($column['value']) && is_callable($column['value'])) {
                                            $cellValue = $column['value']($sample);
                                        } else {
                                            $cellValue = data_get($sample, $column['valuePath'] ?? null);
                                        }
                                    @endphp

                                    @if ($isHtml)
                                        {!! $cellValue ?: 'N/A' !!}
                                    @else
                                        {{ $cellValue ?? 'N/A' }}
                                    @endif
                                </span>
                            </td>
                        @endforeach

                        <td class="px-6 py-4 whitespace-nowrap">
                            @include('livewire.partials.parasite-sample-photo-cell', [
                                'sample' => $sample,
                                'canEdit' => $canEdit,
                                'isGuestMode' => $isGuestMode,
                                'uploadingPhotoId' => $uploadingPhotoId,
                                'uploadErrors' => $uploadErrors,
                            ])
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEdit)
                                <div class="relative min-w-[300px]">
                                    <input type="text" list="parasite-species-list"
                                        wire:change="updateField({{ $sample->id }}, 'species', $event.target.value)"
                                        value="{{ $sample->parasites->parasite_species->name_scientific }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Search parasite species..."
                                        wire:confirm="Are you sure you want to edit the Species?" autocomplete="off">
                                    <datalist id="parasite-species-list">
                                        @foreach ($parasiteSpecies as $species)
                                            <option value="{{ $species->name_scientific }}">
                                                {{ $species->name_scientific }}
                                            </option>
                                        @endforeach
                                    </datalist>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="fas fa-search text-gray-400"></i>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-900 font-medium">{!! '<i>' . e($sample->parasites->parasite_species->name_scientific) . '</i>' ?? 'N/A' !!}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEdit)
                                <div class="relative min-w-[150px]">
                                    <input type="text" list="sex-options-list"
                                        wire:change="updateField({{ $sample->id }}, 'sex', $event.target.value)"
                                        value="{{ $sample->parasites->sex }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Select sex..."
                                        wire:confirm="Are you sure you want to edit the Sex?" autocomplete="off">
                                    <datalist id="sex-options-list">
                                        @foreach ($sexOptions as $option)
                                            <option value="{{ $option }}">
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </datalist>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="fas fa-chevron-down text-gray-400"></i>
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-900 font-medium">{{ $sample->parasites->sex ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEdit)
                                <div class="relative min-w-[150px]">
                                    <input type="text" list="stage-options-list"
                                        wire:change="updateField({{ $sample->id }}, 'stage', $event.target.value)"
                                        value="{{ $sample->parasites->stage }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Select stage..."
                                        wire:confirm="Are you sure you want to edit the Stage?" autocomplete="off">
                                    <datalist id="stage-options-list">
                                        @foreach ($stageOptions as $option)
                                            <option value="{{ $option }}">
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </datalist>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="fas fa-chevron-down text-gray-400"></i>
                                    </div>
                                </div>
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $sample->parasites->stage ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEdit)
                                <div class="relative min-w-[200px]">
                                    <input type="text" list="state-options-list"
                                        wire:change="updateField({{ $sample->id }}, 'state', $event.target.value)"
                                        value="{{ $sample->parasites->state }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Select state..."
                                        wire:confirm="Are you sure you want to edit the State?" autocomplete="off">
                                    <datalist id="state-options-list">
                                        @foreach ($stateOptions as $option)
                                            <option value="{{ $option }}">
                                                {{ $option }}
                                            </option>
                                        @endforeach
                                    </datalist>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="fas fa-chevron-down text-gray-400"></i>
                                    </div>
                                </div>
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $sample->parasites->state ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEdit)
                                <div class="relative min-w-[200px]">
                                    <input type="text" list="sample-types-list"
                                        wire:change="updateField({{ $sample->id }}, 'sample_type', $event.target.value)"
                                        value="{{ $sample->parasite_sample_types->name }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Select sample type..."
                                        wire:confirm="Are you sure you want to edit the Sample Type?"
                                        autocomplete="off">
                                    <datalist id="sample-types-list">
                                        @foreach ($sampleTypes as $type)
                                            <option value="{{ $type->name }}">
                                                {{ $type->name }}
                                            </option>
                                        @endforeach
                                    </datalist>
                                    <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                        <i class="fas fa-chevron-down text-gray-400"></i>
                                    </div>
                                </div>
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $sample->parasite_sample_types->name ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEdit)
                                <input type="date" value="{{ $sample->parasites?->date_identified?->format('Y-m-d') ?? '' }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $sample->id }}, 'date_identified', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the identification date?">
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $sample->parasites?->date_identified?->format('Y-m-d') ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEdit)
                                <input type="date" value="{{ $sample->date_processed?->format('Y-m-d') ?? '' }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $sample->id }}, 'date_processed', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the processed date?">
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $sample->date_processed?->format('Y-m-d') ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap index-people-cell">
                            @if ($sample->parasites?->people)
                                <div class="flex items-center space-x-3">
                                    <x-people-logo :person="$sample->parasites->people" width="30"
                                        class="rounded-full ring-2 ring-gray-100" />
                                    <a href="/profile/{{ $sample->parasites->people->id }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200 font-medium">
                                        {{ trim(($sample->parasites->people->title ?? '').' '.($sample->parasites->people->first_name ?? '').' '.($sample->parasites->people->last_name ?? '')) }}
                                    </a>
                                </div>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap index-people-cell">
                            @if ($sample->people)
                                <div class="flex items-center space-x-3">
                                    <x-people-logo :person="$sample->people" width="30"
                                        class="rounded-full ring-2 ring-gray-100" />
                                    <a href="/profile/{{ $sample->people->id }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200 font-medium">
                                        {{ trim(($sample->people->title ?? '').' '.($sample->people->first_name ?? '').' '.($sample->people->last_name ?? '')) }}
                                    </a>
                                </div>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </td>

                        @if ($isEditing && $canEdit)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button wire:click="delete({{ $sample->id }})"
                                    wire:confirm="Are you sure you want to delete this sample?"
                                    class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $colspan }}" class="px-6 py-12 text-center">
                            <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                <span class="text-sm text-gray-600">No parasite samples found.</span>
                                @if (! $isGuestMode)
                                    <a href="/samples/parasites/create"
                                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                                        <i class="fas fa-plus-circle"></i>
                                        Register parasite sample
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
            @include('livewire.partials.index-pagination-bar', ['paginator' => $parasite_samples])
        </div>
    </div>
</div>
