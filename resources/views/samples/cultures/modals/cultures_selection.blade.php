<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"></path>
                </svg>
                Culture Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select the cultures for this experiment</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-orange-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-orange-600">0</span> cultures selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="cultures_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button id="confirm_culture_selection"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-orange-600 to-amber-600 hover:from-orange-700 hover:to-amber-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all duration-200 transform hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Confirm Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto">
        <table id="culture_selection_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="select_all_cultures"
                                class="h-5 w-5 text-orange-600 focus:ring-orange-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_cultures"
                                class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-orange-600 transition-colors">
                                Select All
                            </label>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Culture Code</span>
                            <svg class="w-4 h-4 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content Details</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Parent Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Step</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date Cultured</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Medium</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Incubation Temp</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Atmosphere</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Cultured By</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Cultured At</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($cultures as $culture)
                    @php
                        $contentTypeClass = (string) ($culture->cultures_content_type ?? '');
                        $contentTypeLabel = $contentTypeClass !== '' ? class_basename($contentTypeClass) : 'N/A';
                        $contentCode = data_get($culture, 'cultures_content.code');
                        $contentHref = match ($contentTypeClass) {
                            'App\\Models\\HumanSamples' => $contentCode ? '/samples/humans/' . $contentCode : null,
                            'App\\Models\\AnimalSamples' => $contentCode ? '/samples/animals/' . $contentCode : null,
                            'App\\Models\\EnvironmentSamples' => $contentCode ? '/samples/environment/' . $contentCode : null,
                            'App\\Models\\ParasiteSamples' => $contentCode ? '/samples/parasites/' . $contentCode : null,
                            'App\\Models\\Pools' => $contentCode ? '/samples/pools/' . $contentCode : null,
                            'App\\Models\\Cultures' => $contentCode ? '/samples/cultures/' . $contentCode : null,
                            'App\\Models\\NucleicAcids' => $contentCode ? '/samples/nucleic/' . $contentCode : null,
                            default => null,
                        };
                    @endphp
                    <tr class="hover:bg-orange-50 transition-all duration-200 ease-in-out group">
                        <td class="sticky left-0 z-10 bg-white group-hover:bg-orange-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox" class="select-culture h-5 w-5 text-orange-600 focus:ring-orange-500 border-gray-300 rounded transition-all duration-200 hover:scale-110" value="{{ $culture->id }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/samples/cultures/{{ $culture->code }}" class="text-sm font-medium text-orange-600 hover:text-orange-800 hover:underline">
                                {{ $culture->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-gray-900">{{ $culture->type ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $contentTypeLabel }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $contentAliases = ($culture->relationLoaded('cultures_content') && $culture->cultures_content && method_exists($culture->cultures_content, 'relationLoaded') && $culture->cultures_content->relationLoaded('tubes'))
                                    ? collect($culture->cultures_content->getRelation('tubes'))
                                        ->pluck('alias_code')
                                        ->map(fn ($alias) => is_string($alias) ? trim($alias) : '')
                                        ->filter(fn ($alias) => $alias !== '')
                                        ->unique()
                                        ->values()
                                        ->all()
                                    : [];
                                $contentAliasLabel = !empty($contentAliases) ? implode(', ', $contentAliases) : 'N/A';
                                $contentDetailsRows = [
                                    ['label' => 'Culture code', 'value' => $culture->code ?? 'N/A'],
                                    ['label' => 'Parent code', 'value' => data_get($culture, 'parent.code') ?? 'N/A'],
                                    ['label' => 'Content code', 'value' => $contentCode ?? 'N/A'],
                                    ['label' => 'Tube alias', 'value' => $contentAliasLabel],
                                ];
                            @endphp
                            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                                <table class="min-w-full text-xs">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            @foreach ($contentDetailsRows as $detailRow)
                                                <th class="whitespace-nowrap px-2 py-1 text-left font-semibold text-gray-600">{{ $detailRow['label'] }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            @foreach ($contentDetailsRows as $detailRow)
                                                <td class="whitespace-nowrap px-2 py-1.5 text-gray-800">{{ $detailRow['value'] }}</td>
                                            @endforeach
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            @if ($contentCode && $contentHref)
                                <a href="{{ $contentHref }}" class="text-sm font-medium text-orange-600 hover:text-orange-800 hover:underline">
                                    {{ $contentCode }}
                                </a>
                            @else
                                <span class="text-sm text-gray-900">{{ $contentCode ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ data_get($culture, 'parent.code') ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $culture->step ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $culture->date_cultured ? \Carbon\Carbon::parse($culture->date_cultured)->format('Y-m-d') : 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $culture->medium ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $culture->incubation_temp ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $culture->athmosphere ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $culture->people?->title }} {{ $culture->people?->first_name }} {{ $culture->people?->last_name }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ data_get($culture, 'laboratories.name') ?? 'N/A' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($cultures, 'links'))
        <div class="px-6 py-4 border-t border-gray-200 bg-white">
            {{ $cultures->withPath($paginationPath ?? route('samples.process.samples.culture'))->withQueryString()->onEachSide(1)->links() }}
        </div>
    @endif
</div>