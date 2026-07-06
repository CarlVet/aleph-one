@php
    /**
     * Expected:
     * - $poolTubes (LengthAwarePaginator)
     * - $tableConfig: array{tableId?: string, subtitle?: string, extraColumns?: array<int, array<string, mixed>>}
     * - $isEditing (bool)
     * - $isGuestMode (bool)
     * - $canEdit (bool)
     * - datalists: $tubeCodes, $animalCodes, $humanCodes, $environmentCodes, $parasiteCodes, $nucleicCodes, $cultureCodes
     */
    $tableId = (string) ($tableConfig['tableId'] ?? 'pools_table');
    $subtitle = (string) ($tableConfig['subtitle'] ?? '');
    $extraColumns = $tableConfig['extraColumns'] ?? [];
    $isEditing = (bool) ($isEditing ?? false);
    $isGuestMode = (bool) ($isGuestMode ?? false);
    $canEdit = (bool) ($canEdit ?? true);

    $prefix = 'pools-' . $tableId;
    $showBulkActions = ! $isGuestMode && $canEdit;
    $showGuestProjectColumn = $isGuestMode;
    $columnCount = 2 + count($extraColumns) + ($showBulkActions ? 1 : 0) + ($showGuestProjectColumn ? 1 : 0) + ($isEditing && ! $isGuestMode && $canEdit ? 1 : 0);
@endphp

<div class="mt-6">
    <div class="text-center flex justify-center space-x-4 mt-6">
        @if (!$isGuestMode)
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/samples/pools/create"
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
            <a href="/samples/pools/list"
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

        <a href="/samples/pools/dashboard"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
            Dashboard
        </a>
    </div>

    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        <div class="flex flex-col items-center w-full p-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">{{ $isEditing ? 'Edit Pools' : 'List of Pools' }}</h2>
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
                        wire:confirm="Are you sure you want to delete the selected pool tubes?"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200"
                        title="Delete selected pool tubes">
                        <i class="fas fa-trash"></i>
                    </button>
                    <span class="text-sm text-gray-600">
                        {{ count(array_filter($selectedPoolTubes ?? [])) }} selected
                    </span>
                </div>
            @endif
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $poolTubes])


        <div class="index-table-container overflow-x-auto">
        <table id="{{ $tableId }}"
            wire:key="pools-table-{{ $tableId }}-{{ $isEditing ? 'editing' : 'viewing' }}"
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
                    @if ($showGuestProjectColumn)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Project code</th>
                    @endif
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Sub-project</th>
                    @foreach ($extraColumns as $column)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            @if (!empty($column['sortKey']))
                                <x-sort-button :field="$column['sortKey']" :active="$sortField" :direction="$sortDirection">{{ $column['label'] ?? '' }}</x-sort-button>
                            @else
                                {{ $column['label'] ?? '' }}
                            @endif
                        </th>
                    @endforeach
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
                        <input type="text" wire:model.live.debounce.300ms="originFilters.tubeCode"
                            class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @if ($showGuestProjectColumn)
                        <th class="px-6 py-3"></th>
                    @endif
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="originFilters.subProjectCode"
                            class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @foreach ($extraColumns as $column)
                        <th class="px-6 py-3">
                            @if (($column['filterType'] ?? null) === 'date_range')
                                <div class="flex items-center space-x-2">
                                    <input type="date"
                                        wire:model.live.debounce.300ms="{{ $column['filterModelStart'] ?? '' }}"
                                        class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <span class="text-gray-500 font-medium">to</span>
                                    <input type="date"
                                        wire:model.live.debounce.300ms="{{ $column['filterModelEnd'] ?? '' }}"
                                        class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </div>
                            @elseif (!empty($column['filterModel']))
                                <input type="text" wire:model.live.debounce.300ms="{{ $column['filterModel'] }}"
                                    class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="{{ $column['filterPlaceholder'] ?? 'Filter' }}">
                            @endif
                        </th>
                    @endforeach
                    @if ($isEditing && !$isGuestMode && $canEdit)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($poolTubes as $tube)
                    <tr wire:key="pool-tube-{{ $tableId }}-{{ $tube->id }}"
                        class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <input type="checkbox" wire:model.live="selectedPoolTubes.{{ $tube->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && !$isGuestMode && $canEdit)
                                <input type="text" list="{{ $prefix }}-tube-codes"
                                    value="{{ $tube->code ?? '' }}" x-data="{ original: @js($tube->code ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Tube Code?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube->id }}, 'tube_code', $el.value);
                                    "
                                    class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                            @else
                                @if ($isGuestMode)
                                    <span class="text-gray-900 font-medium">{{ $tube->code ?? 'N/A' }}</span>
                                @else
                                    <a href="/bank/tubes/{{ $tube->code }}"
                                        class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                        {{ $tube->code ?? 'N/A' }}
                                    </a>
                                @endif
                            @endif
                        </td>

                        @if ($showGuestProjectColumn)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @php
                                    $projectCode = data_get($tube, 'projects.code') ?? data_get($tube, 'tubes_content.projects.code');
                                @endphp
                                @if ($projectCode)
                                    <a href="/projects/{{ $projectCode }}"
                                        class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                        {{ $projectCode }}
                                    </a>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                        @endif

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if (optional($tube->tubes_content?->subProjectAssignment?->subProject)->code)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                    {{ $tube->tubes_content->subProjectAssignment->subProject->code }}
                                </span>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </td>

                        @foreach ($extraColumns as $column)
                            @php
                                $key = $column['key'] ?? null;
                                $isHtml = (bool) ($column['html'] ?? false);
                                $value = null;
                                if (isset($column['value']) && is_callable($column['value'])) {
                                    $value = $column['value']($tube);
                                } else {
                                    $value = data_get($tube, $column['valuePath'] ?? null);
                                }
                                $value = $value === null || $value === '' ? 'N/A' : $value;
                            @endphp
                            <td class="px-6 py-4 whitespace-nowrap text-center align-top">
                                @if ($key === 'date_pooled' && $isEditing && !$isGuestMode && $canEdit)
                                    @php
                                        $raw = data_get($tube, 'tubes_content.date_pooled');
                                        $ymd = $raw ? \Carbon\Carbon::parse($raw)->format('Y-m-d') : '';
                                    @endphp
                                    <input type="date" value="{{ $ymd }}" x-data="{ original: @js($ymd) }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit the Date Pooled?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $tube->id }}, 'date_pooled', $el.value);
                                        "
                                        class="w-full min-w-[160px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                @elseif ($key === 'contents_codes' && $isEditing && !$isGuestMode && $canEdit)
                                    @php
                                        $poolContents = collect(data_get($tube, 'tubes_content.pool_contents', []));
                                    @endphp

                                    <div class="space-y-2 min-w-[260px]" x-data="{ type: 'App\\\\Models\\\\HumanSamples' }">
                                        @foreach ($poolContents as $poolContent)
                                            <div class="flex items-center justify-between gap-2">
                                                <div class="flex items-center gap-2 min-w-0">
                                                    <span class="text-xs text-gray-600 bg-gray-100 px-2 py-1 rounded">
                                                        {{ class_basename($poolContent->samples_type ?? '') ?: 'N/A' }}
                                                    </span>
                                                    <span
                                                        class="text-sm text-gray-700 bg-gray-50 px-2 py-1 rounded truncate">
                                                        {{ data_get($poolContent, 'samples.code') ?? 'N/A' }}
                                                    </span>
                                                </div>
                                                <button type="button"
                                                    class="text-red-500 hover:text-red-700 transition-colors duration-200"
                                                    x-on:click.prevent.stop="
                                                        if (!confirm('Are you sure you want to remove this content code?')) return;
                                                        $wire.removeContentCode({{ $tube->id }}, {{ $poolContent->id }});
                                                    ">
                                                    <i class="fas fa-trash text-sm"></i>
                                                </button>
                                            </div>
                                        @endforeach

                                        <div class="flex items-center gap-2">
                                            <select x-model="type"
                                                class="px-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                <option value="App\\Models\\HumanSamples">Human</option>
                                                <option value="App\\Models\\AnimalSamples">Animal</option>
                                                <option value="App\\Models\\EnvironmentSamples">Environment</option>
                                                <option value="App\\Models\\ParasiteSamples">Parasite</option>
                                                <option value="App\\Models\\NucleicAcids">Nucleic</option>
                                                <option value="App\\Models\\Cultures">Culture</option>
                                                <option value="App\\Models\\Pools">Pool</option>
                                            </select>

                                            <input type="text" placeholder="Add content code..."
                                                class="flex-1 min-w-[140px] px-2 py-1 text-sm border border-gray-200 rounded focus:outline-none focus:ring-1 focus:ring-blue-500"
                                                x-ref="code"
                                                x-bind:list="type === 'App\\\\Models\\\\HumanSamples' ?
                                                    '{{ $prefix }}-codes-human' :
                                                    (type === 'App\\\\Models\\\\AnimalSamples' ?
                                                        '{{ $prefix }}-codes-animal' :
                                                        (type === 'App\\\\Models\\\\EnvironmentSamples' ?
                                                            '{{ $prefix }}-codes-environment' :
                                                            (type === 'App\\\\Models\\\\ParasiteSamples' ?
                                                                '{{ $prefix }}-codes-parasite' :
                                                                (type === 'App\\\\Models\\\\NucleicAcids' ?
                                                                    '{{ $prefix }}-codes-nucleic' :
                                                                    (type === 'App\\\\Models\\\\Cultures' ?
                                                                        '{{ $prefix }}-codes-culture' :
                                                                        '{{ $prefix }}-codes-pool')))))"
                                                x-on:keydown.enter.prevent.stop="
                                                    if (!$refs.code.value) return;
                                                    $wire.addContentCode({{ $tube->id }}, $refs.code.value, type);
                                                    $refs.code.value = '';
                                                ">

                                            <button type="button"
                                                class="text-green-600 hover:text-green-700 transition-colors duration-200"
                                                x-on:click.prevent.stop="
                                                    if (!$refs.code.value) return;
                                                    $wire.addContentCode({{ $tube->id }}, $refs.code.value, type);
                                                    $refs.code.value = '';
                                                ">
                                                <i class="fas fa-plus text-sm"></i>
                                            </button>
                                        </div>
                                    </div>
                                @else
                                    @if ($isHtml)
                                        {!! $value !== 'N/A' ? $value : '<span class="text-gray-500">N/A</span>' !!}
                                    @else
                                        <span class="text-gray-900 font-medium">{{ $value }}</span>
                                    @endif
                                @endif
                            </td>
                        @endforeach

                        @if ($isEditing && !$isGuestMode && $canEdit)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <button type="button"
                                    class="text-red-500 hover:text-red-600 transition-all duration-200 transform hover:scale-110"
                                    x-on:click.prevent.stop="
                                        if (!confirm('Are you sure you want to delete this pool tube?')) return;
                                        $wire.delete({{ $tube->id }});
                                    ">
                                    <i class="fas fa-trash text-xl"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $columnCount }}" class="px-6 py-12 text-center">
                            <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                <span class="text-sm text-gray-600">No pools found.</span>
                                @if (! $isGuestMode)
                                    <a href="/samples/pools/create"
                                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                                        <i class="fas fa-plus-circle"></i>
                                        Register pool
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        <datalist id="{{ $prefix }}-tube-codes">
            @foreach ($tubeCodes ?? [] as $code)
                <option value="{{ $code }}"></option>
            @endforeach
        </datalist>
        <datalist id="{{ $prefix }}-codes-animal">
            @foreach ($animalCodes ?? [] as $code)
                <option value="{{ $code }}"></option>
            @endforeach
        </datalist>
        <datalist id="{{ $prefix }}-codes-human">
            @foreach ($humanCodes ?? [] as $code)
                <option value="{{ $code }}"></option>
            @endforeach
        </datalist>
        <datalist id="{{ $prefix }}-codes-environment">
            @foreach ($environmentCodes ?? [] as $code)
                <option value="{{ $code }}"></option>
            @endforeach
        </datalist>
        <datalist id="{{ $prefix }}-codes-parasite">
            @foreach ($parasiteCodes ?? [] as $code)
                <option value="{{ $code }}"></option>
            @endforeach
        </datalist>
        <datalist id="{{ $prefix }}-codes-nucleic">
            @foreach ($nucleicCodes ?? [] as $code)
                <option value="{{ $code }}"></option>
            @endforeach
        </datalist>
        <datalist id="{{ $prefix }}-codes-culture">
            @foreach ($cultureCodes ?? [] as $code)
                <option value="{{ $code }}"></option>
            @endforeach
        </datalist>
        <datalist id="{{ $prefix }}-codes-pool">
            @foreach ($poolCodes ?? [] as $code)
                <option value="{{ $code }}"></option>
            @endforeach
        </datalist>

        @include('livewire.partials.index-pagination-bar', ['paginator' => $poolTubes])
    </div>
</div>
