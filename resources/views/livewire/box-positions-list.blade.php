<div class="text-center mt-2">

    <!-- Create, Edit, Dashboard (Centered) -->
    <div class="text-center flex justify-center space-x-4 mt-6">
        @if (!$canEdit)
            <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                <i class="fas fa-lock mr-2"></i>
                Create (Viewer)
            </div>
        @else
            <a href="/bank/boxes/create"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                Create
            </a>
        @endif
        @if ($isEditing)
            <a href="/bank/boxes/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
        @else
            @if (!$canEdit)
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
    </div>
    <!-- Table Section -->
    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        <div class="flex flex-col items-center w-full p-4">
            <!-- Index Title (Centered) -->
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                {{ $isEditing ? 'Edit Box Positions' : 'List of Box Positions' }}</h2>

            <!-- Export Button (Centered) -->
            <button wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>

            @if ($showBulkActions)
                <div class="mt-2 w-full flex items-center justify-center gap-2">
                    <button type="button" wire:click="deleteSelected"
                        wire:confirm="Are you sure you want to delete all selected box positions?"
                        class="inline-flex items-center justify-center h-9 w-9 rounded-md border border-red-400 bg-red-600 text-white hover:bg-red-700 transition-colors"
                        title="Delete selected box positions">
                        <i class="fas fa-trash"></i>
                    </button>
                    <span class="text-xs font-medium text-gray-600">
                        {{ count(array_filter($selectedBoxPositions ?? [])) }} selected
                    </span>
                </div>
            @endif
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $box_positions])


        <div class="index-table-container overflow-x-auto">
        <table id="box_positions_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead class="bg-gray-50">
                <tr>
                    @if ($showBulkActions)
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Select
                        </th>
                    @endif
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="box_code" :active="$sortField" :direction="$sortDirection">Box
                        code</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="box_alias_code" :active="$sortField" :direction="$sortDirection">Box
                        alias code</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">Sub-project</x-sort-button>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="content_type" :active="$sortField" :direction="$sortDirection">Content
                        type</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Box
                        format</th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="date_moved" :active="$sortField" :direction="$sortDirection">Date
                        moved</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="location" :active="$sortField" :direction="$sortDirection">Current
                        location</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="sublocation" :active="$sortField" :direction="$sortDirection">Sub-location</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="facility" :active="$sortField" :direction="$sortDirection">Facility (country)</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider index-people-cell"><x-sort-button field="moved_by" :active="$sortField" :direction="$sortDirection">Moved
                        by</x-sort-button></th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="reason" :active="$sortField" :direction="$sortDirection">Reason
                        moved</x-sort-button></th>
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
                        <input type="text" wire:model.live.debounce.300ms="boxCodeFilter"
                            class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="boxAliasCodeFilter"
                            class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="subProjectCodeFilter"
                            class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="contentTypeFilter"
                            class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                    <th class="px-6 py-3">
                        <div class="flex items-center space-x-2">
                            <input type="date" wire:model.live.debounce.300ms="startDate"
                                class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Start Date">
                            <span class="text-gray-500">to</span>
                            <input type="date" wire:model.live.debounce.300ms="endDate"
                                class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="End Date">
                        </div>
                    </th>

                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="locationFilter"
                            class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="subLocationFilter"
                            class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="facilityFilter"
                            class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="scientistFilter"
                            class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="reasonFilter"
                            class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @if ($isEditing)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    $currentPeopleId = (int) ($currentPeopleId ?? 0);
                @endphp
                @forelse ($box_positions as $box_position)
                    @php
                        $canEditRow = $canEdit && (int) ($box_position->people_id ?? 0) === $currentPeopleId;
                    @endphp
                    <tr wire:key="{{ $box_position->id }}"
                        class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <input type="checkbox" wire:model.live="selectedBoxPositions.{{ $box_position->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    title="Select this box position">
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && $canEditRow)
                                <input list="box-codes" id="box" name="box"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $box_position->id }}, 'box', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Box Code?"
                                    wire:model.defer="box_code" placeholder="Search and select box code"
                                    value="{{ $box_position->boxes->code }}">

                                <datalist id="box-codes">
                                    @foreach ($boxes as $box)
                                        <option value="{{ $box->code }}">
                                        </option>
                                    @endforeach
                                </datalist>
                            @else
                                <a href="/bank/boxes/{{ $box_position->boxes->id }}/contents"
                                    class="text-blue-600 hover:text-blue-800 transition-colors duration-200"
                                    title="Click to view box grid">
                                    {{ $box_position->boxes->code ?? 'N/A' }}
                                </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && $canEditRow)
                                <input type="text"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $box_position->id }}, 'box_alias_code', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Box Alias Code?"
                                    value="{{ $box_position->boxes->alias_code ?? '' }}">
                            @else
                                {{ $box_position->boxes->alias_code ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if (optional($box_position->subProjectAssignment?->subProject)->code)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                    {{ $box_position->subProjectAssignment->subProject->code }}
                                </span>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="content_type" name="content_type"
                                    wire:change="updateField({{ $box_position->id }}, 'content_type', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Content Type of the box?"
                                    class="w-full min-w-[180px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Human samples"
                                        {{ 'Human samples' === $box_position->boxes->content_type ? 'selected' : '' }}>
                                        Human samples</option>
                                    <option value="Human nucleic acids"
                                        {{ 'Human nucleic acids' === $box_position->boxes->content_type ? 'selected' : '' }}>
                                        Human nucleic acids</option>
                                    <option value="Animal samples"
                                        {{ 'Animal samples' === $box_position->boxes->content_type ? 'selected' : '' }}>
                                        Animal samples</option>
                                    <option value="Animal nucleic acids"
                                        {{ 'Animal nucleic acids' === $box_position->boxes->content_type ? 'selected' : '' }}>
                                        Animal nucleic acids</option>
                                    <option value="Parasite samples"
                                        {{ 'Parasite samples' === $box_position->boxes->content_type ? 'selected' : '' }}>
                                        Parasite samples</option>
                                    <option value="Parasite nucleic acids"
                                        {{ 'Parasite nucleic acids' === $box_position->boxes->content_type ? 'selected' : '' }}>
                                        Parasite nucleic acids</option>
                                    <option value="Environmental samples"
                                        {{ 'Environmental samples' === $box_position->boxes->content_type ? 'selected' : '' }}>
                                        Environmental samples</option>
                                    <option value="Environmental nucleic acids"
                                        {{ 'Environmental nucleic acids' === $box_position->boxes->content_type ? 'selected' : '' }}>
                                        Environmental nucleic acids</option>
                                    <option value="Nucleic acids"
                                        {{ 'Nucleic acids' === $box_position->boxes->content_type ? 'selected' : '' }}>
                                        Nucleic acids</option>
                                </x-forms.select-input>
                            @else
                                @php
                                    $colorClass = match (true) {
                                        str_contains($box_position->boxes->content_type, 'Human')
                                            => 'from-red-100 to-red-200 text-red-800',
                                        str_contains($box_position->boxes->content_type, 'Animal')
                                            => 'from-orange-100 to-orange-200 text-orange-800',
                                        str_contains($box_position->boxes->content_type, 'Parasite')
                                            => 'from-purple-100 to-purple-200 text-purple-800',
                                        str_contains($box_position->boxes->content_type, 'Environmental')
                                            => 'from-green-100 to-green-200 text-green-800',
                                        str_contains($box_position->boxes->content_type, 'Nucleic')
                                            => 'from-blue-100 to-blue-200 text-blue-800',
                                        default => 'from-gray-100 to-gray-200 text-gray-800',
                                    };
                                @endphp
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r {{ $colorClass }} shadow-sm">
                                    {{ $box_position->boxes->content_type ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            {{ $box_position->boxes->n_rows . 'x' . $box_position->boxes->n_columns }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && $canEditRow)
                                <input type="date" value="{{ $box_position->date_moved ? \Carbon\Carbon::parse($box_position->date_moved)->format('Y-m-d') : '' }}"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $box_position->id }}, 'date_moved', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Date of Movement?">
                            @else
                                {{ $box_position->date_moved ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @php
                                $location = $box_position->locations;
                            @endphp
                            {{ $location?->name ? $location->name . ($location?->room ? ' (' . $location->room . ')' : '') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            {{ $box_position->sublocation ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @php
                                $laboratory = $box_position->locations?->laboratories;
                                $country = $laboratory?->countries;
                            @endphp
                            {{ $laboratory?->name ? $laboratory->name . ($country?->name ? ' (' . $country->name . ')' : '') : 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center index-people-cell">
                            <div class="flex items-center justify-center space-x-3">
                                <x-people-logo :person="$box_position->people" width="30" />
                                <a href="/profile/{{ $box_position->people->id }}"
                                    class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                    {{ $box_position->people->title . ' ' . $box_position->people->first_name . ' ' . $box_position->people->last_name ?? 'N/A' }}
                                </a>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="reason" name="reason"
                                    wire:change="updateField({{ $box_position->id }}, 'reason', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Reason of movement?"
                                    class="w-full min-w-[180px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Sample reorganization">Sample reorganization</option>
                                    <option value="Temperature condition change">Temperature condition change</option>
                                    <option value="Damaged box">Damaged box</option>
                                    <option value="Accidental fall of box">Accidental fall of box</option>
                                    <option value="Storage optimization">Storage optimization</option>
                                    <option value="Correct misplacement">Correct misplacement</option>
                                </x-forms.select-input>
                            @else
                                {{ $box_position->reason ?? 'N/A' }}
                            @endif
                        </td>
                        @if ($isEditing && $canEditRow)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    type="button" wire:click="delete({{ $box_position->id }})"
                                    wire:confirm="Are you sure you want to delete this box position?">
                                    <i class="fas fa-trash text-red-500 hover:text-red-600 mr-2"></i>
                                    Delete
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="100" class="px-6 py-12 text-center">
                            <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                <span class="text-sm text-gray-600">No box positions found.</span>
                                @if ($canEdit)
                                    <a href="/bank/boxes/create"
                                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                                        <i class="fas fa-plus-circle"></i>
                                        Register box position
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @include('livewire.partials.index-pagination-bar', ['paginator' => $box_positions])
    </div>
</div>
