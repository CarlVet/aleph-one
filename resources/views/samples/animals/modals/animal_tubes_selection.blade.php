<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-yellow-50 to-yellow-60 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                Animal Tubes Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select the animal tubes for this experiment</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-yellow-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-yellow-600">0</span> animal tubes selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="animal_tubes_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button type="button" id="confirm_tube_selection"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-yellow-600 to-yellow-700 hover:from-yellow-600 hover:to-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 transition-all duration-200 transform hover:scale-105">
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
        <table id="animal_tubes_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="select_all_tubes"
                                class="h-5 w-5 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_tubes"
                                class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-yellow-600 transition-colors">
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
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Purpose</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Sample code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Animal code</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Field ID</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Species</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Sex</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Age</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Sample Type</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date Collected</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Park</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Latitude</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Longitude</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Collector</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($animal_tubes as $tube)
                    <tr class="hover:bg-yellow-50 transition-all duration-200 ease-in-out group">
                        <td class="sticky left-0 z-10 bg-white group-hover:bg-yellow-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox" class="select-tube h-5 w-5 text-yellow-600 focus:ring-yellow-500 border-gray-300 rounded transition-all duration-200 hover:scale-110" value="{{ $tube->id }}" data-sample-type-label="{{ $tube->tube_sample_type_label ?? '' }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/bank/tubes/{{ $tube->code }}" class="text-sm font-medium text-yellow-600 hover:text-yellow-800 hover:underline">
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
                            <span class="text-sm text-gray-900">{{ $tube->purpose ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/samples/animals/{{ $tube->tubes_content?->code }}" class="text-sm font-medium text-yellow-600 hover:text-yellow-800 hover:underline">
                                {{ $tube->tubes_content?->code ?? 'N/A' }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->animals?->code ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->animals?->field_label ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->animals?->animal_species?->name_common ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php($sex = $tube->tubes_content?->animals?->sex)
                            @if ($sex)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sex === 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                    <span class="w-2 h-2 rounded-full mr-1.5 {{ $sex === 'Male' ? 'bg-blue-400' : 'bg-pink-400' }}"></span>
                                    {{ $sex }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <span class="w-2 h-2 rounded-full mr-1.5 bg-gray-400"></span>
                                    N/A
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->animals?->age ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->sample_types?->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->date_collected ? \Carbon\Carbon::parse($tube->tubes_content->date_collected)->format('Y-m-d') : 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->sampling_sites?->name ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->latitude ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->longitude ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $tube->tubes_content?->people ? $tube->tubes_content->people->first_name . ' ' . $tube->tubes_content->people->last_name : 'N/A' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($animal_tubes, 'links'))
        <div class="px-6 py-4 border-t border-gray-200 bg-white">
            {{ $animal_tubes->withPath($paginationPath ?? route('experiments.create.tubes.animal'))->withQueryString()->onEachSide(1)->links() }}
        </div>
    @endif
</div>
