<div>
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public pools from all projects</p>
                </div>
                <a href="/my-projects"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif

    <!-- Create, List (Centered) -->
    <div class="relative flex justify-center items-center space-x-4 mt-2 mb-4">
        @if (!$isGuestMode)
            <a href="/samples/pools"
                class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                <i class="fas fa-arrow-left text-2xl mr-2"></i>
                <span class="text-sm font-medium">Back to PO Home</span>
            </a>
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/samples/pools/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @endif
            <a href="/samples/pools/list"
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
                <a href="/samples/pools/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            </div>
        @endif
    </div>

    <!-- Header Section -->
    <div class="text-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Pools Dashboard</h1>
        <p class="text-gray-600">Overview of pooled samples and their distribution</p>
    </div>

    <!-- Filters Section -->
    <div data-dashboard-filter-root class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8" x-data="{ open: true }">
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
                <!-- Pool Content Type -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pool content</label>
                    <select wire:model.live.debounce.300ms="contentTypeFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="all">Any content type</option>
                        @foreach ($availableContentTypes as $key => $label)
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

                @if (in_array($contentTypeFilter, ['parasite', 'nucleic', 'culture', 'pool'], true))
                    <div class="md:col-span-2 lg:col-span-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800 mb-3">
                                <i class="fas fa-diagram-project text-slate-500"></i>
                                Trace-to-primary filters
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Primary type</label>
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

                <!-- Nr pooled range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nr pooled</label>
                    <div class="flex space-x-2">
                        <input type="number" min="0" step="1" wire:model.live.debounce.300ms="minNrPooled"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Min">
                        <input type="number" min="0" step="1" wire:model.live.debounce.300ms="maxNrPooled"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Max">
                    </div>
                </div>

                <!-- Laboratory Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Laboratory</label>
                    <select wire:model.live.debounce.300ms="laboratoryFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All laboratories</option>
                        @foreach ($allLaboratories as $lab)
                            <option value="{{ $lab }}">{{ $lab }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pooling Date Range</label>
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
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Pools</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $descriptive_stats['total_pools'] }}</p>
                </div>
                <div class="p-3 bg-indigo-100 rounded-full">
                    <i class="fas fa-layer-group text-2xl text-indigo-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pools with Tubes</p>
                    <p class="text-3xl font-bold text-blue-600">{{ $descriptive_stats['pools_with_tubes'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-vial text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pools This Year</p>
                    <p class="text-3xl font-bold text-green-600">{{ $descriptive_stats['pools_this_year'] }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-calendar-alt text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pools This Month</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $descriptive_stats['pools_this_month'] }}</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-calendar-day text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100" wire:ignore>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Pools by Laboratory</h3>
            <div class="h-64">
                <canvas id="laboratoriesChart"></canvas>
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100" wire:ignore>
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Pooling Timeline</h3>
            <div class="flex flex-wrap gap-4 px-3 py-2" wire:ignore.self>
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
            <div class="h-64">
                <canvas id="timelineChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Map Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8" wire:ignore>
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Pools Distribution</h3>
        <div class="h-96">
            <div id="map" class="w-full h-full rounded-lg"></div>
        </div>
    </div>

    <script>
        window.timelineData = @json($descriptive_stats['pooling_timeline']);
        window.poolsByLaboratory = @json($poolsByLaboratory);
        window.mapPointsUrl = @json($mapPointsUrl);
        window.activeFilters = @json($activeFilters);
    </script>

    @push('scripts')
        <script src="/js/show-pools.js?v={{ filemtime(public_path('js/show-pools.js')) }}"></script>
    @endpush

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</div>
