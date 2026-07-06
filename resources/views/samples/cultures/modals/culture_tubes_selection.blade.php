<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-orange-50 to-amber-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Culture Tubes Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select the culture tubes for this experiment</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-orange-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-orange-600">0</span> culture tubes selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="culture_tubes_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button type="button" id="confirm_culture_tube_selection"
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
        <table id="culture_tubes_selection_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="select_all_culture_tubes"
                                class="h-5 w-5 text-orange-600 focus:ring-orange-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_culture_tubes"
                                class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-orange-600 transition-colors">
                                Select All
                            </label>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Tube Code</span>
                            <svg class="w-4 h-4 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Alias Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Preservant</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Parent Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Step</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date Cultured</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Medium</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Incubation Temp (°C)</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Atmosphere</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content Details</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Cultured At</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Cultured By</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Purpose</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($culture_tubes as $tube)
                    <tr class="hover:bg-orange-50 transition-all duration-200 ease-in-out group">
                        <td class="sticky left-0 z-10 bg-white group-hover:bg-orange-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox" class="select-culture-tube h-5 w-5 text-orange-600 focus:ring-orange-500 border-gray-300 rounded transition-all duration-200 hover:scale-110" value="{{ $tube->id }}" data-sample-type-label="{{ $tube->tube_sample_type_label ?? '' }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/bank/tubes/{{ $tube->code }}" class="text-sm font-medium text-orange-600 hover:text-orange-800 hover:underline">
                                {{ $tube->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->alias_code ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->preservant ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ data_get($tube, 'tubes_content.parent.code') ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->step ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ data_get($tube, 'tubes_content.date_cultured') ? \Carbon\Carbon::parse(data_get($tube, 'tubes_content.date_cultured'))->format('Y-m-d') : 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->medium ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->type ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->incubation_temp ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->athmosphere ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
                                <table class="min-w-full text-xs">
                                    <thead>
                                        <tr class="bg-gray-50">
                                            <th class="whitespace-nowrap px-2 py-1 text-left font-semibold text-gray-600">Culture code</th>
                                            <th class="whitespace-nowrap px-2 py-1 text-left font-semibold text-gray-600">Parent code</th>
                                            <th class="whitespace-nowrap px-2 py-1 text-left font-semibold text-gray-600">Content type</th>
                                            <th class="whitespace-nowrap px-2 py-1 text-left font-semibold text-gray-600">Content code</th>
                                            <th class="whitespace-nowrap px-2 py-1 text-left font-semibold text-gray-600">Tube alias</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            @php
                                                $cultureContent = data_get($tube, 'tubes_content.cultures_content');
                                                $tubeAliases = 'N/A';

                                                if ($cultureContent instanceof \Illuminate\Database\Eloquent\Model && $cultureContent->relationLoaded('tubes')) {
                                                    $aliases = $cultureContent->getRelation('tubes')
                                                        ->pluck('alias_code')
                                                        ->filter()
                                                        ->unique()
                                                        ->implode(', ');
                                                    $tubeAliases = $aliases !== '' ? $aliases : 'N/A';
                                                }
                                            @endphp
                                            <td class="whitespace-nowrap px-2 py-1.5 text-gray-800">{{ data_get($tube, 'tubes_content.code') ?? 'N/A' }}</td>
                                            <td class="whitespace-nowrap px-2 py-1.5 text-gray-800">{{ data_get($tube, 'tubes_content.parent.code') ?? 'N/A' }}</td>
                                            <td class="whitespace-nowrap px-2 py-1.5 text-gray-800">{{ data_get($tube, 'tubes_content.cultures_content_type') ? basename(str_replace('\\', '/', (string) data_get($tube, 'tubes_content.cultures_content_type'))) : 'N/A' }}</td>
                                            <td class="whitespace-nowrap px-2 py-1.5 text-gray-800">{{ data_get($tube, 'tubes_content.cultures_content.code') ?? 'N/A' }}</td>
                                            <td class="whitespace-nowrap px-2 py-1.5 text-gray-800">{{ $tubeAliases }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ data_get($tube, 'tubes_content.cultures_content.code') ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->laboratories->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ trim((data_get($tube, 'tubes_content.people.title') ?? '').' '.(data_get($tube, 'tubes_content.people.first_name') ?? '').' '.(data_get($tube, 'tubes_content.people.last_name') ?? '')) ?: 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->purpose ?? 'N/A' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($culture_tubes, 'links'))
        <div class="px-6 py-4 border-t border-gray-200 bg-white">
            {{ $culture_tubes->withPath($paginationPath ?? route('experiments.create.tubes.culture'))->withQueryString()->onEachSide(1)->links() }}
        </div>
    @endif
</div>
