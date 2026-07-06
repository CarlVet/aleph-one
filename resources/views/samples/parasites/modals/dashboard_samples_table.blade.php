<div class="overflow-x-auto">
    <table class="min-w-full table-auto">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sample Code</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Origin Type</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parasite Species</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stage</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sex</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">State</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sampling Site</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ethnicity</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Occupation</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal Species</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal Sex</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal Age</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Identified By</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Identified</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($samples as $sample)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                        <a href="/samples/parasites/{{ $sample->code }}" class="text-blue-600 hover:text-blue-800">
                            {{ $sample->code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">
                        {{ match ($sample->parasites_origin_type) {
                            \App\Models\HumanSamples::class => 'Human samples',
                            \App\Models\AnimalSamples::class => 'Animal samples',
                            \App\Models\EnvironmentSamples::class => 'Environmental samples',
                            default => 'N/A',
                        } }}
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">
                        {{ $sample->human_country ?? $sample->animal_country ?? $sample->environment_country ?? 'N/A' }}
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500 italic">{{ $sample->parasite_species ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->stage ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->sex ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->state ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->sampling_site_name ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->human_ethnicity ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->human_occupation ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->animal_species ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->animal_sex ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->animal_age ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ trim((string) $sample->identified_by) ?: 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">
                        {{ $sample->date_identified ? \Carbon\Carbon::parse($sample->date_identified)->format('Y-m-d') : 'N/A' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if (method_exists($samples, 'links'))
    <div class="pt-4">
        {{ $samples->withPath($paginationPath)->withQueryString()->onEachSide(1)->links() }}
    </div>
@endif

