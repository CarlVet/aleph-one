<div class="text-center mt-2">

    <!-- Create, Edit, Dashboard (Centered) -->
    <div class="text-center flex justify-center space-x-4 mt-6">
        @if (!$isGuestMode)
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
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
                        <i class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Edit
                    </button>
                @endif
            @else
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Edit (Guest Mode)
                </div>
            @endif
        @endif
        <a href="/meta/dashboard"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
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
            <h3 class="text-lg text-gray-600 mb-4">from parasite samples</h3>

            <!-- Export Button (Centered) -->
            @include('livewire.partials.export-buttons')
            @if ($showBulkActions)
                <div class="mt-3 flex items-center gap-3">
                    <button type="button" wire:click="deleteSelected"
                        wire:confirm="Are you sure you want to delete the selected literature records?"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200"
                        title="Delete selected literature records">
                        <i class="fas fa-trash"></i>
                    </button>
                    <span class="text-sm text-gray-600">{{ count(array_filter($selectedMetaParasites ?? [])) }} selected</span>
                </div>
            @endif
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $meta_parasites])


        <div class="index-table-container overflow-x-auto">
        <table id="meta_parasite_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                    @if ($showBulkActions)
                        <th class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">Select</th>
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
                        <x-sort-button field="parasite_species" :active="$sortField" :direction="$sortDirection">Parasite species</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="parasite_sample_type" :active="$sortField" :direction="$sortDirection">Parasite sample type</x-sort-button></th>
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
                        <x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">Sub-project</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="scientist" :active="$sortField" :direction="$sortDirection">Scientist</x-sort-button></th>
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
                        <input type="text" wire:model.live.debounce.300ms="parasiteSpeciesFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="parasiteSampleTypeFilter"
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
                @forelse ($meta_parasites as $meta_parasite)
                    <tr wire:key="{{ $meta_parasite->id }}"
                        class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <input type="checkbox" wire:model.live="selectedMetaParasites.{{ $meta_parasite->id }}"
                                    class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500">
                            </td>
                        @endif
                        @if ($isGuestMode)
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($meta_parasite->projects?->code)
                                    <a href="{{ route('projects.profile', $meta_parasite->projects->code) }}"
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm hover:underline">
                                        {{ $meta_parasite->projects->code }}
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
                                    class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    x-data="{ original: @js((int) ($meta_parasite->studies_id ?? 0)) }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit Study?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $meta_parasite->id }}, 'studies_id', $el.value);
                                    "
                                >
                                    @foreach (($studiesOptions ?? []) as $opt)
                                        <option value="{{ $opt->id }}" @selected((int) ($meta_parasite->studies_id ?? 0) === (int) $opt->id)>
                                            {{ $opt->ref_key }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <a href="/studies/{{ $meta_parasite->studies->id ?? '#' }}"
                                    class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                    {{ $meta_parasite->studies->ref_key ?? 'N/A' }}
                                </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                            @if ($isEditing && ! $isGuestMode && $canEdit)
                                <select
                                    class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    x-data="{ original: @js((int) ($meta_parasite->parasite_species_id ?? 0)) }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit Parasite species?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $meta_parasite->id }}, 'parasite_species_id', $el.value);
                                    "
                                >
                                    @foreach (($parasiteSpeciesOptions ?? []) as $opt)
                                        <option value="{{ $opt->id }}" @selected((int) ($meta_parasite->parasite_species_id ?? 0) === (int) $opt->id)>
                                            {{ $opt->name_scientific }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm">
                                    {{ $meta_parasite->parasite_species->name_scientific ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                            @if ($isEditing && ! $isGuestMode && $canEdit)
                                <select
                                    class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    x-data="{ original: @js((int) ($meta_parasite->parasite_sample_types_id ?? 0)) }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit Parasite sample type?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $meta_parasite->id }}, 'parasite_sample_types_id', $el.value);
                                    "
                                >
                                    @foreach (($parasiteSampleTypesOptions ?? []) as $opt)
                                        <option value="{{ $opt->id }}" @selected((int) ($meta_parasite->parasite_sample_types_id ?? 0) === (int) $opt->id)>
                                            {{ $opt->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 shadow-sm">
                                    {{ $meta_parasite->parasite_sample_types->name ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                            @if ($isEditing && ! $isGuestMode && $canEdit)
                                <input type="text"
                                    value="{{ $meta_parasite->location ?? '' }}"
                                    x-data="{ original: @js($meta_parasite->location ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit Location?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $meta_parasite->id }}, 'location', $el.value);
                                    "
                                    class="w-full min-w-[18rem] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Location">
                            @else
                                <span class="text-gray-900 font-medium">{{ $meta_parasite->location ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                            @if ($isEditing && ! $isGuestMode && $canEdit)
                                <select
                                    class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    x-data="{ original: @js((int) ($meta_parasite->countries_id ?? 0)) }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit Country?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $meta_parasite->id }}, 'countries_id', $el.value);
                                    "
                                >
                                    @foreach (($countriesOptions ?? []) as $opt)
                                        <option value="{{ $opt->id }}" @selected((int) ($meta_parasite->countries_id ?? 0) === (int) $opt->id)>
                                            {{ $opt->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span class="text-gray-900 font-medium">{{ $meta_parasite->countries->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && ! $isGuestMode && $canEdit)
                                @php
                                    $dateSamplingYmd = $meta_parasite->date_sampling ? \Carbon\Carbon::parse($meta_parasite->date_sampling)->format('Y-m-d') : '';
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
                                        $wire.updateField({{ $meta_parasite->id }}, 'date_sampling', $el.value);
                                    "
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200">
                            @else
                                <span class="text-gray-900 font-medium">{{ $meta_parasite->date_sampling ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                            @if ($isEditing && ! $isGuestMode && $canEdit)
                                <select
                                    class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    x-data="{ original: @js((int) ($meta_parasite->pathogens_id ?? 0)) }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit Pathogen?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $meta_parasite->id }}, 'pathogens_id', $el.value);
                                    "
                                >
                                    @foreach (($pathogensOptions ?? []) as $opt)
                                        <option value="{{ $opt->id }}" @selected((int) ($meta_parasite->pathogens_id ?? 0) === (int) $opt->id)>
                                            {{ $opt->species }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-red-100 to-red-200 text-red-800 shadow-sm">
                                    {{ $meta_parasite->pathogens->species ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                            @if ($isEditing && ! $isGuestMode && $canEdit)
                                <select
                                    class="w-full min-w-[18rem] rounded-lg border border-gray-200 px-3 py-2 text-sm text-gray-800 shadow-sm focus:border-purple-500 focus:outline-none focus:ring-2 focus:ring-purple-500"
                                    x-data="{ original: @js((int) ($meta_parasite->techniques_id ?? 0)) }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit Technique?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $meta_parasite->id }}, 'techniques_id', $el.value);
                                    "
                                >
                                    @foreach (($techniquesOptions ?? []) as $opt)
                                        <option value="{{ $opt->id }}" @selected((int) ($meta_parasite->techniques_id ?? 0) === (int) $opt->id)>
                                            {{ $opt->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-green-100 to-green-200 text-green-800 shadow-sm">
                                    {{ $meta_parasite->techniques->name ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap min-w-[10rem]">
                            @if ($isEditing && ! $isGuestMode && $canEdit)
                                <input type="number" min="0"
                                    value="{{ $meta_parasite->tested_n ?? '' }}"
                                    x-data="{ original: @js($meta_parasite->tested_n ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit Tested N?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $meta_parasite->id }}, 'tested_n', $el.value);
                                    "
                                    class="w-full min-w-[10rem] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Tested N">
                            @else
                                <span class="text-gray-900 font-medium">{{ $meta_parasite->tested_n ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap min-w-[10rem]">
                            @if ($isEditing && ! $isGuestMode && $canEdit)
                                <input type="number" min="0"
                                    value="{{ $meta_parasite->pos_n ?? '' }}"
                                    x-data="{ original: @js($meta_parasite->pos_n ?? '') }"
                                    x-on:change.stop.prevent="
                                        if (!confirm('Are you sure you want to edit Positive N?')) {
                                            $el.value = original;
                                            return;
                                        }
                                        original = $el.value;
                                        $wire.updateField({{ $meta_parasite->id }}, 'pos_n', $el.value);
                                    "
                                    class="w-full min-w-[10rem] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Positive N">
                            @else
                                <span class="text-gray-900 font-medium">{{ $meta_parasite->pos_n ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap min-w-[18rem]">
                            <x-meta.multi-value-cell
                                :values="$meta_parasite->risk_factors->pluck('name')"
                                label="Risk Factors"
                            />
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if (optional($meta_parasite->subProjectAssignment?->subProject)->code)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                    {{ $meta_parasite->subProjectAssignment->subProject->code }}
                                </span>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap index-people-cell">
                            <div class="flex items-center space-x-3">
                                <x-people-logo :person="$meta_parasite->people" width="30" />
                                <a href="/profile/{{ $meta_parasite->people->id }}"
                                    class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                    {{ $meta_parasite->people->first_name . ' ' . $meta_parasite->people->last_name ?? 'N/A' }}
                                </a>
                            </div>
                        </td>
                        @if ($isEditing && !$isGuestMode)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    class="text-red-500 hover:text-red-600 transition-all duration-200 transform hover:scale-110"
                                    type="button" wire:click="delete({{ $meta_parasite->id }})"
                                    wire:confirm="Are you sure you want to delete this meta data?">
                                    <i class="fas fa-trash text-xl"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ ($isGuestMode ? 14 : 13) + ($showBulkActions ? 1 : 0) }}"
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

        @include('livewire.partials.index-pagination-bar', ['paginator' => $meta_parasites])
    </div>
</div>
