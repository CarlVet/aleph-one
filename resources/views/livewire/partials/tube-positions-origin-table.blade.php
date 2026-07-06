@php
    $tableId = (string) ($tableConfig['tableId'] ?? 'tube_positions_table');
    $subtitle = (string) ($tableConfig['subtitle'] ?? '');
    $tubeListKey = (string) ($tableConfig['tubeListKey'] ?? 'tubes');
    $showBoxContentType = (bool) ($tableConfig['showBoxContentType'] ?? true);
    $extraColumns = $tableConfig['extraColumns'] ?? [];

    $tubeOptions = isset($$tubeListKey) ? $$tubeListKey : $tubes ?? collect();
    $boxesOptions = $boxes ?? collect();

    $contentTypeOptions = [
        'Human samples',
        'Animal samples',
        'Environmental samples',
        'Parasite samples',
        'Nucleic acids',
        'Human nucleic acids',
        'Animal nucleic acids',
        'Parasite nucleic acids',
        'Environmental nucleic acids',
        'Cultures',
        'Pools',
        'Miscellaneous',
    ];

    $reasonOptions = [
        'Sample reorganization',
        'Temperature condition change',
        'Damaged box',
        'Accidental fall of box',
        'Storage optimization',
        'Correct misplacement',
    ];

    $prefix = 'tp-' . $tableId;
    $isGuestMode = (bool) ($isGuestMode ?? false);
    $showBulkActions = $canEdit && ! $isGuestMode;
    $columnCount = 7 + count($extraColumns) + ($showBoxContentType ? 1 : 0) + ($isEditing ? 1 : 0) + ($showBulkActions ? 1 : 0);

    $valueForColumn = function ($position, array $column) {
        if (isset($column['value']) && is_callable($column['value'])) {
            return $column['value']($position);
        }

        $path = $column['valuePath'] ?? null;
        if (!$path) {
            return null;
        }

        return data_get($position, $path);
    };
@endphp

<div class="mt-6">
    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        <div class="flex flex-col items-center w-full p-4">
            <h2 class="text-2xl font-bold text-gray-800 mb-2">
                {{ $isEditing ? 'Edit Tube Positions' : 'List of Tube Positions' }}</h2>
            @if ($subtitle)
                <h3 class="text-lg text-gray-600 mb-4">{{ $subtitle }}</h3>
            @endif

            @include('livewire.partials.export-buttons')

            @if ($showBulkActions)
                <div class="mt-3 flex items-center gap-3">
                    <button type="button" wire:click="deleteSelected"
                        wire:confirm="Are you sure you want to delete the selected tube positions?"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200"
                        title="Delete selected tube positions">
                        <i class="fas fa-trash"></i>
                    </button>
                    <span class="text-sm text-gray-600">
                        {{ count(array_filter($selectedTubePositions ?? [])) }} selected
                    </span>
                </div>
            @endif
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $tube_positions])


        <div class="index-table-container overflow-x-auto">
        <table id="{{ $tableId }}" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead class="bg-gray-50">
                <tr>
                    @if ($showBulkActions)
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Select</th>
                    @endif
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="tube_code" :active="$sortField" :direction="$sortDirection">Tube code</x-sort-button></th>
                    @foreach ($extraColumns as $column)
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            {{ $column['label'] ?? '' }}
                        </th>
                    @endforeach
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="box_code" :active="$sortField" :direction="$sortDirection">Box code</x-sort-button></th>
                    @if ($showBoxContentType)
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            <x-sort-button field="content_type" :active="$sortField" :direction="$sortDirection">Content type</x-sort-button></th>
                    @endif
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="position_x" :active="$sortField" :direction="$sortDirection">Position x</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="position_y" :active="$sortField" :direction="$sortDirection">Position y</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="date_moved" :active="$sortField" :direction="$sortDirection">Date moved</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="moved_by" :active="$sortField" :direction="$sortDirection">Moved by</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="reason" :active="$sortField" :direction="$sortDirection">Reason moved</x-sort-button></th>
                    @if ($isEditing)
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Delete</th>
                    @endif
                </tr>
            </thead>

            <thead class="bg-gray-50">
                <tr>
                    @if ($showBulkActions)
                        <th class="px-6 py-3"></th>
                    @endif
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filters.tubeCode"
                            class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>

                    @foreach ($extraColumns as $column)
                        <th class="px-6 py-3">
                            @if (($column['filterType'] ?? null) === 'date_range')
                                <div class="flex items-center space-x-2">
                                    <input type="date"
                                        wire:model.live.debounce.300ms="{{ $column['filterModelStart'] ?? '' }}"
                                        class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <span class="text-gray-500">to</span>
                                    <input type="date"
                                        wire:model.live.debounce.300ms="{{ $column['filterModelEnd'] ?? '' }}"
                                        class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </div>
                            @elseif (!empty($column['filterModel']))
                                <input type="text" wire:model.live.debounce.300ms="{{ $column['filterModel'] }}"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            @endif
                        </th>
                    @endforeach

                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filters.boxCode"
                            class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>

                    @if ($showBoxContentType)
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="filters.contentType"
                                class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                    @endif

                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filters.positionX"
                            class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filters.positionY"
                            class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <div class="flex items-center space-x-2">
                            <input type="date" wire:model.live.debounce.300ms="filters.dateMovedStart"
                                class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <span class="text-gray-500">to</span>
                            <input type="date" wire:model.live.debounce.300ms="filters.dateMovedEnd"
                                class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        </div>
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filters.scientist"
                            class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="filters.reason"
                            class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @if ($isEditing)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>

            <tbody class="bg-white divide-y divide-gray-200">
                @forelse ($tube_positions as $tube_position)
                    @php
                        $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
                        $canEditRow = $canEdit && (int) (data_get($tube_position, 'people_id') ?? 0) === $currentPeopleId;
                        $tubeCode = data_get($tube_position, 'tubes.code');
                        $tubeAliasCode = data_get($tube_position, 'tubes.alias_code');
                        $boxCode = data_get($tube_position, 'boxes.code');
                        $boxId = data_get($tube_position, 'boxes.id');
                        $boxAliasCode = data_get($tube_position, 'boxes.alias_code');
                    @endphp
                    <tr wire:key="tube-position-{{ $tableId }}-{{ $tube_position->id }}"
                        class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <input type="checkbox"
                                    wire:model.live="selectedTubePositions.{{ $tube_position->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && $canEditRow)
                                <div class="flex items-center justify-center gap-2">
                                    <input type="text" list="{{ $prefix }}-tube-codes"
                                        value="{{ $tubeCode ?? '' }}" x-data="{ original: @js($tubeCode ?? '') }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit the Tube Code?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $tube_position->id }}, 'tube', $el.value);
                                        "
                                        class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        autocomplete="off">
                                    <datalist id="{{ $prefix }}-tube-codes">
                                        @foreach ($tubeOptions as $tube)
                                            <option value="{{ $tube->code }}"></option>
                                        @endforeach
                                    </datalist>
                                    @if ($tubeAliasCode)
                                        <span
                                            class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded whitespace-nowrap">
                                            Alias: {{ $tubeAliasCode }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <div class="flex items-center justify-center gap-2">
                                    @if ($isGuestMode)
                                        <span class="text-gray-900 font-medium">{{ $tubeCode ?? 'N/A' }}</span>
                                    @else
                                        <a href="/bank/tubes/{{ $tubeCode }}"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                            {{ $tubeCode ?? 'N/A' }}
                                        </a>
                                    @endif
                                    @if ($tubeAliasCode)
                                        <span
                                            class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded whitespace-nowrap">
                                            Alias: {{ $tubeAliasCode }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>
                        @foreach ($extraColumns as $column)
                            @php
                                $rawValue = $valueForColumn($tube_position, $column);
                                $rawValue = $rawValue === null || $rawValue === '' ? null : $rawValue;
                                $display = $rawValue ?? 'N/A';
                                $isHtml = (bool) ($column['html'] ?? false);
                                $href = null;
                                if (!$isHtml && $display !== 'N/A' && !empty($column['link'])) {
                                    $href = str_replace(
                                        '{value}',
                                        urlencode((string) $display),
                                        (string) $column['link'],
                                    );
                                }
                            @endphp
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($isHtml)
                                    {!! $display !== 'N/A' ? $display : '<span class="text-gray-500">N/A</span>' !!}
                                @elseif ($href && ! $isGuestMode)
                                    <a href="{{ $href }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">{{ $display }}</a>
                                @else
                                    <span class="text-gray-900 font-medium">{{ $display }}</span>
                                @endif
                            </td>
                        @endforeach

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && $canEditRow)
                                <input type="text" list="{{ $prefix }}-box-codes"
                                    value="{{ $boxCode ?? '' }}" x-data="{ original: @js($boxCode ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Box Code?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube_position->id }}, 'box', $el.value);
                                    "
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                                <datalist id="{{ $prefix }}-box-codes">
                                    @foreach ($boxesOptions as $box)
                                        <option value="{{ $box->code }}"></option>
                                    @endforeach
                                </datalist>
                            @else
                                <div class="flex items-center justify-center gap-2">
                                    @if ($boxId)
                                        <a href="/bank/boxes/{{ $boxId }}/contents"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200"
                                            title="Click to view box grid">
                                            {{ $boxCode ?? 'N/A' }}
                                        </a>
                                    @else
                                        {{ $boxCode ?? 'N/A' }}
                                    @endif
                                    @if ($boxAliasCode)
                                        <span
                                            class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded whitespace-nowrap">
                                            Alias: {{ $boxAliasCode }}
                                        </span>
                                    @endif
                                </div>
                            @endif
                        </td>

                        @if ($showBoxContentType)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($isEditing && $canEditRow)
                                    @php $currentContentType = data_get($tube_position, 'boxes.content_type'); @endphp
                                    <input type="text" list="{{ $prefix }}-content-types"
                                        value="{{ $currentContentType ?? '' }}" x-data="{ original: @js($currentContentType ?? '') }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit the Content Type of the box?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $tube_position->id }}, 'content_type', $el.value);
                                        "
                                        class="w-full min-w-[180px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        autocomplete="off">
                                    <datalist id="{{ $prefix }}-content-types">
                                        @foreach ($contentTypeOptions as $opt)
                                            <option value="{{ $opt }}"></option>
                                        @endforeach
                                    </datalist>
                                @else
                                    {{ data_get($tube_position, 'boxes.content_type') ?? 'N/A' }}
                                @endif
                            </td>
                        @endif

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && $canEditRow)
                                <input type="number" step="any"
                                    value="{{ data_get($tube_position, 'position_x') ?? '' }}"
                                    x-data="{ original: @js((string) (data_get($tube_position, 'position_x') ?? '')) }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the X position of the tube?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube_position->id }}, 'position_x', $el.value);
                                    "
                                    class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                {{ data_get($tube_position, 'position_x') ?? 'N/A' }}
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && $canEditRow)
                                <input type="number" step="any"
                                    value="{{ data_get($tube_position, 'position_y') ?? '' }}"
                                    x-data="{ original: @js((string) (data_get($tube_position, 'position_y') ?? '')) }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Y position of the tube?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube_position->id }}, 'position_y', $el.value);
                                    "
                                    class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                {{ data_get($tube_position, 'position_y') ?? 'N/A' }}
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @php
                                $dateMoved = data_get($tube_position, 'date_moved');
                                $dateMovedYmd = $dateMoved ? \Carbon\Carbon::parse($dateMoved)->format('Y-m-d') : '';
                            @endphp
                            @if ($isEditing && $canEditRow)
                                <input type="date" value="{{ $dateMovedYmd }}" x-data="{ original: @js($dateMovedYmd) }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Date of Movement?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube_position->id }}, 'date_moved', $el.value);
                                    "
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                {{ $dateMovedYmd ?: 'N/A' }}
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center space-x-3">
                                <x-people-logo :person="$tube_position->people" width="30" />
                                @if ($isGuestMode)
                                    <span class="text-gray-900 font-medium">
                                        {{ trim((data_get($tube_position, 'people.title') ?? '') . ' ' . (data_get($tube_position, 'people.first_name') ?? '') . ' ' . (data_get($tube_position, 'people.last_name') ?? '')) ?: 'N/A' }}
                                    </span>
                                @else
                                    <a href="/profile/{{ data_get($tube_position, 'people.id') }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ trim((data_get($tube_position, 'people.title') ?? '') . ' ' . (data_get($tube_position, 'people.first_name') ?? '') . ' ' . (data_get($tube_position, 'people.last_name') ?? '')) ?: 'N/A' }}
                                    </a>
                                @endif
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && $canEditRow)
                                @php $currentReason = data_get($tube_position, 'reason'); @endphp
                                <input type="text" list="{{ $prefix }}-reasons"
                                    value="{{ $currentReason ?? '' }}" x-data="{ original: @js($currentReason ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit the Reason of movement?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $tube_position->id }}, 'reason', $el.value);
                                    "
                                    class="w-full min-w-[180px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    autocomplete="off">
                                <datalist id="{{ $prefix }}-reasons">
                                    @foreach ($reasonOptions as $opt)
                                        <option value="{{ $opt }}"></option>
                                    @endforeach
                                </datalist>
                            @else
                                {{ data_get($tube_position, 'reason') ?? 'N/A' }}
                            @endif
                        </td>

                        @if ($isEditing)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($canEditRow)
                                    <button type="button"
                                        x-on:click.prevent="
                                            if (confirm('Are you sure you want to delete this tube position?')) {
                                                $wire.delete({{ $tube_position->id }});
                                            }
                                        "
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                        <i class="fas fa-trash text-red-500 hover:text-red-600 mr-2"></i>
                                        Delete
                                    </button>
                                @endif
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $columnCount }}" class="px-6 py-12 text-center">
                            <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                <span class="text-sm text-gray-600">No tube positions found.</span>
                                @if (! $isGuestMode && $canEdit)
                                    <a href="/bank/tubes/create"
                                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                                        <i class="fas fa-plus-circle"></i>
                                        Register tube position
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @include('livewire.partials.index-pagination-bar', ['paginator' => $tube_positions])
    </div>
</div>
