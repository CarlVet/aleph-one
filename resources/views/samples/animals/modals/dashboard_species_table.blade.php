<div class="overflow-x-auto">
    <table class="min-w-full table-auto">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Common Name</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Scientific Name</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Family</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animal samples</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animals</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($species as $row)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $row->name_common ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{!! $row->name_scientific ? '<i>' . e($row->name_scientific) . '</i>' : 'N/A' !!}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $row->family ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $row->animal_samples_count ?? 0 }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $row->animals_count ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="border-t border-gray-200 bg-white px-4 py-3">
    {{ $species->onEachSide(1)->withPath($paginationPath ?? request()->url())->links() }}
</div>

