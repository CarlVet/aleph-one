<div class="text-center mt-2">
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing animal vaccination records from all projects</p>
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
    <div class="relative flex justify-center items-center space-x-4 mt-6">
        <!-- Left Arrow Home Link -->
        <a href="/samples/animals" class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
            <i class="fas fa-arrow-left text-2xl mr-2"></i>
            <span class="text-sm font-medium">Back to AS Home</span>
        </a>
        @if (!$isGuestMode)
            <a href="/samples/animals/vaccination/create"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                Create
            </a>
            @if ($isEditing)
                <a href="/samples/animals/vaccination/list"
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
        <a href="/samples/animals/vaccination/dashboard"
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
                    Public Animal Vaccination Records
                @else
                    {{ $isEditing ? 'Edit Animal Vaccination Records' : 'List of Animal Vaccination Records' }}
                @endif
            </h2>

            <!-- Export Button (Centered) -->
            <button wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $animal_vaccinations])


        <div class="index-table-container overflow-x-auto">
        <table id="animal_vaccinations_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="animal_code" :active="$sortField" :direction="$sortDirection">Animal Code</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="species" :active="$sortField" :direction="$sortDirection">Species</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="vaccine_name" :active="$sortField" :direction="$sortDirection">Vaccine Name</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="vaccine_type" :active="$sortField" :direction="$sortDirection">Vaccine Type</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="date_administered" :active="$sortField" :direction="$sortDirection">Date Administered</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="next_due_date" :active="$sortField" :direction="$sortDirection">Next Due Date</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="administered_by" :active="$sortField" :direction="$sortDirection">Administered By</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="notes" :active="$sortField" :direction="$sortDirection">Notes</x-sort-button></th>
                    @if ($isEditing)
                        <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Delete</th>
                    @endif
                </tr>
            </thead>
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="animalIdFilter" 
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="speciesFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="vaccineNameFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="vaccineTypeFilter"
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
                        <!-- Next due date filter would be same as start date range -->
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="administeredByFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="notesFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @if ($isEditing)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach ($animal_vaccinations as $vaccination)
                    @php
                        $canEditRow = ($canEdit ?? false) && $this->userCanMutateOwnedRecord((int) ($vaccination->people_id ?? 0), 'animal_samples');
                    @endphp
                    <tr wire:key="{{ $vaccination->id }}" class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="animal_id" name="animal_id"
                                    wire:change="updateField({{ $vaccination->id }}, 'animal_id', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Animal ID?"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($animals as $animal)
                                        <option value="{{ $animal->id }}"
                                            {{ $animal->id === ($vaccination->animals->id ?? '') ? 'selected' : '' }}>
                                            {{ $animal->code }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            @else
                                <a href="/animals/{{ $vaccination->animals->code }}" class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                    {{ $vaccination->animals->code ?? 'N/A' }}
                                </a>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-sm">
                                {{ $vaccination->animals->animal_species->name_common ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $vaccination->vaccine_name ?? '' }}"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $vaccination->id }}, 'vaccine_name', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Vaccine Name?">
                            @else
                                {{ $vaccination->vaccine_name ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $vaccination->vaccine_type ?? '' }}"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $vaccination->id }}, 'vaccine_type', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Vaccine Type?">
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    @if($vaccination->vaccine_type === 'Core') bg-green-100 text-green-800
                                    @elseif($vaccination->vaccine_type === 'Non-core') bg-yellow-100 text-yellow-800
                                    @elseif($vaccination->vaccine_type === 'Optional') bg-blue-100 text-blue-800
                                    @else bg-gray-100 text-gray-800 @endif shadow-sm">
                                    {{ $vaccination->vaccine_type ?? 'N/A' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="date" value="{{ $vaccination->date_administered ? \Carbon\Carbon::parse($vaccination->date_administered)->format('Y-m-d') : '' }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $vaccination->id }}, 'date_administered', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Date Administered?">
                            @else
                                {{ $vaccination->date_administered ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="date" value="{{ $vaccination->next_due_date ? \Carbon\Carbon::parse($vaccination->next_due_date)->format('Y-m-d') : '' }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $vaccination->id }}, 'next_due_date', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Next Due Date?">
                            @else
                                {{ $vaccination->next_due_date ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center space-x-3">
                                @if($vaccination->people)
                                    <x-people-logo :person="$vaccination->people" width="30" />
                                    <a href="/profile/{{ $vaccination->people->id }}" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ $vaccination->people->title . ' ' . $vaccination->people->first_name . ' ' . $vaccination->people->last_name ?? 'N/A' }}
                                    </a>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <textarea value="{{ $vaccination->notes ?? '' }}"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $vaccination->id }}, 'notes', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Notes?">{{ $vaccination->notes ?? '' }}</textarea>
                            @else
                                {{ $vaccination->notes ?? 'N/A' }}
                            @endif
                        </td>
                        @if ($isEditing && $canEditRow)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    type="button" wire:click="delete({{ $vaccination->id }})"
                                    wire:confirm="Are you sure you want to delete this vaccination record?">
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
        @include('livewire.partials.index-pagination-bar', ['paginator' => $animal_vaccinations])
    </div>

    <!-- SweetAlert for Livewire messages -->
    @push('scripts')
        <script>
            // Listen for Livewire events
            window.addEventListener('show-swal', event => {
                Swal.fire({
                    icon: event.detail.icon,
                    title: event.detail.title,
                    text: event.detail.text,
                    confirmButtonColor: event.detail.icon === 'success' ? '#10B981' : '#EF4444'
                });
            });
        </script>
    @endpush
</div>
