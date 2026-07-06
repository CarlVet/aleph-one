@props(['rows' => []])

<div class="min-w-[360px] overflow-x-auto rounded-lg border border-gray-200 bg-white" data-skip-dashboard-modal-enhance="1">
    <table class="min-w-full text-xs" data-skip-dashboard-modal-enhance="1">
        <thead>
            <tr class="bg-gray-50">
                @foreach ($rows as $row)
                    <th class="whitespace-nowrap px-2 py-1 text-left font-semibold text-gray-600">{{ $row['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach ($rows as $row)
                    <td class="whitespace-nowrap px-2 py-1.5 text-gray-800">{{ $row['value'] }}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
</div>
