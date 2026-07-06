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
                        <a href="/samples/humans/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Human Samples List
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-pink-600 to-pink-500 rounded-t-xl shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="bg-white/20 p-3 rounded-lg">
                                    <i class="fas fa-person text-white text-[40px] group-hover:rotate-12 transition-transform duration-300"></i>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-white">Human Sample Profile</h1>
                                    <p class="text-blue-100 text-lg">Code: {{ $humanSample->code }}</p>
                                    @if(optional($humanSample->subProjectAssignment?->subProject)->code)
                                        <span class="mt-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800">
                                            Sub-project: {{ $humanSample->subProjectAssignment->subProject->code }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="flex items-center space-x-4">
                                <span class="text-blue-100 text-sm">
                                    {{ $humanSample->sample_types->name ?? 'N/A' }} • {{ ucfirst($humanSample->sample_purpose) }}
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
                            <a href="/samples/humans/list"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to List
                            </a>
                            @if($canEdit)
                                <button wire:click="deleteHumanSample"
                                        wire:confirm="Are you sure you want to delete this human sample? This action cannot be undone."
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
                                <div class="bg-pink-300 p-2 rounded-lg mr-3">
                                    <i class="fas fa-person text-white text-[20px] group-hover:rotate-12 transition-transform duration-300"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Sample Information</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sample Type</dt>
                                    <dd class="text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-300 text-white">
                                            {{ $humanSample->sample_types->name ?? 'N/A' }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sample Purpose</dt>
                                    <dd class="text-sm text-gray-900">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $humanSample->sample_purpose === 'diagnostic' ? 'bg-red-100 text-red-800' : 
                                               ($humanSample->sample_purpose === 'research' ? 'bg-blue-100 text-blue-800' : 
                                               'bg-green-100 text-green-800') }}">
                                            {{ ucfirst($humanSample->sample_purpose) }}
                                        </span>
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Date Received</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $humanSample->date_received ? $humanSample->date_received->format('M d, Y') : 'N/A' }}
                                    </dd>
                                </div>

                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Patient</dt>
                                    <dd class="text-sm text-gray-900">
                                        <a href="/humans/{{ $humanSample->humans->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $humanSample->humans->code }} - {{ $humanSample->humans->first_name }} {{ $humanSample->humans->last_name }}
                                        </a>
                                    </dd>
                                </div>


                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sampling Site</dt>
                                    <dd class="text-sm text-gray-900 font-medium">
                                        {{ $humanSample->sampling_sites->name }}
                                    </dd>
                                </div>
                            </div>
                        </div>

                        <!-- Experiments Section -->
                        @if($sampleExperiments->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6 mb-6" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-4 bg-white rounded-lg border hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-center">
                                    <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                        <i class="fa-solid fa-flask text-lg text-blue-600"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Experiments results ({{ $sampleExperiments->count() }})</h3>
                                </div>
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="open" x-transition class="mt-4">
                                <p class="text-xs text-gray-500 mb-3">Includes experiments performed directly on this sample and on samples derived from it.</p>
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
                        @if($humanSample->nucleic_acids->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-teal-100 p-2 rounded-lg mr-3">
                                    <svg class="w-6 h-6 text-teal-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                        </path>
                                    </svg>
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
                                        @foreach($humanSample->nucleic_acids as $na)
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

                        @if($humanSample->microplastics->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6 mb-6" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-4 bg-white rounded-lg border hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-center">
                                    <div class="bg-sky-100 p-2 rounded-lg mr-3">
                                        <i class="fas fa-recycle text-lg text-sky-600"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Microplastics ({{ $humanSample->microplastics->count() }})</h3>
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
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Weight (g)</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">r coeff.</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Feret</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach($humanSample->microplastics as $microplastic)
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
                                    <dt class="text-sm font-medium text-gray-500 mb-2">Collector</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="bg-white p-3 rounded-lg border">
                                            <div class="flex items-center space-x-2">
                                                <x-people-logo :person="$humanSample->people" width="24" />
                                                <a href="/profile/{{$humanSample->people->id}}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                                    {{ $humanSample->people->title . ' ' . $humanSample->people->first_name . ' ' . $humanSample->people->last_name ?? 'N/A' }}
                                                </a>
                                            </div>
                                            @if ($humanSample->people->email)
                                                <div class="text-xs text-gray-500">{{ $humanSample->people->email }}</div>
                                            @endif
                                            @if ($humanSample->people->phone)
                                                <div class="text-xs text-gray-500">{{ $humanSample->people->phone }}</div>
                                            @endif
                                        </div>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-2">Date Collected</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="bg-white p-3 rounded-lg border font-medium">
                                            {{ $humanSample->date_collected ? $humanSample->date_collected->format('M d, Y') : 'N/A' }}
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        </div>

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
                                $photoPath = data_get($humanSample, 'humans.photo_path');
                                $photoUrl = $photoPath ? Storage::url($photoPath) : null;
                                $photoExt = $photoPath ? strtolower(pathinfo($photoPath, PATHINFO_EXTENSION)) : null;
                                $photoIsPreviewable = in_array($photoExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                            @endphp
                            @if ($photoUrl)
                                <div class="relative group">
                                    <a href="{{ $photoUrl }}" target="_blank" class="block">
                                        @if($photoIsPreviewable)
                                            <img src="{{ $photoUrl }}" alt="Sample photo"
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif 