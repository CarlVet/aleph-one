<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">

    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-blue-50 to-indigo-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z">
                    </path>
                </svg>
                Human Patients Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select the patients you collected samples from</p>
        </div>


        <div class="bg-gradient-to-r from-gray-50 to-blue-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-blue-600">0</span> patients selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="humans_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button type="button" id="confirm_human_selection"
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
    <div class="overflow-x-auto">
        <table id="humans_selection_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="select_all_humans"
                                class="h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_humans"
                                class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-blue-600 transition-colors">
                                Select All
                            </label>
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Code</span>
                            <svg class="w-4 h-4 ml-1 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">First Name
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Last Name
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Sex</th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date of
                        Birth</th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Ethnicity
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Occupation
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">City</th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Province
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Phone</th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Email</th>
        </tr>
    </thead>
            <tbody class="bg-white divide-y divide-gray-100">
        @foreach ($humans as $human)
                    <tr class="hover:bg-blue-50 transition-all duration-200 ease-in-out group">
                        <td
                            class="sticky left-0 z-10 bg-white group-hover:bg-blue-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox"
                                    class="select-humans h-5 w-5 text-blue-600 focus:ring-blue-500 border-gray-300 rounded transition-all duration-200 hover:scale-110"
                                    value="{{ $human->id }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/humans/{{ $human->code }}"
                                class="text-sm font-medium text-blue-600 hover:text-blue-800 hover:underline">
                                {{ $human->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-gray-900">{{ $human->first_name }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-medium text-gray-900">{{ $human->last_name }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if ($human->sex)
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $human->sex === 'Male' ? 'bg-blue-100 text-blue-800' : 'bg-pink-100 text-pink-800' }}">
                                    <span
                                        class="w-2 h-2 rounded-full mr-1.5 {{ $human->sex === 'Male' ? 'bg-blue-400' : 'bg-pink-400' }}"></span>
                                    {{ $human->sex }}
                                </span>
                            @else
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                    <span class="w-2 h-2 rounded-full mr-1.5 bg-gray-400"></span>
                                    N/A
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $human->date_of_birth ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $human->ethnicity ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $human->occupation ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $human->city ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $human->province ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if ($human->phone)
                                <a href="tel:{{ $human->phone }}"
                                    class="text-sm text-blue-600 hover:text-blue-800 hover:underline flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
                                        </path>
                                    </svg>
                                    {{ $human->phone }}
                                </a>
                            @else
                                <span class="text-sm text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if ($human->email)
                                <a href="mailto:{{ $human->email }}"
                                    class="text-sm text-blue-600 hover:text-blue-800 hover:underline flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                    {{ $human->email }}
                                </a>
                            @else
                                <span class="text-sm text-gray-500">N/A</span>
                            @endif
                </td>
            </tr>
        @endforeach
    </tbody>

        </table>
    </div>

    @if (method_exists($humans, 'links'))
        <div class="border-t border-gray-200 bg-white px-6 py-4">
            {{ $humans->onEachSide(1)->withPath($paginationPath ?? request()->url())->links() }}
        </div>
    @endif

</div>
