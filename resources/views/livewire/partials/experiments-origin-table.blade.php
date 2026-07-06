@php
    /**
     * Expected:
     * - $subtitle, $tableId
     * - $experiments (LengthAwarePaginator)
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
     * - $showPhoto (bool)
     * - $showProjectColumnInGuestMode (bool)
     * - $canEdit (bool)
     * - lists: $exp_protocols, $pathogens, $laboratories_by_country
     */

    $extraColumns = $extraColumns ?? [];
    $showPhoto = (bool) ($showPhoto ?? false);
    $showProjectColumnInGuestMode = (bool) ($showProjectColumnInGuestMode ?? false);
    $canEdit = (bool) ($canEdit ?? true);
    $showBulkActions = !$isGuestMode && $canEdit;

    $prefix = (string) ($tableId ?? 'experiments');

    $columnCount =
        ($showBulkActions ? 1 : 0) +
        1 +
        ($isGuestMode && $showProjectColumnInGuestMode ? 1 : 0) +
        count($extraColumns) +
        10 +
        ($showPhoto ? 1 : 0) +
        ($isEditing && !$isGuestMode && $canEdit ? 1 : 0);
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
                <a href="/experiments/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @endif
        @else
            <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                <i class="fas fa-lock mr-2"></i>
                Create (Guest Mode)
            </div>
        @endif

        @if ($isEditing)
            <a href="/experiments/list"
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

        <a href="/experiments/dashboard"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
            Dashboard
        </a>
    </div>

    <!-- Table Section -->
    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        <div class="flex flex-col w-full p-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-2 text-center">
                @if ($isGuestMode)
                    <i class="fas fa-eye text-purple-600 mr-2"></i>
                    Public Experiments
                @else
                    {{ $isEditing ? 'Edit Experiments' : 'List of Experiments' }}
                @endif
            </h2>
            @if (!empty($subtitle))
                <h3 class="text-lg text-gray-600 mb-2 text-center">{{ $subtitle }}</h3>
            @endif

            <div class="w-full flex items-center justify-center">
                <button wire:click="export"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                    <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                    Export to CSV
                </button>
            </div>

            @if ($showBulkActions)
                @php
                    $selectedItemsCount = collect($selectedExperiments ?? [])
                        ->filter(fn($checked) => (bool) $checked)
                        ->count();
                @endphp
                <div class="mt-2 w-full flex items-center justify-center gap-2">
                    @if ($showPhoto)
                        <input id="{{ $prefix }}-bulk-photo-input" type="file" class="hidden" wire:model.live="bulkPhoto"
                            accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf">
                        <label for="{{ $prefix }}-bulk-photo-input"
                            class="inline-flex items-center justify-center h-9 w-9 rounded-md border border-blue-300 bg-blue-50 text-blue-600 hover:bg-blue-100 transition-colors cursor-pointer"
                            title="Select and apply one photo to checked experiments">
                            <i class="fas fa-images"></i>
                        </label>
                    @endif
                    <button type="button"
                        x-on:click.prevent.stop="
                            if (!confirm('Are you sure you want to delete all selected experiments?')) { return; }
                            $wire.deleteSelected();
                        "
                        class="inline-flex items-center justify-center h-9 w-9 rounded-md border border-red-400 bg-red-600 text-white hover:bg-red-700 transition-colors"
                        title="Delete checked experiments">
                        <i class="fas fa-trash"></i>
                    </button>
                    <span class="text-xs font-medium text-gray-600">
                        {{ $selectedItemsCount }} selected
                    </span>
                </div>
            @endif
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $experiments])


        <div class="index-table-container overflow-x-auto">
            <table id="{{ $tableId }}"
                wire:key="experiments-table-{{ $tableId }}-{{ $isEditing ? 'editing' : 'viewing' }}"
                data-sticky-cols="{{ ($showBulkActions ?? false) ? '2,3' : '1,2' }}"
                class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <datalist id="{{ $prefix }}-protocols-list">
                @foreach ($exp_protocols ?? collect() as $protocol)
                    <option value="{{ $protocol->name }}">{{ $protocol->name }}</option>
                @endforeach
            </datalist>

            <datalist id="{{ $prefix }}-pathogens-list">
                @foreach ($pathogens ?? collect() as $pathogen)
                    <option value="{{ $pathogen->species }}">{{ $pathogen->species }}</option>
                @endforeach
            </datalist>

            <datalist id="{{ $prefix }}-discrete-outcomes-list">
                <option value="Strong positive">Strong positive</option>
                <option value="Positive">Positive</option>
                <option value="Suspect">Suspect</option>
                <option value="Negative">Negative</option>
            </datalist>

            <datalist id="{{ $prefix }}-laboratories-list">
                @foreach ($laboratories_by_country ?? [] as $country => $labs)
                    @foreach ($labs as $lab)
                        <option value="{{ $lab['name'] }}">{{ $country }}</option>
                    @endforeach
                @endforeach
            </datalist>

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
                        <x-sort-button field="code" :active="$sortField" :direction="$sortDirection">EXPERIMENT CODE</x-sort-button>
                    </th>

                    @foreach ($extraColumns as $column)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            {{ strtoupper($column['label'] ?? '') }}
                        </th>
                    @endforeach

                    @if ($isGuestMode && $showProjectColumnInGuestMode)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            PROJECT
                        </th>
                    @endif

                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">SUB-PROJECT</x-sort-button>
                    </th>

                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="protocol" :active="$sortField" :direction="$sortDirection">Protocol</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="protocol_type" :active="$sortField" :direction="$sortDirection">Protocol type</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="pathogen" :active="$sortField" :direction="$sortDirection">Pathogen species</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="outcome_discrete" :active="$sortField" :direction="$sortDirection">Outcome (Discrete)</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="outcome_quant" :active="$sortField" :direction="$sortDirection">Outcome (Quantitative)</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="purpose" :active="$sortField" :direction="$sortDirection">Test purpose</x-sort-button></th>
                    @if ($showPhoto)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Photo</th>
                    @endif
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="date_tested" :active="$sortField" :direction="$sortDirection">Date tested</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="performed_by" :active="$sortField" :direction="$sortDirection">Performed by</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="performed_at" :active="$sortField" :direction="$sortDirection">Performed at</x-sort-button></th>
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
                        <input type="text" wire:model.live.debounce.300ms="experimentIdFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>

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

                    @if ($isGuestMode && $showProjectColumnInGuestMode)
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="projectFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                    @endif

                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="subProjectCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>

                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="protocolFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="protocolTypeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="pathogenFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="discreteFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="quantitativeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <select wire:model.live="purposeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All</option>
                            @foreach (\App\Enums\ExperimentPurpose::options() as $purposeValue => $purposeLabel)
                                <option value="{{ $purposeValue }}">{{ $purposeLabel }}</option>
                            @endforeach
                        </select>
                    </th>
                    @if ($showPhoto)
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
                    @endif
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
                        <input type="text" wire:model.live.debounce.300ms="scientistFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="placeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @if ($isEditing && !$isGuestMode && $canEdit)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
                @endphp
                @foreach ($experiments as $experiment)
                    @php
                        $canEditRow = $canEdit && $this->canMutateExperimentRecord((int) ($experiment->people_id ?? 0));
                    @endphp
                    <tr wire:key="experiment-{{ $tableId }}-{{ $experiment->id }}"
                        class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($canEditRow)
                                    <input type="checkbox" wire:model.live="selectedExperiments.{{ $experiment->id }}"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        title="Select this experiment">
                                @endif
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="/experiments/{{ $experiment->code }}"
                                class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                {{ $experiment->code }}
                            </a>
                        </td>

                        @foreach ($extraColumns as $column)
                            @php
                                $cellValue = null;
                                $isHtml = (bool) ($column['html'] ?? false);

                                if (isset($column['value']) && is_callable($column['value'])) {
                                    $cellValue = $column['value']($experiment);
                                } else {
                                    $cellValue = data_get($experiment, $column['valuePath'] ?? null);
                                }
                            @endphp
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isHtml)
                                    {!! $cellValue ?: '<span class="text-gray-500">N/A</span>' !!}
                                @else
                                    <span class="text-gray-900 font-medium">{{ $cellValue ?? 'N/A' }}</span>
                                @endif
                            </td>
                        @endforeach

                        @if ($isGuestMode && $showProjectColumnInGuestMode)
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($experiment->projects?->code)
                                    <a href="{{ route('projects.profile', $experiment->projects->code) }}"
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm hover:underline">
                                        {{ $experiment->projects->code }}
                                    </a>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm">N/A</span>
                                @endif
                            </td>
                        @endif

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if(optional($experiment->subProjectAssignment?->subProject)->code)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                    {{ $experiment->subProjectAssignment->subProject->code }}
                                </span>
                            @else
                                <span class="text-gray-400 text-xs">-</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEditRow)
                                <input type="text" list="{{ $prefix }}-protocols-list"
                                    value="{{ $experiment->protocols->name ?? '' }}" x-data="{ original: @js($experiment->protocols->name ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Protocol?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $experiment->id }}, 'protocol', $el.value);
                                    "
                                    class="w-full min-w-[200px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                            @else
                                <a href="/protocols/{{ $experiment->protocols->code ?? '' }}"
                                    class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                    {{ $experiment->protocols->name ?? 'N/A' }}
                                </a>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            <span
                                class="text-gray-900 font-medium">{{ $experiment->protocols->techniques->type ?? 'N/A' }}</span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEditRow)
                                <input type="text" list="{{ $prefix }}-pathogens-list"
                                    value="{{ $experiment->pathogens->species ?? '' }}" x-data="{ original: @js($experiment->pathogens->species ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Pathogen Species?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $experiment->id }}, 'pathogen', $el.value);
                                    "
                                    class="w-full min-w-[170px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                            @else
                                <span class="text-gray-900 font-medium">
                                    @if ($experiment->pathogens?->species)
                                        <i>{{ $experiment->pathogens->species }}</i>
                                    @else
                                        N/A
                                    @endif
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEditRow)
                                <input type="text" list="{{ $prefix }}-discrete-outcomes-list"
                                    value="{{ $experiment->outcome_discrete ?? '' }}" x-data="{ original: @js($experiment->outcome_discrete ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Discrete Outcome of the experiment?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $experiment->id }}, 'outcome_discrete', $el.value);
                                    "
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                            @else
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm
                                    {{ $experiment->outcome_discrete === 'Strong positive'
                                        ? 'bg-gradient-to-r from-red-700 to-red-900 text-white'
                                        : ($experiment->outcome_discrete === 'Positive'
                                            ? 'bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800'
                                            : ($experiment->outcome_discrete === 'Suspect'
                                                ? 'bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800'
                                                : 'bg-gradient-to-r from-green-100 to-green-200 text-green-800')) }}">
                                    {{ $experiment->outcome_discrete ?? 'N/A' }}
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEditRow)
                                <input type="number" step="any" value="{{ $experiment->outcome_quant ?? '' }}"
                                    x-data="{ original: @js($experiment->outcome_quant ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Quantitative Outcome?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $experiment->id }}, 'outcome_quant', $el.value);
                                    "
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $experiment->outcome_quant ?? 'N/A' }}</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEditRow)
                                <select
                                    wire:change="updateField({{ $experiment->id }}, 'purpose', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Test purpose?"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select purpose</option>
                                    @foreach (\App\Enums\ExperimentPurpose::options() as $purposeValue => $purposeLabel)
                                        <option value="{{ $purposeValue }}" @selected(($experiment->purpose?->value) === $purposeValue)>
                                            {{ $purposeLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                @if ($experiment->purpose)
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm
                                        {{ $experiment->purpose === \App\Enums\ExperimentPurpose::Screening
                                            ? 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800'
                                            : 'bg-gradient-to-r from-teal-100 to-teal-200 text-teal-800' }}">
                                        {{ $experiment->purpose->label() }}
                                    </span>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            @endif
                        </td>

                        @if ($showPhoto)
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $hasPhotoPath = !empty($experiment->photo_path);
                                    $photoExists = $hasPhotoPath
                                        && \Illuminate\Support\Facades\Storage::disk('local')->exists($experiment->photo_path);
                                @endphp
                                <div class="flex items-center space-x-3">
                                    @if (!$isGuestMode && $canEditRow)
                                        <label for="photo-upload-{{ $experiment->id }}" class="cursor-pointer group">
                                            <i
                                                class="fas fa-camera text-blue-500 group-hover:text-blue-600 text-xl transition-all duration-200 transform group-hover:scale-110"></i>
                                            <input type="file" id="photo-upload-{{ $experiment->id }}"
                                                class="hidden" accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf"
                                                wire:model.live="photo" wire:loading.attr="disabled"
                                                wire:change="uploadPhoto({{ $experiment->id }})" x-data
                                                x-init="$watch('$wire.photo', value => {
                                                    if (value && $wire.currentPhotoId === {{ $experiment->id }}) {
                                                        $wire.uploadPhoto({{ $experiment->id }});
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
                                                    if ($wire.currentPhotoId === {{ $experiment->id }}) {
                                                        $el.value = '';
                                                    }
                                                ">
                                        </label>
                                        @if ($uploadingPhotoId === $experiment->id)
                                            <div wire:loading wire:target="photo" class="text-sm text-gray-500">
                                                <i class="fas fa-spinner fa-spin"></i> Uploading...
                                            </div>
                                        @endif
                                        @if (isset($uploadErrors[$experiment->id]))
                                            <div class="text-sm text-red-500">
                                                {{ $uploadErrors[$experiment->id] }}
                                            </div>
                                        @endif
                                    @endif
                                    @if ($photoExists)
                                        <div class="flex items-center space-x-2">
                                            <button type="button"
                                                wire:click="openPhotoPreview({{ $experiment->id }})"
                                                class="text-green-500 hover:text-green-600 transition-all duration-200 transform hover:scale-110"
                                                title="Preview photo">
                                                <i class="fas fa-eye text-lg"></i>
                                            </button>
                                        </div>
                                    @elseif ($hasPhotoPath)
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-red-600">Missing file</span>
                                            @if (!$isGuestMode && $canEditRow)
                                                <button type="button" wire:click="clearBrokenPhotoPath({{ $experiment->id }})"
                                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 underline underline-offset-2">
                                                    Clear path
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                        @endif

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEditRow)
                                @php
                                    $dateTested = $experiment->date_tested
                                        ? \Carbon\Carbon::parse($experiment->date_tested)->format('Y-m-d')
                                        : '';
                                @endphp
                                <input type="date" value="{{ $dateTested }}" x-data="{ original: @js($dateTested) }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Date of Experiment?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $experiment->id }}, 'date_tested', $el.value);
                                    "
                                    class="w-full min-w-[160px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span class="text-gray-900 font-medium">
                                    {{ $experiment->date_tested ? \Carbon\Carbon::parse($experiment->date_tested)->format('Y-m-d') : 'N/A' }}
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $this->canMutateAnyExperimentRecord())
                                <select wire:change="updateField({{ $experiment->id }}, 'people_id', $event.target.value)"
                                    class="w-full min-w-[220px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:confirm="Are you sure you want to edit the Performed by field?">
                                    <option value="">Select person</option>
                                    @foreach ($people as $personOption)
                                        @php
                                            $personLabel = trim(($personOption->title ?? '') . ' ' . ($personOption->first_name ?? '') . ' ' . ($personOption->last_name ?? '')) ?: 'N/A';
                                        @endphp
                                        <option value="{{ $personOption->id }}" @selected((int) ($experiment->people_id ?? 0) === (int) $personOption->id)>
                                            {{ $personLabel }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <div class="flex items-center space-x-3">
                                    <x-people-logo :person="$experiment->people" width="30"
                                        class="rounded-full ring-2 ring-gray-100" />
                                    @if ($experiment->people)
                                        <a href="/profile/{{ $experiment->people->id }}"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200 font-medium">
                                            {{ trim(($experiment->people->title ?? '') . ' ' . ($experiment->people->first_name ?? '') . ' ' . ($experiment->people->last_name ?? '')) ?: 'N/A' }}
                                        </a>
                                    @else
                                        <span class="text-gray-500">N/A</span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && !$isGuestMode && $canEditRow)
                                <input type="text" list="{{ $prefix }}-laboratories-list"
                                    value="{{ $experiment->laboratories->name ?? '' }}" x-data="{ original: @js($experiment->laboratories->name ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Location of the Experiment?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $experiment->id }}, 'lab', $el.value);
                                    "
                                    class="w-full min-w-[250px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $experiment->laboratories->name ?? 'N/A' }}</span>
                            @endif
                        </td>

                        @if ($isEditing && !$isGuestMode && $canEdit)
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($canEditRow)
                                    <button type="button"
                                        class="text-red-500 hover:text-red-600 transition-all duration-200 transform hover:scale-110"
                                        x-on:click.prevent.stop="
                                            if (!confirm('Are you sure you want to delete this experiment?')) return;
                                            $wire.delete({{ $experiment->id }});
                                        ">
                                        <i class="fas fa-trash text-xl"></i>
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @endforeach

                @if ($experiments->count() === 0)
                    <tr>
                        <td colspan="{{ $columnCount }}" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center space-y-4">
                                <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-flask text-gray-400 text-2xl"></i>
                                </div>
                                <div class="text-center">
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">
                                        @if ($isGuestMode)
                                            No Public Experiments Found
                                        @else
                                            No Experiments Found
                                        @endif
                                    </h3>
                                    <p class="text-gray-500 mb-4">
                                        @if ($isGuestMode)
                                            There are currently no public experiments available for viewing.
                                        @else
                                            No experiments have been registered yet for this project.
                                        @endif
                                    </p>
                                    @if (!$isGuestMode)
                                        <a href="/experiments/create"
                                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                            <i class="fas fa-plus mr-2"></i>
                                            Register First Experiment
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </td>
                    </tr>
                @endif
            </tbody>
            </table>
        </div>

        @include('livewire.partials.index-pagination-bar', ['paginator' => $experiments])
    </div>
</div>
