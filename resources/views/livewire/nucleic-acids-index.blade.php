<div class="text-center mt-2">
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing animal samples with public tubes from all projects
                    </p>
                </div>
                <a href="/my-projects"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif

    <div class="mt-2 flex items-center justify-center w-full relative mb-2">
        <!-- Icons Section (Right-Aligned) -->
        <div class="flex flex-col">
            @if (!$isGuestMode)
                <!-- Left Arrow Home Link -->
                <a href="/samples/nucleic"
                    class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                    <i class="fas fa-arrow-left text-2xl mr-2"></i>
                    <span class="text-sm font-medium">Back to NA Home</span>
                </a>
            @endif
            <h2 class="text-xl font-bold mb-4 text-gray-700">Select content type:</h2>
            <div class="flex items-center space-x-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                <button wire:click="$set('selectedTable', 'nucleic_acids_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view All Nucleic Acids">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'nucleic_acids_table' ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-dna text-3xl {{ $selectedTable === 'nucleic_acids_table' ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'nucleic_acids_table' ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}">All</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'human_samples_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Human Nucleic Acids">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'human_samples_table' ? 'bg-pink-100 ring-2 ring-pink-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-person text-3xl {{ $selectedTable === 'human_samples_table' ? 'text-pink-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'human_samples_table' ? 'text-pink-600' : 'text-gray-500 group-hover:text-gray-600' }}">Humans</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'animal_samples_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Animal Nucleic Acids">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'animal_samples_table' ? 'bg-yellow-100 ring-2 ring-yellow-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-paw text-3xl {{ $selectedTable === 'animal_samples_table' ? 'text-yellow-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'animal_samples_table' ? 'text-yellow-600' : 'text-gray-500 group-hover:text-gray-600' }}">Animals</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'environment_samples_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Environmental Nucleic Acids">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'environment_samples_table' ? 'bg-green-100 ring-2 ring-green-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-leaf text-3xl {{ $selectedTable === 'environment_samples_table' ? 'text-green-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'environment_samples_table' ? 'text-green-600' : 'text-gray-500 group-hover:text-gray-600' }}">Environment</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'parasite_samples_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Parasite Nucleic Acids">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'parasite_samples_table' ? 'bg-purple-100 ring-2 ring-purple-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-spider text-3xl {{ $selectedTable === 'parasite_samples_table' ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'parasite_samples_table' ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}">Parasites</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'culture_samples_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Culture Nucleic Acids">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'culture_samples_table' ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-bacteria text-3xl {{ $selectedTable === 'culture_samples_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'culture_samples_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Cultures</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'pool_samples_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Pool Nucleic Acids">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'pool_samples_table' ? 'bg-cyan-100 ring-2 ring-cyan-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-layer-group text-3xl {{ $selectedTable === 'pool_samples_table' ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'pool_samples_table' ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}">Pools</span>
                    </div>
                </button>
            </div>
        </div>
    </div>

    @if ($selectedTable === 'nucleic_acids_table')
        <!-- Create, Edit, Dashboard (Centered) -->
        <div class="text-center flex justify-center space-x-4 mt-6">
            @if (!$isGuestMode)
                @if (!$canEdit)
                    <div
                        class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
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
                    <div
                        class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
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
                <i
                    class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>
        <!-- Table Section -->
        <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
            @php
                $showBulkActions = $canEdit && ! $isGuestMode;
            @endphp
            <div class="flex flex-col items-center w-full p-4">
                <!-- Index Title (Centered) -->
                <h2 class="text-2xl font-bold text-gray-800 mb-4">
                    @if ($isGuestMode)
                        <i class="fas fa-eye text-purple-600 mr-2"></i>
                        Public Nucleic Acids
                    @else
                        {{ $isEditing ? 'Edit Nucleic Acids' : 'List of Nucleic Acids' }}
                    @endif
                </h2>

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
            <table id="nucleic_acids_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
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
                        <option value="{{ $protocol->name ?? $protocol['name'] }}">
                            {{ $protocol->name ?? $protocol['name'] }}</option>
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
                            Tube code (current project)</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Content type</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Content code</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Sub-project</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Elution type</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Nucleic type</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Extraction Protocol</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Extracted by</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Extracted at</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Date extracted</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Volume</th>
                        @if ($isEditing)
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
                            <input type="text" wire:model.live.debounce.300ms="tubeIdFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="contentTypeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="contentIdFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="subProjectCodeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
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
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Start Date">
                                <span class="text-gray-500 font-medium">to</span>
                                <input type="date" wire:model.live.debounce.300ms="endDate"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="End Date">
                            </div>
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="volumeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        @if ($isEditing)
                            <th class="px-6 py-3"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($nucleic_tubes as $tube)
                        @php
                            $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
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
                                @if ($isEditing)
                                    <!-- Existing Tubes List -->
                                    <div class="space-y-2 min-w-[200px]">
                                        <div class="flex items-center space-x-2">
                                            <div class="flex flex-col">
                                                <div>
                                                    <span class="text-sm text-gray-700">
                                                        {{ $tube->code ?? 'N/A' }}
                                                    </span>
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
                                                </div>
                                                @if ($tube->alias_code)
                                                    <span
                                                        class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded mt-1">
                                                        Alias: {{ $tube->alias_code }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                    </div>
                                @else
                                    <ul>
                                        <li class="flex items-center space-x-2 mb-1">
                                            <div class="flex flex-col">
                                                <div>
                                                    @if ($isGuestMode)
                                                        <span class="text-gray-900 font-medium">{{ $tube->code ?? 'N/A' }}</span>
                                                    @else
                                                        <a href="/bank/tubes/{{ $tube->code }}"
                                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                            {{ $tube->code ?? 'N/A' }}
                                                        </a>
                                                    @endif
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
                                    </ul>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $type = $tube->tubes_content->nucleic_content_type;
                                    $code = $tube->tubes_content->nucleic_content->code;
                                    $route = null;
                                    $badge = null;
                                    if ($type === 'App\\Models\\HumanSamples') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-pink-100 to-pink-200 text-pink-800 shadow-sm">Human Sample</span>';
                                        $route = "/samples/humans/{$code}";
                                    } elseif ($type === 'App\\Models\\AnimalSamples') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800 shadow-sm">Animal Sample</span>';
                                        $route = "/samples/animals/{$code}";
                                    } elseif ($type === 'App\\Models\\EnvironmentSamples') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-green-100 to-green-200 text-green-800 shadow-sm">Environmental Sample</span>';
                                        $route = "/samples/environments/{$code}";
                                    } elseif ($type === 'App\\Models\\ParasiteSamples') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm">Parasite Sample</span>';
                                        $route = "/samples/parasites/{$code}";
                                    } elseif ($type === 'App\\Models\\Experiments') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-900 to-blue-800 text-blue-100 shadow-sm">Experiments</span>';
                                        $route = "/experiments/{$code}";
                                    } elseif ($type === 'App\\Models\\Cultures') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-sm">Culture</span>';
                                        $route = "/samples/cultures/{$code}";
                                    } elseif ($type === 'App\\Models\\Pools') {
                                        $badge =
                                            '<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-cyan-100 to-cyan-200 text-cyan-800 shadow-sm">Pool</span>';
                                        $route = "/samples/pools/{$code}";
                                    }
                                @endphp
                                {!! $badge !!}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($route && $code !== 'N/A')
                                    @php
                                        $isSampleContentType = in_array($type, [
                                            'App\\Models\\HumanSamples',
                                            'App\\Models\\AnimalSamples',
                                            'App\\Models\\EnvironmentSamples',
                                            'App\\Models\\ParasiteSamples',
                                            'App\\Models\\Cultures',
                                            'App\\Models\\Pools',
                                        ], true);
                                    @endphp
                                    @if ($isGuestMode && $isSampleContentType)
                                        <span class="text-gray-900 font-medium">{{ $code }}</span>
                                    @else
                                        <a href="{{ $route }}"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">{{ $code }}</a>
                                    @endif
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if (optional($tube->tubes_content?->subProjectAssignment?->subProject)->code)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                        {{ $tube->tubes_content->subProjectAssignment->subProject->code }}
                                    </span>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
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
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 shadow-sm">
                                        {{ $tube->tubes_content->type ?? 'N/A' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="text" list="nucleic-protocols-list"
                                        value="{{ $tube->tubes_content->protocols->name ?? '' }}"
                                        x-data="{ original: @js($tube->tubes_content->protocols->name ?? '') }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit the type of Nucleic Acid Extraction Protocol?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $tube->id }}, 'protocol', $el.value);
                                        "
                                        class="w-full min-w-[240px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        autocomplete="off">
                                @else
                                    <a href="/protocols/{{ $tube->tubes_content->protocols->code }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ $tube->tubes_content->protocols->name ?? 'N/A' }}
                                    </a>
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
                                            if (!confirm('Are you sure you want to edit the Date of Nucleic Acid Extraction?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $tube->id }}, 'date_extracted', $el.value);
                                        "
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                @else
                                    <span
                                        class="text-gray-900 font-medium">{{ \Carbon\Carbon::parse($tube->tubes_content->date_extracted)->format('Y-m-d') ?? 'N/A' }}</span>
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
                                        class="w-full min-w-[80px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        autocomplete="off">
                                @else
                                    <span
                                        class="text-gray-900 font-medium">{{ $tube->tubes_content->volume . ' µl' ?? 'N/A' }}</span>
                                @endif
                            </td>
                            @if ($isEditing)
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($canEditRow)
                                        <button
                                            class="text-red-500 hover:text-red-600 transition-all duration-200 transform hover:scale-110"
                                            type="button" wire:click="delete({{ $tube->id }})"
                                            wire:confirm="Are you sure you want to delete this nucleic tube?">
                                            <i class="fas fa-trash text-xl"></i>
                                        </button>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $showBulkActions ? 13 : 12 }}" class="px-6 py-10 text-center">
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

            @include('livewire.partials.index-pagination-bar', ['paginator' => $nucleic_tubes])

        </div>
    @elseif ($selectedTable === 'human_samples_table')
        @include('livewire.partials.nucleic-acids-origin-table', [
            'subtitle' => 'extracted from human samples',
            'tableId' => 'human_samples_table',
            'originLabel' => 'Human sample code',
            'originRoutePrefix' => '/samples/humans',
            'extraColumns' => [
                [
                    'label' => 'Sample type',
                    'filterModel' => 'sampleTypeFilter',
                    'valuePath' => 'tubes_content.nucleic_content.sample_types.name',
                ],
                [
                    'label' => 'Sampling site',
                    'filterModel' => 'samplingSiteFilter',
                    'valuePath' => 'tubes_content.nucleic_content.sampling_sites.name',
                ],
                [
                    'label' => 'Collection date',
                    'filterType' => 'date_range',
                    'filterModelStart' => 'collectionStartFilter',
                    'filterModelEnd' => 'collectionEndFilter',
                    'value' => function ($tube) {
                        $date = $tube->tubes_content?->nucleic_content?->date_collected;
        
                        return $date ? $date->format('Y-m-d') : null;
                    },
                ],
            ],
        ])
    @elseif ($selectedTable === 'animal_samples_table')
        @include('livewire.partials.nucleic-acids-origin-table', [
            'subtitle' => 'extracted from animal samples',
            'tableId' => 'animal_samples_table',
            'originLabel' => 'Animal sample code',
            'originRoutePrefix' => '/samples/animals',
            'extraColumns' => [
                [
                    'label' => 'Species',
                    'filterModel' => 'speciesFilter',
                    'valuePath' => 'tubes_content.nucleic_content.animals.animal_species.name_common',
                ],
                [
                    'label' => 'Sampling site',
                    'filterModel' => 'siteFilter',
                    'valuePath' => 'tubes_content.nucleic_content.sampling_sites.name',
                ],
                [
                    'label' => 'Sample type',
                    'filterModel' => 'sampleTypeFilter',
                    'valuePath' => 'tubes_content.nucleic_content.sample_types.name',
                ],
            ],
        ])
    @elseif ($selectedTable === 'environment_samples_table')
        @include('livewire.partials.nucleic-acids-origin-table', [
            'subtitle' => 'extracted from environmental samples',
            'tableId' => 'environment_samples_table',
            'originLabel' => 'Environment sample code',
            'originRoutePrefix' => '/samples/environment',
            'extraColumns' => [
                [
                    'label' => 'Sampling site',
                    'filterModel' => 'samplingSiteFilter',
                    'valuePath' => 'tubes_content.nucleic_content.sampling_sites.name',
                ],
                [
                    'label' => 'Sample type',
                    'filterModel' => 'sampleTypeFilter',
                    'valuePath' => 'tubes_content.nucleic_content.environment_sample_types.name',
                ],
            ],
        ])
    @elseif ($selectedTable === 'parasite_samples_table')
        @include('livewire.partials.nucleic-acids-origin-table', [
            'subtitle' => 'extracted from parasite samples',
            'tableId' => 'parasite_samples_table',
            'originLabel' => 'Parasite sample code',
            'originRoutePrefix' => '/samples/parasites',
        ])
    @elseif ($selectedTable === 'culture_samples_table')
        @include('livewire.partials.nucleic-acids-origin-table', [
            'subtitle' => 'extracted from cultures',
            'tableId' => 'culture_samples_table',
            'originLabel' => 'Culture code',
            'originRoutePrefix' => '/samples/cultures',
        ])
    @elseif ($selectedTable === 'pool_samples_table')
        @include('livewire.partials.nucleic-acids-origin-table', [
            'subtitle' => 'extracted from pools',
            'tableId' => 'pool_samples_table',
            'originLabel' => 'Pool code',
            'originRoutePrefix' => '/samples/pools',
        ])
    @endif

    <!-- Tube Request Modal -->
    @if ($isGuestMode)
        @if ($showTubeRequestModal)
            <div wire:key="tube-request-modal-{{ $selectedTubeId }}"
                class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="modal">
                <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                    <div class="mt-3">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-medium text-gray-900">Request Tube Access</h3>
                            <button wire:click="closeTubeRequestModal" class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>

                        @if ($selectedTube)
                            <div class="mb-4">
                                <div class="bg-gray-50 p-3 rounded-lg">
                                    <h4 class="font-medium text-gray-900 mb-2">Tube Details</h4>
                                    <p class="text-sm text-gray-600"><strong>Code:</strong> {{ $selectedTube->code }}
                                    </p>
                                    <p class="text-sm text-gray-600"><strong>Type:</strong>
                                        {{ $selectedTube->tube_type ?? 'Nucleic Acid' }}</p>
                                    <p class="text-sm text-gray-600"><strong>Source Project:</strong>
                                        {{ $sourceProject->code ?? 'N/A' }}</p>
                                </div>
                            </div>

                            <form>
                                <div class="mb-4">
                                    <label for="targetProjectId" class="block text-sm font-medium text-gray-700 mb-2">
                                        Select Target Project *
                                    </label>
                                    <select wire:model="targetProjectId" id="targetProjectId"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        required>
                                        <option value="">Choose a project...</option>
                                        @foreach ($userProjects as $project)
                                            <option value="{{ $project->id }}">
                                                {{ $project->code }} - {{ $project->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('targetProjectId')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="requestMessage" class="block text-sm font-medium text-gray-700 mb-2">
                                        Request Message (Optional)
                                    </label>
                                    <textarea wire:model="requestMessage" id="requestMessage" rows="3"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Explain why you need access to this tube..."></textarea>
                                    @error('requestMessage')
                                        <span class="text-red-500 text-xs">{{ $message }}</span>
                                    @enderror
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <button type="button" wire:click="closeTubeRequestModal"
                                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                        Cancel
                                    </button>
                                    <button type="button" wire:click="submitTubeRequest"
                                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                        Submit Request
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="text-center">
                                <p class="text-gray-500">Tube not found.</p>
                                <button wire:click="closeTubeRequestModal"
                                    class="mt-3 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                    Close
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('swal', function(event) {
                if (typeof Swal === 'undefined') {
                    return;
                }

                const detail = event.detail || {};
                const payload = Array.isArray(detail) ? (detail[0] || {}) : detail;

                Swal.fire({
                    icon: payload.icon || 'success',
                    title: payload.title || 'Success',
                    text: payload.text || '',
                });
            });
        </script>
    @endpush
@endonce
