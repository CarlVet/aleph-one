<div data-profile-tables class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        @if (! $canView)
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
                        <a href="/samples/microplastics/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Microplastics List
                        </a>
                    </div>
                </div>
            </div>
        @elseif ($microplastic)
            <div class="bg-gradient-to-r from-sky-500 to-cyan-600 rounded-t-xl shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="bg-white/20 rounded-lg w-20 h-20 flex items-center justify-center">
                                    <i class="fas fa-recycle text-white text-[40px]"></i>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-white">Microplastics Details</h1>
                                    <p class="text-sky-100 text-lg">Code: {{ $microplastic->code }}</p>
                                </div>
                            </div>

                            <div class="flex items-center space-x-4">
                                <span class="text-sky-100 text-sm">
                                    {{ $microplastic->mps_types?->name ?? 'N/A' }} • {{ class_basename((string) $microplastic->microplastics_content_type) }}
                                </span>
                                @if (! $canEdit)
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
                            <a href="/samples/microplastics/list"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to List
                            </a>
                            @if ($canEdit)
                                <button type="button" wire:click="deleteRecord"
                                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Delete
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white shadow-lg rounded-b-xl">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 p-8">
                    <div class="lg:col-span-2 space-y-8">
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-sky-100 p-2 rounded-lg mr-3">
                                    <i class="fas fa-recycle text-lg text-sky-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Identification Information</h2>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Microplastics type</dt>
                                    <dd class="text-sm text-gray-900 font-medium">{{ $microplastic->mps_types?->name ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Sample weight (g)</dt>
                                    <dd class="text-sm text-gray-900 font-medium">{{ $microplastic->sample_weight ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Pearson r</dt>
                                    <dd class="text-sm text-gray-900 font-medium">{{ $microplastic->r_coeff ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Feret diameter</dt>
                                    <dd class="text-sm text-gray-900 font-medium">{{ $microplastic->m_feret ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Protocol</dt>
                                    <dd class="text-sm text-gray-900 font-medium">{{ $microplastic->protocols?->name ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-1">Laboratory</dt>
                                    <dd class="text-sm text-gray-900 font-medium">{{ $microplastic->laboratories?->name ?? 'N/A' }}</dd>
                                </div>
                            </div>
                        </div>

                        <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-4 bg-white rounded-lg border hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-center">
                                    <div class="bg-cyan-100 p-2 rounded-lg mr-3">
                                        <i class="fas fa-link text-lg text-cyan-600"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Sample Source Information</h3>
                                </div>
                                <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>

                            <div x-show="open" x-transition class="mt-4">
                                <div class="bg-white rounded-lg border p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 mb-1">Project</dt>
                                            <dd class="text-sm text-gray-900 font-medium">{{ $microplastic->projects?->code ?? 'N/A' }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 mb-1">Source type</dt>
                                            <dd class="text-sm text-gray-900 font-medium">{{ class_basename((string) $microplastic->microplastics_content_type) }}</dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 mb-1">Source code</dt>
                                            <dd class="text-sm text-gray-900 font-medium">
                                                @if ($sourceProfileUrl)
                                                    <a href="{{ $sourceProfileUrl }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                        {{ $microplastic->microplastics_content?->code }}
                                                    </a>
                                                @else
                                                    {{ $microplastic->microplastics_content?->code ?? 'N/A' }}
                                                @endif
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="text-sm font-medium text-gray-500 mb-1">Identified by</dt>
                                            <dd class="text-sm text-gray-900 font-medium">{{ trim(($microplastic->people?->title ?? '').' '.($microplastic->people?->first_name ?? '').' '.($microplastic->people?->last_name ?? '')) ?: 'N/A' }}</dd>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <div class="bg-gray-50 rounded-xl p-6">
                            <div class="flex items-center mb-6">
                                <div class="bg-yellow-100 p-2 rounded-lg mr-3">
                                    <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                </div>
                                <h2 class="text-lg font-semibold text-gray-900">Personnel</h2>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-2">Identified by</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="flex items-center space-x-3 bg-white p-3 rounded-lg border">
                                            <x-people-logo :person="$microplastic->people" width="40" />
                                            <div>
                                                @if ($microplastic->people)
                                                    <a href="/profile/{{ $microplastic->people->id }}"
                                                        class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                        {{ ($microplastic->people->title ?? '') . ' ' . ($microplastic->people->first_name ?? '') . ' ' . ($microplastic->people->last_name ?? '') }}
                                                    </a>
                                                    @if ($microplastic->people->email)
                                                        <p class="text-xs text-gray-500">{{ $microplastic->people->email }}</p>
                                                    @endif
                                                @else
                                                    <span class="font-medium text-gray-900">N/A</span>
                                                @endif
                                            </div>
                                        </div>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 mb-2">Identified on</dt>
                                    <dd class="text-sm text-gray-900">
                                        <div class="bg-white p-3 rounded-lg border font-medium">
                                            {{ $microplastic->identification_date ? $microplastic->identification_date->format('M d, Y') : ($microplastic->created_at ? $microplastic->created_at->format('M d, Y') : 'N/A') }}
                                        </div>
                                    </dd>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="px-8 pb-8 space-y-8">
                    @if ($microplastic->tubes->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-4 bg-white rounded-lg border hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-center">
                                    <div class="bg-amber-100 p-2 rounded-lg mr-3">
                                        <i class="fas fa-vial text-lg text-amber-600"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Generated Tubes ({{ $microplastic->tubes->count() }})</h3>
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
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alias</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach ($microplastic->tubes as $tube)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ $tube->code }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $tube->alias_code ?: 'No alias' }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($microplastic->experiments->count() > 0)
                        <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center justify-between w-full p-4 bg-white rounded-lg border hover:bg-gray-50 transition-colors duration-200">
                                <div class="flex items-center">
                                    <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                                        <i class="fas fa-flask text-lg text-indigo-600"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900">Experiments results ({{ $microplastic->experiments->count() }})</h3>
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
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Tested</th>
                                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outcome</th>
                                                </tr>
                                            </thead>
                                            <tbody class="bg-white divide-y divide-gray-200">
                                                @foreach ($microplastic->experiments as $experiment)
                                                    <tr class="hover:bg-gray-50">
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                            <a href="/experiments/{{ $experiment->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">{{ $experiment->code }}</a>
                                                        </td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $experiment->protocols?->name ?: 'N/A' }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $experiment->pathogens?->species ?: 'N/A' }}</td>
                                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $experiment->date_tested ? \Carbon\Carbon::parse($experiment->date_tested)->format('M d, Y') : 'N/A' }}</td>
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
                </div>
            </div>
        @endif
    </div>
</div>
