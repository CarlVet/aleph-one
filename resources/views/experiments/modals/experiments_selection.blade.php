<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-red-50 to-pink-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"></path>
                </svg>
                Experiments Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select the experiments for this analysis</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-red-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-red-600">0</span> experiments selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="experiments_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button id="confirm_experiment_selection"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-red-600 to-pink-600 hover:from-red-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 transform hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Confirm Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto">
        <table id="experiment_selection_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="select_all_experiments"
                                class="h-5 w-5 text-red-600 focus:ring-red-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_experiments"
                                class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-red-600 transition-colors">
                                Select All
                            </label>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Experiment Code</span>
                            <svg class="w-4 h-4 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Nucleic Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Nucleic Content Details</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Protocol</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Pathogen Species</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Categorical Outcome</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Quantitative Outcome</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date Tested</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tested By</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tested At</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($experiments_nucleic as $experiment)
                    <tr class="hover:bg-red-50 transition-all duration-200 ease-in-out group">
                        <td class="sticky left-0 z-10 bg-white group-hover:bg-red-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox" class="select-experiment h-5 w-5 text-red-600 focus:ring-red-500 border-gray-300 rounded transition-all duration-200 hover:scale-110" value="{{ $experiment->id }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/experiments/{{ $experiment->code }}" class="text-sm font-medium text-red-600 hover:text-red-800 hover:underline">
                                {{ $experiment->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $nucleicCode = $experiment->experiments_content->code ?? null;
                            @endphp
                            @if ($nucleicCode)
                                <a href="/samples/nucleic/{{ $nucleicCode }}" class="text-sm font-medium text-red-600 hover:text-red-800 hover:underline">
                                    {{ $nucleicCode }}
                                </a>
                            @else
                                <span class="text-sm text-gray-900">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($experiment->experiments_content->nucleic_content_type === 'App\Models\HumanSamples')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">Human Sample</span>
                            @elseif($experiment->experiments_content->nucleic_content_type === 'App\Models\AnimalSamples')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Animal Sample</span>
                            @elseif($experiment->experiments_content->nucleic_content_type === 'App\Models\EnvironmentSamples')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">Environmental Sample</span>
                            @elseif($experiment->experiments_content->nucleic_content_type === 'App\Models\ParasiteSamples')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Parasite Sample</span>
                            @elseif($experiment->experiments_content->nucleic_content_type === 'App\Models\Cultures')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Cultures</span>
                            @elseif($experiment->experiments_content->nucleic_content_type === 'App\Models\Pools')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">Pools</span>
                            @else
                                <span class="text-sm text-gray-900">{{ $experiment->experiments_content->nucleic_content_type ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $nucleic = $experiment->experiments_content;
                                $content = $nucleic?->nucleic_content;
                                $contentType = (string) ($nucleic->nucleic_content_type ?? '');
                                $directAliasCodes = collect(data_get($content, 'tubes', []))->pluck('alias_code');
                                $nucleicAliasCodes = collect(data_get($nucleic, 'tubes', []))->pluck('alias_code');
                                $pooledSampleAliasCodes = $contentType === 'App\\Models\\Pools'
                                    ? collect(data_get($content, 'pool_contents', []))
                                        ->flatMap(fn ($poolContent) => collect(data_get($poolContent, 'samples.tubes', []))->pluck('alias_code'))
                                    : collect();
                                $aliasCodes = $directAliasCodes
                                    ->merge($nucleicAliasCodes)
                                    ->merge($pooledSampleAliasCodes)
                                    ->map(fn ($alias) => is_string($alias) ? trim($alias) : '')
                                    ->filter(fn ($alias) => $alias !== '')
                                    ->unique()
                                    ->values()
                                    ->all();
                                $aliasCodesLabel = !empty($aliasCodes) ? implode(', ', $aliasCodes) : 'N/A';
                                $detailsRows = match ($contentType) {
                                    'App\\Models\\HumanSamples' => [
                                        ['label' => 'Sample code', 'value' => data_get($content, 'code') ?? 'N/A'],
                                        ['label' => 'Sample type', 'value' => data_get($content, 'sample_types.name') ?? 'N/A'],
                                        ['label' => 'Sampling site', 'value' => data_get($content, 'sampling_sites.name') ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    'App\\Models\\AnimalSamples' => [
                                        ['label' => 'Sample code', 'value' => data_get($content, 'code') ?? 'N/A'],
                                        ['label' => 'Species', 'value' => data_get($content, 'animals.animal_species.name_common') ?? data_get($content, 'animals.animal_species.name_scientific') ?? 'N/A'],
                                        ['label' => 'Sample type', 'value' => data_get($content, 'sample_types.name') ?? 'N/A'],
                                        ['label' => 'Sampling site', 'value' => data_get($content, 'sampling_sites.name') ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    'App\\Models\\EnvironmentSamples' => [
                                        ['label' => 'Sample code', 'value' => data_get($content, 'code') ?? 'N/A'],
                                        ['label' => 'Env type', 'value' => data_get($content, 'environment_sample_types.name') ?? 'N/A'],
                                        ['label' => 'Area', 'value' => data_get($content, 'area') ?? 'N/A'],
                                        ['label' => 'Sampling site', 'value' => data_get($content, 'sampling_sites.name') ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    'App\\Models\\ParasiteSamples' => [
                                        ['label' => 'Sample code', 'value' => data_get($content, 'code') ?? 'N/A'],
                                        ['label' => 'Species', 'value' => data_get($content, 'parasites.parasite_species.name_scientific') ?? 'N/A'],
                                        ['label' => 'Sample type', 'value' => data_get($content, 'parasite_sample_types.name') ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    'App\\Models\\Cultures' => [
                                        ['label' => 'Culture code', 'value' => data_get($content, 'code') ?? 'N/A'],
                                        ['label' => 'Medium', 'value' => data_get($content, 'medium') ?? 'N/A'],
                                        ['label' => 'Step', 'value' => data_get($content, 'step') ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    'App\\Models\\Pools' => [
                                        ['label' => 'Pool code', 'value' => data_get($content, 'code') ?? 'N/A'],
                                        ['label' => 'Nr pooled', 'value' => data_get($content, 'nr_pooled') ?? 'N/A'],
                                        ['label' => 'Date pooled', 'value' => data_get($content, 'date_pooled') ? \Carbon\Carbon::parse(data_get($content, 'date_pooled'))->format('Y-m-d') : 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    default => [
                                        ['label' => 'Details', 'value' => 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                };
                                $poolContentRows = $contentType === 'App\\Models\\Pools'
                                    ? collect(data_get($content, 'pool_contents', []))->map(fn ($poolContent) => [
                                        'type' => class_basename((string) data_get($poolContent, 'samples_type')),
                                        'code' => data_get($poolContent, 'samples.code') ?? 'N/A',
                                    ])->all()
                                    : [];
                            @endphp
                            <div class="min-w-[280px] overflow-hidden rounded-lg border border-gray-200 bg-white">
                                <table class="min-w-full text-xs">
                                    <tbody class="divide-y divide-gray-100">
                                        @foreach ($detailsRows as $detailRow)
                                            <tr>
                                                <td class="w-28 bg-gray-50 px-2 py-1.5 font-semibold text-gray-700">{{ $detailRow['label'] }}</td>
                                                <td class="px-2 py-1.5 text-gray-800 break-words">{{ $detailRow['value'] }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>

                                @if (!empty($poolContentRows))
                                    <div class="border-t border-gray-200 px-2 py-1.5">
                                        <div class="mb-1 text-[11px] font-semibold uppercase tracking-wide text-gray-600">Pooled contents</div>
                                        <table class="min-w-full text-xs">
                                            <thead>
                                                <tr class="bg-gray-50">
                                                    <th class="px-2 py-1 text-left font-semibold text-gray-600">Type</th>
                                                    <th class="px-2 py-1 text-left font-semibold text-gray-600">Code</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                @foreach ($poolContentRows as $poolRow)
                                                    <tr>
                                                        <td class="px-2 py-1 text-gray-700">{{ $poolRow['type'] ?: 'N/A' }}</td>
                                                        <td class="px-2 py-1 text-gray-800">{{ $poolRow['code'] }}</td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $experiment->protocols->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $experiment->pathogens->species ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $experiment->outcome_discrete ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $experiment->outcome_quant ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($experiment->date_tested)->format('Y-m-d') ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $experiment->people?->title }} {{ $experiment->people?->first_name }} {{ $experiment->people?->last_name }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $experiment->laboratories?->name ?? 'N/A' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($experiments_nucleic, 'links'))
        <div class="px-6 py-4 border-t border-gray-200 bg-white">
            {{ $experiments_nucleic->withPath($paginationPath ?? url()->current())->withQueryString()->onEachSide(1)->links() }}
        </div>
    @endif
</div>