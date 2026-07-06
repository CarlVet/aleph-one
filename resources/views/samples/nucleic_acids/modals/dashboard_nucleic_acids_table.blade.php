<div class="overflow-x-auto">
    @php
        $modalSourceKey = $modalSourceKey ?? 'all';
        $showSourceTypeColumn = in_array($modalSourceKey, ['all', 'parasite', 'culture'], true);

        $normalizeSourceTypeLabel = static function (?string $rawType): string {
            $rawType = (string) ($rawType ?? '');
            if ($rawType === '') {
                return 'N/A';
            }

            $base = class_basename(ltrim($rawType, '\\'));
            if (str_starts_with($base, 'AppModels')) {
                $base = substr($base, strlen('AppModels'));
            }

            return match ($base) {
                'HumanSamples' => 'Human',
                'AnimalSamples' => 'Animal',
                'EnvironmentSamples' => 'Environment',
                'ParasiteSamples' => 'Parasite',
                'Cultures' => 'Culture',
                'Pools' => 'Pool',
                'NucleicAcids' => 'Nucleic acids',
                default => $base !== '' ? $base : 'N/A',
            };
        };
    @endphp
    <table class="min-w-full table-auto">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sample Code</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nucleic Type</th>
                @if ($showSourceTypeColumn)
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Source Type</th>
                @endif
                @if ($modalSourceKey === 'human')
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sampling Site</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sex</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ethnicity</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Occupation</th>
                @endif
                @if ($modalSourceKey === 'animal')
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sampling Site</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal Species</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sex</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                @endif
                @if ($modalSourceKey === 'environment')
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sampling Site</th>
                @endif
                @if ($modalSourceKey === 'parasite')
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parasite Species</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parasite Stage</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parasite Sex</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parasite Sample Type</th>
                @endif
                @if ($modalSourceKey === 'culture')
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Culture Type</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Culture Medium</th>
                @endif
                @if ($modalSourceKey === 'pool')
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pool Size</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pool Content Type</th>
                @endif
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocol</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laboratory</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Extracted By</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Extracted</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($samples as $sample)
                @php
                    $sourceBase = class_basename((string) ($sample->nucleic_content_type ?? ''));
                    $sourceLabel = match ($sourceBase) {
                        'HumanSamples', 'AppModelsHumanSamples' => 'Human',
                        'AnimalSamples', 'AppModelsAnimalSamples' => 'Animal',
                        'EnvironmentSamples', 'AppModelsEnvironmentSamples' => 'Environment',
                        'ParasiteSamples', 'AppModelsParasiteSamples' => 'Parasite',
                        'Cultures', 'AppModelsCultures' => 'Culture',
                        'Pools', 'AppModelsPools' => 'Pool',
                        default => $sourceBase !== '' ? $sourceBase : 'N/A',
                    };

                    $sourceTypeValue = match ($modalSourceKey) {
                        'parasite' => $normalizeSourceTypeLabel($sample->parasite_content_type ?? null),
                        'culture' => $normalizeSourceTypeLabel($sample->culture_content_type ?? null),
                        'pool' => collect(explode(',', (string) ($sample->pool_content_type ?? '')))
                            ->map(fn ($value) => trim((string) $value))
                            ->filter(fn ($value) => $value !== '')
                            ->map(fn ($value) => $normalizeSourceTypeLabel($value))
                            ->unique()
                            ->values()
                            ->implode(', '),
                        default => $sourceLabel,
                    };
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                        <a href="/samples/nucleic/{{ $sample->code }}" class="text-blue-600 hover:text-blue-800">
                            {{ $sample->code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->type ?? 'N/A' }}</td>
                    @if ($showSourceTypeColumn)
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sourceTypeValue !== '' ? $sourceTypeValue : 'N/A' }}</td>
                    @endif
                    @if ($modalSourceKey === 'human')
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->human_country ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->human_sampling_site ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->human_sex ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->human_ethnicity ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->human_occupation ?? 'N/A' }}</td>
                    @endif
                    @if ($modalSourceKey === 'animal')
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->animal_country ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->animal_sampling_site ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->animal_species ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->animal_sex ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->animal_age ?? 'N/A' }}</td>
                    @endif
                    @if ($modalSourceKey === 'environment')
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->environment_country ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->environment_sampling_site ?? 'N/A' }}</td>
                    @endif
                    @if ($modalSourceKey === 'parasite')
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->parasite_species ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->parasite_stage ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->parasite_sex ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->parasite_sample_type ?? 'N/A' }}</td>
                    @endif
                    @if ($modalSourceKey === 'culture')
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->culture_type ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->culture_medium ?? 'N/A' }}</td>
                    @endif
                    @if ($modalSourceKey === 'pool')
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->pool_nr_pooled ?? 'N/A' }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500">{{ $sourceTypeValue !== '' ? $sourceTypeValue : 'N/A' }}</td>
                    @endif
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->protocol ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->laboratory ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->extracted_by ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">
                        {{ $sample->date_extracted ? \Carbon\Carbon::parse($sample->date_extracted)->format('Y-m-d') : 'N/A' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="border-t border-gray-200 bg-white px-4 py-3">
    {{ $samples->onEachSide(1)->withPath($paginationPath ?? request()->url())->links() }}
</div>

