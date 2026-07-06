<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-blue-50 to-blue-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Nucleic Acid Tubes Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select the nucleic acid tubes for this experiment</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-blue-600">0</span> nucleic acid tubes selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="nucleic_tubes_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button type="button" id="confirm_na_tube_selection"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-blue-600 to-blue-600 hover:from-blue-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 transform hover:scale-105">
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
                            <input type="checkbox" id="select_all_na_tubes" class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_na_tubes" class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-blue-600 transition-colors">
                                Select All
                            </label>
                        </div>
                    </th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tube code</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Alias code</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Preservant</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Extraction protocol</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date extracted</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Volume</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content type</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content details</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content code</th>
                    <th class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Purpose</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($nucleic_tubes as $tube)
                    <tr class="hover:bg-blue-50 transition-all duration-200 ease-in-out group" wire:key="{{ $tube->id }}">
                        <td class="sticky left-0 z-10 bg-white group-hover:bg-blue-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox" class="select-na-tube h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-all duration-200 hover:scale-110" value="{{ $tube->id }}" data-sample-type-label="{{ $tube->tube_sample_type_label ?? '' }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/bank/tubes/{{ $tube->code }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                {{ $tube->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->alias_code ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->preservant ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->type ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->protocols->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($tube->tubes_content->date_extracted)->format('Y-m-d') ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->volume ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if(optional($tube->tubes_content)->nucleic_content_type === 'App\\Models\\HumanSamples')
                                <span class="text-sm text-gray-900">Human Sample</span>
                            @elseif(optional($tube->tubes_content)->nucleic_content_type === 'App\\Models\\AnimalSamples')
                                <span class="text-sm text-gray-900">Animal Sample</span>
                            @elseif(optional($tube->tubes_content)->nucleic_content_type === 'App\\Models\\EnvironmentSamples')
                                <span class="text-sm text-gray-900">Environmental Sample</span>
                            @elseif(optional($tube->tubes_content)->nucleic_content_type === 'App\\Models\\Experiments')
                                <span class="text-sm text-gray-900">Experiments</span>
                            @elseif (optional($tube->tubes_content)->nucleic_content_type === 'App\\Models\\ParasiteSamples')
                                <span class="text-sm text-gray-900">Parasite Sample</span>
                            @elseif(optional($tube->tubes_content)->nucleic_content_type === 'App\\Models\\Cultures')
                                <span class="text-sm text-gray-900">Cultures</span>
                            @elseif(optional($tube->tubes_content)->nucleic_content_type === 'App\\Models\\Pools')
                                <span class="text-sm text-gray-900">Pools</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $tubeContent = $tube->relationLoaded('tubes_content') ? $tube->getRelation('tubes_content') : null;
                                $directContent = ($tubeContent instanceof \App\Models\NucleicAcids && $tubeContent->relationLoaded('nucleic_content'))
                                    ? $tubeContent->getRelation('nucleic_content')
                                    : null;
                                $directContentType = $directContent ? get_class($directContent) : null;
                                $experiment = $directContent instanceof \App\Models\Experiments ? $directContent : null;
                                $experimentSourceNucleic = ($experiment instanceof \App\Models\Experiments && $experiment->relationLoaded('experiments_content') && $experiment->getRelation('experiments_content') instanceof \App\Models\NucleicAcids)
                                    ? $experiment->getRelation('experiments_content')
                                    : null;
                                $source = ($experimentSourceNucleic instanceof \App\Models\NucleicAcids && $experimentSourceNucleic->relationLoaded('nucleic_content'))
                                    ? $experimentSourceNucleic->getRelation('nucleic_content')
                                    : $directContent;
                                $sourceType = $source ? get_class($source) : null;
                                $sourceTubes = ($source && method_exists($source, 'relationLoaded') && $source->relationLoaded('tubes'))
                                    ? collect($source->getRelation('tubes'))
                                    : collect();
                                $experimentSourceTubes = ($experimentSourceNucleic instanceof \App\Models\NucleicAcids && $experimentSourceNucleic->relationLoaded('tubes'))
                                    ? collect($experimentSourceNucleic->getRelation('tubes'))
                                    : collect();
                                $poolContents = ($sourceType === \App\Models\Pools::class && $source instanceof \App\Models\Pools && $source->relationLoaded('pool_contents'))
                                    ? collect($source->getRelation('pool_contents'))
                                    : collect();
                                $sourceAliasCodes = $sourceTubes
                                    ->pluck('alias_code')
                                    ->merge($experimentSourceTubes->pluck('alias_code'))
                                    ->merge(
                                        $sourceType === \App\Models\Pools::class
                                            ? $poolContents->flatMap(function ($poolContent) {
                                                if (! $poolContent || ! method_exists($poolContent, 'relationLoaded') || ! $poolContent->relationLoaded('samples')) {
                                                    return collect();
                                                }

                                                $poolSample = $poolContent->getRelation('samples');
                                                if (! $poolSample || ! method_exists($poolSample, 'relationLoaded') || ! $poolSample->relationLoaded('tubes')) {
                                                    return collect();
                                                }

                                                return collect($poolSample->getRelation('tubes'))->pluck('alias_code');
                                            })
                                            : collect()
                                    )
                                    ->map(fn ($alias) => is_string($alias) ? trim($alias) : '')
                                    ->filter(fn ($alias) => $alias !== '')
                                    ->unique()
                                    ->values()
                                    ->all();
                                $sourceSampleType = ($sourceType === \App\Models\HumanSamples::class && $source instanceof \App\Models\HumanSamples && $source->relationLoaded('sample_types'))
                                    ? optional($source->getRelation('sample_types'))->name
                                    : (($sourceType === \App\Models\AnimalSamples::class && $source instanceof \App\Models\AnimalSamples && $source->relationLoaded('sample_types'))
                                        ? optional($source->getRelation('sample_types'))->name
                                        : (($sourceType === \App\Models\ParasiteSamples::class && $source instanceof \App\Models\ParasiteSamples && $source->relationLoaded('parasite_sample_types'))
                                            ? optional($source->getRelation('parasite_sample_types'))->name
                                            : 'N/A'));
                                $sourceSamplingSite = ($sourceType === \App\Models\HumanSamples::class && $source instanceof \App\Models\HumanSamples && $source->relationLoaded('sampling_sites'))
                                    ? optional($source->getRelation('sampling_sites'))->name
                                    : (($sourceType === \App\Models\AnimalSamples::class && $source instanceof \App\Models\AnimalSamples && $source->relationLoaded('sampling_sites'))
                                        ? optional($source->getRelation('sampling_sites'))->name
                                        : 'N/A');
                                $sourceSpecies = ($sourceType === \App\Models\AnimalSamples::class && $source instanceof \App\Models\AnimalSamples && $source->relationLoaded('animals'))
                                    ? optional(optional($source->getRelation('animals'))->relationLoaded('animal_species') ? $source->getRelation('animals')->getRelation('animal_species') : null)->name_common
                                    : (($sourceType === \App\Models\ParasiteSamples::class && $source instanceof \App\Models\ParasiteSamples && $source->relationLoaded('parasites'))
                                        ? optional(optional($source->getRelation('parasites'))->relationLoaded('parasite_species') ? $source->getRelation('parasites')->getRelation('parasite_species') : null)->name_scientific
                                        : 'N/A');
                                $sourceEnvironmentType = ($sourceType === \App\Models\EnvironmentSamples::class && $source instanceof \App\Models\EnvironmentSamples && $source->relationLoaded('environment_sample_types'))
                                    ? optional($source->getRelation('environment_sample_types'))->name
                                    : 'N/A';
                                $experimentProtocolName = ($experiment instanceof \App\Models\Experiments && $experiment->relationLoaded('protocols'))
                                    ? optional($experiment->getRelation('protocols'))->name
                                    : 'N/A';
                                $experimentPathogenName = ($experiment instanceof \App\Models\Experiments && $experiment->relationLoaded('pathogens'))
                                    ? optional($experiment->getRelation('pathogens'))->species
                                    : 'N/A';
                                $sourceExperimentProtocolName = ($sourceType === \App\Models\Experiments::class && $source instanceof \App\Models\Experiments && $source->relationLoaded('protocols'))
                                    ? optional($source->getRelation('protocols'))->name
                                    : 'N/A';
                                $sourceExperimentPathogenName = ($sourceType === \App\Models\Experiments::class && $source instanceof \App\Models\Experiments && $source->relationLoaded('pathogens'))
                                    ? optional($source->getRelation('pathogens'))->species
                                    : 'N/A';
                                $sourceCultureContentType = ($sourceType === \App\Models\Cultures::class && $source instanceof \App\Models\Cultures && $source->relationLoaded('cultures_content'))
                                    ? class_basename((string) $source->cultures_content_type)
                                    : 'N/A';
                                $sourceCultureContentCode = ($sourceType === \App\Models\Cultures::class && $source instanceof \App\Models\Cultures && $source->relationLoaded('cultures_content'))
                                    ? (optional($source->getRelation('cultures_content'))->code ?? 'N/A')
                                    : 'N/A';
                                $sourceAliasCodesLabel = !empty($sourceAliasCodes) ? implode(', ', $sourceAliasCodes) : 'N/A';
                                $tracebackRows = match ($sourceType) {
                                    \App\Models\HumanSamples::class => [
                                        ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                                        ['label' => 'Sample type', 'value' => $sourceSampleType ?: 'N/A'],
                                        ['label' => 'Sampling site', 'value' => $sourceSamplingSite ?: 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
                                    ],
                                    \App\Models\AnimalSamples::class => [
                                        ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                                        ['label' => 'Species', 'value' => $sourceSpecies ?: 'N/A'],
                                        ['label' => 'Sample type', 'value' => $sourceSampleType ?: 'N/A'],
                                        ['label' => 'Sampling site', 'value' => $sourceSamplingSite ?: 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
                                    ],
                                    \App\Models\EnvironmentSamples::class => [
                                        ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                                        ['label' => 'Env type', 'value' => $sourceEnvironmentType ?: 'N/A'],
                                        ['label' => 'Area', 'value' => $source->area ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
                                    ],
                                    \App\Models\ParasiteSamples::class => [
                                        ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                                        ['label' => 'Species', 'value' => $sourceSpecies ?: 'N/A'],
                                        ['label' => 'Sample type', 'value' => $sourceSampleType ?: 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
                                    ],
                                    \App\Models\Cultures::class => [
                                        ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                                        ['label' => 'Content type', 'value' => $sourceCultureContentType ?: 'N/A'],
                                        ['label' => 'Content code', 'value' => $sourceCultureContentCode ?: 'N/A'],
                                        ['label' => 'Medium', 'value' => $source->medium ?? 'N/A'],
                                        ['label' => 'Step', 'value' => $source->step ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
                                    ],
                                    \App\Models\Pools::class => [
                                        ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                                        ['label' => 'Nr pooled', 'value' => $source->nr_pooled ?? 'N/A'],
                                        ['label' => 'Date pooled', 'value' => $source->date_pooled ? \Carbon\Carbon::parse($source->date_pooled)->format('Y-m-d') : 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
                                    ],
                                    \App\Models\NucleicAcids::class => [
                                        ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                                        ['label' => 'NA type', 'value' => $source->type ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
                                    ],
                                    \App\Models\Experiments::class => [
                                        ['label' => 'Source code', 'value' => $source->code ?? 'N/A'],
                                        ['label' => 'Protocol', 'value' => $sourceExperimentProtocolName ?: 'N/A'],
                                        ['label' => 'Pathogen', 'value' => $sourceExperimentPathogenName ?: 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
                                    ],
                                    default => [
                                        ['label' => 'Source code', 'value' => $directContent->code ?? 'N/A'],
                                        ['label' => 'Tube alias', 'value' => $sourceAliasCodesLabel],
                                    ],
                                };
                                $poolContentRows = $sourceType === \App\Models\Pools::class
                                    ? $poolContents->map(fn ($poolContent) => [
                                        'type' => class_basename((string) ($poolContent->samples_type ?? '')),
                                        'code' => ($poolContent && method_exists($poolContent, 'relationLoaded') && $poolContent->relationLoaded('samples'))
                                            ? ($poolContent->getRelation('samples')->code ?? 'N/A')
                                            : 'N/A',
                                    ])->all()
                                    : [];
                                if ($experiment instanceof \App\Models\Experiments) {
                                    array_unshift(
                                        $tracebackRows,
                                        ['label' => 'Experiment code', 'value' => $experiment->code ?? 'N/A'],
                                        ['label' => 'Exp. protocol', 'value' => $experimentProtocolName ?: 'N/A'],
                                        ['label' => 'Target pathogen', 'value' => $experimentPathogenName ?: 'N/A'],
                                        ['label' => 'Input nucleic', 'value' => data_get($experimentSourceNucleic, 'code') ?? 'N/A']
                                    );
                                }
                            @endphp
                            <div class="min-w-[420px] space-y-2">
                                <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                                    <table class="min-w-full text-xs">
                                        <thead>
                                            <tr class="bg-gray-50">
                                                @foreach ($tracebackRows as $tracebackRow)
                                                    <th class="whitespace-nowrap px-2 py-1 text-left font-semibold text-gray-600">{{ $tracebackRow['label'] }}</th>
                                                @endforeach
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                @foreach ($tracebackRows as $tracebackRow)
                                                    <td class="whitespace-nowrap px-2 py-1.5 text-gray-800">{{ $tracebackRow['value'] }}</td>
                                                @endforeach
                                            </tr>
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
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if($directContentType === \App\Models\HumanSamples::class)
                                <a href="/samples/humans/{{ $directContent->code ?? '' }}" class="text-sm font-medium text-pink-600 hover:text-pink-800 hover:underline">
                                    {{ $directContent->code ?? 'N/A' }}
                                </a>
                            @elseif($directContentType === \App\Models\AnimalSamples::class)
                                <a href="/samples/animals/{{ $directContent->code ?? '' }}" class="text-sm font-medium text-yellow-600 hover:text-yellow-800 hover:underline">
                                    {{ $directContent->code ?? 'N/A' }}
                                </a>
                            @elseif($directContentType === \App\Models\EnvironmentSamples::class)
                                <a href="/samples/environment/{{ $directContent->code ?? '' }}" class="text-sm font-medium text-green-600 hover:text-green-800 hover:underline">
                                    {{ $directContent->code ?? 'N/A' }}
                                </a>
                            @elseif($directContentType === \App\Models\Experiments::class)
                                <a href="/experiments/{{ $directContent->code ?? '' }}" class="text-sm font-medium text-blue-800 hover:text-blue-900 hover:underline">
                                    {{ $directContent->code ?? 'N/A' }}
                                </a>
                            @elseif ($directContentType === \App\Models\ParasiteSamples::class)
                                <a href="/samples/parasites/{{ $directContent->code ?? '' }}" class="text-sm font-medium text-purple-600 hover:text-purple-800 hover:underline">
                                    {{ $directContent->code ?? 'N/A' }}
                                </a>
                            @elseif($directContentType === \App\Models\Cultures::class)
                                <a href="/samples/cultures/{{ $directContent->code ?? '' }}" class="text-sm font-medium text-orange-600 hover:text-orange-800 hover:underline">
                                    {{ $directContent->code ?? 'N/A' }}
                                </a>
                            @elseif($directContentType === \App\Models\Pools::class)
                                <a href="/samples/pools/{{ $directContent->code ?? '' }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-800 hover:underline">
                                    {{ $directContent->code ?? 'N/A' }}
                                </a>
                            @elseif($directContentType === \App\Models\NucleicAcids::class)
                                <a href="/samples/nucleic/{{ $directContent->code ?? '' }}" class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ $directContent->code ?? 'N/A' }}
                                </a>
                            @else
                                <span class="text-sm text-gray-700">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->purpose ?? 'N/A' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($nucleic_tubes, 'links'))
        <div class="px-6 py-4 border-t border-gray-200 bg-white">
            {{ $nucleic_tubes->withPath($paginationPath ?? route('experiments.create.tubes.nucleic'))->withQueryString()->onEachSide(1)->links() }}
        </div>
    @endif
</div>
