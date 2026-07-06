<div class="overflow-x-auto">
    <table class="min-w-full table-auto">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Species</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sex</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Samples</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($animals as $animal)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">
                        <a href="/animals/{{ $animal->code }}" class="text-blue-600 hover:text-blue-800">
                            {{ $animal->code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $animal->species_name_common ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $animal->sex ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $animal->age ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $animal->animal_samples_count ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="border-t border-gray-200 bg-white px-4 py-3">
    {{ $animals->onEachSide(1)->withPath($paginationPath ?? request()->url())->links() }}
</div>

