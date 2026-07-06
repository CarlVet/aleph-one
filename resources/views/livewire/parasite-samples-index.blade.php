<div class="text-center mt-2">
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing parasite samples with public tubes from all
                        projects</p>
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
                <a href="/samples/parasites"
                    class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                    <i class="fas fa-arrow-left text-2xl mr-2"></i>
                    <span class="text-sm font-medium">Back to PS Home</span>
                </a>
            @endif
            <h2 class="text-xl font-bold mb-4 text-gray-700">Select content type:</h2>
            <div class="flex items-center space-x-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                <button wire:click="$set('selectedTable', 'parasite_samples_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view All Parasite Samples">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'parasite_samples_table' ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-flask text-3xl {{ $selectedTable === 'parasite_samples_table' ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'parasite_samples_table' ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}">All</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'parasite_human_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Human Parasites">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'parasite_human_table' ? 'bg-rose-100 ring-2 ring-rose-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-person text-3xl {{ $selectedTable === 'parasite_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'parasite_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}">Humans</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'parasite_animal_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Animal Parasites">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'parasite_animal_table' ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-paw text-3xl {{ $selectedTable === 'parasite_animal_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'parasite_animal_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Animals</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'parasite_environment_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Environmental Parasites">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'parasite_environment_table' ? 'bg-emerald-100 ring-2 ring-emerald-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-leaf text-3xl {{ $selectedTable === 'parasite_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'parasite_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}">Environment</span>
                    </div>
                </button>
            </div>
        </div>
    </div>

    @if ($selectedTable === 'parasite_samples_table')


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
                        <i
                            class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
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
                            <i
                                class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                            Edit
                        </button>
                    @endif
                @endif
            @else
                <div class="flex items-center space-x-4">
                    <div
                        class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                        <i class="fas fa-lock mr-2"></i>
                        Create (Project Mode)
                    </div>
                    <div
                        class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                        <i class="fas fa-lock mr-2"></i>
                        Edit (Project Mode)
                    </div>
                </div>
            @endif
            <a href="/samples/parasites/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i
                    class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
            @if ($isGuestMode)
                <a href="/tube-requests"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-indigo-600">
                    <i
                        class="fas fa-handshake mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    My Requests
                </a>
            @endif
        </div>

        <!-- Table Section -->
        <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
            @php
                $showBulkActions = !$isGuestMode && $canEdit;
                $selectedItemsCount = collect($selectedParasiteSamples ?? [])
                    ->filter(fn($checked) => (bool) $checked)
                    ->count();
            @endphp
            <div class="flex flex-col items-center w-full p-4">
                <!-- Index Title (Centered) -->
                <h2 class="text-2xl font-bold text-gray-800 mb-4">
                    @if ($isGuestMode)
                        <i class="fas fa-eye text-purple-600 mr-2"></i>
                        Public Parasite Samples
                    @else
                        {{ $isEditing ? 'Edit Parasite Samples' : 'List of Parasite Samples' }}
                    @endif
                </h2>
                <!-- Export Button (Centered) -->
                @include('livewire.partials.export-buttons')

                @if ($showBulkActions)
                    <div class="mt-2 w-full flex items-center justify-center gap-2">
                        <input id="parasite-bulk-photo-input" type="file" class="hidden" wire:model.live="bulkPhoto"
                            accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf">
                        <label for="parasite-bulk-photo-input"
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
            <table id="parasite_samples_table"
                data-sticky-cols="{{ ($showBulkActions ?? false) ? '2,3' : '1,2' }}"
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
                            <x-sort-button field="code" :active="$sortField" :direction="$sortDirection">SAMPLE CODE</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            ASSOCIATED TUBES</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">SUB-PROJECT</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            SAMPLE ORIGIN TYPE</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            SAMPLE ORIGIN CODE</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            PARASITE PHOTO</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="parasite_species" :active="$sortField" :direction="$sortDirection">SPECIES</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="sex" :active="$sortField" :direction="$sortDirection">SEX</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="stage" :active="$sortField" :direction="$sortDirection">STAGE</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="state" :active="$sortField" :direction="$sortDirection">REPLETION STATE</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="sample_type" :active="$sortField" :direction="$sortDirection">SAMPLE TYPE</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="date_identified" :active="$sortField" :direction="$sortDirection">IDENTIFICATION DATE</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            <x-sort-button field="date_processed" :active="$sortField" :direction="$sortDirection">PROCESSED DATE</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 index-people-cell">
                            <x-sort-button field="identified_by" :active="$sortField" :direction="$sortDirection">IDENTIFIED BY</x-sort-button></th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 index-people-cell">
                            <x-sort-button field="processed_by" :active="$sortField" :direction="$sortDirection">PROCESSED BY</x-sort-button></th>

                        @if ($isEditing && $canEdit)
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
                            <input type="text" wire:model.live.debounce.300ms="subProjectCodeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3"></th>
                        <th class="px-6 py-3"></th>
                        <th class="px-6 py-3">
                            <select wire:model.live="photoFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                <option value="">All photos</option>
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
                    @php
                        $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
                    @endphp
                    @if (session()->has('error'))
                        <tr>
                            <td colspan="10"
                                class="px-6 py-4 text-red-500 text-center bg-red-50 border-l-4 border-red-500">
                                {{ session('error') }}
                            </td>
                        </tr>
                    @endif
                    @if (session()->has('message'))
                        <tr>
                            <td colspan="10"
                                class="px-6 py-4 text-green-500 text-center bg-green-50 border-l-4 border-green-500">
                                {{ session('message') }}
                            </td>
                        </tr>
                    @endif
                    @forelse ($parasite_samples as $sample)
                        @php
                            $baseCanEdit = $canEdit;
                            $canEdit = $baseCanEdit && $this->canMutateSampleRecord((int) ($sample->people_id ?? 0));
                        @endphp
                        <tr wire:key="{{ $sample->id }}"
                            class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                            @if ($showBulkActions)
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if ($canEdit)
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
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
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
                                    <!-- Existing Tubes List -->
                                    <div class="space-y-2 min-w-[200px]">
                                        @foreach ($sample->tubes as $tube)
                                            <div class="flex items-center space-x-2">
                                                <div class="flex flex-col">
                                                    <div>
                                                        <span class="text-sm text-gray-700">
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

                                        @if ($sample->tubes->count() === 0 && $sample->pools->count() === 0)
                                            <span class="text-sm text-gray-500 italic">No tubes associated</span>
                                        @endif

                                        {{-- Pool tubes if pooled --}}
                                        @if ($sample->pools && $sample->pools->count() > 0)
                                            <div>
                                                @foreach ($sample->pools as $poolContent)
                                                    @if ($poolContent->pools && $poolContent->pools->tubes && $poolContent->pools->tubes->count() > 0)
                                                        @foreach ($poolContent->pools->tubes as $poolTube)
                                                            <div class="flex flex-col mt-1">
                                                                <div class="flex items-center space-x-2">
                                                                    <a href="/bank/tubes/{{ $poolTube->code }}"
                                                                        class="text-sm text-blue-700 bg-blue-50 px-2 py-1 rounded mr-1">
                                                                        {{ $poolTube->code ?? 'N/A' }}
                                                                    </a>
                                                                    <span class="text-xs text-gray-500">(Pool)</span>
                                                                </div>
                                                                @if ($poolTube->alias_code)
                                                                    <span
                                                                        class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded mt-1">
                                                                        Alias: {{ $poolTube->alias_code }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <ul>
                                        @foreach ($sample->tubes as $tube)
                                            <li class="flex items-center space-x-2 mb-1">
                                                <div class="flex flex-col">
                                                    <div>
                                                        @if ($isGuestMode)
                                                            <span class="text-gray-900 font-medium">
                                                                {{ $tube->code ?? 'N/A' }}
                                                            </span>
                                                        @else
                                                            <a href="/bank/tubes/{{ $tube->code }}"
                                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                                {{ $tube->code ?? 'N/A' }}
                                                            </a>
                                                        @endif
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

                                        {{-- Pool tubes if pooled --}}
                                        @if ($sample->pools && $sample->pools->count() > 0)
                                            <li>

                                                @foreach ($sample->pools as $poolContent)
                                                    @if ($poolContent->pools && $poolContent->pools->tubes && $poolContent->pools->tubes->count() > 0)
                                                        @foreach ($poolContent->pools->tubes as $poolTube)
                                                            <div class="flex flex-col mt-1">
                                                                <div class="flex items-center space-x-2">
                                                                    @if ($isGuestMode)
                                                                        <span
                                                                            class="text-sm text-gray-900 font-medium bg-blue-50 px-2 py-1 rounded mr-1">
                                                                            {{ $poolTube->code ?? 'N/A' }}
                                                                        </span>
                                                                    @else
                                                                        <a href="/bank/tubes/{{ $poolTube->code }}"
                                                                            class="text-sm text-blue-700 bg-blue-50 px-2 py-1 rounded mr-1">
                                                                            {{ $poolTube->code ?? 'N/A' }}
                                                                        </a>
                                                                    @endif
                                                                    <span class="text-xs text-gray-500">(Pool)</span>
                                                                </div>
                                                                @if ($poolTube->alias_code)
                                                                    <span
                                                                        class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded mt-1">
                                                                        Alias: {{ $poolTube->alias_code }}
                                                                    </span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </li>
                                        @endif
                                    </ul>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if(optional($sample->subProjectAssignment?->subProject)->code)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                        {{ $sample->subProjectAssignment->subProject->code }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">-</span>
                                @endif
                            </td>
                            @if ($sample->parasites->parasites_origin_type === 'App\Models\AnimalSamples')
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-sm">
                                        Animal Sample
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($isEditing && $canEdit)
                                        <div class="relative min-w-[200px]">
                                            <input type="text" list="animal-samples-list"
                                                wire:change="updateField({{ $sample->id }}, 'animal_id', $event.target.value)"
                                                value="{{ $sample->parasites->parasites_origin->code }}"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                                placeholder="Search animal sample..."
                                                wire:confirm="Are you sure you want to edit the Animal Sample Code?"
                                                autocomplete="off">
                                            <datalist id="animal-samples-list">
                                                @foreach ($animalSamples as $option)
                                                    <option value="{{ $option->code }}">
                                                        {{ $option->code }}
                                                    </option>
                                                @endforeach
                                            </datalist>
                                            <div
                                                class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <i class="fas fa-search text-gray-400"></i>
                                            </div>
                                        </div>
                                    @else
                                        @if ($isGuestMode)
                                            <span class="text-gray-900 font-medium">{{ $sample->parasites->parasites_origin->code ?? 'N/A' }}</span>
                                        @else
                                            <a href="/samples/animals/{{ $sample->parasites->parasites_origin->code }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                {{ $sample->parasites->parasites_origin->code ?? 'N/A' }}
                                            </a>
                                        @endif
                                    @endif
                                </td>
                            @elseif($sample->parasites->parasites_origin_type === 'App\Models\HumanSamples')
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-rose-100 to-rose-200 text-rose-800 shadow-sm">
                                        Human Sample
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($isEditing && $canEdit)
                                        <div class="relative min-w-[200px]">
                                            <input type="text" list="human-samples-list"
                                                wire:change="updateField({{ $sample->id }}, 'human_id', $event.target.value)"
                                                value="{{ $sample->parasites->parasites_origin->code }}"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                                placeholder="Search human sample..."
                                                wire:confirm="Are you sure you want to edit the Human Sample Code?"
                                                autocomplete="off">
                                            <datalist id="human-samples-list">
                                                @foreach ($humanSamples as $option)
                                                    <option value="{{ $option->code }}">
                                                        {{ $option->code }}
                                                    </option>
                                                @endforeach
                                            </datalist>
                                            <div
                                                class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <i class="fas fa-search text-gray-400"></i>
                                            </div>
                                        </div>
                                    @else
                                        @if ($isGuestMode)
                                            <span class="text-gray-900 font-medium">{{ $sample->parasites->parasites_origin->code ?? 'N/A' }}</span>
                                        @else
                                            <a href="/samples/humans/{{ $sample->parasites->parasites_origin->code }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                {{ $sample->parasites->parasites_origin->code ?? 'N/A' }}
                                            </a>
                                        @endif
                                    @endif
                                </td>
                            @elseif($sample->parasites->parasites_origin_type === 'App\Models\EnvironmentSamples')
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-emerald-100 to-emerald-200 text-emerald-800 shadow-sm">
                                        Environment Sample
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($isEditing && $canEdit)
                                        <div class="relative min-w-[200px]">
                                            <input type="text" list="environment-samples-list"
                                                wire:change="updateField({{ $sample->id }}, 'environment_id', $event.target.value)"
                                                value="{{ $sample->parasites->parasites_origin->code }}"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                                placeholder="Search environment sample..."
                                                wire:confirm="Are you sure you want to edit the Environment Sample Code?"
                                                autocomplete="off">
                                            <datalist id="environment-samples-list">
                                                @foreach ($environmentSamples as $option)
                                                    <option value="{{ $option->code }}">
                                                        {{ $option->code }}
                                                    </option>
                                                @endforeach
                                            </datalist>
                                            <div
                                                class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                                <i class="fas fa-search text-gray-400"></i>
                                            </div>
                                        </div>
                                    @else
                                        @if ($isGuestMode)
                                            <span class="text-gray-900 font-medium">{{ $sample->parasites->parasites_origin->code ?? 'N/A' }}</span>
                                        @else
                                            <a href="/samples/environment/{{ $sample->parasites->parasites_origin->code }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                {{ $sample->parasites->parasites_origin->code ?? 'N/A' }}
                                            </a>
                                        @endif
                                    @endif
                                </td>
                            @endif
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
                                            wire:confirm="Are you sure you want to edit the Species?"
                                            autocomplete="off">
                                        <datalist id="parasite-species-list">
                                            @foreach ($parasiteSpecies as $species)
                                                <option value="{{ $species->name_scientific }}">
                                                    {{ $species->name_scientific }}
                                                </option>
                                            @endforeach
                                        </datalist>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
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
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                @else
                                    <span
                                        class="text-gray-900 font-medium">{{ $sample->parasites->sex ?? 'N/A' }}</span>
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
                                            wire:confirm="Are you sure you want to edit the Stage?"
                                            autocomplete="off">
                                        <datalist id="stage-options-list">
                                            @foreach ($stageOptions as $option)
                                                <option value="{{ $option }}">
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </datalist>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
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
                                            wire:confirm="Are you sure you want to edit the State?"
                                            autocomplete="off">
                                        <datalist id="state-options-list">
                                            @foreach ($stateOptions as $option)
                                                <option value="{{ $option }}">
                                                    {{ $option }}
                                                </option>
                                            @endforeach
                                        </datalist>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
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
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
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
                                        class="text-red-600 hover:text-red-800 transition-colors duration-200"
                                        wire:confirm="Are you sure you want to delete this sample?">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            @endif
                        </tr>
                        @php
                            $canEdit = $baseCanEdit;
                        @endphp
                    @empty
                        <tr>
                            <td colspan="100" class="px-6 py-12 text-center">
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

            @include('livewire.partials.index-pagination-bar', ['paginator' => $parasite_samples])

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
    @elseif ($selectedTable === 'parasite_human_table')
        @include('livewire.partials.parasite-samples-origin-table', [
            'subtitle' => 'collected from humans',
            'tableId' => 'parasite_human_table',
            'originLabel' => 'Human sample code',
            'originRoutePrefix' => '/samples/humans',
            'originEditField' => 'human_id',
            'originListId' => 'human-samples-list',
            'originSamples' => $humanSamples,
            'originFilterModel' => 'humanSampleCodeFilter',
            'extraColumns' => [
                [
                    'label' => 'Patient code',
                    'html' => true,
                    'filterModel' => 'humanPatientCodeFilter',
                    'filterPlaceholder' => 'Filter',
                    'value' => function ($sample) {
                        $code = $sample->parasites?->parasites_origin?->humans?->code;
        
                        if (!$code) {
                            return 'N/A';
                        }
        
                        return '<a href="/humans/' .
                            e($code) .
                            '" class="text-blue-600 hover:text-blue-800 transition-colors duration-200 font-medium">' .
                            e($code) .
                            '</a>';
                    },
                ],
                [
                    'label' => 'Collection date',
                    'html' => false,
                    'filterType' => 'date_range',
                    'filterModelStart' => 'humanDateCollectedStart',
                    'filterModelEnd' => 'humanDateCollectedEnd',
                    'value' => function ($sample) {
                        $date = $sample->parasites?->parasites_origin?->date_collected;
        
                        return $date ? $date->format('Y-m-d') : null;
                    },
                ],
                [
                    'label' => 'Sampling site',
                    'html' => false,
                    'filterModel' => 'humanSamplingSiteFilter',
                    'filterPlaceholder' => 'Filter',
                    'value' => function ($sample) {
                        return $sample->parasites?->parasites_origin?->sampling_sites?->name;
                    },
                ],
            ],
        ])
    @elseif ($selectedTable === 'parasite_animal_table')
        @include('livewire.partials.parasite-samples-origin-table', [
            'subtitle' => 'collected from animals',
            'tableId' => 'parasite_animal_table',
            'originLabel' => 'Animal sample code',
            'originRoutePrefix' => '/samples/animals',
            'originEditField' => 'animal_id',
            'originListId' => 'animal-samples-list',
            'originSamples' => $animalSamples,
            'extraColumns' => [
                [
                    'label' => 'Animal code',
                    'html' => false,
                    'filterModel' => 'animalCodeFilter',
                    'filterPlaceholder' => 'Code or field label',
                    'value' => function ($sample) {
                        $animal = $sample->parasites?->parasites_origin?->animals;
                        if (!$animal) {
                            return 'N/A';
                        }

                        $code = $animal->code ?? 'N/A';
                        $fieldLabel = $animal->field_label;

                        return filled($fieldLabel)
                            ? sprintf('%s (%s)', $code, $fieldLabel)
                            : $code;
                    },
                ],
                [
                    'label' => 'Animal species',
                    'html' => true,
                    'filterModel' => 'animalSpeciesFilter',
                    'filterPlaceholder' => 'Filter',
                    'value' => function ($sample) {
                        $species = $sample->parasites?->parasites_origin?->animals?->animal_species;
        
                        if (!$species) {
                            return 'N/A';
                        }
        
                        $common = $species->name_common ?? ($species->name ?? null);
                        $scientific = $species->name_scientific ?? null;
        
                        $output = e($common ?? 'N/A');
        
                        if ($scientific) {
                            $output .= ' <span class="text-gray-500"><br>(<i>' . e($scientific) . '</i>)</span>';
                        }
        
                        return $output;
                    },
                ],
                [
                    'label' => 'Sampling site',
                    'html' => false,
                    'filterModel' => 'samplingSiteFilter',
                    'filterPlaceholder' => 'Filter',
                    'value' => function ($sample) {
                        return $sample->parasites?->parasites_origin?->sampling_sites?->name;
                    },
                ],
                [
                    'label' => 'Collection date',
                    'html' => false,
                    'filterType' => 'date_range',
                    'filterModelStart' => 'animalDateCollectedStart',
                    'filterModelEnd' => 'animalDateCollectedEnd',
                    'value' => function ($sample) {
                        $date = $sample->parasites?->parasites_origin?->date_collected;
        
                        return $date ? $date->format('Y-m-d') : null;
                    },
                ],
            ],
        ])
    @elseif ($selectedTable === 'parasite_environment_table')
        @include('livewire.partials.parasite-samples-origin-table', [
            'subtitle' => 'collected from the environment',
            'tableId' => 'parasite_environment_table',
            'originLabel' => 'Environment sample code',
            'originRoutePrefix' => '/samples/environment',
            'originEditField' => 'environment_id',
            'originListId' => 'environment-samples-list',
            'originSamples' => $environmentSamples,
            'originFilterModel' => 'environmentSampleCodeFilter',
            'extraColumns' => [
                [
                    'label' => 'Collection date',
                    'html' => false,
                    'filterType' => 'date_range',
                    'filterModelStart' => 'environmentDateCollectedStart',
                    'filterModelEnd' => 'environmentDateCollectedEnd',
                    'value' => function ($sample) {
                        $date = $sample->parasites?->parasites_origin?->date_collected;
        
                        return $date ? $date->format('Y-m-d') : null;
                    },
                ],
                [
                    'label' => 'Sampling site',
                    'html' => false,
                    'filterModel' => 'environmentSamplingSiteFilter',
                    'filterPlaceholder' => 'Filter',
                    'value' => function ($sample) {
                        return $sample->parasites?->parasites_origin?->sampling_sites?->name;
                    },
                ],
            ],
        ])
    @endif

    @if ($photoPreviewSampleId && $photoPreviewUrl)
        @php
            $currentPreview = $photoPreviewPhotos[$photoPreviewIndex] ?? null;
            $previewPath = $currentPreview['path'] ?? null;
            $previewIsImage = \App\Support\MediaPreview::isImage($previewPath);
            $previewIsPdf = \App\Support\MediaPreview::isPdf($previewPath);
        @endphp
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            wire:click.self="closePhotoPreview">
            <div class="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-pink-950 shadow-2xl ring-1 ring-white/10">
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-white">
                            Parasite Photos{{ $photoPreviewCode ? ' · '.$photoPreviewCode : '' }}
                        </h3>
                        @if (count($photoPreviewPhotos) > 1)
                            <p class="text-sm text-white/60">{{ $photoPreviewIndex + 1 }} of {{ count($photoPreviewPhotos) }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="closePhotoPreview"
                        class="rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="relative aspect-[16/10] min-h-[300px] max-h-[65vh] bg-black/30">
                    <div wire:key="preview-{{ $photoPreviewIndex }}" class="absolute inset-0">
                        @if ($previewIsImage)
                            <img src="{{ $photoPreviewUrl }}" alt="Parasite photo preview"
                                class="absolute inset-0 h-full w-full object-contain transition-opacity duration-300">
                        @elseif ($previewIsPdf)
                            <iframe src="{{ $photoPreviewUrl }}" title="Parasite PDF preview"
                                class="absolute inset-0 h-full w-full" frameborder="0"></iframe>
                        @else
                            <div class="absolute inset-0 flex items-center justify-center p-8">
                                <a href="{{ $photoPreviewUrl }}" target="_blank"
                                    class="rounded-xl border border-white/20 bg-white/10 px-6 py-4 text-center text-white backdrop-blur-sm hover:bg-white/20 transition-colors">
                                    File uploaded — click to open
                                </a>
                            </div>
                        @endif
                    </div>

                    @if (count($photoPreviewPhotos) > 1)
                        <button type="button" wire:click="previousPhotoPreview"
                            class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-3 text-white backdrop-blur-sm transition hover:bg-black/70">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" wire:click="nextPhotoPreview"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-3 text-white backdrop-blur-sm transition hover:bg-black/70">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    @endif

                    @if ($currentPreview && (!empty($currentPreview['observed_at']) || !empty($currentPreview['notes']) || !empty($currentPreview['observer'])))
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent p-5 pt-12">
                            @if (!empty($currentPreview['observed_at']))
                                <p class="text-xs font-semibold uppercase tracking-wider text-pink-200">Observed</p>
                                <p class="text-base font-medium text-white">{{ $currentPreview['observed_at'] }}</p>
                            @endif
                            @if (!empty($currentPreview['observer']))
                                <p class="mt-1 text-sm text-white/80">by {{ $currentPreview['observer'] }}</p>
                            @endif
                            @if (!empty($currentPreview['notes']))
                                <p class="mt-2 max-w-3xl text-sm text-white/85">{{ $currentPreview['notes'] }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                @if (count($photoPreviewPhotos) > 1)
                    <div class="flex gap-2 overflow-x-auto border-t border-white/10 bg-black/20 px-4 py-3">
                        @foreach ($photoPreviewPhotos as $index => $previewPhoto)
                            @php
                                $thumbPath = $previewPhoto['path'] ?? null;
                                $thumbIsImage = \App\Support\MediaPreview::isImage($thumbPath);
                                $thumbIsPdf = \App\Support\MediaPreview::isPdf($thumbPath);
                            @endphp
                            <button type="button" wire:click="showPhotoPreviewAt({{ $index }})"
                                class="flex-shrink-0 overflow-hidden rounded-lg transition-all {{ $photoPreviewIndex === $index ? 'ring-2 ring-pink-400 ring-offset-2 ring-offset-slate-900 scale-105' : 'opacity-60 hover:opacity-100' }}">
                                @if ($thumbIsImage)
                                    <img src="{{ $previewPhoto['url'] }}" alt="Thumbnail {{ $index + 1 }}"
                                        class="h-16 w-24 object-cover">
                                @elseif ($thumbIsPdf)
                                    <div class="flex h-16 w-24 items-center justify-center bg-red-50">
                                        <i class="fas fa-file-pdf text-xl text-red-600"></i>
                                    </div>
                                @else
                                    <div class="flex h-16 w-24 items-center justify-center bg-gray-100">
                                        <i class="fas fa-file-alt text-gray-500"></i>
                                    </div>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-white/10 bg-black/30 px-4 py-3">
                    <a href="{{ $photoPreviewUrl }}" download target="_blank"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                        <i class="fas fa-download mr-2"></i>Download
                    </a>
                    @if ($photoPreviewCanDelete)
                        <button type="button" wire:click="deletePreviewPhoto"
                            wire:confirm="Delete this observation photo?"
                            class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                            <i class="fas fa-trash mr-2"></i>Delete
                        </button>
                    @endif
                    <button type="button" wire:click="closePhotoPreview"
                        class="inline-flex items-center rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                        Close
                    </button>
                </div>
            </div>
        </div>
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
                                        {{ $selectedTube->tube_type }}</p>
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
