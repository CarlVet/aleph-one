<div class="overflow-x-auto">
    <table class="min-w-full table-auto">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sequence Code</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Accession</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nucleic Acid</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Origin</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sub-project</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Length</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Method</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Instrument</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sequenced By</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laboratory</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Sequenced</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($samples as $sample)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                        <a href="/samples/nucleic/sequences/{{ $sample->code }}" class="text-blue-600 hover:text-blue-800">
                            {{ $sample->code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->accession_number ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                        @if ($sample->nucleic_code)
                            <a href="/samples/nucleic/{{ $sample->nucleic_code }}" class="text-blue-600 hover:text-blue-800">
                                {{ $sample->nucleic_code }}
                            </a>
                        @else
                            <span class="text-gray-500">N/A</span>
                        @endif
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">
                        {{ $sample->nucleic_content_type ? class_basename($sample->nucleic_content_type) : 'N/A' }}
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->sub_project_code ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->length ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->method ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->instrument ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->sequenced_by ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $sample->laboratory ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">
                        {{ $sample->date_sequenced ? \Carbon\Carbon::parse($sample->date_sequenced)->format('Y-m-d') : 'N/A' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="border-t border-gray-200 bg-white px-4 py-3">
    {{ $samples->onEachSide(1)->withPath($paginationPath ?? request()->url())->links() }}
</div>

