<div>
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public parasite samples from all projects</p>
                    <p class="text-xs text-purple-500 mt-1">Note: only samples that have a tube are shown (raw samples without a tube are not included).</p>
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
            <a href="/samples/parasites"
                class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                <i class="fas fa-arrow-left text-2xl mr-2"></i>
                <span class="text-sm font-medium">Back to PS Home</span>
            </a>
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/samples/parasites/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
                <a href="/samples/parasites/dissection/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-purple-500 to-violet-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-purple-600">
                    <i
                        class="fas fa-cut mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Dissect
                </a>
            @endif
            <a href="/samples/parasites/list"
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
                <a href="/samples/parasites/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            </div>
        @endif
    </div>

    <!-- Header Section -->
    <div class="text-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Parasite Samples Dashboard</h1>
        <p class="text-gray-600">Overview of all parasite samples and their characteristics</p>
    </div>

    <!-- Filters Section -->
    <div data-dashboard-filter-root class="parasite-filters bg-gradient-to-br from-white via-slate-50 to-indigo-50 rounded-2xl shadow-xl p-6 border border-indigo-100 mb-8" x-data="{ open: true }">
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
                <!-- Parasite Species Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parasite Species</label>
                    <select wire:model.live.debounce.300ms="parasiteSpeciesFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Species</option>
                        @foreach ($allParasiteSpecies as $species)
                            <option value="{{ $species }}">{{ $species }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Parasite Genus Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parasite Genus</label>
                    <select wire:model.live.debounce.300ms="parasiteGenusFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Genera</option>
                        @foreach ($allParasiteGenera as $genus)
                            <option value="{{ $genus }}">{{ $genus }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Parasite Family Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Parasite Family</label>
                    <select wire:model.live.debounce.300ms="parasiteFamilyFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Families</option>
                        @foreach ($allParasiteFamilies as $family)
                            <option value="{{ $family }}">{{ $family }}</option>
                        @endforeach
                    </select>
                </div>

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

                <!-- Origin Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Origin Type</label>
                    <select wire:model.live.debounce.300ms="originTypeFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="all">All Origins</option>
                        @foreach ($availableOriginTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($originTypeFilter === 'animal')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Origin animal species</label>
                        <select wire:model.live.debounce.300ms="originAnimalSpeciesFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All species</option>
                            @foreach ($allOriginAnimalSpecies as $species)
                                <option value="{{ $species }}">{{ $species }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Origin animal sex</label>
                        <select wire:model.live.debounce.300ms="originAnimalSexFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All sexes</option>
                            @foreach ($originAnimalSexesOptions as $sex)
                                <option value="{{ $sex }}">{{ $sex }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Origin animal age</label>
                        <select wire:model.live.debounce.300ms="originAnimalAgeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All ages</option>
                            @foreach ($originAnimalAgesOptions as $age)
                                <option value="{{ $age }}">{{ $age }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if ($originTypeFilter === 'human')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Origin human ethnicity</label>
                        <select wire:model.live.debounce.300ms="originHumanEthnicityFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All ethnicities</option>
                            @foreach ($originHumanEthnicitiesOptions as $ethnicity)
                                <option value="{{ $ethnicity }}">{{ $ethnicity }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Origin human occupation</label>
                        <select wire:model.live.debounce.300ms="originHumanOccupationFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All occupations</option>
                            @foreach ($originHumanOccupationsOptions as $occupation)
                                <option value="{{ $occupation }}">{{ $occupation }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Origin human country</label>
                        <select wire:model.live.debounce.300ms="originHumanCountryFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All countries</option>
                            @foreach ($originHumanCountriesOptions as $country)
                                <option value="{{ $country }}">{{ $country }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Stage Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Stage</label>
                    <select wire:model.live.debounce.300ms="stageFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Stages</option>
                        @foreach ($allStages as $stage)
                            <option value="{{ $stage }}">{{ $stage }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Sex Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sex</label>
                    <select wire:model.live.debounce.300ms="sexFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Sexes</option>
                        @foreach ($allSexes as $sex)
                            <option value="{{ $sex }}">{{ $sex }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- State Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">State</label>
                    <select wire:model.live.debounce.300ms="stateFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All States</option>
                        @foreach ($allStates as $state)
                            <option value="{{ $state }}">{{ $state }}</option>
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

            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Samples -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('samplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Samples</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $descriptive_stats['total_samples'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-bug text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Human Samples -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('humanSamplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Human Samples</p>
                    <p class="text-3xl font-bold text-red-600">{{ $descriptive_stats['human_samples'] }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-user text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <!-- Animal Samples -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('animalSamplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Animal Samples</p>
                    <p class="text-3xl font-bold text-green-600">{{ $descriptive_stats['animal_samples'] }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-paw text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Environment Samples -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('environmentSamplesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Environment Samples</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $descriptive_stats['environment_samples'] }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-leaf text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div id="pieBox" class="dashboard-box relative bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
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
                <div class="dashboard-box-tools">
                    <button type="button" data-expand-target="pieBox"
                        class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                        title="Expand">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button type="button" data-format-target="pie" data-format="png"
                        class="icon-btn format-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                        title="Format: png (click to change)">
                        <i class="fas fa-file-export"></i>
                    </button>
                    <button type="button" data-download-target="pie"
                        class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                        title="Download">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                <canvas id="pieTabbedChart"></canvas>
            </div>
            <div id="pieLegendScroller" class="mt-2 max-w-full overflow-x-scroll overflow-y-hidden"></div>
        </div>

        <div id="barBox" class="dashboard-box relative bg-white rounded-2xl shadow-xl p-6 border border-gray-100">
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
                <div class="dashboard-box-tools">
                    <button type="button" data-expand-target="barBox"
                        class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                        title="Expand">
                        <i class="fas fa-expand"></i>
                    </button>
                    <button type="button" data-format-target="bar" data-format="png"
                        class="icon-btn format-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                        title="Format: png (click to change)">
                        <i class="fas fa-file-export"></i>
                    </button>
                    <button type="button" data-download-target="bar"
                        class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                        title="Download">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
                <canvas id="barTabbedChart"></canvas>
            </div>
        </div>
    </div>


    <!-- Map Section -->
    <div id="mapBox" class="dashboard-box relative bg-white rounded-2xl shadow-xl p-6 border border-gray-100 mb-8">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Samples Distribution</h3>
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
            <div class="dashboard-box-tools">
                <button type="button" data-expand-target="mapBox"
                    class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                    title="Expand">
                    <i class="fas fa-expand"></i>
                </button>
                <button type="button" data-format-target="map" data-format="png"
                    class="icon-btn format-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                    title="Format: png (click to change)">
                    <i class="fas fa-file-export"></i>
                </button>
                <button type="button" data-download-target="map"
                    class="icon-btn p-2 text-xs rounded-lg border border-gray-200 text-gray-700 hover:bg-gray-50"
                    title="Download">
                    <i class="fas fa-download"></i>
                </button>
            </div>
            <div id="map" class="w-full h-full rounded-lg"></div>
        </div>
        <div id="mapLegend" class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-600"></div>
        <div class="mt-2 text-xs text-gray-500">
            Tip: cluster circles are pie charts showing mixed category composition at the same location.
        </div>
    </div>

    <!-- Processing Status and Timeline Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-4">

        <!-- Processing Timeline Chart -->
        <div>
            <h4 class="text-md font-semibold text-gray-700 mb-4">Sample Collection Timeline</h4>
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

    <!-- Modal for All Samples -->
    <div id="samplesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">All Parasite Samples in Filtered Dataset</h3>
                <button onclick="closeModal('samplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="samplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <!-- Modal for Human Samples -->
    <div id="humanSamplesModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Human Origin Samples in Filtered Dataset</h3>
                <button onclick="closeModal('humanSamplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="humanSamplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <!-- Modal for Animal Samples -->
    <div id="animalSamplesModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Animal Origin Samples in Filtered Dataset</h3>
                <button onclick="closeModal('animalSamplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="animalSamplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <!-- Modal for Environment Samples -->
    <div id="environmentSamplesModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Environment Origin Samples in Filtered Dataset</h3>
                <button onclick="closeModal('environmentSamplesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div id="environmentSamplesModalContent" data-modal-content class="text-sm text-gray-500">Loading…</div>
        </div>
    </div>

    <script>
        const timelineData = @json($descriptive_stats['collection_timeline']);
        const parasiteSamplesByOrigin = @json($parasiteSamplesByOrigin);
        const parasiteSamplesByStage = @json($parasiteSamplesByStage);
        const parasiteSamplesBySex = @json($parasiteSamplesBySex);
        const parasiteSamplesBySpecies = @json($parasiteSamplesBySpecies);
        const parasiteSamplesByGenus = @json($parasiteSamplesByGenus ?? []);
        const parasiteSamplesBySampleType = @json($parasiteSamplesBySampleType ?? []);
        const pieChartTabs = @json($pieChartTabs ?? []);
        const barChartTabs = @json($barChartTabs ?? []);
        const mapColorVariableOptions = @json($mapColorVariableOptions ?? []);
        const mapPointsUrl = @json($mapPointsUrl ?? null);
        const activeFilters = @json($activeFilters ?? []);
        const modalTableUrls = @json($modalTableUrls ?? []);

        window.timelineData = timelineData;
        window.parasiteSamplesByOrigin = parasiteSamplesByOrigin;
        window.parasiteSamplesByStage = parasiteSamplesByStage;
        window.parasiteSamplesBySex = parasiteSamplesBySex;
        window.parasiteSamplesBySpecies = parasiteSamplesBySpecies;
        window.parasiteSamplesByGenus = parasiteSamplesByGenus;
        window.parasiteSamplesBySampleType = parasiteSamplesBySampleType;
        window.pieChartTabs = pieChartTabs;
        window.barChartTabs = barChartTabs;
        window.mapColorVariableOptions = mapColorVariableOptions;
        window.mapPointsUrl = mapPointsUrl;
        window.activeFilters = activeFilters;
        window.modalTableUrls = modalTableUrls;

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.getElementById(modalId).classList.add('flex');
            window.loadParasiteDashboardModal?.(modalId);
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('flex');
            document.getElementById(modalId).classList.add('hidden');
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modals = ['samplesModal', 'humanSamplesModal', 'animalSamplesModal', 'environmentSamplesModal'];

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
        <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
        <script src="/js/show-parasite-samples.js"></script>
    @endpush

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        .parasite-filters label {
            color: #334155;
            font-weight: 600;
        }

        .parasite-filters select,
        .parasite-filters input[type='date'] {
            border-color: #dbeafe !important;
            background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
            border-radius: 0.75rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
        }

        .parasite-filters select:focus,
        .parasite-filters input[type='date']:focus {
            border-color: #6366f1 !important;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.15);
        }

        .chart-tab-btn.is-active {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #ffffff;
        }

        .map-tab-btn.is-active {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
        }

        .dashboard-box.is-expanded {
            position: fixed;
            inset: 1.5rem;
            z-index: 80;
            overflow: auto;
            box-shadow: 0 20px 80px rgba(15, 23, 42, 0.35);
        }

        .dashboard-box-tools {
            position: absolute;
            top: 0.75rem;
            right: 0.75rem;
            display: flex;
            gap: 0.35rem;
            z-index: 5;
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(4px);
            border: 1px solid #e5e7eb;
            border-radius: 0.6rem;
            padding: 0.2rem;
        }

        .icon-btn {
            width: 1.9rem;
            height: 1.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .format-btn::after {
            content: attr(data-format);
            font-size: 8px;
            font-weight: 700;
            line-height: 1;
            margin-left: 3px;
            text-transform: uppercase;
        }

        .dashboard-box.is-expanded .h-72 {
            height: 65vh !important;
        }

        .dashboard-box.is-expanded .h-96 {
            height: 70vh !important;
        }

        #pieLegendScroller::-webkit-scrollbar {
            height: 8px;
        }

        #pieLegendScroller::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 999px;
        }

        #pieLegendScroller::-webkit-scrollbar-thumb {
            background: #94a3b8;
            border-radius: 999px;
        }

        body.dashboard-box-open {
            overflow: hidden;
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
