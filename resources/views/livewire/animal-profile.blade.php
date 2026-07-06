<div data-profile-tables class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="{
    editing: {
        field_label: false,
        sex: false,
        age: false
    }
}"
x-on:start-edit.window="editing[$event.detail.field] = true"
x-on:save-edit.window="editing[$event.detail.field] = false"
x-on:cancel-edit.window="editing[$event.detail.field] = false"
x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success!', text: $event.detail.message, timer: 2000, showConfirmButton: false }); }"
x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error!', text: $event.detail.message, confirmButtonColor: '#d33' }); }"
wire:ignore.self>
    <div class="px-4 py-6 sm:px-0">
        @if(!$canView)
            <!-- Unauthorized Access Message -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-2xl p-8 shadow-lg">
                <div class="flex items-center justify-center">
                    <div class="text-center max-w-md">
                        <div class="bg-red-100 p-4 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6 shadow-inner">
                            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-red-900 mb-3">Access Denied</h2>
                        <p class="text-red-700 text-lg mb-6 leading-relaxed">{{ $unauthorizedMessage }}</p>
                        <a href="/samples/animals/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Animal Samples List
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-orange-900 to-orange-800 rounded-t-xl shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="bg-white/20 p-3 rounded-lg">
                                    <i class="fa-solid fa-paw text-2xl text-white"></i>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-white">Animal Profile</h1>
                                    <p class="text-orange-100 text-lg">Code: {{ $animal->code }}</p>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="flex items-center space-x-4">
                                <span class="text-orange-100 text-sm">
                                    {{ $animal->animal_species->name_common}} • {{ $animal->sex ?? 'N/A' }}
                                </span>
                                @if(!$canEdit)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View Only
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <a href="/animals/list"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white shadow-lg rounded-b-xl">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 p-8">

                    <!-- Left Column - Main Details -->
                    <div class="lg:col-span-2 space-y-8">

                        <!-- Animal Information Section -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-orange-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-paw text-2xl text-orange-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Animal Information</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Field Label</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        <div class="inline-edit" wire:key="field_label_{{ $animal->id }}">
                                            <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}" 
                                                  @if($canEdit) wire:click="startEdit('field_label')" @endif
                                                  x-show="!editing.field_label">
                                                {{ $animal->field_label }}
                                            </span>
                                            @if($canEdit)
                                                <div x-show="editing.field_label" class="inline-flex items-center space-x-2">
                                                    <input type="text" 
                                                           wire:model="editingValues.field_label" 
                                                           class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                           placeholder="{{ $animal->field_label }}"
                                                           x-ref="field_label_input"
                                                           x-init="$nextTick(() => $refs.field_label_input.focus())">
                                                    <button wire:click="saveEdit('field_label')" 
                                                            class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="cancelEdit('field_label')" 
                                                            class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Species</dt>
                                    <dd class="text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            {{ $animal->animal_species->name_common ?? 'N/A' }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sex</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="inline-edit" wire:key="sex_{{ $animal->id }}">
                                            <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}" 
                                                  @if($canEdit) wire:click="startEdit('sex')" @endif
                                                  x-show="!editing.sex">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                    {{ $animal->sex === 'Male' ? 'bg-blue-100 text-blue-800' : 
                                                       ($animal->sex === 'Female' ? 'bg-pink-100 text-pink-800' : 
                                                       'bg-gray-100 text-gray-800') }}">
                                                    {{ $animal->sex ?? 'N/A' }}
                                                </span>
                                            </span>
                                            @if($canEdit)
                                                <div x-show="editing.sex" class="inline-flex items-center space-x-2">
                                                    <select wire:model="editingValues.sex" 
                                                            class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                            x-ref="sex_input"
                                                            x-init="$nextTick(() => $refs.sex_input.focus())">
                                                        <option value="Male">Male</option>
                                                        <option value="Female">Female</option>
                                                        <option value="NA">N/A</option>
                                                    </select>
                                                    <button wire:click="saveEdit('sex')" 
                                                            class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="cancelEdit('sex')" 
                                                            class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Age</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        <div class="inline-edit" wire:key="age_{{ $animal->id }}">
                                            <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}" 
                                                  @if($canEdit) wire:click="startEdit('age')" @endif
                                                  x-show="!editing.age">
                                                {{ $animal->age ?? 'N/A' }}
                                            </span>
                                            @if($canEdit)
                                                <div x-show="editing.age" class="inline-flex items-center space-x-2">
                                                    <select wire:model="editingValues.age" 
                                                            class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                            x-ref="age_input"
                                                            x-init="$nextTick(() => $refs.age_input.focus())">
                                                        <option value="Juvenile">Juvenile</option>
                                                        <option value="Sub-adult">Sub-adult</option>
                                                        <option value="Adult">Adult</option>
                                                        <option value="NA">N/A</option>
                                                    </select>
                                                    <button wire:click="saveEdit('age')" 
                                                            class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button wire:click="cancelEdit('age')" 
                                                            class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        </div>

                    <!-- Health Information Section -->
                    @if($animal->animal_health->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-red-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-heart-pulse text-2xl text-red-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Health Assessments ({{ $animal->animal_health->count() }})</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Check Date
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Health Status
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Check Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Clinical Signs
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Lesions
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Alive
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($animal->animal_health as $health)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $health->check_date ? \Carbon\Carbon::parse($health->check_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $health->health_status === 'Healthy' ? 'bg-green-100 text-green-800' : 
                                                   ($health->health_status === 'Sick' ? 'bg-red-100 text-red-800' : 
                                                   'bg-yellow-100 text-yellow-800') }}">
                                                {{ $health->health_status ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $health->check_type ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $health->clinical_signs_id && $health->clinical_signs ? $health->clinical_signs->name : 'No clinical signs recorded' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $health->lesions_id && $health->lesions ? $health->lesions->name : 'No lesions recorded' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $health->alive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                {{ $health->alive ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Vaccinations Section -->
                    @if($animal->animal_vaccinations->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-green-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-syringe text-2xl text-green-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Vaccinations ({{ $animal->animal_vaccinations->count() }})</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Vaccine Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date Administered
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Administered By
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Next Due
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($animal->animal_vaccinations as $vaccination)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            {{ $vaccination->vaccine_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $vaccination->vaccine_type ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $vaccination->date_administered ? \Carbon\Carbon::parse($vaccination->date_administered)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @php
                                                // Avoid lazy loading + avoid "Undefined array key" when not loaded
                                                $vaccinationPerson = $vaccination->getRelations()['people'] ?? null;
                                            @endphp
                                            {{ $vaccinationPerson ? $vaccinationPerson->first_name.' '.$vaccinationPerson->last_name : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $vaccination->next_due_date ? \Carbon\Carbon::parse($vaccination->next_due_date)->format('M d, Y') : 'N/A' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Medications Section -->
                    @if($animal->animal_medications->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-pills text-2xl text-purple-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Medications ({{ $animal->animal_medications->count() }})</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Medication Name
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Dosage
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Prescribed By
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Duration
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($animal->animal_medications as $medication)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
                                            {{ $medication->medication_name }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $medication->dosage ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $medication->people && $medication->people ? $medication->people->first_name . ' ' . $medication->people->last_name : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $medication->start_date ? \Carbon\Carbon::parse($medication->start_date)->format('M d, Y') : 'N/A' }}
                                            @if($medication->end_date)
                                                - {{ \Carbon\Carbon::parse($medication->end_date)->format('M d, Y') }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Animal Movements Section -->
                    @if($animal->animal_movements->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-route text-2xl text-indigo-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Animal Movements ({{ $animal->animal_movements->count() }})</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date Moved
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            From
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            To
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Reason
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Coordinates
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($animal->animal_movements as $movement)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $movement->date_moved ? \Carbon\Carbon::parse($movement->date_moved)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $movement->source_sampling_site_id && $movement->source_sampling_site ? $movement->source_sampling_site->name : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $movement->destination_sampling_site_id && $movement->destination_sampling_site ? $movement->destination_sampling_site->name : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                {{ $movement->movement_reason ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            @if($movement->coordinates_start_lat && $movement->coordinates_start_lng)
                                                Start: {{ $movement->coordinates_start_lat }}, {{ $movement->coordinates_start_lng }}<br>
                                            @endif
                                            @if($movement->coordinates_destination_lat && $movement->coordinates_destination_lng)
                                                End: {{ $movement->coordinates_destination_lat }}, {{ $movement->coordinates_destination_lng }}
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Related Samples Section -->
                    @if($animal->animal_samples->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-vial text-2xl text-blue-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Related Samples</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Sample Code
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Sample Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date Collected
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($animal->animal_samples as $sample)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="/samples/animals/{{ $sample->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                {{ $sample->code }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                @if($sample->sample_types_id)
                                                    @php
                                                        $sampleType = \App\Models\SampleTypes::find($sample->sample_types_id);
                                                    @endphp
                                                    {{ $sampleType ? $sampleType->name : 'N/A' }}
                                                @else
                                                    N/A
                                                @endif
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $sample->date_collected ? \Carbon\Carbon::parse($sample->date_collected)->format('M d, Y') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Available
                                            </span>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    <!-- Experiments Results Section -->
                    @if($sampleExperiments->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-flask text-2xl text-blue-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Experiments results ({{ $sampleExperiments->count() }})</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition>
                            <p class="text-xs text-gray-500 mb-3">All experiments conducted on this animal's samples and on samples derived from them.</p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocol</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pathogen</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Tested</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outcome</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($sampleExperiments as $experiment)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="/experiments/{{ $experiment->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $experiment->code }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $experiment->protocols->name ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $experiment->pathogens->species ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $experiment->date_tested ? \Carbon\Carbon::parse($experiment->date_tested)->format('M d, Y') : 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    {{ $experiment->outcome_discrete === 'Strong positive'
                                                        ? 'bg-red-700 text-white'
                                                        : ($experiment->outcome_discrete === 'Positive'
                                                            ? 'bg-orange-100 text-orange-800'
                                                            : ($experiment->outcome_discrete === 'Suspect'
                                                                ? 'bg-yellow-100 text-yellow-800'
                                                                : ($experiment->outcome_discrete === 'Negative'
                                                                    ? 'bg-green-100 text-green-800'
                                                                    : 'bg-gray-100 text-gray-800'))) }}">
                                                    {{ $experiment->outcome_discrete ?? 'N/A' }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Column - Sidebar -->
                <div class="space-y-6">

                    <!-- Photo Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center gap-3">
                            <div class="bg-pink-100 p-2 rounded-lg">
                                <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor"
                                     viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                            </div>
                            <div class="min-w-0">
                                <h2 class="text-lg font-semibold text-gray-900 leading-tight">Photo</h2>
                                <p class="mt-0.5 text-xs text-gray-500 leading-snug">
                                    Max file size: 50MB <br> Formats: JPG, PNG, WEBP, TIFF, PDF
                                </p>
                            </div>
                        </div>

                        @if($canEdit)
                            <div class="mt-2">
                                @if(!$photo)
                                    <label for="photo-upload"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200 cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        Select photo
                                    </label>
                                    <input
                                        type="file"
                                        id="photo-upload"
                                        class="hidden"
                                        accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf"
                                        wire:model="photo"
                                        wire:loading.attr="disabled"
                                        x-data
                                        x-on:photo-uploaded.window="$el.value = ''"
                                        x-on:photo-cancelled.window="$el.value = ''"
                                    >
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        <button wire:click="uploadPhoto"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Upload
                                        </button>
                                        <button wire:click="cancelPhotoSelection"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Cancel
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($uploadError)
                            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-red-800 break-words">{{ $uploadError }}</span>
                                </div>
                            </div>
                        @endif

                        <x-upload-progress wireModel="photo" class="mt-4" />

                        <!-- Photo Display -->
                        @php
                            $photoPath = $animal->pic_path;
                            $photoUrl = $photoPath ? Storage::url($photoPath) : null;
                            $photoExt = $photoPath ? strtolower(pathinfo($photoPath, PATHINFO_EXTENSION)) : null;
                            $photoIsPreviewable = in_array($photoExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                        @endphp
                        @if ($photoUrl)
                            <div class="relative group">
                                <a href="{{ $photoUrl }}" target="_blank" class="block">
                                    @if($photoIsPreviewable)
                                        <img src="{{ $photoUrl }}" alt="Animal photo"
                                            class="w-full h-auto rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-200">
                                    @else
                                        <div class="w-full rounded-lg border border-gray-200 bg-white p-6 text-center text-sm text-gray-700 shadow-sm hover:shadow transition-shadow duration-200">
                                            File uploaded ({{ strtoupper((string) $photoExt) }}) — click to open
                                        </div>
                                    @endif
                                </a>
                                
                                @if($canEdit)
                                    <!-- Delete Button (appears on hover) -->
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <button wire:click="deletePhoto" 
                                            wire:confirm="Are you sure you want to delete this photo?"
                                            class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-full shadow-lg transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- No Photo Placeholder -->
                            <div class="text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600">No photo uploaded yet</p>
                                    <p class="text-xs text-gray-500 mt-1">Click "Select Photo" to add an image</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Ownership Information -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <i class="fa-solid fa-user text-2xl text-blue-600"></i>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900">Ownership</h2>
                        </div>

                        <div class="space-y-4">
                            @if($animal->owner)
                                @if($animal->owner instanceof \App\Models\Humans)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 mb-2">Owner (Person)</dt>
                                        <dd class="text-sm text-gray-900">
                                            <div class="flex items-center space-x-3 bg-white p-3 rounded-lg border">
                                                <x-people-logo :person="$animal->owner" width="40" />
                                                <div>
                                                    <a href="/profile/{{ $animal->owner->id ?? '' }}"
                                                        class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                        {{ ($animal->owner->title ?? '') . ' ' . ($animal->owner->first_name ?? '') . ' ' . ($animal->owner->last_name ?? '') }}
                                                    </a>
                                                    @if ($animal->owner->email)
                                                        <p class="text-xs text-gray-500">{{ $animal->owner->email }}</p>
                                                    @endif
                                                </div>
                                            </div>
                                        </dd>
                                    </div>
                                @elseif($animal->owner instanceof \App\Models\Organizations)
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 mb-2">Owner (Organization)</dt>
                                        <dd class="text-sm text-gray-900">
                                            <div class="bg-white p-3 rounded-lg border">
                                                <div class="font-medium">{{ $animal->owner->name ?? 'N/A' }}</div>
                                                @if ($animal->owner->organization)
                                                    <div class="text-xs text-gray-500">{{ $animal->owner->organization }}</div>
                                                @endif
                                                @if ($animal->owner->region)
                                                    <div class="text-xs text-gray-500">{{ $animal->owner->region }}, {{ $animal->owner->country }}</div>
                                                @endif
                                            </div>
                                        </dd>
                                    </div>
                                @else
                                    <div>
                                        <dt class="text-sm font-medium text-gray-500 mb-2">Owner</dt>
                                        <dd class="text-sm text-gray-900">
                                            <div class="bg-white p-3 rounded-lg border text-gray-500">
                                                Unknown owner type
                                            </div>
                                        </dd>
                                    </div>
                                @endif
                            @else
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-2">Owner</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="bg-white p-3 rounded-lg border text-gray-500">
                                            No owner information available
                                        </div>
                                    </dd>
                                </div>
                            @endif
                        </div>
                    </div>

                </div>
            </div>
        </div>
        @endif
    </div>
</div> 

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('SweetAlert available:', typeof Swal !== 'undefined');
    
    // Listen for Livewire events
    window.addEventListener('show-success', function(event) {
        console.log('show-success event received:', event.detail);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: event.detail.message,
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
    
    window.addEventListener('show-error', function(event) {
        console.log('show-error event received:', event.detail);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: event.detail.message,
                confirmButtonColor: '#d33'
            });
        }
    });
});
</script> 