<div data-profile-tables class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
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
                        <a href="/samples/pools/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Pools List
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-cyan-900 to-cyan-800 rounded-t-xl shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="bg-white/20 p-3 rounded-lg">
                                    <i class="fa-solid fa-layer-group text-2xl text-white"></i>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-white">Pool Details</h1>
                                    <p class="text-cyan-100 text-lg">Code: {{ $pool->code }}</p>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="flex items-center space-x-4">
                                <span class="text-cyan-100 text-sm">
                                    {{ class_basename(optional($pool->pool_contents->first())->samples_type) ?: 'No source samples' }}
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
                            <a href="/samples/pools/list"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to List
                            </a>
                            @if($canEdit)
                                <button wire:click="deletePool"
                                    wire:confirm="Are you sure you want to delete this pool? This action cannot be undone."
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
                <div class="border-b border-gray-200 px-8 py-6">
                    <div class="grid grid-cols-1 gap-4 text-sm text-gray-800 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)_minmax(0,1fr)]">
                        <div class="rounded-lg bg-cyan-50 px-4 py-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Content Type</div>
                            <div class="mt-1 break-words font-semibold text-gray-900">
                                {{ class_basename(optional($pool->pool_contents->first())->samples_type) ?: 'No source samples' }}
                            </div>
                        </div>
                        <div class="rounded-lg bg-cyan-50 px-4 py-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Nr Pooled</div>
                            <div class="mt-1 break-words font-semibold text-gray-900">
                                {{ $pool->nr_pooled ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="rounded-lg bg-cyan-50 px-4 py-3">
                            <div class="text-xs font-semibold uppercase tracking-wide text-cyan-700">Date Pooled</div>
                            <div class="mt-1 break-words font-semibold text-gray-900">
                                {{ $pool->date_pooled ? \Carbon\Carbon::parse($pool->date_pooled)->format('Y-m-d') : 'N/A' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 gap-8 p-8 lg:grid-cols-3">

                    <!-- Left Column - Main Details -->
                    <div class="lg:col-span-2 space-y-8">

                        <!-- Pooled Contents Details Section -->
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-cyan-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-layer-group text-2xl text-cyan-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Pooled Contents Details</h2>
                            </div>

                            <div class="overflow-auto max-h-[60vh] rounded-lg border border-gray-200 bg-white">
                                <table class="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Type</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Code</th>
                                            <th class="px-3 py-2 text-left font-semibold text-gray-600">Details</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach($pool->pool_contents as $content)
                                            @php
                                                $sample = $content->samples;
                                                $sampleCode = $sample->code ?? 'N/A';
                                                $sampleType = class_basename((string) $content->samples_type);
                                                $details = match ($content->samples_type) {
                                                    'App\\Models\\HumanSamples' => 'Occupation: ' . (data_get($sample, 'humans.occupation') ?? 'N/A') . ', Country: ' . (data_get($sample, 'humans.countries.name') ?? 'N/A'),
                                                    'App\\Models\\AnimalSamples' => 'Species: ' . (data_get($sample, 'animals.animal_species.name_common') ?? 'N/A') . ', Sex: ' . (data_get($sample, 'animals.sex') ?? 'N/A'),
                                                    'App\\Models\\EnvironmentSamples' => 'Sample type: ' . (data_get($sample, 'environment_sample_types.name') ?? 'N/A') . ', Area: ' . (data_get($sample, 'area') ?? 'N/A'),
                                                    'App\\Models\\ParasiteSamples' => 'Species: ' . (data_get($sample, 'parasites.parasite_species.name_scientific') ?? 'N/A') . ', Stage: ' . (data_get($sample, 'parasites.stage') ?? 'N/A'),
                                                    'App\\Models\\NucleicAcids' => 'Nucleic type: ' . (data_get($sample, 'type') ?? 'N/A') . ', Extracted: ' . (data_get($sample, 'date_extracted') ? \Carbon\Carbon::parse(data_get($sample, 'date_extracted'))->format('Y-m-d') : 'N/A'),
                                                    'App\\Models\\Cultures' => 'Medium: ' . (data_get($sample, 'medium') ?? 'N/A') . ', Step: ' . (data_get($sample, 'step') ?? 'N/A'),
                                                    default => 'N/A',
                                                };
                                            @endphp
                                            <tr>
                                                <td class="px-3 py-2 text-gray-700">{{ $sampleType ?: 'N/A' }}</td>
                                                <td class="px-3 py-2 font-medium text-gray-900">{{ $sampleCode }}</td>
                                                <td class="px-3 py-2 text-gray-700">{{ $details }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- Experiments Section -->
                        @if($pool->experiments->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                                <div class="flex items-center">
                                    <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                        <i class="fa-solid fa-flask text-2xl text-blue-600"></i>
                                    </div>
                                    <h2 class="text-xl font-semibold text-gray-900">Experiments results ({{ $pool->experiments->count() }})</h2>
                                </div>
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                            <div x-show="open" x-transition>
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
                                        @foreach($pool->experiments as $experiment)
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

                        <!-- Related Nucleic Acids Section -->
                        @if($pool->nucleic_acids->count() > 0)
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
                                        @foreach($pool->nucleic_acids as $na)
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

                        @if($pool->microplastics->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                                <div class="flex items-center">
                                    <div class="bg-sky-100 p-2 rounded-lg mr-3">
                                        <i class="fas fa-recycle text-2xl text-sky-600"></i>
                                    </div>
                                    <h2 class="text-xl font-semibold text-gray-900">Microplastics ({{ $pool->microplastics->count() }})</h2>
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
                                            @foreach($pool->microplastics as $microplastic)
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

                        <!-- Personnel Information -->
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
                                    <dt class="text-sm font-medium text-gray-500 mb-2">Pooled By</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="flex items-center space-x-3 bg-white p-3 rounded-lg border">
                                            <x-people-logo :person="$pool->people" width="40" />
                                            <div>
                                                <a href="/profile/{{ $pool->people->id }}"
                                                    class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $pool->people->title . ' ' . $pool->people->first_name . ' ' . $pool->people->last_name ?? 'N/A' }}
                                                </a>
                                                @if ($pool->people->email)
                                                    <p class="text-xs text-gray-500">{{ $pool->people->email }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-2">Pooled At</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="bg-white p-3 rounded-lg border">
                                            <div class="font-medium">{{ $pool->laboratories->name ?? 'N/A' }}</div>
                                            @if ($pool->laboratories->countries->name)
                                                <div class="text-xs text-gray-500">{{ $pool->laboratories->countries->name }}
                                                </div>
                                            @endif
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                 </div>
             </div>
         </div>
     </div>
     @endif
 </div> 