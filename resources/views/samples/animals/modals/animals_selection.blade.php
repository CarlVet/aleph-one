<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                </svg>
                Animal Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select the animals for this sample</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-green-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-green-600">0</span> animals selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="animals_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button type="button" id="confirm_animal_selection"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 transform hover:scale-105">
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
        <table id="animals_selection_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="select_all_animals"
                                class="h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_animals"
                                class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-green-600 transition-colors">
                                Select All
                            </label>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Animal Code</span>
                            <svg class="w-4 h-4 ml-1 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </div>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Field ID</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Species</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Sex</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Age</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Owner</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($animals as $animal)
                    <tr class="hover:bg-green-50 transition-all duration-200 ease-in-out group">
                        <td class="sticky left-0 z-10 bg-white group-hover:bg-green-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox" class="select-animal h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300 rounded transition-all duration-200 hover:scale-110" value="{{ $animal->id }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/animals/{{ $animal->code }}" class="text-sm font-medium text-green-600 hover:text-green-800 hover:underline">
                                {{ $animal->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-gray-900">{{ $animal->field_label ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $animal->species_name_common ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if ($animal->sex)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $animal->sex === 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                    <span class="w-2 h-2 rounded-full mr-1.5 {{ $animal->sex === 'Male' ? 'bg-blue-400' : 'bg-pink-400' }}"></span>
                                    {{ $animal->sex }}
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <span class="w-2 h-2 rounded-full mr-1.5 bg-gray-400"></span>
                                    N/A
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $animal->age ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if(($animal->owner_type ?? '') === \App\Models\Humans::class)
                                @if (! empty($animal->owner_human_code))
                                    <a href="/humans/{{ $animal->owner_human_code }}" class="text-sm font-medium text-pink-600 hover:text-pink-800 hover:underline">
                                        {{ trim(($animal->owner_first_name ?? '') . ' ' . ($animal->owner_last_name ?? '')) ?: 'N/A' }}
                                    </a>
                                @else
                                    <span class="text-sm text-gray-900">{{ trim(($animal->owner_first_name ?? '') . ' ' . ($animal->owner_last_name ?? '')) ?: 'N/A' }}</span>
                                @endif
                            @else
                                <span class="text-sm text-gray-900">{{ $animal->owner_organization_name ?? 'N/A' }}</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($animals, 'links'))
        <div class="border-t border-gray-200 bg-white px-6 py-4">
            {{ $animals->onEachSide(1)->withPath($paginationPath ?? request()->url())->links() }}
        </div>
    @endif
</div>