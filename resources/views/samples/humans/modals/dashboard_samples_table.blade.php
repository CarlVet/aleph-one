<div class="overflow-x-auto">
    <table class="min-w-full table-auto">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sample Code</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sample Type</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sampling Site</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ethnicity</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Occupation</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Collected</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($samples as $sample)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                        <a href="/samples/humans/{{ $sample->code }}" class="text-blue-600 hover:text-blue-800">
                            {{ $sample->code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->sample_type ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->sampling_site ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->ethnicity ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->occupation ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->country ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">
                        {{ $sample->date_collected ? \Carbon\Carbon::parse($sample->date_collected)->format('Y-m-d') : 'N/A' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="border-t border-gray-200 bg-white px-4 py-3">
    {{ $samples->onEachSide(1)->withPath($paginationPath ?? request()->url())->links() }}
</div>

