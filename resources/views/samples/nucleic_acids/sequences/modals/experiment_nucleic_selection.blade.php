<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-indigo-50 to-purple-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Nucleic Acid Tubes Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select the nucleic acid tubes for this experiment</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-indigo-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-indigo-600">0</span> nucleic acid tubes selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="nucleic_tubes_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button id="confirm_na_tube_selection"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200 transform hover:scale-105">
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
        <table id="nucleic_tubes_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="select_all_na_tubes"
                                class="h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_na_tubes"
                                class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-indigo-600 transition-colors">
                                Select All
                            </label>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Tube Code</span>
                            <svg class="w-4 h-4 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Alias Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Extraction Protocol</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date Extracted</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Volume</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content Details</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Experiment Protocol</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Target Pathogen</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($nucleic_experiment_tubes as $tube)
                    @php
                        $derivedNucleic = $tube->tubes_content;
                        $experiment = ($derivedNucleic instanceof \App\Models\NucleicAcids && $derivedNucleic->relationLoaded('nucleic_content'))
                            ? $derivedNucleic->getRelation('nucleic_content')
                            : null;
                        $originalNucleic = ($experiment instanceof \App\Models\Experiments && $experiment->relationLoaded('experiments_content') && $experiment->experiments_content instanceof \App\Models\NucleicAcids)
                            ? $experiment->experiments_content
                            : null;
                        $source = ($originalNucleic instanceof \App\Models\NucleicAcids && $originalNucleic->relationLoaded('nucleic_content'))
                            ? $originalNucleic->getRelation('nucleic_content')
                            : null;
                        $sourceType = $source ? get_class($source) : null;
                    @endphp
                    <tr class="hover:bg-indigo-50 transition-all duration-200 ease-in-out group">
                        <td class="sticky left-0 z-10 bg-white group-hover:bg-indigo-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox" class="select-na-tube h-5 w-5 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded transition-all duration-200 hover:scale-110" value="{{ $tube->id }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/bank/tubes/{{ $tube->code }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                {{ $tube->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->alias_code ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->type ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->protocols?->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">
                                {{ $tube->tubes_content?->date_extracted ? \Carbon\Carbon::parse($tube->tubes_content->date_extracted)->format('Y-m-d') : 'N/A' }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->volume ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($sourceType === \App\Models\HumanSamples::class)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">Human Sample</span>
                            @elseif($sourceType === \App\Models\AnimalSamples::class)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Animal Sample</span>
                            @elseif($sourceType === \App\Models\EnvironmentSamples::class)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">Environmental Sample</span>
                            @elseif($sourceType === \App\Models\ParasiteSamples::class)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Parasite Sample</span>
                            @elseif($sourceType === \App\Models\Cultures::class)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Culture</span>
                            @elseif($sourceType === \App\Models\Pools::class)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">Pool</span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $aliasCodes = collect(data_get($source, 'tubes', []))
                                    ->pluck('alias_code')
                                    ->merge(collect(data_get($originalNucleic, 'tubes', []))->pluck('alias_code'))
                                    ->merge(
                                        $sourceType === \App\Models\Pools::class
                                            ? collect(data_get($source, 'pool_contents', []))
                                                ->flatMap(fn ($poolContent) => collect(data_get($poolContent, 'samples.tubes', []))->pluck('alias_code'))
                                            : collect()
                                    )
                                    ->map(fn ($alias) => is_string($alias) ? trim($alias) : '')
                                    ->filter(fn ($alias) => $alias !== '')
                                    ->unique()
                                    ->values()
                                    ->all();
                                $aliasCodesLabel = !empty($aliasCodes) ? implode(', ', $aliasCodes) : 'N/A';
                                $detailsRows = match ($sourceType) {
                                    \App\Models\HumanSamples::class => [
                                        ['label' => 'Sample code', 'value' => data_get($source, 'code') ?? 'N/A'],
                                        ['label' => 'Sample type', 'value' => data_get($source, 'sample_types.name') ?? 'N/A'],
                                        ['label' => 'Sampling site', 'value' => data_get($source, 'sampling_sites.name') ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    \App\Models\AnimalSamples::class => [
                                        ['label' => 'Sample code', 'value' => data_get($source, 'code') ?? 'N/A'],
                                        ['label' => 'Species', 'value' => data_get($source, 'animals.animal_species.name_common') ?? data_get($source, 'animals.animal_species.name_scientific') ?? 'N/A'],
                                        ['label' => 'Sample type', 'value' => data_get($source, 'sample_types.name') ?? 'N/A'],
                                        ['label' => 'Sampling site', 'value' => data_get($source, 'sampling_sites.name') ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    \App\Models\EnvironmentSamples::class => [
                                        ['label' => 'Sample code', 'value' => data_get($source, 'code') ?? 'N/A'],
                                        ['label' => 'Env type', 'value' => data_get($source, 'environment_sample_types.name') ?? 'N/A'],
                                        ['label' => 'Area', 'value' => data_get($source, 'area') ?? 'N/A'],
                                        ['label' => 'Sampling site', 'value' => data_get($source, 'sampling_sites.name') ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    \App\Models\ParasiteSamples::class => [
                                        ['label' => 'Sample code', 'value' => data_get($source, 'code') ?? 'N/A'],
                                        ['label' => 'Species', 'value' => data_get($source, 'parasites.parasite_species.name_scientific') ?? 'N/A'],
                                        ['label' => 'Sample type', 'value' => data_get($source, 'parasite_sample_types.name') ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    \App\Models\Cultures::class => [
                                        ['label' => 'Culture code', 'value' => data_get($source, 'code') ?? 'N/A'],
                                        ['label' => 'Medium', 'value' => data_get($source, 'medium') ?? 'N/A'],
                                        ['label' => 'Step', 'value' => data_get($source, 'step') ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    \App\Models\Pools::class => [
                                        ['label' => 'Pool code', 'value' => data_get($source, 'code') ?? 'N/A'],
                                        ['label' => 'Nr pooled', 'value' => data_get($source, 'nr_pooled') ?? 'N/A'],
                                        ['label' => 'Date pooled', 'value' => data_get($source, 'date_pooled') ? \Carbon\Carbon::parse(data_get($source, 'date_pooled'))->format('Y-m-d') : 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                    default => [
                                        ['label' => 'Details', 'value' => 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $aliasCodesLabel],
                                    ],
                                };
                                $poolContentRows = $sourceType === \App\Models\Pools::class
                                    ? collect(data_get($source, 'pool_contents', []))->map(fn ($poolContent) => [
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
                            @if($sourceType === \App\Models\HumanSamples::class)
                                <a href="/samples/humans/{{ $source->code }}" class="text-sm font-medium text-pink-700 hover:text-pink-900 hover:underline">
                                    {{ $source->code }}
                                </a>
                            @elseif($sourceType === \App\Models\AnimalSamples::class)
                                <a href="/samples/animals/{{ $source->code }}" class="text-sm font-medium text-yellow-700 hover:text-yellow-900 hover:underline">
                                    {{ $source->code }}
                                </a>
                            @elseif($sourceType === \App\Models\EnvironmentSamples::class)
                                <a href="/samples/environment/{{ $source->code }}" class="text-sm font-medium text-green-700 hover:text-green-900 hover:underline">
                                    {{ $source->code }}
                                </a>
                            @elseif($sourceType === \App\Models\ParasiteSamples::class)
                                <a href="/samples/parasites/{{ $source->code }}" class="text-sm font-medium text-purple-700 hover:text-purple-900 hover:underline">
                                    {{ $source->code }}
                                </a>
                            @elseif($sourceType === \App\Models\Cultures::class)
                                <a href="/samples/cultures/{{ $source->code }}" class="text-sm font-medium text-orange-700 hover:text-orange-900 hover:underline">
                                    {{ $source->code }}
                                </a>
                            @elseif($sourceType === \App\Models\Pools::class)
                                <a href="/samples/pools/{{ $source->code }}" class="text-sm font-medium text-cyan-700 hover:text-cyan-900 hover:underline">
                                    {{ $source->code }}
                                </a>
                            @else
                                <span class="text-sm text-gray-900">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $experiment?->protocols?->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $experiment?->pathogens?->species ?? 'N/A' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($nucleic_experiment_tubes, 'links'))
        <div class="px-6 py-4 border-t border-gray-200 bg-white">
            {{ $nucleic_experiment_tubes->withPath($paginationPath ?? url()->current())->withQueryString()->onEachSide(1)->links() }}
        </div>
    @endif
</div>
