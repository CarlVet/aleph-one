<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden" data-meta-studies-modal>
    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-green-50 to-emerald-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                    </path>
                </svg>
                Studies Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select a study for this reference</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-green-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    Filter the table below, then select one study.
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="studies_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                            </path>
                        </svg>
                        Cancel
                    </button>
                    <button id="confirm_study_selection"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-green-600 to-emerald-600 hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 transform hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Confirm Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto" data-modal-content data-base-url="{{ $paginationPath }}">
        <table id="studies_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <span class="text-sm font-semibold text-gray-700">Select</span>
                    </th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Reference Key</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Title</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Year</th>
                    <th scope="col" class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">DOI</th>
                </tr>
            </thead>
            <thead class="bg-gradient-to-r from-gray-100 to-gray-50">
                <tr>
                    <th class="px-6 py-3"></th>
                    <th class="px-6 py-3">
                        <input type="text" name="filters[1]" value="{{ $filters[1] ?? '' }}"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" name="filters[2]" value="{{ $filters[2] ?? '' }}"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" name="filters[3]" value="{{ $filters[3] ?? '' }}"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" name="filters[4]" value="{{ $filters[4] ?? '' }}"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($studies as $study)
                    <tr class="hover:bg-green-50 transition-all duration-200 ease-in-out group">
                        <td class="sticky left-0 z-10 bg-white group-hover:bg-green-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="radio" name="study_selection"
                                    class="select-study h-5 w-5 text-green-600 focus:ring-green-500 border-gray-300 transition-all duration-200 hover:scale-110"
                                    value="{{ $study->id }}" data-text="{{ $study->ref_key }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-green-600">{{ $study->ref_key }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $study->title }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                <span class="w-2 h-2 rounded-full mr-1.5 bg-blue-400"></span>
                                {{ $study->publication_year }}
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            @if ($study->doi)
                                <a href="https://doi.org/{{ $study->doi }}" target="_blank"
                                    class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                                    {{ $study->doi }}
                                </a>
                            @else
                                <span class="text-sm text-gray-500">N/A</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if (method_exists($studies, 'links'))
            <div class="px-6 py-4 border-t border-gray-200 bg-white">
                {{ $studies->withPath($paginationPath ?? url()->current())->withQueryString()->onEachSide(1)->links() }}
            </div>
        @endif
    </div>
</div>

