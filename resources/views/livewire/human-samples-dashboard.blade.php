<div>
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public human samples from all projects</p>
                    <p class="text-xs text-purple-500 mt-1">Note: only processed samples that have a tube are shown (raw, unprocessed samples are not included).</p>
                </div>
                <a href="/my-projects"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif

    <!-- Create, Edit, Dashboard (Centered) -->
    <div class="relative flex justify-center items-center space-x-4 mt-2 mb-4">
        @if (!$isGuestMode)
            <!-- Left Arrow Home Link -->
            <a href="/samples/humans"
                class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                <i class="fas fa-arrow-left text-2xl mr-2"></i>
                <span class="text-sm font-medium">Back to HS Home</span>
            </a>
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/samples/humans/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @endif
            <a href="/samples/humans/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
        @else
            <div class="flex items-center space-x-4">
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Project Mode)
                </div>
                <a href="/samples/humans/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            </div>
        @endif
    </div>

    <!-- Header Section -->
    <div class="text-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Human Samples Dashboard</h1>
        <p class="text-gray-600">Overview of all human samples and their collection data</p>
    </div>

    <!-- Filters Section -->
    <div data-dashboard-filter-root class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8" x-data="{ open: true }">
        <div class="flex items-center justify-between mb-4">
            <button type="button"
                class="inline-flex items-center gap-2 text-lg font-semibold text-gray-800 hover:text-gray-900"
                x-on:click="open = !open" x-bind:aria-expanded="open.toString()">
                <i class="fas fa-chevron-down text-sm transition-transform duration-200"
                    x-bind:class="open ? 'rotate-180' : ''"></i>
                <span>Filters</span>
            </button>
            <button type="button"
                wire:click="resetFilters"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-slate-900 px-3 py-2 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 transition-colors"
                x-show="open">
                <i class="fas fa-rotate-left text-xs"></i>
                Reset
            </button>
        </div>
        <div x-show="open" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Count records by</label>
                    <div class="flex flex-wrap gap-4 rounded-lg border border-gray-200 px-3 py-2">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" class="text-blue-600 focus:ring-blue-500"
                                wire:model.live="visualize_by" value="samples">
                            <span>Human sample unit</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" class="text-blue-600 focus:ring-blue-500"
                                wire:model.live="visualize_by" value="patients">
                            <span>Human patient unit</span>
                        </label>
                    </div>
                </div>
                @if (!$isGuestMode)
                    <!-- Sample visibility -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Samples to visualize</label>
                        <div class="flex flex-wrap gap-4 rounded-lg border border-gray-200 px-3 py-2">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" class="text-blue-600 focus:ring-blue-500"
                                    wire:model.live="sampleVisibility" value="all">
                                <span>All registered</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="radio" class="text-blue-600 focus:ring-blue-500"
                                    wire:model.live="sampleVisibility" value="processed_with_tubes">
                                <span>Processed + has tube</span>
                            </label>
                        </div>
                    </div>
                @endif
                <!-- Sample Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sample Type</label>
                    <select wire:model.live.debounce.300ms="sampleTypeFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Types</option>
                        @foreach ($allSampleTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sampling Site Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampling Site</label>
                    <select wire:model.live.debounce.300ms="samplingSiteFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Sites</option>
                        @foreach ($allSamplingSites as $site)
                            <option value="{{ $site }}">{{ $site }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sub-project</label>
                    <select wire:model.live.debounce.300ms="subProjectFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All sub-projects</option>
                        @foreach ($allSubProjects as $subProjectCode)
                            <option value="{{ $subProjectCode }}">{{ $subProjectCode }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Ethnicity Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ethnicity</label>
                    <select wire:model.live.debounce.300ms="ethnicityFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Ethnicities</option>
                        @foreach ($allEthnicities as $ethnicity)
                            <option value="{{ $ethnicity }}">{{ $ethnicity }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Occupation Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                    <select wire:model.live.debounce.300ms="occupationFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Occupations</option>
                        @foreach ($allOccupations as $occupation)
                            <option value="{{ $occupation }}">{{ $occupation }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Country Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <select wire:model.live.debounce.300ms="countryFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Countries</option>
                        @foreach ($allCountries as $country)
                            <option value="{{ $country }}">{{ $country }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Collection Date Range</label>
                    <div class="flex space-x-2">
                        <input type="date" wire:model.live.debounce.300ms="startDate"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Start Date">
                        <input type="date" wire:model.live.debounce.300ms="endDate"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="End Date">
                    </div>
                </div>

                <!-- Timeline Granularity -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Timeline</label>
                    <div class="flex flex-wrap gap-4 rounded-lg border border-gray-200 px-3 py-2">
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" class="text-blue-600 focus:ring-blue-500"
                                wire:model.live="timelineGranularity" value="monthly">
                            <span>Monthly</span>
                        </label>
                        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                            <input type="radio" class="text-blue-600 focus:ring-blue-500"
                                wire:model.live="timelineGranularity" value="yearly">
                            <span>Yearly</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
        <!-- Total Samples -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('humanSamplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">{{ ($activeFilters['visualize_by'] ?? 'samples') === 'patients' ? 'Total Patients' : 'Total Samples' }}</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $descriptive_stats['total_samples'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-user text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('sampleTypeModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Sample Types</p>
                    <p class="text-3xl font-bold text-indigo-600">{{ $descriptive_stats['unique_sample_types'] }}</p>
                </div>
                <div class="p-3 bg-indigo-100 rounded-full">
                    <i class="fas fa-vial text-2xl text-indigo-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('samplingSiteModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Sampling Sites</p>
                    <p class="text-3xl font-bold text-amber-600">{{ $descriptive_stats['unique_sampling_sites'] }}</p>
                </div>
                <div class="p-3 bg-amber-100 rounded-full">
                    <i class="fas fa-map-marker-alt text-2xl text-amber-600"></i>
                </div>
            </div>
        </div>

        <!-- Ethnicity -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('ethnicityModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Ethnicity</p>
                    <p class="text-3xl font-bold text-red-600">{{ count($humanSamplesByEthnicity) }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-users text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <!-- Occupation -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('occupationModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Occupation</p>
                    <p class="text-3xl font-bold text-green-600">{{ count($humanSamplesByOccupation) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-briefcase text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Country -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('countryModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Country</p>
                    <p class="text-3xl font-bold text-purple-600">{{ count($humanSamplesByCountry) }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-globe text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- Tabbed Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8" wire:ignore>
        <div id="pieBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div id="pieContent" data-expand-content-root data-expand-title="Human samples distribution">
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach (($pieChartTabs ?? []) as $tab)
                        <button type="button"
                            data-pie-tab="{{ $tab['key'] }}"
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
            <div id="barContent" data-expand-content-root data-expand-title="Human samples counts">
                <div class="flex flex-wrap gap-2 mb-4">
                    @foreach (($barChartTabs ?? []) as $tab)
                        <button type="button"
                            data-bar-tab="{{ $tab['key'] }}"
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

    <!-- Map Section -->
    <div id="mapBox" class="dashboard-box bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
        <div id="mapContent" data-expand-content-root data-expand-title="Human samples distribution map">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <h3 class="map-inline-title text-lg font-semibold text-gray-800">Samples Distribution</h3>
            <div class="flex flex-wrap gap-2" id="mapVariableTabs">
                @foreach (($mapColorVariableOptions ?? []) as $option)
                    <button type="button"
                        data-map-tab="{{ $option['key'] }}"
                        class="map-tab-btn px-3 py-1.5 text-xs rounded-full border border-gray-200 text-gray-600 hover:text-indigo-700 hover:border-indigo-200">
                        {{ $option['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
        <div class="relative h-96">
            <div id="map" class="w-full h-full rounded-lg"></div>
            <div id="mapLegend"
                class="absolute inset-x-3 bottom-3 z-[450] ml-auto max-w-md rounded-xl border border-gray-200 bg-white/95 px-3 py-2 shadow-lg backdrop-blur-sm">
            </div>
        </div>
        </div>
    </div>

    <!-- Timeline Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Sample Collection Timeline (Last 12 Months)</h4>
        <div class="timeline-chart-container">
            <canvas id="timelineChart" class="w-full h-48"></canvas>
        </div>
    </div>

    <!-- Modal for All Human Samples -->
    <div id="humanSamplesModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">All Human Samples in Filtered Dataset</h3>
                <button onclick="closeModal('humanSamplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="humanSamplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <!-- Modal for Sample Type -->
    <div id="sampleTypeModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Human Samples by Sample Type</h3>
                <button onclick="closeModal('sampleTypeModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sample Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($humanSamplesByType as $sampleType => $count)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $sampleType }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Ethnicity -->
    <div id="ethnicityModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Human Samples by Ethnicity</h3>
                <button onclick="closeModal('ethnicityModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Ethnicity</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($humanSamplesByEthnicityCount as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['ethnicity'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $item['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Occupation -->
    <div id="occupationModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Human Samples by Occupation</h3>
                <button onclick="closeModal('occupationModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Occupation</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($humanSamplesByOccupationCount as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['occupation'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $item['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Country -->
    <div id="countryModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Human Samples by Country</h3>
                <button onclick="closeModal('countryModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Country</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($humanSamplesByCountryCount as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['country'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $item['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Sampling Site -->
    <div id="samplingSiteModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Human Samples by Sampling Site</h3>
                <button onclick="closeModal('samplingSiteModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sampling Site</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($humanSamplesBySamplingSiteCount as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['sampling_site'] }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $item['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Sex -->
    <div id="sexModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Human Samples by Sex</h3>
                <button onclick="closeModal('sexModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sex</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($humanSamplesBySexCount as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['sex'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $item['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Age -->
    <div id="ageModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Human Samples by Age Range</h3>
                <button onclick="closeModal('ageModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Age Range</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($humanSamplesByAgeRangeCount as $item)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $item['age_range'] }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $item['count'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const timelineData = @json($descriptive_stats['collection_timeline']);
        const pieChartTabs = @json($pieChartTabs ?? []);
        const barChartTabs = @json($barChartTabs ?? []);
        const mapColorVariableOptions = @json($mapColorVariableOptions ?? []);

        window.timelineData = timelineData;
        window.pieChartTabs = pieChartTabs;
        window.barChartTabs = barChartTabs;
        window.mapColorVariableOptions = mapColorVariableOptions;
        window.mapPointsUrl = @json($mapPointsUrl);
        window.activeFilters = @json($activeFilters);
        window.modalTableUrls = @json($modalTableUrls);

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.getElementById(modalId).classList.add('flex');
            window.loadHumanDashboardModal?.(modalId);
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('flex');
            document.getElementById(modalId).classList.add('hidden');
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modals = ['humanSamplesModal', 'ethnicityModal', 'occupationModal', 'countryModal',
                'samplingSiteModal', 'sexModal', 'ageModal'
            ];

            modals.forEach(modalId => {
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
        <script src="/js/show-human-samples.js?v={{ filemtime(public_path('js/show-human-samples.js')) }}"></script>
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

        .pie-chart-cluster-icon {
            background: transparent !important;
            border: none !important;
        }

        .pie-chart-cluster-icon svg {
            filter: drop-shadow(2px 2px 4px rgba(0, 0, 0, 0.3));
        }

        .invisible-marker {
            background: transparent !important;
            border: none !important;
        }

        .leaflet-tooltip {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
        }
    </style>

</div>
