<div class="text-center mt-2">
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public literature data from all projects</p>
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
            <h2 class="text-xl font-bold mb-4 text-gray-700">Select content type:</h2>
            <div class="flex items-center space-x-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                <button wire:click="$set('selectedTable', 'meta_human_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Human Meta Data">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'meta_human_table' ? 'bg-pink-100 ring-2 ring-pink-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-person text-3xl {{ $selectedTable === 'meta_human_table' ? 'text-pink-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'meta_human_table' ? 'text-pink-600' : 'text-gray-500 group-hover:text-gray-600' }}">Humans</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'meta_animal_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Animal Meta Data">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'meta_animal_table' ? 'bg-yellow-100 ring-2 ring-yellow-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-paw text-3xl {{ $selectedTable === 'meta_animal_table' ? 'text-yellow-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'meta_animal_table' ? 'text-yellow-600' : 'text-gray-500 group-hover:text-gray-600' }}">Animals</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'meta_parasite_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Parasite Literature Data">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'meta_parasite_table' ? 'bg-purple-100 ring-2 ring-purple-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-spider text-3xl {{ $selectedTable === 'meta_parasite_table' ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'meta_parasite_table' ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}">Parasites</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'meta_environment_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Environment Literature Data">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'meta_environment_table' ? 'bg-green-100 ring-2 ring-green-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-leaf text-3xl {{ $selectedTable === 'meta_environment_table' ? 'text-green-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'meta_environment_table' ? 'text-green-600' : 'text-gray-500 group-hover:text-gray-600' }}">Environment</span>
                    </div>
                </button>
            </div>
        </div>
    </div>

    @if ($selectedTable === 'meta_animal_table')
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
                    <a href="/meta/create"
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
                <a href="/meta/list/animal"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            @else
                @if (!$isGuestMode)
                    @if (!$canEdit)
                        <div
                            class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                            <i class="fas fa-lock mr-2"></i>
                            Edit (Viewer)
                        </div>
                    @else
                        <button wire:click="toggleEditMode"
                            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                            <i
                                class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                            Edit
                        </button>
                    @endif
                @else
                    <div
                        class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                        <i class="fas fa-lock mr-2"></i>
                        Edit (Guest Mode)
                    </div>
                @endif
            @endif
            <a href="/meta/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i
                    class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>

        <!-- Table Section -->
        @php
            $showBulkActions = ! $isGuestMode && $canEdit;
        @endphp
        <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
            <div class="flex flex-col items-center w-full p-4">
                <!-- Index Title (Centered) -->
                <h2 class="text-2xl font-bold text-gray-800 mb-4">
                    @if ($isGuestMode)
                        <i class="fas fa-eye text-purple-600 mr-2"></i>
                        Public Literature Data
                    @else
                        {{ $isEditing ? 'Edit Literature Data' : 'List of Literature Data' }}
                    @endif
                </h2>
                <h3 class="text-lg text-gray-600 mb-4">from animal samples</h3>

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
                            wire:confirm="Are you sure you want to delete the selected literature records?"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200"
                            title="Delete selected literature records">
                            <i class="fas fa-trash"></i>
                        </button>
                        <span class="text-sm text-gray-600">{{ count(array_filter($selectedMetaAnimals ?? [])) }} selected</span>
                    </div>
                @endif
            </div>

            @include('livewire.partials.index-per-page-toolbar', ['paginator' => $meta_animals])


            <div class="index-table-container overflow-x-auto">
            <table id="meta_animals_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
                <thead>
                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                    @if ($showBulkActions)
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Select
                        </th>
                    @endif
                        @if ($isGuestMode)
                            <th
                                class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                                <x-sort-button field="project" :active="$sortField" :direction="$sortDirection">Project</x-sort-button></th>
                        @endif
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="study" :active="$sortField" :direction="$sortDirection">Study</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="animal_species" :active="$sortField" :direction="$sortDirection">Animal Species</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="sample_type" :active="$sortField" :direction="$sortDirection">Sample Type</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="location" :active="$sortField" :direction="$sortDirection">Location</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="country" :active="$sortField" :direction="$sortDirection">Country</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="date_sampling" :active="$sortField" :direction="$sortDirection">Date Sampling</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="pathogen" :active="$sortField" :direction="$sortDirection">Pathogen</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="technique" :active="$sortField" :direction="$sortDirection">Technique</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="tested_n" :active="$sortField" :direction="$sortDirection">Tested N</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="pos_n" :active="$sortField" :direction="$sortDirection">Positive N</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Risk Factors</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Clinical Signs</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Lesions</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">Sub-project</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="reviewer" :active="$sortField" :direction="$sortDirection">Reviewer</x-sort-button></th>
                        @if ($isEditing && !$isGuestMode)
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
                        @if ($isGuestMode)
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="projectFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                        @endif
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="studyFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="animalSpeciesFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="sampleTypeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="samplingSiteFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="countryFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <div class="flex items-center space-x-2">
                                <input type="date" wire:model.live.debounce.300ms="collectedStartDate"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Start Date">
                                <span class="text-gray-500 font-medium">to</span>
                                <input type="date" wire:model.live.debounce.300ms="collectedEndDate"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="End Date">
                            </div>
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="pathogenFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="techniqueFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="number" wire:model.live.debounce.300ms="testedNFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="number" wire:model.live.debounce.300ms="posNFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="riskFactorFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="clinicalSignsFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="lesionsFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="subProjectCodeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="scientistFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        @if ($isEditing && !$isGuestMode)
                            <th class="px-6 py-3"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($meta_animals as $meta)
                        <tr wire:key="{{ $meta->id }}"
                            class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                            @if ($showBulkActions)
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <input type="checkbox" wire:model.live="selectedMetaAnimals.{{ $meta->id }}"
                                        class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                                </td>
                            @endif
                            @if ($isGuestMode)
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($meta->projects?->code)
                                        <a href="{{ route('projects.profile', $meta->projects->code) }}"
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm hover:underline">
                                            {{ $meta->projects->code }}
                                        </a>
                                    @else
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm">N/A</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                                @if ($isEditing && ! $isGuestMode && $canEdit)
                                    <select
                                        class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                        x-data="{ original: @js((int) ($meta->studies_id ?? 0)) }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit Study?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $meta->id }}, 'studies_id', $el.value);
                                        "
                                    >
                                        @foreach (($studiesOptions ?? []) as $opt)
                                            <option value="{{ $opt->id }}" @selected((int) ($meta->studies_id ?? 0) === (int) $opt->id)>
                                                {{ $opt->ref_key }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <a href="/studies/{{ $meta->studies->id ?? '#' }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ $meta->studies->ref_key ?? 'N/A' }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                                @if ($isEditing && ! $isGuestMode && $canEdit)
                                    <select
                                        class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                        x-data="{ original: @js((int) ($meta->animal_species_id ?? 0)) }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit Animal species?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $meta->id }}, 'animal_species_id', $el.value);
                                        "
                                    >
                                        @foreach (($animalSpeciesOptions ?? []) as $opt)
                                            <option value="{{ $opt->id }}" @selected((int) ($meta->animal_species_id ?? 0) === (int) $opt->id)>
                                                {{ $opt->name_common }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800 shadow-sm">
                                        {{ $meta->animal_species->name_common ?? 'N/A' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                                @if ($isEditing && ! $isGuestMode && $canEdit)
                                    <select
                                        class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                        x-data="{ original: @js((int) ($meta->sample_types_id ?? 0)) }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit Sample type?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $meta->id }}, 'sample_types_id', $el.value);
                                        "
                                    >
                                        @foreach (($sampleTypesOptions ?? []) as $opt)
                                            <option value="{{ $opt->id }}" @selected((int) ($meta->sample_types_id ?? 0) === (int) $opt->id)>
                                                {{ $opt->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 shadow-sm">
                                        {{ $meta->sample_types->name ?? 'N/A' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                                @if ($isEditing && ! $isGuestMode && $canEdit)
                                    <input type="text"
                                        value="{{ $meta->location ?? '' }}"
                                        x-data="{ original: @js($meta->location ?? '') }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit Location?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $meta->id }}, 'location', $el.value);
                                        "
                                        class="w-full min-w-[18rem] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Location">
                                @else
                                    <span class="text-gray-900 font-medium">{{ $meta->location ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                                @if ($isEditing && ! $isGuestMode && $canEdit)
                                    <select
                                        class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                        x-data="{ original: @js((int) ($meta->countries_id ?? 0)) }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit Country?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $meta->id }}, 'countries_id', $el.value);
                                        "
                                    >
                                        @foreach (($countriesOptions ?? []) as $opt)
                                            <option value="{{ $opt->id }}" @selected((int) ($meta->countries_id ?? 0) === (int) $opt->id)>
                                                {{ $opt->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <span class="text-gray-900 font-medium">{{ $meta->countries->name ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && ! $isGuestMode && $canEdit)
                                    @php
                                        $dateSamplingYmd = $meta->date_sampling ? \Carbon\Carbon::parse($meta->date_sampling)->format('Y-m-d') : '';
                                    @endphp
                                    <input type="date"
                                        value="{{ $dateSamplingYmd }}"
                                        x-data="{ original: @js($dateSamplingYmd) }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit Date sampling?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $meta->id }}, 'date_sampling', $el.value);
                                        "
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent shadow-sm transition-all duration-200">
                                @else
                                    <span class="text-gray-900 font-medium">{{ $meta->date_sampling ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                                @if ($isEditing && ! $isGuestMode && $canEdit)
                                    <select
                                        class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                        x-data="{ original: @js((int) ($meta->pathogens_id ?? 0)) }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit Pathogen?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $meta->id }}, 'pathogens_id', $el.value);
                                        "
                                    >
                                        @foreach (($pathogensOptions ?? []) as $opt)
                                            <option value="{{ $opt->id }}" @selected((int) ($meta->pathogens_id ?? 0) === (int) $opt->id)>
                                                {{ $opt->species }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-red-100 to-red-200 text-red-800 shadow-sm">
                                        {{ $meta->pathogens->species ?? 'N/A' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                                @if ($isEditing && ! $isGuestMode && $canEdit)
                                    <select
                                        class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-yellow-500 focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                        x-data="{ original: @js((int) ($meta->techniques_id ?? 0)) }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit Technique?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $meta->id }}, 'techniques_id', $el.value);
                                        "
                                    >
                                        @foreach (($techniquesOptions ?? []) as $opt)
                                            <option value="{{ $opt->id }}" @selected((int) ($meta->techniques_id ?? 0) === (int) $opt->id)>
                                                {{ $opt->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-green-100 to-green-200 text-green-800 shadow-sm">
                                        {{ $meta->techniques->name ?? 'N/A' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[10rem]">
                                @if ($isEditing && ! $isGuestMode && $canEdit)
                                    <input type="number" min="0"
                                        value="{{ $meta->tested_n ?? '' }}"
                                        x-data="{ original: @js($meta->tested_n ?? '') }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit Tested N?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $meta->id }}, 'tested_n', $el.value);
                                        "
                                        class="w-full min-w-[10rem] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Tested N">
                                @else
                                    <span class="text-gray-900 font-medium">{{ $meta->tested_n ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[10rem]">
                                @if ($isEditing && ! $isGuestMode && $canEdit)
                                    <input type="number" min="0"
                                        value="{{ $meta->pos_n ?? '' }}"
                                        x-data="{ original: @js($meta->pos_n ?? '') }"
                                        x-on:change.stop.prevent="
                                            if (!confirm('Are you sure you want to edit Positive N?')) {
                                                $el.value = original;
                                                return;
                                            }
                                            original = $el.value;
                                            $wire.updateField({{ $meta->id }}, 'pos_n', $el.value);
                                        "
                                        class="w-full min-w-[10rem] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Positive N">
                                @else
                                    <span class="text-gray-900 font-medium">{{ $meta->pos_n ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                                <x-meta.multi-value-cell
                                    :values="$meta->risk_factors->pluck('name')"
                                    label="Risk Factors"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                                <x-meta.multi-value-cell
                                    :values="$meta->clinical_signs->pluck('name')"
                                    label="Clinical Signs"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                                <x-meta.multi-value-cell
                                    :values="$meta->lesions->pluck('name')"
                                    label="Lesions"
                                />
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if (optional($meta->subProjectAssignment?->subProject)->code)
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                        {{ $meta->subProjectAssignment->subProject->code }}
                                    </span>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap index-people-cell">
                                <div class="flex items-center space-x-3">
                                    <x-people-logo :person="$meta->people" width="30" />
                                    <a href="/profile/{{ $meta->people->id }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ $meta->people->title . ' ' . $meta->people->first_name . ' ' . $meta->people->last_name ?? 'N/A' }}
                                    </a>
                                </div>
                            </td>
                            @if ($isEditing && !$isGuestMode)
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        class="text-red-500 hover:text-red-600 transition-all duration-200 transform hover:scale-110"
                                        type="button" wire:click="delete({{ $meta->id }})"
                                        wire:confirm="Are you sure you want to delete this meta data?">
                                        <i class="fas fa-trash text-xl"></i>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ ($isGuestMode ? 17 : 16) + ($showBulkActions ? 1 : 0) }}"
                                class="px-6 py-12 text-center">
                                <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                    <span class="text-sm text-gray-600">No literature records found.</span>
                                    @if (! $isGuestMode)
                                        <a href="/meta/create"
                                            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                                            <i class="fas fa-plus-circle"></i>
                                            Register literature
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>

            @include('livewire.partials.index-pagination-bar', ['paginator' => $meta_animals])
        </div>
    @elseif ($selectedTable === 'meta_human_table')
        @livewire('meta_human-index')
    @elseif ($selectedTable === 'meta_parasite_table')
        @livewire('meta_parasite-index')
    @elseif ($selectedTable === 'meta_environment_table')
        @livewire('meta_environment-index')
    @endif
</div>
