<div class="overflow-x-auto">
    <table class="min-w-full table-auto">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sample Code</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal Code</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Species</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sample Type</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Collected</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processing status</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($samples as $sample)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                        <a href="/samples/animals/{{ $sample->code }}" class="text-blue-600 hover:text-blue-800">
                            {{ $sample->code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">
                        <a href="/animals/{{ $sample->animal_code }}" class="text-blue-600 hover:text-blue-800">
                            {{ $sample->animal_code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->species_name_common ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->sample_type ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">
                        {{ $sample->date_collected ? \Carbon\Carbon::parse($sample->date_collected)->format('Y-m-d') : 'N/A' }}
                    </td>
                    <td class="px-4 py-2 text-sm">
                        @if ($sample->processed)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                <i class="fas fa-check mr-1"></i>Processed
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="fas fa-clock mr-1"></i>Pending
                            </span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="border-t border-gray-200 bg-white px-4 py-3">
    {{ $samples->onEachSide(1)->withPath($paginationPath ?? request()->url())->links() }}
</div>

