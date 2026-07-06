<div class="overflow-x-auto">
    <table class="min-w-full table-auto">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site Name</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Country</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Organization</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Site Type</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Samples</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Animals</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach ($sites as $site)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $site->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $site->country_name ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $site->organization_name ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $site->site_type ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $site->animal_samples_count ?? 0 }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500">{{ $site->animals_count ?? 0 }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div class="border-t border-gray-200 bg-white px-4 py-3">
    {{ $sites->onEachSide(1)->withPath($paginationPath ?? request()->url())->links() }}
</div>

