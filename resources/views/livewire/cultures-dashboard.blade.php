<div>
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public cultures from all projects</p>
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
            <a href="/samples/cultures"
                class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                <i class="fas fa-arrow-left text-2xl mr-2"></i>
                <span class="text-sm font-medium">Back to CU Home</span>
            </a>
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/samples/cultures/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @endif
            <a href="/samples/cultures/list"
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
                <a href="/samples/cultures/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            </div>
        @endif
    </div>

    <!-- Header Section -->
    <div class="text-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Cultures Dashboard</h1>
        <p class="text-gray-600">Overview of all culture samples and their outcomes</p>
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
                <!-- Culture Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Culture Type</label>
                    <select wire:model.live.debounce.300ms="cultureTypeFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Types</option>
                        @foreach ($allCultureTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Source Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Content type (direct)</label>
                    <select wire:model.live.debounce.300ms="sourceTypeFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="all">All Sources</option>
                        @foreach ($availableSourceTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
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

                @if (in_array($sourceTypeFilter, ['parasite', 'pool', 'nucleic'], true))
                    <div class="md:col-span-2 lg:col-span-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800 mb-3">
                                <i class="fas fa-diagram-project text-slate-500"></i>
                                Trace-to-primary filters
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Trace-from type</label>
                                    <select wire:model.live.debounce.300ms="tracePrimaryTypeFilter"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="all">Any upstream</option>
                                        @foreach ($availableTracePrimaryTypes as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                @if ($tracePrimaryTypeFilter === 'animal')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary animal species</label>
                                        <select wire:model.live.debounce.300ms="tracePrimaryAnimalSpeciesFilter"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All species</option>
                                            @foreach ($tracePrimaryAnimalSpeciesOptions as $species)
                                                <option value="{{ $species }}">{{ $species }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary animal sex</label>
                                        <select wire:model.live.debounce.300ms="tracePrimaryAnimalSexFilter"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All sexes</option>
                                            @foreach ($tracePrimaryAnimalSexesOptions as $sex)
                                                <option value="{{ $sex }}">{{ $sex }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary animal age</label>
                                        <select wire:model.live.debounce.300ms="tracePrimaryAnimalAgeFilter"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All ages</option>
                                            @foreach ($tracePrimaryAnimalAgesOptions as $age)
                                                <option value="{{ $age }}">{{ $age }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if ($tracePrimaryTypeFilter === 'human')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary human ethnicity</label>
                                        <select wire:model.live.debounce.300ms="tracePrimaryHumanEthnicityFilter"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All ethnicities</option>
                                            @foreach ($tracePrimaryHumanEthnicitiesOptions as $ethnicity)
                                                <option value="{{ $ethnicity }}">{{ $ethnicity }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary human occupation</label>
                                        <select wire:model.live.debounce.300ms="tracePrimaryHumanOccupationFilter"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All occupations</option>
                                            @foreach ($tracePrimaryHumanOccupationsOptions as $occupation)
                                                <option value="{{ $occupation }}">{{ $occupation }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary human country</label>
                                        <select wire:model.live.debounce.300ms="tracePrimaryHumanCountryFilter"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All countries</option>
                                            @foreach ($tracePrimaryHumanCountriesOptions as $country)
                                                <option value="{{ $country }}">{{ $country }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if ($tracePrimaryTypeFilter === 'parasite')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Parasite species</label>
                                        <select wire:model.live.debounce.300ms="tracePrimaryParasiteSpeciesFilter"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All species</option>
                                            @foreach ($tracePrimaryParasiteSpeciesOptions as $species)
                                                <option value="{{ $species }}">{{ $species }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if ($tracePrimaryTypeFilter === 'culture')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Culture type</label>
                                        <select wire:model.live.debounce.300ms="tracePrimaryCultureTypeFilter"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All types</option>
                                            @foreach ($tracePrimaryCultureTypesOptions as $t)
                                                <option value="{{ $t }}">{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Culture medium</label>
                                        <select wire:model.live.debounce.300ms="tracePrimaryCultureMediumFilter"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All media</option>
                                            @foreach ($tracePrimaryCultureMediumsOptions as $m)
                                                <option value="{{ $m }}">{{ $m }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if ($tracePrimaryTypeFilter === 'nucleic')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Nucleic type</label>
                                        <select wire:model.live.debounce.300ms="tracePrimaryNucleicTypeFilter"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All types</option>
                                            @foreach ($tracePrimaryNucleicTypesOptions as $t)
                                                <option value="{{ $t }}">{{ $t }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if ($tracePrimaryTypeFilter === 'pool')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pool min nr pooled</label>
                                        <input type="number" min="1" wire:model.live.debounce.300ms="tracePrimaryPoolMinNrPooled"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                            placeholder="Min">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Pool max nr pooled</label>
                                        <input type="number" min="1" wire:model.live.debounce.300ms="tracePrimaryPoolMaxNrPooled"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                            placeholder="Max">
                                    </div>
                                @endif
                            </div>

                            @if (in_array($tracePrimaryTypeFilter, ['parasite', 'culture', 'nucleic', 'pool'], true))
                                <div class="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                                    <div class="flex items-center gap-2 text-sm font-semibold text-slate-800 mb-3">
                                        <i class="fas fa-route text-slate-500"></i>
                                        Trace further back to the primary sample
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Primary type</label>
                                            <select wire:model.live.debounce.300ms="traceDeepPrimaryTypeFilter"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                <option value="all">Any primary</option>
                                        @foreach ($availableTraceDeepPrimaryTypes as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                            </select>
                                        </div>

                                        @if ($traceDeepPrimaryTypeFilter === 'animal')
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Primary animal species</label>
                                                <select wire:model.live.debounce.300ms="traceDeepAnimalSpeciesFilter"
                                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    <option value="">All species</option>
                                                    @foreach ($traceDeepAnimalSpeciesOptions as $species)
                                                        <option value="{{ $species }}">{{ $species }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Primary animal sex</label>
                                                <select wire:model.live.debounce.300ms="traceDeepAnimalSexFilter"
                                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    <option value="">All sexes</option>
                                                    @foreach ($traceDeepAnimalSexesOptions as $sex)
                                                        <option value="{{ $sex }}">{{ $sex }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Primary animal age</label>
                                                <select wire:model.live.debounce.300ms="traceDeepAnimalAgeFilter"
                                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    <option value="">All ages</option>
                                                    @foreach ($traceDeepAnimalAgesOptions as $age)
                                                        <option value="{{ $age }}">{{ $age }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif

                                        @if ($traceDeepPrimaryTypeFilter === 'human')
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Primary human ethnicity</label>
                                                <select wire:model.live.debounce.300ms="traceDeepHumanEthnicityFilter"
                                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    <option value="">All ethnicities</option>
                                                    @foreach ($traceDeepHumanEthnicitiesOptions as $ethnicity)
                                                        <option value="{{ $ethnicity }}">{{ $ethnicity }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Primary human occupation</label>
                                                <select wire:model.live.debounce.300ms="traceDeepHumanOccupationFilter"
                                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    <option value="">All occupations</option>
                                                    @foreach ($traceDeepHumanOccupationsOptions as $occupation)
                                                        <option value="{{ $occupation }}">{{ $occupation }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Primary human country</label>
                                                <select wire:model.live.debounce.300ms="traceDeepHumanCountryFilter"
                                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    <option value="">All countries</option>
                                                    @foreach ($traceDeepHumanCountriesOptions as $country)
                                                        <option value="{{ $country }}">{{ $country }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Medium Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Medium</label>
                    <select wire:model.live.debounce.300ms="mediumFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Mediums</option>
                        @foreach ($allMediums as $medium)
                            <option value="{{ $medium }}">{{ $medium }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Pathogen Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pathogen tested</label>
                    <select wire:model.live.debounce.300ms="pathogenFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All pathogens</option>
                        @foreach ($allPathogens as $pathogen)
                            <option value="{{ $pathogen }}">{{ $pathogen }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Culturing Date Range</label>
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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <!-- Total Cultures -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('culturesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Cultures</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $descriptive_stats['total_samples'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-flask text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Active Cultures -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('activeCulturesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Active Cultures</p>
                    <p class="text-3xl font-bold text-emerald-600">{{ $descriptive_stats['active_cultures'] }}</p>
                </div>
                <div class="p-3 bg-emerald-100 rounded-full">
                    <i class="fas fa-seedling text-2xl text-emerald-600"></i>
                </div>
            </div>
        </div>

        <!-- Media Types -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('mediaTypesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Media Types</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $descriptive_stats['distinct_media'] }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-vial text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Average Time on Culture -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('cultureDurationModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Avg Days on Culture</p>
                    <p class="text-3xl font-bold text-amber-600">
                        {{ $descriptive_stats['average_days_on_culture'] !== null ? number_format($descriptive_stats['average_days_on_culture'], 1) : 'N/A' }}
                    </p>
                </div>
                <div class="p-3 bg-amber-100 rounded-full">
                    <i class="fas fa-clock text-2xl text-amber-600"></i>
                </div>
            </div>
        </div>

        <!-- Confirmed Pathogens -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('confirmedPathogensModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Confirmed Pathogens</p>
                    <p class="text-3xl font-bold text-red-600">{{ $descriptive_stats['confirmed_pathogen_results'] }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-virus text-2xl text-red-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div id="pieBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
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

        <div id="barBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
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
    <div id="mapBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
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

    <!-- Timeline Section -->
    <div class="dashboard-box bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-4">
        <h4 class="text-md font-semibold text-gray-700 mb-4">Culturing Timeline</h4>
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

    <!-- Modal for All Cultures -->
    <div id="culturesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-[95vw] w-full mx-4 max-h-[85vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">All Cultures in Filtered Dataset</h3>
                <button onclick="closeModal('culturesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            @include('livewire.partials.cultures-dashboard-culture-modal-table', [
                'cultures' => $all_cultures,
                'tableId' => 'culturesDashboardAllTable',
                'emptyMessage' => 'No cultures in the filtered dataset.',
                'showDuration' => false,
            ])
        </div>
    </div>

    <!-- Modal for Active Cultures -->
    <div id="activeCulturesModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-[95vw] w-full mx-4 max-h-[85vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Active Cultures</h3>
                <button onclick="closeModal('activeCulturesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            @include('livewire.partials.cultures-dashboard-culture-modal-table', [
                'cultures' => $active_cultures,
                'tableId' => 'culturesDashboardActiveTable',
                'emptyMessage' => 'No active cultures in the filtered dataset.',
                'showDuration' => false,
            ])
        </div>
    </div>

    <!-- Modal for Media Types -->
    <div id="mediaTypesModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Media Types in Filtered Dataset</h3>
                <button onclick="closeModal('mediaTypesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Medium</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Culture Count</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($culturesByMedium as $medium => $count)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $medium }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-6 text-center text-sm text-gray-500">No media recorded in the filtered dataset.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Culture Duration -->
    <div id="cultureDurationModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-[95vw] w-full mx-4 max-h-[85vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Time on Culture</h3>
                    <p class="text-sm text-gray-500">Days from culturing date to discard date or today for active cultures.</p>
                </div>
                <button onclick="closeModal('cultureDurationModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            @include('livewire.partials.cultures-dashboard-culture-modal-table', [
                'cultures' => $culture_duration_cultures,
                'tableId' => 'culturesDashboardDurationTable',
                'emptyMessage' => 'No cultures with culturing dates in the filtered dataset.',
                'showDuration' => true,
            ])
        </div>
    </div>

    <!-- Modal for Confirmed Pathogens -->
    <div id="confirmedPathogensModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-lg font-semibold text-gray-800">Confirmed Pathogen Results</h3>
                    <p class="text-sm text-gray-500">Positive or strong positive experiments on culture tubes or nucleic acids extracted from cultures.</p>
                </div>
                <button onclick="closeModal('confirmedPathogensModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Culture Code</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pathogen</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outcome</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Test Source</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Tested</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse ($confirmed_pathogen_rows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                    <a href="/samples/cultures/{{ $row->culture_code }}" class="text-blue-600 hover:text-blue-800">
                                        {{ $row->culture_code ?? 'N/A' }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900 italic">{{ $row->pathogen ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->outcome ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->test_source ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    {{ $row->date_tested ? \Carbon\Carbon::parse($row->date_tested)->format('Y-m-d') : 'N/A' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">No confirmed pathogen results in the filtered dataset.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const samples = @json($samples);
        const timelineData = @json($descriptive_stats['culturing_timeline']);
        const culturesBySource = @json($culturesBySource);
        const culturesByType = @json($culturesByType);
        const culturesByLaboratory = @json($culturesByLaboratory);
        const culturesByMedium = @json($culturesByMedium);
        const culturesByCulturedBy = @json($culturesByCulturedBy ?? []);
        const pieChartTabs = @json($pieChartTabs ?? []);
        const barChartTabs = @json($barChartTabs ?? []);
        const mapColorVariableOptions = @json($mapColorVariableOptions ?? []);
        const allCultures = @json($all_cultures);

        window.timelineData = timelineData;
        window.culturesBySource = culturesBySource;
        window.culturesByType = culturesByType;
        window.culturesByLaboratory = culturesByLaboratory;
        window.culturesByMedium = culturesByMedium;
        window.culturesByCulturedBy = culturesByCulturedBy;
        window.pieChartTabs = pieChartTabs;
        window.barChartTabs = barChartTabs;
        window.mapColorVariableOptions = mapColorVariableOptions;
        window.samples = samples;
        window.allCultures = allCultures;
        window.activeFilters = @json($activeFilters ?? []);

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.getElementById(modalId).classList.add('flex');
            if (typeof window.initCulturesDashboardModalTableFilters === 'function') {
                window.initCulturesDashboardModalTableFilters();
            }
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('flex');
            document.getElementById(modalId).classList.add('hidden');
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modals = ['culturesModal', 'activeCulturesModal', 'mediaTypesModal', 'cultureDurationModal', 'confirmedPathogensModal'];

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
        <script src="/js/show-cultures.js?v={{ filemtime(public_path('js/show-cultures.js')) }}"></script>
    @endpush

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
        .chart-tab-btn.is-active {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #ffffff;
            box-shadow: 0 8px 20px -10px rgba(79, 70, 229, 0.65);
        }

        .map-tab-btn.is-active {
            background: #0f172a;
            border-color: #0f172a;
            color: #ffffff;
            box-shadow: 0 8px 20px -12px rgba(15, 23, 42, 0.75);
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
            flex-shrink: 0;
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

        .dashboard-box.is-expanded .h-72 {
            height: calc(100vh - 14rem);
        }

        .dashboard-box.is-expanded .h-96 {
            height: calc(100vh - 14rem);
        }

        body.dashboard-box-open {
            overflow: hidden;
        }
    </style>

</div>
