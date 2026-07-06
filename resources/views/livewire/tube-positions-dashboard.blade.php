<div>
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public tube positions from all projects</p>
                </div>
                <a href="/my-projects"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif

    <div class="relative flex justify-center items-center space-x-4 mt-2 mb-4">
        @if (!$isGuestMode)
            <a href="/bank/tubes"
                class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                <i class="fas fa-arrow-left text-2xl mr-2"></i>
                <span class="text-sm font-medium">Back to Tubes Home</span>
            </a>
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Register (Viewer)
                </div>
            @else
                <a href="/bank/tubes/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Register
                </a>
            @endif
            <a href="/bank/tubes/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
        @else
            <div class="flex items-center space-x-4">
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Register (Project Mode)
                </div>
                <a href="/bank/tubes/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            </div>
        @endif
    </div>

    <div class="text-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Tube Positions Dashboard</h1>
        <p class="text-gray-600">Overview of tube storage positions across laboratories and locations</p>
    </div>

    <div id="tube-positions-dashboard-filters" data-dashboard-filter-root class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8" x-data="{ open: true }">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between mb-4">
            <button type="button"
                class="inline-flex items-center gap-2 text-lg font-semibold text-gray-800 hover:text-gray-900"
                x-on:click="open = !open" x-bind:aria-expanded="open.toString()">
                <i class="fas fa-chevron-down text-sm transition-transform duration-200"
                    x-bind:class="open ? 'rotate-180' : ''"></i>
                <span>Filters</span>
            </button>
            <button type="button" wire:click="resetFilters" wire:loading.attr="disabled"
                class="inline-flex items-center justify-center px-4 py-2 text-sm font-medium rounded-lg border border-gray-200 bg-white text-gray-700 hover:bg-gray-50 transition-colors disabled:opacity-60 disabled:cursor-not-allowed">
                <i class="fas fa-rotate-left mr-2"></i>
                Reset all filters
            </button>
        </div>
        <div x-show="open" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Laboratory</label>
                    <select wire:model.live.debounce.300ms="laboratoryFilter" data-dashboard-placeholder-current="true"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All laboratories</option>
                        @foreach ($allLaboratories as $laboratory)
                            <option value="{{ $laboratory }}">{{ $laboratory }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Location</label>
                    <select wire:model.live.debounce.300ms="locationFilter" data-dashboard-placeholder-current="true"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All locations</option>
                        @foreach ($allLocations as $location)
                            <option value="{{ $location }}">{{ $location }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Box</label>
                    <select wire:model.live.debounce.300ms="boxFilter" data-dashboard-placeholder-current="true"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All boxes</option>
                        @foreach ($allBoxes as $box)
                            <option value="{{ $box }}">{{ $box }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content type</label>
                    <select wire:model.live.debounce.300ms="contentTypeFilter" data-dashboard-placeholder-current="true"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All content types</option>
                        @foreach ($allContentTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                @if (!$isGuestMode)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Sub-project</label>
                        <select wire:model.live.debounce.300ms="subProjectFilter" data-dashboard-placeholder-current="true"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All sub-projects</option>
                            @foreach ($allSubProjects as $subProjectCode)
                                <option value="{{ $subProjectCode }}">{{ $subProjectCode }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date moved range</label>
                    <div class="flex space-x-2">
                        <input type="date" wire:model.live.debounce.300ms="startDate"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <input type="date" wire:model.live.debounce.300ms="endDate"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('tubesWithPositionModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Tubes with position</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $descriptive_stats['tubes_with_position'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-map-pin text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('tubesWithoutPositionModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Tubes without position</p>
                    <p class="text-3xl font-bold text-amber-600">{{ $descriptive_stats['tubes_without_position'] }}</p>
                </div>
                <div class="p-3 bg-amber-100 rounded-full">
                    <i class="fas fa-question-circle text-2xl text-amber-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('locationsModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Locations</p>
                    <p class="text-3xl font-bold text-green-600">{{ $descriptive_stats['unique_locations'] }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-door-open text-2xl text-green-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('boxesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Boxes</p>
                    <p class="text-3xl font-bold text-indigo-600">{{ $descriptive_stats['unique_boxes'] }}</p>
                </div>
                <div class="p-3 bg-indigo-100 rounded-full">
                    <i class="fas fa-box text-2xl text-indigo-600"></i>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('laboratoriesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Laboratories</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $descriptive_stats['unique_laboratories'] }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-flask text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8" wire:ignore>
        <div id="pieBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div id="pieContent" data-expand-content-root data-expand-title="Tube positions distribution">
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach (($pieChartTabs ?? []) as $tab)
                        <button type="button" data-pie-tab="{{ $tab['key'] }}"
                            class="chart-tab-btn px-3 py-1.5 text-xs rounded-full border border-gray-200 text-gray-600 hover:text-indigo-700 hover:border-indigo-200">
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>
                <div class="relative h-72">
                    <canvas id="pieTabbedChart"></canvas>
                </div>
                <div id="pieLegendScroller" class="mt-3"></div>
            </div>
        </div>
        <div id="barBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div id="barContent" data-expand-content-root data-expand-title="Tube positions counts">
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach (($barChartTabs ?? []) as $tab)
                        <button type="button" data-bar-tab="{{ $tab['key'] }}"
                            class="chart-tab-btn px-3 py-1.5 text-xs rounded-full border border-gray-200 text-gray-600 hover:text-cyan-700 hover:border-cyan-200">
                            {{ $tab['label'] }}
                        </button>
                    @endforeach
                </div>
                <div class="relative h-72">
                    <canvas id="barTabbedChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div id="mapBox" class="dashboard-box bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
        <div id="mapContent" data-expand-content-root data-expand-title="Tube positions distribution map">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h3 class="map-inline-title text-lg font-semibold text-gray-800">Tube Positions Distribution</h3>
                <div class="flex flex-wrap gap-2" id="mapVariableTabs">
                    @foreach (($mapColorVariableOptions ?? []) as $option)
                        <button type="button" data-map-tab="{{ $option['key'] }}"
                            class="map-tab-btn px-3 py-1.5 text-xs rounded-full border border-gray-200 text-gray-600 hover:text-indigo-700 hover:border-indigo-200">
                            {{ $option['label'] }}
                        </button>
                    @endforeach
                </div>
            </div>
            <div class="relative h-96" wire:ignore>
                <div id="map" class="w-full h-full rounded-lg"></div>
                <div id="mapLegend"
                    class="absolute inset-x-3 bottom-3 z-[450] ml-auto max-w-md rounded-xl border border-gray-200 bg-white/95 px-3 py-2 shadow-lg backdrop-blur-sm">
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-500">
                Tubes are plotted at their laboratory coordinates. Cluster circles are pie charts showing mixed category composition at the same location.
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-4" wire:ignore>
        <div>
            <h4 class="text-md font-semibold text-gray-700 mb-2">Tube Movement Timeline</h4>
            <div class="flex flex-wrap gap-4 px-3 py-2">
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="radio" class="text-blue-600 focus:ring-blue-500" wire:model.live="timelineGranularity"
                        value="monthly">
                    <span>Monthly</span>
                </label>
                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="radio" class="text-blue-600 focus:ring-blue-500" wire:model.live="timelineGranularity"
                        value="yearly">
                    <span>Yearly</span>
                </label>
            </div>
            <div class="timeline-chart-container">
                <canvas id="timelineChart" class="w-full h-48"></canvas>
            </div>
        </div>
    </div>

    <div id="tubesWithPositionModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Tubes with position (latest 500)</h3>
                <button onclick="closeModal('tubesWithPositionModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tube</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Alias</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Content</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Laboratory</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Box</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Position</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date moved</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($tubesWithPositionRows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                    <a href="/bank/tubes/{{ $row->code }}"
                                        class="text-blue-600 hover:text-blue-800 hover:underline">{{ $row->code }}</a>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->alias_code ?: '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->content_type }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->laboratory }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->location }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    @if ($row->box_code)
                                        <a href="/bank/boxes/{{ $row->box_code }}/contents"
                                            class="text-blue-600 hover:text-blue-800 hover:underline">{{ $row->box_code }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->position ?: '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->date_moved }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-4 py-3 text-sm text-gray-500">No tubes with position for current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="tubesWithoutPositionModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-5xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Tubes without position (latest 500)</h3>
                <button onclick="closeModal('tubesWithoutPositionModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tube</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Alias</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Content</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date processed</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($tubesWithoutPositionRows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                    <a href="/bank/tubes/{{ $row->code }}"
                                        class="text-blue-600 hover:text-blue-800 hover:underline">{{ $row->code }}</a>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->alias_code ?: '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->content_type }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->date_processed ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-sm text-gray-500">No tubes without position for current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="locationsModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Locations in filtered dataset</h3>
                <button onclick="closeModal('locationsModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Laboratory</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tubes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($locationSummaryRows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $row->location }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->laboratory }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->tube_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-sm text-gray-500">No locations for current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="boxesModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-5xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Boxes in filtered dataset</h3>
                <button onclick="closeModal('boxesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Box code</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Box name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Laboratory</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tubes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($boxSummaryRows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                    @if ($row->box_code)
                                        <a href="/bank/boxes/{{ $row->box_code }}/contents"
                                            class="text-blue-600 hover:text-blue-800 hover:underline">{{ $row->box_code }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->box_name ?: '—' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->location }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->laboratory }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->tube_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-3 text-sm text-gray-500">No boxes for current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div id="laboratoriesModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Laboratories in filtered dataset</h3>
                <button onclick="closeModal('laboratoriesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Laboratory</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Locations</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tubes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($laboratorySummaryRows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $row->laboratory }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->location_count }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->tube_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-3 text-sm text-gray-500">No laboratories for current filters.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        window.timelineData = @json($descriptive_stats['position_timeline']);
        window.pieChartTabs = @json($pieChartTabs ?? []);
        window.barChartTabs = @json($barChartTabs ?? []);
        window.mapColorVariableOptions = @json($mapColorVariableOptions ?? []);
        window.mapPointsUrl = @json($mapPointsUrl);
        window.activeFilters = @json($activeFilters);

        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.getElementById(modalId).classList.add('flex');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('flex');
            document.getElementById(modalId).classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            ['tubesWithPositionModal', 'tubesWithoutPositionModal', 'locationsModal', 'boxesModal', 'laboratoriesModal'].forEach((modalId) => {
                const modal = document.getElementById(modalId);
                if (modal) {
                    modal.addEventListener('click', function(e) {
                        if (e.target === modal) {
                            closeModal(modalId);
                        }
                    });
                }
            });
        });
    </script>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
        <script src="/js/show-tube-positions.js?v={{ filemtime(public_path('js/show-tube-positions.js')) }}"></script>
    @endpush

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        .chart-tab-btn.is-active {
            border-color: #6366f1;
            color: #4338ca;
            background: rgba(99, 102, 241, 0.12);
            box-shadow: 0 8px 20px -10px rgba(79, 70, 229, 0.65);
        }

        .map-tab-btn.is-active {
            border-color: #334155;
            color: #0f172a;
            background: rgba(15, 23, 42, 0.08);
            box-shadow: 0 8px 20px -12px rgba(15, 23, 42, 0.75);
        }

        #mapLegend {
            max-height: 8rem;
            overflow: auto;
        }

        .invisible-marker {
            background: transparent !important;
            border: none !important;
        }

        .pie-chart-cluster-icon {
            background: transparent !important;
            border: none !important;
        }

        .pie-chart-cluster-icon svg {
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3));
        }

        .leaflet-tooltip {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
    </style>
</div>
