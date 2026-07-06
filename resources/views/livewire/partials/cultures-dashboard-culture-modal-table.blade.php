@php
    use App\Support\CultureContentDetailsPresenter;
@endphp

@props([
    'cultures',
    'tableId',
    'emptyMessage' => 'No cultures in the filtered dataset.',
    'showDuration' => false,
])

<div class="overflow-x-auto" data-skip-dashboard-modal-enhance="1">
    <table id="{{ $tableId }}" class="min-w-full table-auto cultures-dashboard-modal-table" data-dashboard-modal-table="1" data-skip-dashboard-modal-enhance="1">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Culture Code</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Culture Type</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content Type</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider min-w-[380px]">Content Details</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content Code</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Parent Code</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medium</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Laboratory</th>
                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Cultured</th>
                @if ($showDuration)
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">End Date</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Days</th>
                @endif
            </tr>
            <tr data-column-filters="1">
                <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="0" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="1" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="2" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="3" placeholder="Filter content details…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="4" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="5" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="6" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="7" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="8" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                @if ($showDuration)
                    <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="9" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                    <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="10" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                    <th class="px-2 py-2 bg-gray-50"><input type="text" data-filter-col="11" placeholder="Filter…" class="w-full rounded border border-gray-200 px-2 py-1 text-xs"></th>
                @endif
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @forelse ($cultures as $culture)
                @php
                    $contentCode = CultureContentDetailsPresenter::contentCode($culture);
                    $contentHref = CultureContentDetailsPresenter::contentHref($culture);
                    $contentDetailsRows = CultureContentDetailsPresenter::rows($culture);
                    $durationEnd = $culture->is_discarded && $culture->date_discarded
                        ? \Carbon\Carbon::parse($culture->date_discarded)->format('Y-m-d')
                        : 'Today';
                    $durationStatus = $culture->is_discarded ? 'Discarded' : 'Active';
                    $durationDays = CultureContentDetailsPresenter::daysOnCulture($culture);
                    $rowSearchText = CultureContentDetailsPresenter::searchText($culture);
                    $contentDetailsSearchText = CultureContentDetailsPresenter::contentDetailsSearchText($culture);
                    if ($showDuration) {
                        $rowSearchText .= ' '.strtolower(implode(' ', [$durationEnd, $durationStatus, $durationDays !== null ? number_format($durationDays, 1, '.', '') : '']));
                    }
                @endphp
                <tr class="hover:bg-gray-50" data-culture-search="{{ $rowSearchText }}">
                    <td class="px-4 py-2 text-sm font-medium text-gray-900" data-col="0">
                        <a href="/samples/cultures/{{ $culture->code }}" class="text-blue-600 hover:text-blue-800">
                            {{ $culture->code ?? 'N/A' }}
                        </a>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500" data-col="1">{{ $culture->type ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500" data-col="2">{{ CultureContentDetailsPresenter::contentTypeLabel($culture) }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500" data-col="3" data-content-details-search="{{ $contentDetailsSearchText }}">
                        <x-cultures.content-details-subtable :rows="$contentDetailsRows" />
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500" data-col="4">
                        @if ($contentCode && $contentHref)
                            <a href="{{ $contentHref }}" class="text-blue-600 hover:text-blue-800">{{ $contentCode }}</a>
                        @else
                            {{ $contentCode ?? 'N/A' }}
                        @endif
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-500" data-col="5">{{ data_get($culture, 'parent.code') ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500" data-col="6">{{ $culture->medium ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500" data-col="7">{{ $culture->laboratories->name ?? 'N/A' }}</td>
                    <td class="px-4 py-2 text-sm text-gray-500" data-col="8">
                        {{ $culture->date_cultured ? \Carbon\Carbon::parse($culture->date_cultured)->format('Y-m-d') : 'N/A' }}
                    </td>
                    @if ($showDuration)
                        <td class="px-4 py-2 text-sm text-gray-500" data-col="9">{{ $durationEnd }}</td>
                        <td class="px-4 py-2 text-sm text-gray-500" data-col="10">{{ $durationStatus }}</td>
                        <td class="px-4 py-2 text-sm font-semibold text-gray-900" data-col="11">{{ $durationDays !== null ? number_format($durationDays, 1) : 'N/A' }}</td>
                    @endif
                </tr>
            @empty
                <tr data-empty-row="1">
                    <td colspan="{{ $showDuration ? 12 : 9 }}" class="px-4 py-6 text-center text-sm text-gray-500">{{ $emptyMessage }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
