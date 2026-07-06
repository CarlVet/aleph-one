<div>
    <!-- Table Section -->
    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        <div class="flex flex-col items-center w-full p-4">
            <!-- Index Title (Centered) -->
            <h2 class="text-2xl font-bold text-gray-800 mb-4">{{ $isEditing ? 'Edit Environmental Box Positions' : 'List of Environmental Box Positions' }}</h2>

            <!-- Export Button (Centered) -->
            <button wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $box_positions])


        <div class="index-table-container overflow-x-auto">
        <table id="box_positions_environmental_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Box code</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content type</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Current location</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sub-location</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Facility (country)</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date moved</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Moved by</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reason moved</th>
                    @if ($isEditing)
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Delete</th>
                    @endif
                </tr>
            </thead>
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="boxCodeFilter" 
                            class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="contentTypeFilter"
                            class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
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
                @foreach ($box_positions as $box_position)
                    <tr wire:key="{{ $box_position->id }}" class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing)
                                <input list="box-codes" id="box" name="box"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $box_position->id }}, 'box', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Box Code?"
                                    wire:model.defer="box_code"
                                    placeholder="Search and select box code"
                                    value="{{ $box_position->boxes->code }}">

                                <datalist id="box-codes">
                                    @foreach ($boxes as $box)
                                        <option value="{{ $box->code }}">
                                        </option>
                                    @endforeach
                                </datalist>
                            @else
                                <a href="/bank/boxes/{{ $box_position->boxes->id }}/contents" class="text-blue-600 hover:text-blue-800 transition-colors duration-200" title="Click to view box grid">
                                    {{ $box_position->boxes->code ?? 'N/A' }}
                                </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing)
                                <x-forms.select-input id="content_type" name="content_type"
                                    wire:change="updateField({{ $box_position->id }}, 'content_type', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Content Type of the box?"
                                    class="w-full min-w-[180px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Environmental samples" {{ 'Environmental samples' === $box_position->boxes->content_type ? 'selected' : '' }}>Environmental samples</option>
                                    <option value="Environmental nucleic acids" {{ 'Environmental nucleic acids' === $box_position->boxes->content_type ? 'selected' : '' }}>Environmental nucleic acids</option>
                                </x-forms.select-input>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-green-100 to-green-200 text-green-800 shadow-sm">
                                    {{ $box_position->boxes->content_type ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $box_position->locations->name . ' (' . $box_position->locations->room . ')' ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $box_position->sublocation ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $box_position->locations->places->name . ' (' . $box_position->locations->places->country . ')' ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing)
                                <input type="date" value="{{ $box_position->date_moved ? \Carbon\Carbon::parse($box_position->date_moved)->format('Y-m-d') : '' }}"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $box_position->id }}, 'date_moved', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Date of Movement?">
                            @else
                                {{ $box_position->date_moved ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            {{ $box_position->people->title . ' ' . $box_position->people->first_name . ' ' . $box_position->people->last_name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing)
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
                        @if ($isEditing)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    type="button" wire:click="delete({{ $box_position->id }})"
                                    wire:confirm="Are you sure you want to delete this box position?">
                                    <i class="fas fa-trash text-red-500 hover:text-red-600 mr-2"></i>
                                    Delete
                                </button>
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @include('livewire.partials.index-pagination-bar', ['paginator' => $box_positions])
    </div>
</div>
