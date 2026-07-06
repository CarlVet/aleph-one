<div class="text-center mt-2">
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing animal health records from all projects</p>
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
            <a href="/samples/animals/health/create"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                Create
            </a>
            @if ($isEditing)
                <a href="/samples/animals/health/list"
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
        <a href="/samples/animals/health/dashboard"
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
                    Public Animal Health Records
                @else
                    {{ $isEditing ? 'Edit Animal Health Records' : 'List of Animal Health Records' }}
                @endif
            </h2>

            <!-- Export Button (Centered) -->
            <button wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $animal_health])


        <div class="index-table-container overflow-x-auto">
        <table id="animal_health_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="animal_code" :active="$sortField" :direction="$sortDirection">Animal Code</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="species" :active="$sortField" :direction="$sortDirection">Species</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="health_status" :active="$sortField" :direction="$sortDirection">Health Status</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="check_date" :active="$sortField" :direction="$sortDirection">Check Date</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="check_type" :active="$sortField" :direction="$sortDirection">Check Type</x-sort-button></th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Clinical Signs</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Lesions</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider"><x-sort-button field="alive" :active="$sortField" :direction="$sortDirection">Alive</x-sort-button></th>
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
                        <input type="text" wire:model.live.debounce.300ms="healthStatusFilter"
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
                        <input type="text" wire:model.live.debounce.300ms="checkTypeFilter"
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
                        <select wire:model.live.debounce.300ms="aliveFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All</option>
                            <option value="1">Alive</option>
                            <option value="0">Deceased</option>
                        </select>
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
                @foreach ($animal_health as $health)
                    @php
                        $canEditRow = ($canEdit ?? false) && $this->userCanMutateOwnedRecord((int) ($health->people_id ?? 0), 'animal_samples');
                    @endphp
                    <tr wire:key="{{ $health->id }}" class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <a href="/animals/{{ $health->animals->code }}" class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                {{ $health->animals->code ?? 'N/A' }}
                            </a>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-sm">
                                {{ $health->animals->animal_species->name_common ?? 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="health_status" name="health_status"
                                    wire:change="updateField({{ $health->id }}, 'health_status', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Health Status?"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Healthy" {{ 'Healthy' === $health->health_status ? 'selected' : '' }}>Healthy</option>
                                    <option value="Sick" {{ 'Sick' === $health->health_status ? 'selected' : '' }}>Sick</option>
                                    <option value="Recovering" {{ 'Recovering' === $health->health_status ? 'selected' : '' }}>Recovering</option>
                                    <option value="Under Treatment" {{ 'Under Treatment' === $health->health_status ? 'selected' : '' }}>Under Treatment</option>
                                    <option value="Critical" {{ 'Critical' === $health->health_status ? 'selected' : '' }}>Critical</option>
                                    <option value="Stable" {{ 'Stable' === $health->health_status ? 'selected' : '' }}>Stable</option>
                                </x-forms.select-input>
                            @else
                                {{ $health->health_status ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="date" value="{{ $health->check_date ? \Carbon\Carbon::parse($health->check_date)->format('Y-m-d') : '' }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $health->id }}, 'check_date', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Check Date?">
                            @else
                                {{ $health->check_date ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="check_type" name="check_type"
                                    wire:change="updateField({{ $health->id }}, 'check_type', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Check Type?"
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Routine" {{ 'Routine' === $health->check_type ? 'selected' : '' }}>Routine</option>
                                    <option value="Follow-up" {{ 'Follow-up' === $health->check_type ? 'selected' : '' }}>Follow-up</option>
                                    <option value="Emergency" {{ 'Emergency' === $health->check_type ? 'selected' : '' }}>Emergency</option>
                                    <option value="Treatment" {{ 'Treatment' === $health->check_type ? 'selected' : '' }}>Treatment</option>
                                    <option value="Pre-release" {{ 'Pre-release' === $health->check_type ? 'selected' : '' }}>Pre-release</option>
                                    <option value="Post-treatment" {{ 'Post-treatment' === $health->check_type ? 'selected' : '' }}>Post-treatment</option>
                                </x-forms.select-input>
                            @else
                                {{ $health->check_type ?? 'N/A' }}
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                // Get multiple clinical signs
                                $clinicalSigns = ($health->clinical_signs_many ?? collect())->pluck('name')->implode(', ');
                                if (empty($clinicalSigns) && $health->clinical_signs) {
                                    $clinicalSigns = $health->clinical_signs->name;
                                }
                            @endphp
                            <div class="max-w-xs">
                                @if (!empty($clinicalSigns))
                                    @foreach (explode(', ', $clinicalSigns) as $sign)
                                        <span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-1 mb-1">
                                            {{ trim($sign) }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-gray-500 italic">N/A</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                // Get multiple lesions
                                $lesions = ($health->lesions_many ?? collect())->pluck('name')->implode(', ');
                                if (empty($lesions) && $health->lesions) {
                                    $lesions = $health->lesions->name;
                                }
                            @endphp
                            <div class="max-w-xs">
                                @if (!empty($lesions))
                                    @foreach (explode(', ', $lesions) as $lesion)
                                        <span class="inline-block bg-red-100 text-red-800 text-xs px-2 py-1 rounded-full mr-1 mb-1">
                                            {{ trim($lesion) }}
                                        </span>
                                    @endforeach
                                @else
                                    <span class="text-gray-500 italic">N/A</span>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="alive" name="alive"
                                    wire:change="updateField({{ $health->id }}, 'alive', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Alive status?"
                                    class="w-full min-w-[100px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="1" {{ $health->alive ? 'selected' : '' }}>Alive</option>
                                    <option value="0" {{ !$health->alive ? 'selected' : '' }}>Deceased</option>
                                </x-forms.select-input>
                            @else
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $health->alive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }} shadow-sm">
                                    {{ $health->alive ? 'Alive' : 'Deceased' }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $health->notes ?? '' }}"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $health->id }}, 'notes', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Notes?">
                            @else
                                <div class="max-w-xs truncate" title="{{ $health->notes ?? 'N/A' }}">
                                    {{ $health->notes ?? 'N/A' }}
                                </div>
                            @endif
                        </td>
                        @if ($isEditing && $canEditRow)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
                                    type="button" wire:click="delete({{ $health->id }})"
                                    wire:confirm="Are you sure you want to delete this health record?">
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
        @include('livewire.partials.index-pagination-bar', ['paginator' => $animal_health])
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