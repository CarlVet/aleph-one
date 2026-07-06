<div data-profile-tables class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8"
    x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success!', text: $event.detail.message, timer: 2000, showConfirmButton: false }); }"
    x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error!', text: $event.detail.message, confirmButtonColor: '#d33' }); }">
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
                        <a href="/samples/parasites/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Parasite Samples List
                        </a>
                    </div>
                </div>
            </div>
        @else
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-purple-900 to-purple-800 rounded-t-xl shadow-lg">
            <div class="px-6 py-8">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="bg-white/20 p-3 rounded-lg">
                                <i class="fa-solid fa-spider text-3xl text-white"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">Parasite Sample Details</h1>
                                <p class="text-purple-100 text-lg">Code: {{ $sample->code }}</p>
                                @if(optional($sample->subProjectAssignment?->subProject)->code)
                                    <span class="mt-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                        Sub-project: {{ $sample->subProjectAssignment->subProject->code }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center space-x-4">
                            <span class="text-purple-100 text-sm">
                                {{ $sample->parasite_sample_types->name ?? 'N/A' }} • 
                                {!! '<i>' . e($sample->parasites->parasite_species->name_scientific) . '</i>' ?? 'N/A' !!}
                            </span>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <a href="/samples/parasites/list"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to List
                        </a>
                        @if($canEdit)
                            <button wire:click="deleteParasiteSample"
                                    wire:confirm="Are you sure you want to delete this parasite sample? This action cannot be undone."
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

                    <!-- Parasite Sample Information Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                <i class="fa-solid fa-spider text-2xl text-purple-600"></i>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Parasite Sample Information</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Parasite Code</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <a href="/parasites/{{ $sample->parasites->code ?? '' }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                        {{ $sample->parasites->code ?? 'N/A' }}
                                    </a>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Parasite Species</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {!! '<i>' . e($sample->parasites->parasite_species->name_scientific) . '</i>' ?? 'N/A' !!}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Sample Type</dt>
                                <dd class="text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <div class="inline-edit" wire:key="parasite_sample_types_id_{{ $sample->id }}">
                                            @if($editingField !== 'parasite_sample_types_id')
                                            <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                                  @if($canEdit) wire:click="startEdit('parasite_sample_types_id')" @endif>
                                                {{ $sample->parasite_sample_types->name ?? 'N/A' }}
                                            </span>
                                            @elseif($canEdit)
                                                <div class="inline-flex items-center space-x-2">
                                                    <select wire:model="editingValues.parasite_sample_types_id"
                                                            class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white">
                                                        @foreach($parasiteSampleTypes as $type)
                                                            <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" wire:click="saveEdit('parasite_sample_types_id')"
                                                            class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                        </svg>
                                                    </button>
                                                    <button type="button" wire:click="cancelEdit('parasite_sample_types_id')"
                                                            class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                        </svg>
                                                    </button>
                                                </div>
                                            @endif
                                        </div>
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Date Identified</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $sample->parasites->date_identified ? \Carbon\Carbon::parse($sample->parasites->date_identified)->format('M d, Y') : 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Processed By</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="flex items-center space-x-2">
                                        <x-people-logo :person="$sample->people" width="24" />
                                        <a href="/profile/{{ $sample->people->id ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                            {{ $sample->people->title . ' ' . $sample->people->first_name . ' ' . $sample->people->last_name ?? 'N/A' }}
                                        </a>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Collection Site</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $sample->parasites->parasites_origin->sampling_sites->name ?? 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Date processed</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="date_processed_{{ $sample->id }}">
                                        @if($editingField !== 'date_processed')
                                        <span class="{{ $canEdit ? 'editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors' : '' }}"
                                              @if($canEdit) wire:click="startEdit('date_processed')" @endif>
                                            {{ $sample->date_processed ? \Carbon\Carbon::parse($sample->date_processed)->format('Y-m-d') : 'N/A' }}
                                        </span>
                                        @elseif($canEdit)
                                            <div class="inline-flex items-center space-x-2">
                                                <input type="date" wire:model="editingValues.date_processed"
                                                       class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent bg-white">
                                                <button type="button" wire:click="saveEdit('date_processed')"
                                                        class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                    </svg>
                                                </button>
                                                <button type="button" wire:click="cancelEdit('date_processed')"
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Collected From</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    @if($sample->parasites->parasites_origin_type === 'App\Models\HumanSamples')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">
                                    {{ 'Human patient - ' . $sample->parasites->parasites_origin->sample_types->name ?? 'N/A'}}
                                    </span>
                                    @elseif($sample->parasites->parasites_origin_type === 'App\Models\AnimalSamples')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                    {{ 'Animal patient - ' . $sample->parasites->parasites_origin->sample_types->name ?? 'N/A'}}
                                    </span>
                                    @elseif($sample->parasites->parasites_origin_type === 'App\Models\EnvironmentSamples')
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    {{ 'Environmental sample - ' . $sample->parasites->parasites_origin->environment_sample_types->name ?? 'N/A'}}
                                    </span>
                                    @endif
                                </dd>
                            </div>

                        </div>
                    </div>

                    <!-- Experiments Section -->
                    @if($sampleExperiments->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-flask text-2xl text-blue-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Experiments results ({{ $sampleExperiments->count() }})</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" 
                                 :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition>
                            <p class="text-xs text-gray-500 mb-3">Includes experiments performed directly on this sample and on samples derived from it.</p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Code
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Protocol
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Pathogen
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date Tested
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Outcome
                                            </th>
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
                                                @if($experiment->pathogens)<i>{{ $experiment->pathogens->species }}</i>@else N/A @endif
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

                    <!-- Nucleic Acids Section -->
                    @if($sample->nucleic_acids->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-dna text-2xl text-blue-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Nucleic Acids ({{ $sample->nucleic_acids->count() }})</h2>
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
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Code
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Volume
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date Extracted
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($sample->nucleic_acids as $nucleic)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="/samples/nucleic/{{ $nucleic->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $nucleic->code }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $nucleic->type ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $nucleic->volume ?? 'N/A' }} μL
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $nucleic->date_extracted ? \Carbon\Carbon::parse($nucleic->date_extracted)->format('M d, Y') : 'N/A' }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Cultures Section -->
                    @if($sample->cultures->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-orange-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-bacteria text-2xl text-orange-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Cultures ({{ $sample->cultures->count() }})</h2>
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
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Code
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date Created
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($sample->cultures as $culture)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="/samples/cultures/{{ $culture->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $culture->code }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $culture->type ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $culture->date_cultured ? \Carbon\Carbon::parse($culture->date_cultured)->format('M d, Y') : 'N/A' }}
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Pools Section -->
                    @if($sample->pools->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-cyan-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-layer-group text-2xl text-cyan-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Pools ({{ $sample->pools->count() }})</h2>
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
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Code
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Type
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Date Created
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Created By
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($sample->pools as $poolContent)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="/samples/pools/{{ $poolContent->pools->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $poolContent->pools->code }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $poolContent->pools->type ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $poolContent->pools->date_created ? \Carbon\Carbon::parse($poolContent->pools->date_created)->format('M d, Y') : 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <div class="flex items-center space-x-2">
                                                    <x-people-logo :person="$poolContent->pools->people" width="24" />
                                                    <a href="/profile/{{ $poolContent->pools->people->id ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                        {{ $poolContent->pools->people ? ($poolContent->pools->people->title ?? '') . ' ' . ($poolContent->pools->people->first_name ?? '') . ' ' . ($poolContent->pools->people->last_name ?? '') : 'N/A' }}
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Tubes Section -->
                    @if($sample->tubes->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-gray-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-vial text-2xl text-gray-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Tubes ({{ $sample->tubes->count() }})</h2>
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
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Code
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Alias
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Purpose
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Preservant
                                            </th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Box
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($sample->tubes as $tube)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="/bank/tubes/{{ $tube->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $tube->code }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $tube->alias_code ?: 'No alias' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $tube->purpose ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium">
                                                    {{ $tube->preservant ?? 'N/A' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                @if($tube->tube_positions && $tube->tube_positions->first() && $tube->tube_positions->first()->boxes)
                                                <a href="/bank/boxes/{{ $tube->tube_positions->first()->boxes->id }}/contents" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $tube->tube_positions->first()->boxes->code }}
                                                </a>
                                                @else
                                                    No box assigned
                                                @endif
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($sample->microplastics->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-sky-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-recycle text-2xl text-sky-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Microplastics ({{ $sample->microplastics->count() }})</h2>
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
                                        @foreach($sample->microplastics as $microplastic)
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
                    @php
                        $parasiteCollector = data_get($sample, 'parasites.parasites_origin.people');
                        $parasiteDateCollected = data_get($sample, 'parasites.parasites_origin.date_collected');
                    @endphp

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
                                        <x-people-logo :person="$parasiteCollector" width="40" />
                                        <div>
                                            @if ($parasiteCollector)
                                                <a href="/profile/{{ $parasiteCollector->id }}"
                                                    class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ ($parasiteCollector->title ?? '') . ' ' . ($parasiteCollector->first_name ?? '') . ' ' . ($parasiteCollector->last_name ?? '') }}
                                                </a>
                                                @if ($parasiteCollector->email)
                                                    <p class="text-xs text-gray-500">{{ $parasiteCollector->email }}</p>
                                                @endif
                                            @else
                                                <span class="font-medium text-gray-900">N/A</span>
                                            @endif
                                        </div>
                                    </div>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-2">Date Collected</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="bg-white p-3 rounded-lg border font-medium">
                                        {{ $parasiteDateCollected ? \Carbon\Carbon::parse($parasiteDateCollected)->format('M d, Y') : 'N/A' }}
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>

                    <!-- Project Information -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900">Project</h2>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-2">Project</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="bg-white p-3 rounded-lg border">
                                        <div class="font-medium">{{ $sample->projects->title ?? 'N/A' }}</div>
                                        @if ($sample->projects->code)
                                            <div class="text-xs text-gray-500">{{ $sample->projects->code }}</div>
                                        @endif
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="px-8 pb-8 border-t border-gray-100">
                @include('partials.parasite-observation-gallery', [
                    'owner' => $sample,
                    'observations' => $sample->observations,
                    'legacyPhotoPath' => $sample->photo_path,
                ])
            </div>
        </div>
    </div>
</div> 
@endif