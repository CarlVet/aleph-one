<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-pink-50 to-rose-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Human Tubes Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select the human tubes for this experiment</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-pink-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-pink-600">0</span> human tubes selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="human_tubes_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button type="button" id="confirm_human_tube_selection"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-pink-600 to-rose-600 hover:from-pink-700 hover:to-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-pink-500 transition-all duration-200 transform hover:scale-105">
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
        <table id="human_tubes_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="select_all_human_tubes"
                                class="h-5 w-5 text-pink-600 focus:ring-pink-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_human_tubes"
                                class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-pink-600 transition-colors">
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
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Alias code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Preservant</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Sample Code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Patient</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Sample Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date Collected</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Sampling Site</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Latitude</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Longitude</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Collector</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Purpose</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($human_tubes as $tube)
                    <tr class="hover:bg-pink-50 transition-all duration-200 ease-in-out group">
                        <td class="sticky left-0 z-10 bg-white group-hover:bg-pink-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox" class="select-human-tube h-5 w-5 text-pink-600 focus:ring-pink-500 border-gray-300 rounded transition-all duration-200 hover:scale-110" value="{{ $tube->id }}" data-sample-type-label="{{ $tube->tube_sample_type_label ?? '' }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/bank/tubes/{{ $tube->code }}" class="text-sm font-medium text-pink-600 hover:text-pink-800 hover:underline">
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
                            <a href="/samples/humans/{{ $tube->tubes_content->code }}" class="text-sm font-medium text-pink-600 hover:text-pink-800 hover:underline">
                                {{ $tube->tubes_content->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->humans->first_name . " " . $tube->tubes_content->humans->last_name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->sample_types->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($tube->tubes_content->date_collected)->format('Y-m-d') ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->sampling_sites->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->latitude ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->longitude ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content->people->title . ' ' . $tube->tubes_content->people->first_name . ' ' . $tube->tubes_content->people->last_name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->purpose ?? 'N/A' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($human_tubes, 'links'))
        <div class="px-6 py-4 border-t border-gray-200 bg-white">
            {{ $human_tubes->withPath($paginationPath ?? route('experiments.create.tubes.human'))->withQueryString()->onEachSide(1)->links() }}
        </div>
    @endif
</div>
