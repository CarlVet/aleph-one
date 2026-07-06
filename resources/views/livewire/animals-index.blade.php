<div class="text-center mt-2">
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing animals from all projects</p>
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
            <a href="/animals/create"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                Create
            </a>
            @if ($isEditing)
                <a href="/animals/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            @else
                <button wire:click="toggleEditMode"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                    <i class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Edit
                </button>
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
        <a href="/animals/dashboard"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
            Dashboard
        </a>
    </div>

    <!-- Table Section -->
    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        <div class="flex flex-col items-center w-full p-4">
            <!-- Index Title (Centered) -->
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                @if ($isGuestMode)
                    <i class="fas fa-eye text-purple-600 mr-2"></i>
                    All Animals
                @else
                    {{ $isEditing ? 'Edit Animals' : 'List of Animals' }}
                @endif
            </h2>

            <!-- Export Buttons (Centered) -->
            @include('livewire.partials.export-buttons')
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $animals])


        <div class="index-table-container overflow-x-auto">
        <table id="animals_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="animal_code" :active="$sortField" :direction="$sortDirection">Animal code</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="field_id" :active="$sortField" :direction="$sortDirection">Field ID</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="species" :active="$sortField" :direction="$sortDirection">Species</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="sex" :active="$sortField" :direction="$sortDirection">Sex</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="age" :active="$sortField" :direction="$sortDirection">Age</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Handler/Owner</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                    @if ($isEditing)
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Delete</th>
                    @endif
                </tr>
            </thead>
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="animalIdFilter" 
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="fieldIdFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="speciesFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="sexFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="ageFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="handlerFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="locationFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @if ($isEditing)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @php
                    $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
                @endphp
                @foreach ($animals as $animal)
                    @php
                        $canEditRow = ($canEdit ?? false) && (int) ($animal->people_id ?? 0) === $currentPeopleId;
                    @endphp
                    <tr wire:key="{{ $animal->id }}" class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="/animals/{{ $animal->code }}" class="text-green-600 hover:text-green-800 font-medium transition-colors duration-200">
                                {{ $animal->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $animal->field_label ?? 'N/A' }}"
                                    class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $animal->id }}, 'field_label', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Field ID of the animal?">
                            @else
                                {{ $animal->field_label ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="animal_species" name="animal_species"
                                    wire:change="updateField({{ $animal->id }}, 'species', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Animal Species of the animal?"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($species_by_family as $family => $species_list)
                                        <optgroup label="{{ $family }}">
                                            @foreach ($species_list as $species)
                                                <option value="{{ $species['common'] }}"
                                                    {{ $species['common'] === ($animal->animal_species->name_common ?? '') ? 'selected' : '' }}>
                                                    {{ $species['common'] }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </x-forms.select-input>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-sm">
                                    {{ $animal->animal_species->name_common ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="sex" name="sex"
                                    wire:change="updateField({{ $animal->id }}, 'sex', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Sex of the animal?"
                                    class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Male" {{ 'Male' === $animal->sex ? 'selected' : '' }}>Male</option>
                                    <option value="Female" {{ 'Female' === $animal->sex ? 'selected' : '' }}>Female</option>
                                    <option value="NA" {{ 'NA' === $animal->sex ? 'selected' : '' }}>N/A</option>
                                </x-forms.select-input>
                            @else
                                {{ $animal->sex ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="age" name="age"
                                    wire:change="updateField({{ $animal->id }}, 'age', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Age of the animal?"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Juvenile" {{ 'Juvenile' === $animal->age ? 'selected' : '' }}>Juvenile</option>
                                    <option value="Sub-adult" {{ 'Sub-adult' === $animal->age ? 'selected' : '' }}>Sub-adult</option>
                                    <option value="Adult" {{ 'Adult' === $animal->age ? 'selected' : '' }}>Adult</option>
                                    <option value="NA" {{ 'NA' === $animal->age ? 'selected' : '' }}>N/A</option>
                                </x-forms.select-input>
                            @else
                                {{ $animal->age ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <span class="text-gray-900 font-medium">
                                    @php
                                        $owner = $animal->owner;
                                        $ownerLabel = 'N/A';
                                        if ($owner instanceof \App\Models\Humans) {
                                            $ownerLabel = trim(($owner->title ?? '').' '.($owner->first_name ?? '').' '.($owner->last_name ?? '')) ?: 'N/A';
                                        } elseif ($owner instanceof \App\Models\Organizations) {
                                            $ownerLabel = $owner->name ?? 'N/A';
                                        }
                                    @endphp
                                    {{ $ownerLabel }}
                                </span>
                            @else
                                @php
                                    $owner = $animal->owner;
                                    $ownerLabel = 'N/A';
                                    if ($owner instanceof \App\Models\Humans) {
                                        $ownerLabel = trim(($owner->title ?? '').' '.($owner->first_name ?? '').' '.($owner->last_name ?? '')) ?: 'N/A';
                                    } elseif ($owner instanceof \App\Models\Organizations) {
                                        $ownerLabel = $owner->name ?? 'N/A';
                                    }
                                @endphp
                                <span class="text-gray-900 font-medium">{{ $ownerLabel }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <span class="text-gray-900 font-medium">
                                    {{ data_get($animal, 'latest_movement.destination_sampling_site.name')
                                        ?: data_get($animal, 'latest_movement.source_sampling_site.name')
                                        ?: 'No location' }}
                                </span>
                            @else
                                <span class="text-gray-900 font-medium">
                                    {{ data_get($animal, 'latest_movement.destination_sampling_site.name')
                                        ?: data_get($animal, 'latest_movement.source_sampling_site.name')
                                        ?: 'No location' }}
                                </span>
                            @endif
                        </td>
                        @if ($isEditing && $canEditRow)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    type="button" wire:click="delete({{ $animal->id }})"
                                    wire:confirm="Are you sure you want to delete this animal?">
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
        @include('livewire.partials.index-pagination-bar', ['paginator' => $animals])
    </div>
</div> 