<div data-profile-tables class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="{
    editing: {
        area: false,
        latitude: false,
        longitude: false
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
                        <a href="/samples/environment/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Environment Samples List
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-t-xl shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="bg-white/20 rounded-lg w-20 h-20 flex items-center justify-center">
                                    <i class="fas fa-leaf text-white text-[40px] group-hover:rotate-12 transition-transform duration-300"></i>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-white">Environment Sample Details</h1>
                                    <p class="text-green-100 text-lg">Code: {{ $environmentSample->code }}</p>
                                    @if(optional($environmentSample->subProjectAssignment?->subProject)->code)
                                        <span class="mt-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                            Sub-project: {{ $environmentSample->subProjectAssignment->subProject->code }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="flex items-center space-x-4">
                                <span class="text-green-100 text-sm">
                                    {{ $environmentSample->environment_sample_types->name ?? 'N/A' }}
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
                            <a href="/samples/environment/list"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to List
                            </a>
                            @if($canEdit)
                                <button wire:click="deleteEnvironmentSample"
                                        wire:confirm="Are you sure you want to delete this environment sample? This action cannot be undone."
                                        class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

        <!-- Main Content -->
        <div class="bg-white shadow-lg rounded-b-xl">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 p-8">

                <!-- Left Column - Main Details -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Sample Information Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-leaf text-lg text-green-600 group-hover:rotate-12 transition-transform duration-300"></i>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Sample Information</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Sample Type</dt>
                                <dd class="text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        {{ $environmentSample->environment_sample_types->name ?? 'N/A' }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Sampling Site</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $environmentSample->sampling_sites->name ?? 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Collector</dt>
                                <dd class="text-sm text-gray-900">
                                    {{ $environmentSample->people->title . ' ' . $environmentSample->people->first_name . ' ' . $environmentSample->people->last_name ?? 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Area</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="area_{{ $environmentSample->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}" 
                                              @if($canEdit) wire:click="startEdit('area')" @endif
                                              x-show="!editing.area">
                                            {{ $environmentSample->area ?? 'N/A' }}
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.area" class="inline-flex items-center space-x-2">
                                                <input type="text" 
                                                       wire:model="editingValues.area" 
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                       placeholder="{{ $environmentSample->area ?? 'Enter area' }}"
                                                       x-ref="area_input"
                                                       x-init="$nextTick(() => $refs.area_input.focus())">
                                                <button wire:click="saveEdit('area')" 
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('area')" 
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Coordinates</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="coordinates_{{ $environmentSample->id }}">
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}" 
                                              @if($canEdit) wire:click="startEdit('latitude')" @endif
                                              x-show="!editing.latitude && !editing.longitude">
                                            @if($environmentSample->latitude && $environmentSample->longitude)
                                                {{ $environmentSample->latitude }}, {{ $environmentSample->longitude }}
                                            @else
                                                N/A
                                            @endif
                                        </span>
                                        @if($canEdit)
                                            <div x-show="editing.latitude || editing.longitude" class="inline-flex items-center space-x-2">
                                                <input type="number" 
                                                       wire:model="editingValues.latitude" 
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-20"
                                                       placeholder="Lat"
                                                       step="0.000001"
                                                       x-ref="latitude_input"
                                                       x-init="$nextTick(() => $refs.latitude_input.focus())">
                                                <span class="text-gray-500">,</span>
                                                <input type="number" 
                                                       wire:model="editingValues.longitude" 
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent w-20"
                                                       placeholder="Lng"
                                                       step="0.000001"
                                                       x-ref="longitude_input">
                                                <button wire:click="saveEdit('latitude')" 
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button wire:click="cancelEdit('latitude')" 
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

                    <!-- Experiments Section -->
                    @if($environmentSample->experiments->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6 mb-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full p-4 bg-white rounded-lg border hover:bg-gray-50 transition-colors duration-200">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-flask text-lg text-blue-600"></i>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900">Experiments results ({{ $environmentSample->experiments->count() }})</h3>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="mt-4">
                            <div class="bg-white rounded-lg border overflow-hidden">
                                <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocol</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pathogen</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Tested</th>
                                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outcome</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($environmentSample->experiments as $experiment)
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
                                                    {{ $experiment->purpose ? ucfirst($experiment->purpose) : 'N/A' }}
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
                    </div>
                    @endif

                    <!-- Related Nucleic Acids Section -->
                    @if($environmentSample->nucleic_acids->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-teal-100 p-2 rounded-lg mr-3">
                                <i class="fa-solid fa-dna text-2xl text-teal-600"></i>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Related Nucleic Acids</h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Code
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Concentration
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date Extracted
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($environmentSample->nucleic_acids as $na)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="/samples/nucleic/{{ $na->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                {{ $na->code }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $na->type === 'DNA' ? 'bg-blue-100 text-blue-800' : 
                                                   ($na->type === 'RNA' ? 'bg-green-100 text-green-800' : 
                                                   'bg-gray-100 text-gray-800') }}">
                                                {{ $na->type }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $na->concentration ?? 'N/A' }} ng/μL
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $na->date_extracted ? $na->date_extracted->format('M d, Y') : 'N/A' }}
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

                    @if($environmentSample->microplastics->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-sky-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-recycle text-2xl text-sky-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Microplastics ({{ $environmentSample->microplastics->count() }})</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200"
                                 :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight (g)</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">r coeff.</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feret</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($environmentSample->microplastics as $microplastic)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="/samples/microplastics/{{ $microplastic->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $microplastic->code }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $microplastic->mps_types?->name ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $microplastic->sample_weight ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $microplastic->r_coeff ?? 'N/A' }}</td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $microplastic->m_feret ?? 'N/A' }}</td>
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
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900">Personnel</h2>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-2">Collector</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="flex items-center space-x-3 bg-white p-3 rounded-lg border">
                                        <x-people-logo :person="$environmentSample->people" width="40" />
                                        <div>
                                            <a href="/profile/{{ $environmentSample->people->id }}"
                                                class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                {{ $environmentSample->people ? ($environmentSample->people->title ?? '') . ' ' . ($environmentSample->people->first_name ?? '') . ' ' . ($environmentSample->people->last_name ?? '') : 'N/A' }}
                                            </a>
                                            @if ($environmentSample->people && $environmentSample->people->email)
                                                <p class="text-xs text-gray-500">{{ $environmentSample->people->email }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-2">Date Collected</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="bg-white p-3 rounded-lg border font-medium">
                                        {{ $environmentSample->date_collected ? \Carbon\Carbon::parse($environmentSample->date_collected)->format('M d, Y') : 'N/A' }}
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif 

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