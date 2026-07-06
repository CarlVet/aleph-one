<div
    class="text-center mt-2"
    x-data="{}"
    x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success', text: $event.detail.message, timer: 2200, showConfirmButton: false }); }"
    x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error', text: $event.detail.message, confirmButtonColor: '#d33' }); }"
>
    <div class="mt-2 flex items-center justify-center w-full relative mb-2">
        <!-- Icons Section (Right-Aligned) -->
        <div class="flex flex-col">
            <h2 class="text-xl font-bold mb-4 text-gray-700">Select content type:</h2>
            <div class="flex items-center space-x-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                <button wire:click="$set('selectedTable', 'sequences_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view All Sequences">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'sequences_table' ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-dna text-3xl {{ $selectedTable === 'sequences_table' ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'sequences_table' ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}">All
                            sequences</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'human_sequences_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Human Sequences">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'human_sequences_table' ? 'bg-pink-100 ring-2 ring-pink-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-person text-3xl {{ $selectedTable === 'human_sequences_table' ? 'text-pink-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'human_sequences_table' ? 'text-pink-600' : 'text-gray-500 group-hover:text-gray-600' }}">Humans</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'animal_sequences_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Animal Sequences">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'animal_sequences_table' ? 'bg-yellow-100 ring-2 ring-yellow-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-paw text-3xl {{ $selectedTable === 'animal_sequences_table' ? 'text-yellow-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'animal_sequences_table' ? 'text-yellow-600' : 'text-gray-500 group-hover:text-gray-600' }}">Animals</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'environment_sequences_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Environmental Sequences">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'environment_sequences_table' ? 'bg-green-100 ring-2 ring-green-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-leaf text-3xl {{ $selectedTable === 'environment_sequences_table' ? 'text-green-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'environment_sequences_table' ? 'text-green-600' : 'text-gray-500 group-hover:text-gray-600' }}">Environment</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'parasite_sequences_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Parasite Sequences">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'parasite_sequences_table' ? 'bg-purple-100 ring-2 ring-purple-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-spider text-3xl {{ $selectedTable === 'parasite_sequences_table' ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'parasite_sequences_table' ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}">Parasites</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'culture_sequences_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Culture Sequences">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'culture_sequences_table' ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-bacteria text-3xl {{ $selectedTable === 'culture_sequences_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'culture_sequences_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Cultures</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'pool_sequences_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Pool Sequences">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'pool_sequences_table' ? 'bg-cyan-100 ring-2 ring-cyan-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-layer-group text-3xl {{ $selectedTable === 'pool_sequences_table' ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'pool_sequences_table' ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}">Pools</span>
                    </div>
                </button>
            </div>
        </div>
    </div>

    @php
        $tableLabels = [
            'sequences_table' => 'All sequences',
            'human_sequences_table' => 'Human sequences',
            'animal_sequences_table' => 'Animal sequences',
            'environment_sequences_table' => 'Environmental sequences',
            'parasite_sequences_table' => 'Parasite sequences',
            'culture_sequences_table' => 'Culture sequences',
            'pool_sequences_table' => 'Pool sequences',
        ];
        $tableTitle = $tableLabels[$selectedTable] ?? 'Sequences';
    @endphp

    @if (in_array($selectedTable, ['sequences_table','human_sequences_table','animal_sequences_table','environment_sequences_table','parasite_sequences_table','culture_sequences_table','pool_sequences_table'], true))
        <!-- Create, Edit, Dashboard (Centered) -->
        <div class="text-center flex justify-center space-x-4 mt-6">
            @if ($canEdit ?? false)
                <a href="/samples/nucleic/sequences/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @else
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (No permission)
                </div>
            @endif

            @if ($isEditing)
                <a href="/samples/nucleic/sequences/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            @else
                @if ($canEdit ?? false)
                    <button wire:click="toggleEditMode"
                        class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                        <i class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Edit
                    </button>
                @else
                    <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                        <i class="fas fa-lock mr-2"></i>
                        Edit (No permission)
                    </div>
                @endif
            @endif
            <a href="/samples/nucleic/sequences/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i
                    class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>
        <!-- Table Section -->
        <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
            @php
                $showBulkActions = $isEditing && ($canEdit ?? false);
            @endphp
            <div class="flex flex-col items-center w-full p-4">
                <!-- Index Title (Centered) -->
                <h2 class="text-2xl font-bold text-gray-800 mb-4">
                    {{ $isEditing ? "Edit {$tableTitle}" : "List of {$tableTitle}" }}</h2>

                <!-- Export Button (Centered) -->
                <button wire:click="export"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                    <i
                        class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                    Export to CSV
                </button>
                @if ($showBulkActions)
                    <div class="mt-3 flex items-center gap-3">
                        <button type="button" wire:click="deleteSelected"
                            wire:confirm="Are you sure you want to delete the selected sequences?"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200"
                            title="Delete selected sequences">
                            <i class="fas fa-trash"></i>
                        </button>
                        <span class="text-sm text-gray-600">
                            {{ count(array_filter($selectedSequences ?? [])) }} selected
                        </span>
                    </div>
                @endif
            </div>

            @include('livewire.partials.index-per-page-toolbar', ['paginator' => $sequences])


            <div class="index-table-container overflow-x-auto">
            <table id="sequences_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
                <thead>
                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                        @if ($showBulkActions)
                            <th
                                class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                                Select</th>
                        @endif
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="sequence_code" :active="$sortField" :direction="$sortDirection">Sequence code</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="accession_number" :active="$sortField" :direction="$sortDirection">Accession number</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Experiment code</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Original nucleic acid content</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">Sub-project</x-sort-button></th>
                        @if ($selectedTable === 'sequences_table')
                            <th
                                class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                                Original content details</th>
                        @elseif ($selectedTable === 'human_sequences_table')
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Patient code</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Sample type</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Occupation</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Sex</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Age</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Country</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Ethnicity</th>
                        @elseif ($selectedTable === 'animal_sequences_table')
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Animal code</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Animal species</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Sample type</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Sex</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Age</th>
                        @elseif ($selectedTable === 'environment_sequences_table')
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Sample type</th>
                        @elseif ($selectedTable === 'parasite_sequences_table')
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Parasite species</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Stage</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Sex</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">State</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Sample type</th>
                        @elseif ($selectedTable === 'culture_sequences_table')
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Culture code</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Medium</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Culture type</th>
                        @elseif ($selectedTable === 'pool_sequences_table')
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Pool code</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Nr pooled</th>
                        @endif
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="length" :active="$sortField" :direction="$sortDirection">Length (nt)</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Target pathogen</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="method" :active="$sortField" :direction="$sortDirection">Method</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="instrument" :active="$sortField" :direction="$sortDirection">Instrument</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="date_sequenced" :active="$sortField" :direction="$sortDirection">Date sequenced</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="sequenced_by" :active="$sortField" :direction="$sortDirection">Sequenced by</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="sequenced_at" :active="$sortField" :direction="$sortDirection">Sequenced at</x-sort-button></th>
                        @if (!($canEdit ?? false))
                            {{-- guest mode typically (no selected project) --}}
                        @endif
                        @if (! (session('selected_project_id')))
                            <th
                                class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                                Project</th>
                        @endif
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            FASTA File</th>
                        @if ($isEditing && ($canEdit ?? false))
                            <th
                                class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                                Delete</th>
                        @endif
                    </tr>
                </thead>
                <thead class="bg-gradient-to-r from-gray-100 to-gray-50">
                    <tr>
                        @if ($showBulkActions)
                            <th class="px-6 py-3"></th>
                        @endif
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="codeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="accessionFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="experimentFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="originalContentFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="subProjectCodeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        @if ($selectedTable === 'sequences_table')
                            <th class="px-6 py-3"></th>
                        @elseif ($selectedTable === 'human_sequences_table')
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="patientCodeFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="humanSampleTypeFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="humanOccupationFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="humanSexFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <div class="flex items-center space-x-2">
                                    <input type="number" wire:model.live.debounce.300ms="humanMinAge"
                                        class="w-full min-w-[90px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Min">
                                    <span class="text-gray-500 font-medium">to</span>
                                    <input type="number" wire:model.live.debounce.300ms="humanMaxAge"
                                        class="w-full min-w-[90px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Max">
                                </div>
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="humanCountryFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="humanEthnicityFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                        @elseif ($selectedTable === 'animal_sequences_table')
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="animalCodeFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="animalSpeciesFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="animalSampleTypeFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="animalSexFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="animalAgeFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                        @elseif ($selectedTable === 'environment_sequences_table')
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="environmentSampleTypeFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                        @elseif ($selectedTable === 'parasite_sequences_table')
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="parasiteSpeciesFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="parasiteStageFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="parasiteSexFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="parasiteStateFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="parasiteSampleTypeFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                        @elseif ($selectedTable === 'culture_sequences_table')
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="cultureCodeFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="cultureMediumFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="cultureTypeFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                        @elseif ($selectedTable === 'pool_sequences_table')
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="poolCodeFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                            <th class="px-6 py-3"></th>
                        @endif
                        <th class="px-6 py-3">
                            <div class="flex items-center space-x-2">
                                <input type="numeric" wire:model.live.debounce.300ms="startLength"
                                    class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Min">
                                <span class="text-gray-500 font-medium">to</span>
                                <input type="numeric" wire:model.live.debounce.300ms="endLength"
                                    class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Max">
                            </div>
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="pathogenFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="methodFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="instrumentFilter"
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
                            <input type="text" wire:model.live.debounce.300ms="peopleFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="laboratoriesFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        @if (! (session('selected_project_id')))
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="projectsFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                        @endif
                        <th class="px-6 py-3"></th>
                        @if ($isEditing && ($canEdit ?? false))
                            <th class="px-6 py-3"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @php
                        $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
                    @endphp
                    @forelse ($sequences as $sequence)
                        @php
                            $canEditRow = ($canEdit ?? false) && (int) ($sequence->people_id ?? 0) === $currentPeopleId;
                        @endphp
                        <tr wire:key="{{ $sequence->id }}"
                            class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                            @if ($showBulkActions)
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <input type="checkbox" wire:model.live="selectedSequences.{{ $sequence->id }}"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        title="Select this sequence">
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $sequenceProfileUrl = session('selected_project_id')
                                        ? '/samples/nucleic/sequences/'.$sequence->code
                                        : '/guest/sequences/profile/'.$sequence->code;
                                @endphp
                                <a href="{{ $sequenceProfileUrl }}"
                                    class="text-blue-600 hover:text-blue-900">
                                    {{ $sequence->code }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="text" value="{{ $sequence->accession_number }}"
                                        class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $sequence->id }}, 'accession_number', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Accession Number?">
                                @else
                                    @if (filled($sequence->accession_number))
                                        <a
                                            href="https://www.ncbi.nlm.nih.gov/nuccore/{{ urlencode($sequence->accession_number) }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="font-medium text-blue-600 hover:text-blue-800 hover:underline"
                                        >
                                            {{ $sequence->accession_number }}
                                        </a>
                                    @else
                                        <span class="text-gray-500 italic">N/A</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $experiment = $sequence->nucleic_acids?->nucleic_content;
                                @endphp
                                @if ($experiment instanceof \App\Models\Experiments)
                                    <a href="/experiments/{{ $experiment->code }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ $experiment->code }}
                                    </a>
                                @else
                                    <span class="text-gray-500 italic">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $nucleic = $sequence->nucleic_acids;
                                    $originalNucleic = ($experiment && $experiment instanceof \App\Models\Experiments) ? ($experiment->experiments_content instanceof \App\Models\NucleicAcids ? $experiment->experiments_content : null) : null;
                                    $source = $originalNucleic?->nucleic_content ?? $nucleic?->nucleic_content;
                                    $originNucleicForAliases = $originalNucleic ?? $nucleic;
                                    $originalAliasCodes = collect(data_get($originNucleicForAliases, 'tubes', []))
                                        ->pluck('alias_code')
                                        ->filter(fn ($alias) => is_string($alias) && trim($alias) !== '')
                                        ->map(fn ($alias) => trim($alias))
                                        ->unique()
                                        ->values()
                                        ->all();
                                    $contentTypeName = $source ? class_basename($source) : 'N/A';
                                    $sourceCode = $source->code ?? 'N/A';
                                    $sourceUrl = match ($contentTypeName) {
                                        'HumanSamples' => '/samples/humans/'.$sourceCode,
                                        'AnimalSamples' => '/samples/animals/'.$sourceCode,
                                        'EnvironmentSamples' => '/samples/environment/'.$sourceCode,
                                        'ParasiteSamples' => '/samples/parasites/'.$sourceCode,
                                        'Cultures' => '/samples/cultures/'.$sourceCode,
                                        'Pools' => '/samples/pools/'.$sourceCode,
                                        'NucleicAcids' => '/samples/nucleic/'.$sourceCode,
                                        'Experiments' => '/experiments/'.$sourceCode,
                                        default => null,
                                    };
                                @endphp
                                <div class="flex flex-col items-center">
                                    <span
                                        class="px-2 py-1 text-xs font-semibold rounded-full 
                                        @if ($contentTypeName === 'HumanSamples') bg-pink-100 text-pink-800
                                        @elseif($contentTypeName === 'AnimalSamples') bg-yellow-100 text-yellow-800
                                        @elseif($contentTypeName === 'EnvironmentSamples') bg-green-100 text-green-800
                                        @elseif($contentTypeName === 'ParasiteSamples') bg-purple-100 text-purple-800
                                        @elseif($contentTypeName === 'Cultures') bg-orange-100 text-orange-800
                                        @elseif($contentTypeName === 'Pools') bg-cyan-100 text-cyan-800
                                        @elseif($contentTypeName === 'NucleicAcids') bg-blue-100 text-blue-800
                                        @elseif($contentTypeName === 'Experiments') bg-indigo-100 text-indigo-800
                                        @else bg-gray-100 text-gray-800 @endif">
                                        {{ $contentTypeName }}
                                    </span>
                                    @if ($sourceUrl && $sourceCode !== 'N/A' && session('selected_project_id'))
                                        <a href="{{ $sourceUrl }}" class="mt-1 text-xs text-blue-600 hover:text-blue-800 hover:underline">
                                            {{ $sourceCode }}
                                        </a>
                                    @else
                                        <span class="text-xs text-gray-600 mt-1">{{ $sourceCode }}</span>
                                    @endif
                                    <span class="mt-1 text-[11px] text-gray-500">
                                        Alias: {{ !empty($originalAliasCodes) ? implode(', ', $originalAliasCodes) : 'N/A' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if (optional($sequence->subProjectAssignment?->subProject)->code)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                        {{ $sequence->subProjectAssignment->subProject->code }}
                                    </span>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            @if ($selectedTable === 'sequences_table')
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $details = '';
                                        if ($source) {
                                            $details = match (class_basename($source)) {
                                                'HumanSamples' => trim(implode(' | ', array_filter([
                                                    $source->humans?->code ?? null,
                                                    $source->sample_types?->name ?? null,
                                                ]))),
                                                'AnimalSamples' => trim(implode(' | ', array_filter([
                                                    $source->animals?->code ?? null,
                                                    $source->animals?->animal_species?->name_common ?? null,
                                                    $source->sample_types?->name ?? null,
                                                ]))),
                                                'EnvironmentSamples' => $source->environment_sample_types?->name ?? '',
                                                'ParasiteSamples' => trim(implode(' | ', array_filter([
                                                    $source->parasites?->parasite_species?->name_scientific ?? null,
                                                    $source->parasites?->stage ?? null,
                                                    $source->parasites?->sex ?? null,
                                                ]))),
                                                'Cultures' => trim(implode(' | ', array_filter([
                                                    $source->code ?? null,
                                                    $source->medium ?? null,
                                                    $source->type ?? null,
                                                ]))),
                                                'Pools' => trim(implode(' | ', array_filter([
                                                    $source->code ?? null,
                                                    $source->nr_pooled ?? null,
                                                ]))),
                                                default => '',
                                            };
                                        }
                                        $detailsWithAlias = $details ?: 'N/A';
                                        if (!empty($originalAliasCodes)) {
                                            $detailsWithAlias .= ' | Tube alias: '.implode(', ', $originalAliasCodes);
                                        }
                                    @endphp
                                    <span class="text-gray-900 font-medium">{{ $detailsWithAlias }}</span>
                                </td>
                            @elseif ($selectedTable === 'human_sequences_table')
                                @php $human = ($source instanceof \App\Models\HumanSamples) ? $source->humans : null; @endphp
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($human?->code)
                                        <a href="/humans/{{ $human->code }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $human->code }}</a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ ($source instanceof \App\Models\HumanSamples) ? ($source->sample_types?->name ?? 'N/A') : 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $human?->occupation ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $human?->sex ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $age = $human?->date_of_birth ? \Carbon\Carbon::parse($human->date_of_birth)->age : null;
                                    @endphp
                                    {{ $age !== null ? $age : 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $human?->countries?->name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $human?->ethnicity ?? 'N/A' }}</td>
                            @elseif ($selectedTable === 'animal_sequences_table')
                                @php $animal = ($source instanceof \App\Models\AnimalSamples) ? $source->animals : null; @endphp
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($animal?->code)
                                        <a href="/animals/{{ $animal->code }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $animal->code }}</a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $animal?->animal_species?->name_common ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ ($source instanceof \App\Models\AnimalSamples) ? ($source->sample_types?->name ?? 'N/A') : 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $animal?->sex ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $animal?->age ?? 'N/A' }}</td>
                            @elseif ($selectedTable === 'environment_sequences_table')
                                <td class="px-6 py-4 whitespace-nowrap">{{ ($source instanceof \App\Models\EnvironmentSamples) ? ($source->environment_sample_types?->name ?? 'N/A') : 'N/A' }}</td>
                            @elseif ($selectedTable === 'parasite_sequences_table')
                                @php $parasite = ($source instanceof \App\Models\ParasiteSamples) ? $source->parasites : null; @endphp
                                <td class="px-6 py-4 whitespace-nowrap">{{ $parasite?->parasite_species?->name_scientific ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $parasite?->stage ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $parasite?->sex ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ $parasite?->state ?? 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ ($source instanceof \App\Models\ParasiteSamples) ? ($source->parasite_sample_types?->name ?? 'N/A') : 'N/A' }}</td>
                            @elseif ($selectedTable === 'culture_sequences_table')
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(($source instanceof \App\Models\Cultures) && $source->code)
                                        <a href="/samples/cultures/{{ $source->code }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $source->code }}</a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ ($source instanceof \App\Models\Cultures) ? ($source->medium ?? 'N/A') : 'N/A' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ ($source instanceof \App\Models\Cultures) ? ($source->type ?? 'N/A') : 'N/A' }}</td>
                            @elseif ($selectedTable === 'pool_sequences_table')
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(($source instanceof \App\Models\Pools) && $source->code)
                                        <a href="/samples/pools/{{ $source->code }}" class="text-blue-600 hover:text-blue-800 hover:underline">{{ $source->code }}</a>
                                    @else
                                        N/A
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">{{ ($source instanceof \App\Models\Pools) ? ($source->nr_pooled ?? 'N/A') : 'N/A' }}</td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="number" step="any" value="{{ $sequence->length }}"
                                        class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $sequence->id }}, 'length', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Length?">
                                @else
                                    <span class="text-gray-900 font-medium">{{ $sequence->length }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $pathogenSpecies = '';
                                    if ($experiment && $experiment->pathogens) {
                                        $pathogenSpecies = $experiment->pathogens->species ?? '';
                                    }
                                @endphp
                                <span class="text-gray-900 font-medium">{{ $pathogenSpecies ?: 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <x-forms.select-input id="method" name="method"
                                        wire:change="updateField({{ $sequence->id }}, 'method', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Method?"
                                        class="w-full min-w-[200px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($methods as $method)
                                            <option value="{{ $method }}"
                                                {{ $method === ($sequence->method ?? '') ? 'selected' : '' }}>
                                                {{ $method }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 shadow-sm">
                                        {{ $sequence->method }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <x-forms.select-input id="instrument" name="instrument"
                                        wire:change="updateField({{ $sequence->id }}, 'instrument', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Instrument?"
                                        class="w-full min-w-[140px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($instruments as $instrument)
                                            <option value="{{ $instrument }}"
                                                {{ $instrument === ($sequence->instrument ?? '') ? 'selected' : '' }}>
                                                {{ $instrument }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 shadow-sm">
                                        {{ $sequence->instrument }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="date" value="{{ $sequence->date_sequenced ? \Carbon\Carbon::parse($sequence->date_sequenced)->format('Y-m-d') : '' }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $sequence->id }}, 'date_sequenced', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Date Sequenced?">
                                @else
                                    <span class="text-gray-900 font-medium">{{ \Carbon\Carbon::parse($sequence->date_sequenced)->format('Y-m-d') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap index-people-cell">
                                <div class="flex items-center space-x-3">
                                    <x-people-logo :person="$sequence->people" width="30" />
                                    <a href="/profile/{{ $sequence->people->id }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ $sequence->people->title . ' ' . $sequence->people->first_name . ' ' . $sequence->people->last_name ?? 'N/A' }}
                                    </a>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <x-forms.select-input id="laboratories_id" name="laboratories_id"
                                        wire:change="updateField({{ $sequence->id }}, 'laboratories_id', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Laboratory?"
                                        class="w-full min-w-[200px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($laboratories as $lab)
                                            <option value="{{ $lab->id }}"
                                                {{ $lab->id === ($sequence->laboratories_id ?? '') ? 'selected' : '' }}>
                                                {{ $lab->name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <span class="text-gray-900 font-medium">{{ $sequence->laboratories->name ?? 'N/A' }}</span>
                                @endif
                            </td>
                            @if (! (session('selected_project_id')))
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($sequence->projects?->code)
                                        <a href="{{ route('projects.profile', $sequence->projects->code) }}"
                                            class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                            {{ $sequence->projects->code }}
                                        </a>
                                    @else
                                        <span class="text-gray-900 font-medium">N/A</span>
                                    @endif
                                </td>
                            @endif

                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($sequence->fasta_path)
                                    <div class="text-xs text-green-600">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        File uploaded
                                    </div>
                                    <a href="{{ Storage::url($sequence->fasta_path) }}" download
                                        class="text-xs text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-download mr-1"></i>
                                        Download
                                    </a>
                                @else
                                    <div class="text-xs text-gray-500">
                                        <i class="fas fa-file-upload mr-1"></i>
                                        No file
                                    </div>
                                @endif
                                <a href="/samples/nucleic/sequences/{{ $sequence->code }}"
                                    class="text-xs text-gray-600 hover:text-gray-900 hover:underline mt-2 inline-block">
                                    Manage file
                                </a>
                            </td>
                            @if ($isEditing && $canEditRow)
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        class="text-red-500 hover:text-red-600 transition-all duration-200 transform hover:scale-110"
                                        type="button" wire:click="delete({{ $sequence->id }})"
                                        wire:confirm="Are you sure you want to delete this sequence?">
                                        <i class="fas fa-trash text-xl"></i>
                                    </button>
                                </td>
                            @endif

                        </tr>
                    @empty
                        <tr>
                            <td colspan="30" class="px-6 py-10 text-center">
                                <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                    <span class="text-sm text-gray-600">No sequences found.</span>
                                    @if ($canEdit ?? false)
                                        <a href="/samples/nucleic/sequences/create"
                                            class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                            Register sequence
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>

            @include('livewire.partials.index-pagination-bar', ['paginator' => $sequences])

            <!-- Flash Messages -->
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="fixed bottom-4 right-4 z-50">
                @if (session()->has('message'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                        role="alert">
                        <span class="block sm:inline">{{ session('message') }}</span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                            <svg class="fill-current h-6 w-6 text-green-500" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <title>Close</title>
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                            </svg>
                        </span>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                        role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <title>Close</title>
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                            </svg>
                        </span>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
