@php
    use App\Support\ParasiteOriginDetailsPresenter;
@endphp

<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="flex flex-col h-full">
        <div class="bg-gradient-to-r from-purple-50 to-violet-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 2v2m0 6v2m0-2c1.657 0 3-.895 3-2s-1.343-2-3-2-3-.895-3-2 1.343-2 3-2m0 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8V6m0 2v2m0 6v2"></path>
                </svg>
                Parasites Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select parasites to dissect</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-purple-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-purple-600">0</span> parasites selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="parasites_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button type="button" id="confirm_parasite_selection"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-purple-600 to-violet-600 hover:from-purple-700 hover:to-violet-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 transform hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Confirm Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="parasites_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="select_all_parasites"
                                class="h-5 w-5 text-purple-600 focus:ring-purple-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_parasites"
                                class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-purple-600 transition-colors">
                                Select All
                            </label>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Parasite Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Alias Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Species</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Origin Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Origin Content Details</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Origin Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Identification Date</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Identified By</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Laboratory</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($parasites as $p)
                    @php
                        $originHref = ParasiteOriginDetailsPresenter::originSampleHref($p);
                        $contentDetailsRows = ParasiteOriginDetailsPresenter::rows($p);
                        $contentDetailsSearchText = ParasiteOriginDetailsPresenter::contentDetailsSearchText($p);
                        $canEditStatus = in_array((int) $p->id, $editable_parasite_ids ?? [], true);
                        $currentStatus = $p->status?->value ?? 'intact';
                    @endphp
                    <tr class="hover:bg-purple-50 transition-all duration-200 ease-in-out group">
                        <td class="sticky left-0 z-10 bg-white group-hover:bg-purple-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox" class="select-parasite h-5 w-5 text-purple-600 focus:ring-purple-500 border-gray-300 rounded transition-all duration-200 hover:scale-110" value="{{ $p->id }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="{{ ParasiteOriginDetailsPresenter::parasiteHref($p) }}" class="text-sm font-medium text-purple-600 hover:text-purple-800 hover:underline">
                                {{ $p->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ filled($p->parasite_alias_code) ? $p->parasite_alias_code : 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if ($canEditStatus)
                                <select
                                    class="parasite-status-select w-full min-w-[7rem] rounded-md border border-gray-200 px-2 py-1 text-xs text-gray-700 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500"
                                    data-parasite-id="{{ $p->id }}"
                                    data-original-status="{{ $currentStatus }}"
                                >
                                    @foreach (($status_options ?? []) as $value => $label)
                                        <option value="{{ $value }}" @selected($currentStatus === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            @else
                                <x-parasites.status-badge :status="$p->status" />
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{!! $p->species_name ? '<i>'.e($p->species_name).'</i>' : 'N/A' !!}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ ParasiteOriginDetailsPresenter::originTypeLabel($p->parasites_origin_type) }}</span>
                        </td>
                        <td class="px-6 py-4" data-content-details-search="{{ $contentDetailsSearchText }}">
                            <x-cultures.content-details-subtable :rows="$contentDetailsRows" />
                        </td>
                        <td class="px-6 py-4">
                            @if ($originHref && filled($p->origin_code_sort))
                                <a href="{{ $originHref }}" class="text-sm font-medium text-purple-600 hover:text-purple-800 hover:underline">
                                    {{ $p->origin_code_sort }}
                                </a>
                            @else
                                <span class="text-sm text-gray-900">{{ $p->origin_code_sort ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $p->date_identified ? \Carbon\Carbon::parse($p->date_identified)->format('Y-m-d') : 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $p->identified_by ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $p->lab_name ?? 'N/A' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($parasites, 'links'))
        <div class="px-6 py-4 border-t border-gray-200 bg-white">
            {{ $parasites->withPath(route('parasites.dissection.parasites'))->withQueryString()->onEachSide(1)->links() }}
        </div>
    @endif
</div>
