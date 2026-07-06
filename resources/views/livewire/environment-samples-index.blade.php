<div class="text-center mt-2">
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing environment samples with public tubes from all
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

    <!-- Create, Edit, Dashboard (Centered) -->
    <div class="text-center flex justify-center space-x-4 mt-6">
        @if (!$isGuestMode)
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/samples/environment/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @endif
            @if ($isEditing)
                <a href="/samples/environment/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            @else
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
            @endif
        @else
            <div class="flex items-center space-x-4">
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Project Mode)
                </div>
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Edit (Project Mode)
                </div>
            </div>
        @endif
        <a href="/samples/environment/dashboard"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
            Dashboard
        </a>
        @if ($isGuestMode)
            <a href="/tube-requests"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-indigo-600">
                <i class="fas fa-handshake mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                My Requests
            </a>
        @endif
    </div>

    <!-- Table Section -->
    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        @php
            $showBulkActions = !$isGuestMode && $canEdit;
            $selectedItemsCount = collect($selectedEnvironmentSamples ?? [])
                ->filter(fn($checked) => (bool) $checked)
                ->count();
        @endphp
        <div class="flex flex-col items-center w-full p-4">
            <!-- Index Title (Centered) -->
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                @if ($isGuestMode)
                    <i class="fas fa-eye text-purple-600 mr-2"></i>
                    Public Environment Samples
                @else
                    {{ $isEditing ? 'Edit Environment Samples' : 'List of Environment Samples' }}
                @endif
            </h2>

            <!-- Export Button (Centered) -->
            <button wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>
            @if ($showBulkActions)
                <div class="mt-2 w-full flex items-center justify-center gap-2">
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

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $environment_samples])


        <div class="index-table-container overflow-x-auto">
        <table id="environment_samples_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead class="bg-gray-50">
                <tr>
                    @if ($showBulkActions)
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Select</th>
                    @endif
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="code" :active="$sortField" :direction="$sortDirection">Sample code</x-sort-button>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">Sub-project</x-sort-button>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Associated tubes (current project)</th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="sample_type" :active="$sortField" :direction="$sortDirection">Sample Type</x-sort-button>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="date_collected" :active="$sortField" :direction="$sortDirection">Date Collected</x-sort-button>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="sampling_site" :active="$sortField" :direction="$sortDirection">Sampling site</x-sort-button>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Area
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="latitude" :active="$sortField" :direction="$sortDirection">Latitude</x-sort-button>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                        <x-sort-button field="longitude" :active="$sortField" :direction="$sortDirection">Longitude</x-sort-button>
                    </th>
                    <th scope="col"
                        class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider index-people-cell">
                        <x-sort-button field="collector" :active="$sortField" :direction="$sortDirection">Collector</x-sort-button>
                    </th>
                    @if ($isEditing && $canEdit)
                        <th scope="col"
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Delete</th>
                    @endif
                </tr>
            </thead>
            <thead class="bg-gray-50">
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
                        <input type="text" wire:model.live.debounce.300ms="sampleIdFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="subProjectCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="tubeCodeFilter"
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
                            <span class="text-gray-500">to</span>
                            <input type="date" wire:model.live.debounce.300ms="endDate"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="End Date">
                        </div>
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="parkFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="areaFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="latitudeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="longitudeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="collectorFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @if ($isEditing && $canEdit)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php($currentPeopleId = (int) ($this->currentPeopleId() ?? 0))
                @forelse ($environment_samples as $sample)
                    <tr wire:key="{{ $sample->id }}"
                        class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @php($canEditRow = $canEdit && (int) ($sample->people_id ?? 0) === $currentPeopleId)
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($canEditRow)
                                    <input type="checkbox" wire:model.live="selectedEnvironmentSamples.{{ $sample->id }}"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        title="Select this sample">
                                @endif
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isGuestMode)
                                <span class="text-gray-900 font-medium">{{ $sample->code }}</span>
                            @else
                                <a href="/samples/environment/{{ $sample->code }}"
                                    class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                    {{ $sample->code }}
                                </a>
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
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <!-- Existing Tubes List -->
                                <div class="space-y-2 min-w-[200px]">
                                    @foreach ($sample->tubes as $tube)
                                        <div class="flex items-center space-x-2">
                                            <div class="flex flex-col">
                                                <div>
                                                    <span class="text-sm text-gray-700 bg-gray-100 px-2 py-1 rounded">
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
                                            <button type="button" wire:click="removeTube({{ $tube->id }})"
                                                wire:confirm="Are you sure you want to remove this tube?"
                                                class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </div>
                                    @endforeach

                                    @if ($sample->tubes->count() === 0)
                                        <span class="text-sm text-gray-500 italic">No tubes associated</span>
                                    @endif
                                </div>
                            @else
                                <ul>
                                    @foreach ($sample->tubes as $tube)
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
                                    @endforeach
                                    @if ($sample->tubes->count() === 0)
                                        <span class="text-gray-500 italic">No tubes associated</span>
                                    @endif
                                </ul>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="sample_type" name="sample_type"
                                    wire:change="updateField({{ $sample->id }}, 'sample_type', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Sample Type?"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($environment_sample_types as $sample_type)
                                        <option value="{{ $sample_type['name'] }}"
                                            {{ $sample_type['name'] === ($sample->environment_sample_types->name ?? '') ? 'selected' : '' }}>
                                            {{ $sample_type['name'] }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            @else
                                {{ $sample->environment_sample_types->name ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="date"
                                    value="{{ $sample->date_collected ? \Carbon\Carbon::parse($sample->date_collected)->format('Y-m-d') : '' }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $sample->id }}, 'date_collected', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Date of Sample Collection?">
                            @else
                                {{ \Carbon\Carbon::parse($sample->date_collected)->format('Y-m-d') ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="sampling_site" name="sampling_site"
                                    wire:change="updateField({{ $sample->id }}, 'sampling_site', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Sampling Site?"
                                    class="w-full min-w-[180px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($sampling_sites_available as $country => $places_list)
                                        <optgroup label="{{ $country }}">
                                            @foreach ($places_list as $place)
                                                <option value="{{ $place['name'] }}"
                                                    {{ $place['name'] === ($sample->sampling_sites->name ?? '') ? 'selected' : '' }}>
                                                    {{ $place['name'] }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </x-forms.select-input>
                            @else
                                {{ $sample->sampling_sites->name ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $sample->area ?? '' }}"
                                    class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $sample->id }}, 'area', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Area?">
                            @else
                                {{ $sample->area ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="number" step="any" value="{{ $sample->latitude ?? '' }}"
                                    class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $sample->id }}, 'latitude', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Latitude?">
                            @else
                                {{ $sample->latitude ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="number" step="any" value="{{ $sample->longitude ?? '' }}"
                                    class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $sample->id }}, 'longitude', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Longitude?">
                            @else
                                {{ $sample->longitude ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap index-people-cell">
                            <div class="flex items-center space-x-3">
                                <x-people-logo :person="$sample->people" width="30" />
                                <a href="/profile/{{ $sample->people->id }}"
                                    class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                    {{ $sample->people->title . ' ' . $sample->people->first_name . ' ' . $sample->people->last_name ?? 'N/A' }}
                                </a>
                            </div>
                        </td>
                        @if ($isEditing && $canEditRow)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    type="button" wire:click="delete({{ $sample->id }})"
                                    wire:confirm="Are you sure you want to delete this sample?">
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
                                <span class="text-sm text-gray-600">No environment samples found.</span>
                                @if (! $isGuestMode)
                                    <a href="/samples/environment/create"
                                        class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                                        <i class="fas fa-plus-circle"></i>
                                        Register environment sample
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>
        @include('livewire.partials.index-pagination-bar', ['paginator' => $environment_samples])
    </div>

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
