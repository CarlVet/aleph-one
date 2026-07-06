<div>

    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public experiments from all projects</p>
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
            <a href="/experiments"
                class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                <i class="fas fa-arrow-left text-2xl mr-2"></i>
                <span class="text-sm font-medium">Back to EX Home</span>
            </a>
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/experiments/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @endif
            <a href="/experiments/list"
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
                <a href="/experiments/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            </div>
        @endif
    </div>

    <!-- Header Section -->
    <div class="text-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Experiments Dashboard</h1>
        <p class="text-gray-600">Overview of all experiments and their outcomes</p>
    </div>

    <!-- Filters Section -->
    <div id="experiments-dashboard-filters" class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8" x-data="{ open: true }">
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
                <!-- Experiment Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Experiment Type</label>
                    <select data-dashboard-filter-model="experimentTypeFilter" data-current-value="{{ $experimentTypeFilter }}"
                        data-dashboard-selectize="1"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="all">All Types</option>
                        @foreach ($allExperimentTypes as $type)
                            <option value="{{ $type }}">{{ class_basename($type) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sub-project</label>
                    <select data-dashboard-filter-model="subProjectFilter" data-current-value="{{ $subProjectFilter }}"
                        data-dashboard-selectize="1"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All sub-projects</option>
                        @foreach ($allSubProjects as $subProjectCode)
                            <option value="{{ $subProjectCode }}">{{ $subProjectCode }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sampling site</label>
                    <select data-dashboard-filter-model="samplingSiteFilter" data-current-value="{{ $samplingSiteFilter }}"
                        data-dashboard-selectize="1"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All sampling sites</option>
                        @foreach ($allSamplingSitesForExperiments as $site)
                            <option value="{{ $site }}">{{ $site }}</option>
                        @endforeach
                    </select>
                </div>

                @php
                    $normalizedExperimentTypeFilter = str_starts_with((string) $experimentTypeFilter, 'AppModels')
                        ? 'App\\Models\\' . substr((string) $experimentTypeFilter, strlen('AppModels'))
                        : (string) $experimentTypeFilter;
                @endphp

                @if (class_basename($normalizedExperimentTypeFilter) === 'AnimalSamples')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Animal species</label>
                        <select data-dashboard-filter-model="animalSpeciesFilter" data-current-value="{{ $animalSpeciesFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All species</option>
                            @foreach ($allAnimalSpeciesForExperiments as $species)
                                <option value="{{ $species }}">{{ $species }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Animal sex</label>
                        <select data-dashboard-filter-model="animalSexFilter" data-current-value="{{ $animalSexFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All sexes</option>
                            @foreach ($allAnimalSexes as $sex)
                                <option value="{{ $sex }}">{{ $sex }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (class_basename($normalizedExperimentTypeFilter) === 'ParasiteSamples')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parasite species</label>
                        <select data-dashboard-filter-model="parasiteSpeciesFilter" data-current-value="{{ $parasiteSpeciesFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All species</option>
                            @foreach ($allParasiteSpeciesForExperiments as $species)
                                <option value="{{ $species }}">{{ $species }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parasite stage</label>
                        <select data-dashboard-filter-model="parasiteStageFilter" data-current-value="{{ $parasiteStageFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All stages</option>
                            @foreach ($allParasiteStagesForExperiments as $stage)
                                <option value="{{ $stage }}">{{ $stage }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parasite sex</label>
                        <select data-dashboard-filter-model="parasiteSexFilter" data-current-value="{{ $parasiteSexFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All sexes</option>
                            @foreach ($allParasiteSexesForExperiments as $sex)
                                <option value="{{ $sex }}">{{ $sex }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parasite state</label>
                        <select data-dashboard-filter-model="parasiteStateFilter" data-current-value="{{ $parasiteStateFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All states</option>
                            @foreach ($allParasiteStatesForExperiments as $state)
                                <option value="{{ $state }}">{{ $state }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Parasite sample type</label>
                        <select data-dashboard-filter-model="parasiteSampleTypeFilter" data-current-value="{{ $parasiteSampleTypeFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All sample types</option>
                            @foreach ($allParasiteSampleTypesForExperiments as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (class_basename($normalizedExperimentTypeFilter) === 'Cultures')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Culture type</label>
                        <select data-dashboard-filter-model="cultureTypeFilter" data-current-value="{{ $cultureTypeFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All types</option>
                            @foreach ($allCultureTypesForExperiments as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Culture medium</label>
                        <select data-dashboard-filter-model="cultureMediumFilter" data-current-value="{{ $cultureMediumFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All media</option>
                            @foreach ($allCultureMediumsForExperiments as $medium)
                                <option value="{{ $medium }}">{{ $medium }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (class_basename($normalizedExperimentTypeFilter) === 'NucleicAcids')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nucleic type</label>
                        <select data-dashboard-filter-model="nucleicTypeFilter" data-current-value="{{ $nucleicTypeFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All types</option>
                            @foreach ($allNucleicTypesForExperiments as $type)
                                <option value="{{ $type }}">{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (class_basename($normalizedExperimentTypeFilter) === 'Pools')
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pool content type</label>
                        <select data-dashboard-filter-model="poolContentTypeFilter" data-current-value="{{ $poolContentTypeFilter }}"
                            data-dashboard-selectize="1"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="all">Any</option>
                            @foreach ($availablePoolContentTypesForExperiments as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pool min nr pooled</label>
                        <input type="number" min="1" wire:model.live.debounce.300ms="poolMinNrPooledFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Min">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Pool max nr pooled</label>
                        <input type="number" min="1" wire:model.live.debounce.300ms="poolMaxNrPooledFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Max">
                    </div>
                @endif

                @if (in_array(class_basename($normalizedExperimentTypeFilter), ['ParasiteSamples', 'NucleicAcids', 'Cultures', 'Pools'], true))
                    <div class="md:col-span-2 lg:col-span-3">
                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                            <div class="flex items-center gap-2 text-sm font-semibold text-slate-800 mb-3">
                                <i class="fas fa-diagram-project text-slate-500"></i>
                                Trace-to-primary filters
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Primary type</label>
                                    <select data-dashboard-filter-model="tracePrimaryTypeFilter" data-current-value="{{ $tracePrimaryTypeFilter }}"
                                        data-dashboard-selectize="1"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="all">Any primary</option>
                                        @foreach ($availableTraceTypes as $key => $label)
                                            <option value="{{ $key }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                    @if (class_basename($normalizedExperimentTypeFilter) === 'Pools' && empty($availableTraceTypes) && ! empty($poolsWithMissingPoolContents))
                                        <div class="mt-2 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                                            <div class="flex items-start gap-2">
                                                <i class="fas fa-triangle-exclamation mt-0.5 text-amber-700"></i>
                                                <div>
                                                    <div class="font-semibold">Trace options unavailable</div>
                                                    <div>
                                                        These Pools have no linked <span class="font-mono">pool_contents</span>, so there’s nothing to trace:
                                                        <span class="font-mono">{{ implode(', ', $poolsWithMissingPoolContents) }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                @if ($tracePrimaryTypeFilter === 'animal')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary animal species</label>
                                        <select data-dashboard-filter-model="tracePrimaryAnimalSpeciesFilter" data-current-value="{{ $tracePrimaryAnimalSpeciesFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All species</option>
                                            @foreach ($tracePrimaryAnimalSpeciesOptions as $species)
                                                <option value="{{ $species }}">{{ $species }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary animal sex</label>
                                        <select data-dashboard-filter-model="tracePrimaryAnimalSexFilter" data-current-value="{{ $tracePrimaryAnimalSexFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All sexes</option>
                                            @foreach ($tracePrimaryAnimalSexesOptions as $sex)
                                                <option value="{{ $sex }}">{{ $sex }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary animal age</label>
                                        <select data-dashboard-filter-model="tracePrimaryAnimalAgeFilter" data-current-value="{{ $tracePrimaryAnimalAgeFilter }}"
                                            data-dashboard-selectize="1"
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
                                        <select data-dashboard-filter-model="tracePrimaryHumanEthnicityFilter" data-current-value="{{ $tracePrimaryHumanEthnicityFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All ethnicities</option>
                                            @foreach ($tracePrimaryHumanEthnicitiesOptions as $ethnicity)
                                                <option value="{{ $ethnicity }}">{{ $ethnicity }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary human occupation</label>
                                        <select data-dashboard-filter-model="tracePrimaryHumanOccupationFilter" data-current-value="{{ $tracePrimaryHumanOccupationFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All occupations</option>
                                            @foreach ($tracePrimaryHumanOccupationsOptions as $occupation)
                                                <option value="{{ $occupation }}">{{ $occupation }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Primary human country</label>
                                        <select data-dashboard-filter-model="tracePrimaryHumanCountryFilter" data-current-value="{{ $tracePrimaryHumanCountryFilter }}"
                                            data-dashboard-selectize="1"
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
                                        <select data-dashboard-filter-model="tracePrimaryParasiteSpeciesFilter" data-current-value="{{ $tracePrimaryParasiteSpeciesFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All species</option>
                                            @foreach ($allTraceParasiteSpecies as $species)
                                                <option value="{{ $species }}">{{ $species }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Parasite stage</label>
                                        <select data-dashboard-filter-model="tracePrimaryParasiteStageFilter" data-current-value="{{ $tracePrimaryParasiteStageFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All stages</option>
                                            @foreach ($allTraceParasiteStages as $stage)
                                                <option value="{{ $stage }}">{{ $stage }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Parasite sex</label>
                                        <select data-dashboard-filter-model="tracePrimaryParasiteSexFilter" data-current-value="{{ $tracePrimaryParasiteSexFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All sexes</option>
                                            @foreach ($allTraceParasiteSexes as $sex)
                                                <option value="{{ $sex }}">{{ $sex }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Parasite state</label>
                                        <select data-dashboard-filter-model="tracePrimaryParasiteStateFilter" data-current-value="{{ $tracePrimaryParasiteStateFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All states</option>
                                            @foreach ($allTraceParasiteStates as $state)
                                                <option value="{{ $state }}">{{ $state }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Parasite sample type</label>
                                        <select data-dashboard-filter-model="tracePrimaryParasiteSampleTypeFilter" data-current-value="{{ $tracePrimaryParasiteSampleTypeFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All sample types</option>
                                            @foreach ($allTraceParasiteSampleTypes as $type)
                                                <option value="{{ $type }}">{{ $type }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if ($tracePrimaryTypeFilter === 'culture')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Culture type</label>
                                        <select data-dashboard-filter-model="tracePrimaryCultureTypeFilter" data-current-value="{{ $tracePrimaryCultureTypeFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All types</option>
                                            @foreach ($allTraceCultureTypes as $type)
                                                <option value="{{ $type }}">{{ $type }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Culture medium</label>
                                        <select data-dashboard-filter-model="tracePrimaryCultureMediumFilter" data-current-value="{{ $tracePrimaryCultureMediumFilter }}"
                                            data-dashboard-selectize="1"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">All media</option>
                                            @foreach ($allTraceCultureMediums as $medium)
                                                <option value="{{ $medium }}">{{ $medium }}</option>
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
                                        <i class="fas fa-magnifying-glass text-slate-500"></i>
                                        Trace further back to the primary sample
                                    </div>

                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Primary sample type</label>
                                            <select data-dashboard-filter-model="traceDeepPrimaryTypeFilter" data-current-value="{{ $traceDeepPrimaryTypeFilter }}"
                                            data-dashboard-selectize="1"
                                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                <option value="all">Any</option>
                                                @foreach ($availableDeepPrimaryTypes as $key => $label)
                                                    <option value="{{ $key }}">{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        @if ($traceDeepPrimaryTypeFilter === 'animal')
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Animal species</label>
                                                <select data-dashboard-filter-model="traceDeepAnimalSpeciesFilter" data-current-value="{{ $traceDeepAnimalSpeciesFilter }}"
                                                    data-dashboard-selectize="1"
                                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    <option value="">All species</option>
                                                    @foreach ($traceDeepAnimalSpeciesOptions as $species)
                                                        <option value="{{ $species }}">{{ $species }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Animal sex</label>
                                                <select data-dashboard-filter-model="traceDeepAnimalSexFilter" data-current-value="{{ $traceDeepAnimalSexFilter }}"
                                                    data-dashboard-selectize="1"
                                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    <option value="">All sexes</option>
                                                    @foreach ($traceDeepAnimalSexesOptions as $sex)
                                                        <option value="{{ $sex }}">{{ $sex }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Animal age</label>
                                                <select data-dashboard-filter-model="traceDeepAnimalAgeFilter" data-current-value="{{ $traceDeepAnimalAgeFilter }}"
                                                    data-dashboard-selectize="1"
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
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Ethnicity</label>
                                                <select data-dashboard-filter-model="traceDeepHumanEthnicityFilter" data-current-value="{{ $traceDeepHumanEthnicityFilter }}"
                                                    data-dashboard-selectize="1"
                                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    <option value="">All ethnicities</option>
                                                    @foreach ($traceDeepHumanEthnicitiesOptions as $ethnicity)
                                                        <option value="{{ $ethnicity }}">{{ $ethnicity }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Occupation</label>
                                                <select data-dashboard-filter-model="traceDeepHumanOccupationFilter" data-current-value="{{ $traceDeepHumanOccupationFilter }}"
                                                    data-dashboard-selectize="1"
                                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    <option value="">All occupations</option>
                                                    @foreach ($traceDeepHumanOccupationsOptions as $occupation)
                                                        <option value="{{ $occupation }}">{{ $occupation }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                                                <select data-dashboard-filter-model="traceDeepHumanCountryFilter" data-current-value="{{ $traceDeepHumanCountryFilter }}"
                                                    data-dashboard-selectize="1"
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

                <!-- Date Range Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date Range</label>
                    <div class="flex space-x-2">
                        <input type="date" wire:model.live.debounce.300ms="startDate"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Start Date">
                        <input type="date" wire:model.live.debounce.300ms="endDate"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="End Date">
                    </div>
                </div>

                

                <!-- Protocol Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Protocol</label>
                    <select data-dashboard-filter-model="protocolFilter" data-current-value="{{ $protocolFilter }}"
                        data-dashboard-selectize="1"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Protocols</option>
                        @foreach ($allProtocols as $protocol)
                            <option value="{{ $protocol }}">{{ $protocol }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Technique type</label>
                    <select data-dashboard-filter-model="techniqueTypeFilter" data-current-value="{{ $techniqueTypeFilter }}"
                        data-dashboard-selectize="1"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All technique types</option>
                        @foreach ($allTechniqueTypes as $techniqueType)
                            <option value="{{ $techniqueType }}">{{ $techniqueType }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Technique category</label>
                    <select data-dashboard-filter-model="techniqueCategoryFilter" data-current-value="{{ $techniqueCategoryFilter }}"
                        data-dashboard-selectize="1"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All technique categories</option>
                        @foreach ($allTechniqueCategories as $techniqueCategory)
                            <option value="{{ $techniqueCategory }}">{{ $techniqueCategory }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pathogen</label>
                    <select data-dashboard-filter-model="pathogenFilter" data-current-value="{{ $pathogenFilter }}"
                        data-dashboard-selectize="1"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Pathogens</option>
                        @foreach ($allPathogens as $pathogen)
                            <option value="{{ $pathogen }}">{{ $pathogen }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Outcome Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Outcome</label>
                    <select data-dashboard-filter-model="outcomeFilter" data-current-value="{{ $outcomeFilter }}"
                        data-dashboard-selectize="1"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Outcomes</option>
                        @foreach ($allOutcomes as $outcome)
                            <option value="{{ $outcome }}">{{ $outcome }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Test Purpose Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Test purpose</label>
                    <select data-dashboard-filter-model="purposeFilter" data-current-value="{{ $purposeFilter }}"
                        data-dashboard-selectize="1"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All purposes</option>
                        <option value="screening">Screening only</option>
                        <option value="confirmation">Confirmation only</option>
                        <option value="either">Screening or confirmation</option>
                        <option value="screening_with_confirmation">Screening with confirmation</option>
                    </select>
                </div>

            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
        <!-- Total Experiments -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('experimentsModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Experiments</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $descriptive_stats['total_experiments'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-flask text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Pathogens -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('pathogensModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Pathogens</p>
                    <p class="text-3xl font-bold text-red-600">{{ count($topPathogens) }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-virus text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <!-- Protocols -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('protocolsModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Protocols</p>
                    <p class="text-3xl font-bold text-green-600">{{ count($topProtocols) }}</p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-clipboard-list text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Sampling Sites -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('samplingSitesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Sampling Sites</p>
                    <p id="samplingSitesCount" wire:ignore class="text-3xl font-bold text-purple-600">0</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-map-marker-alt text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <!-- Prevalence -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('prevalenceModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Prevalence</p>
                    <p class="text-3xl font-bold text-indigo-600">
                        {{ data_get($prevalenceSummary, 'percentage', 0) }}%
                    </p>
                </div>
                <div class="p-3 bg-indigo-100 rounded-full">
                    <i class="fas fa-percent text-2xl text-indigo-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div id="pieBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div id="pieContent" data-expand-content-root data-expand-title="Experiments by distribution">
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
                        <button type="button" data-expand-target="pieContent"
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
        </div>

        <div id="barBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <div id="barContent" data-expand-content-root data-expand-title="Experiments by counts">
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
                        <button type="button" data-expand-target="barContent"
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
    </div>

    <!-- Map Section -->
    <div id="mapBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
        <div id="mapContent" data-expand-content-root data-expand-title="Experiments distribution map">
            <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
                <h3 class="map-inline-title text-lg font-semibold text-gray-800">Experiments Distribution</h3>
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
            <div class="relative h-96" wire:ignore>
                <div class="dashboard-box-tools">
                    <button type="button" data-expand-target="mapContent"
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
                <div id="mapLoadingOverlay"
                    class="pointer-events-none absolute inset-0 z-[480] hidden items-center justify-center rounded-lg bg-white/90 backdrop-blur-sm">
                    <div class="w-full max-w-sm px-6 text-center">
                        <div class="mb-3 inline-flex h-11 w-11 items-center justify-center rounded-full bg-indigo-50 text-indigo-600">
                            <i id="mapLoadingSpinner" class="fas fa-spinner fa-spin text-lg"></i>
                        </div>
                        <p id="mapLoadingMessage" class="text-sm font-medium text-gray-800">Loading map data...</p>
                        <p id="mapLoadingDetail" class="mt-1 text-xs text-gray-500"></p>
                        <div class="map-loading-track mt-4 h-2 w-full rounded-full bg-gray-200">
                            <div id="mapLoadingProgressBar"
                                class="h-full rounded-full bg-indigo-600 transition-[width] duration-300 ease-out"
                                style="width: 0%;"></div>
                        </div>
                        <p id="mapLoadingPercent" class="mt-2 text-xs font-semibold text-indigo-700">0%</p>
                    </div>
                </div>
                <div id="map" class="w-full h-full rounded-lg"></div>
                <div id="mapLegend"
                    wire:ignore
                    class="pointer-events-none absolute inset-x-3 bottom-3 z-[450] ml-auto max-w-md rounded-xl border border-gray-200 bg-white/95 px-3 py-2 shadow-lg backdrop-blur-sm">
                </div>
            </div>
            <div class="mt-2 text-xs text-gray-500">
                Tip: cluster circles are pie charts showing mixed category composition at the same location.
            </div>
        </div>
    </div>

    <div id="dashboardExpandModal" class="fixed inset-0 z-[70] hidden">
        <div class="absolute inset-0 bg-slate-950/70" data-expand-close></div>
        <div class="relative mx-auto flex h-full w-full max-w-7xl items-center justify-center p-4 sm:p-6">
            <div class="dashboard-expand-surface flex h-full w-full flex-col overflow-hidden rounded-2xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <h3 id="dashboardExpandTitle" class="text-lg font-semibold text-slate-900">Expanded chart</h3>
                    <button type="button" data-expand-close
                        class="inline-flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="dashboardExpandBody" class="dashboard-expand-body min-h-0 flex-1 overflow-auto p-5"></div>
            </div>
        </div>
    </div>

    <!-- Processing Status and Timeline Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-4" wire:ignore>

        <!-- Testing Timeline Chart -->
        <div>
            <h4 class="text-md font-semibold text-gray-700 mb-2">Experiment Timeline</h4>
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


    <!-- Modal for All Experiments -->
    <div id="experimentsModal" wire:ignore.self
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">All Experiments in Filtered Dataset</h3>
                <button onclick="closeModal('experimentsModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Experiment Code</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sample Code</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Sample Type</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Protocol</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pathogen</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Laboratory</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Outcome</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date Tested</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($all_experiments as $experiment)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">
                                    <a href="/experiments/{{ $experiment->code }}"
                                        class="text-blue-600 hover:text-blue-800">
                                        {{ $experiment->code ?? 'N/A' }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-700">
                                    {{ $experiment->sample_code ?? 'N/A' }}
                                    @if (!empty($experiment->sample_alias))
                                        <span class="text-gray-400">({{ $experiment->sample_alias }})</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    {{ class_basename($experiment->experiments_content_type) ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    {{ $experiment->protocols->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500 italic">
                                    {{ $experiment->pathogens->species ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    {{ $experiment->laboratories->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm">
                                    @if ($experiment->outcome_discrete)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        @if ($experiment->outcome_discrete == 'Positive' || $experiment->outcome_discrete == 'Strong positive') bg-red-100 text-red-800
                                        @elseif($experiment->outcome_discrete == 'Suspect') bg-yellow-100 text-yellow-800
                                        @else bg-green-100 text-green-800 @endif">
                                            {{ $experiment->outcome_discrete }}
                                        </span>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    {{ $experiment->date_tested ? \Carbon\Carbon::parse($experiment->date_tested)->format('Y-m-d') : 'N/A' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if (method_exists($all_experiments, 'links'))
                <div class="mt-4">
                    {{ $all_experiments->onEachSide(1)->links('livewire::tailwind') }}
                </div>
            @endif
        </div>
    </div>

    <!-- Modal for Pathogens -->
    <div id="pathogensModal" wire:ignore.self
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Pathogens in Filtered Dataset</h3>
                <button onclick="closeModal('pathogensModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pathogen Species</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Experiments</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($topPathogens as $pathogen => $count)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900 italic">{{ $pathogen }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Protocols -->
    <div id="protocolsModal" wire:ignore.self
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Protocols in Filtered Dataset</h3>
                <button onclick="closeModal('protocolsModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Protocol Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Experiments</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($topProtocols as $protocol => $count)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $protocol }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Sampling Sites -->
    <div id="samplingSitesModal" wire:ignore.self
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Sampling Sites in Filtered Dataset</h3>
                <button onclick="closeModal('samplingSitesModal')" class="text-gray-500 hover:text-gray-700">
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
                                Experiments</th>
                        </tr>
                    </thead>
                    <tbody id="samplingSitesModalBody" wire:ignore class="bg-white divide-y divide-gray-200">
                        <tr>
                            <td class="px-4 py-3 text-sm text-gray-500" colspan="2">Loading…</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Laboratories -->
    <div id="laboratoriesModal" wire:ignore.self
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Laboratories in Filtered Dataset</h3>
                <button onclick="closeModal('laboratoriesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Laboratory Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Experiments</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($experimentsByLab as $lab => $count)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $lab }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Prevalence -->
    <div id="prevalenceModal" wire:ignore.self
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            @php($isScreeningConfirmation = ($screeningConfirmationMode ?? false))
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">
                    @if ($isScreeningConfirmation)
                        Confirmed Prevalence (Screening with Confirmation)
                    @else
                        Prevalence by Pathogen and Protocol
                    @endif
                </h3>
                <button onclick="closeModal('prevalenceModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            @if ($isScreeningConfirmation)
                <div class="mb-4 text-sm text-gray-700">
                    <span class="font-semibold">Confirmed prevalence:</span>
                    {{ data_get($prevalenceSummary, 'positive', 0) }} confirmed positive /
                    {{ data_get($prevalenceSummary, 'negative', 0) }} confirmed negative
                    ({{ data_get($prevalenceSummary, 'percentage', 0) }}% positive)
                </div>
                <p class="mb-4 text-xs text-gray-500">
                    Only samples tested with both a screening and a confirmation tool (per pathogen) are included.
                    A sample is a confirmed positive when at least one screening and at least one confirmation result are positive.
                    Prevalence is reported separately for each screening test.
                </p>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pathogen</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Screening test</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Confirmed with</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Confirmed positive</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prevalence (%)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse (($screeningConfirmationBreakdown ?? []) as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm text-gray-900 italic">{{ $row['pathogen'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $row['screening_test'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $row['confirmation_tests'] !== '' ? $row['confirmation_tests'] : '—' }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $row['positive_count'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $row['total_count'] }}</td>
                                    <td class="px-4 py-2 text-sm font-semibold text-indigo-700">{{ $row['prevalence'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-500" colspan="6">No samples with both screening and confirmation for current filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="mb-4 text-sm text-gray-700">
                    <span class="font-semibold">Filtered prevalence:</span>
                    {{ data_get($prevalenceSummary, 'positive', 0) }} positive /
                    {{ data_get($prevalenceSummary, 'negative', 0) }} negative
                    ({{ data_get($prevalenceSummary, 'percentage', 0) }}% positive)
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pathogen</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocol</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Positive</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prevalence (%)</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse ($prevalenceBreakdown as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm text-gray-900 italic">{{ $row['pathogen'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $row['protocol'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $row['positive_count'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-700">{{ $row['total_count'] }}</td>
                                    <td class="px-4 py-2 text-sm font-semibold text-indigo-700">{{ $row['prevalence'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="px-4 py-3 text-sm text-gray-500" colspan="5">No prevalence rows for current filters.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <script>
        const samples = @json($samples);
        const mapPointsUrl = @json($mapPointsUrl ?? null);
        const activeFilters = @json($activeFilters ?? []);
        const isGuestMode = @json($isGuestMode ?? false);
        const timelineData = @json($descriptive_stats['testing_timeline']);
        const experimentsByOutcome = @json($experimentsByOutcome);
        const topProtocols = @json($topProtocols);
        const pieChartTabs = @json($pieChartTabs ?? []);
        const barChartTabs = @json($barChartTabs ?? []);
        const mapColorVariableOptions = @json($mapColorVariableOptions ?? []);

        window.timelineData = timelineData;
        window.experimentsByOutcome = experimentsByOutcome;
        window.topProtocols = topProtocols;
        window.pieChartTabs = pieChartTabs;
        window.barChartTabs = barChartTabs;
        window.mapColorVariableOptions = mapColorVariableOptions;
        window.samples = samples;
        window.mapPointsUrl = mapPointsUrl;
        window.activeFilters = activeFilters;
        window.isGuestMode = isGuestMode;

        // Modal functions
        function openModal(modalId) {
            document.getElementById(modalId).classList.remove('hidden');
            document.getElementById(modalId).classList.add('flex');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('flex');
            document.getElementById(modalId).classList.add('hidden');
        }

        // Close modal when clicking outside
        document.addEventListener('DOMContentLoaded', function() {
            const modals = ['experimentsModal', 'pathogensModal', 'protocolsModal', 'laboratoriesModal', 'samplingSitesModal', 'prevalenceModal'];

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
        <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
        <script src="/js/show-experiments.js?v={{ filemtime(public_path('js/show-experiments.js')) }}"></script>
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

        .dashboard-box-tools {
            position: absolute;
            top: 0.6rem;
            right: 0.6rem;
            display: inline-flex;
            gap: 0.4rem;
            z-index: 5;
        }

        body.dashboard-box-open {
            overflow: hidden;
        }

        #experiments-dashboard-filters [data-dashboard-datalist-shell] {
            position: relative;
        }

        #experiments-dashboard-filters .dashboard-autocomplete-panel {
            scrollbar-width: thin;
            scrollbar-color: rgba(148, 163, 184, 0.75) transparent;
        }

        #experiments-dashboard-filters .dashboard-autocomplete-panel::-webkit-scrollbar {
            width: 8px;
        }

        #experiments-dashboard-filters .dashboard-autocomplete-panel::-webkit-scrollbar-track {
            background: transparent;
        }

        #experiments-dashboard-filters .dashboard-autocomplete-panel::-webkit-scrollbar-thumb {
            background: rgba(148, 163, 184, 0.8);
            border-radius: 9999px;
        }

        #experiments-dashboard-filters .dashboard-autocomplete-option {
            border: 0;
            background: transparent;
        }

        .dashboard-expand-surface [data-expand-content-root] {
            min-height: 100%;
        }

        .dashboard-expand-body .map-inline-title {
            display: none;
        }

        .dashboard-expand-surface .h-72 {
            height: min(70vh, 42rem);
        }

        .dashboard-expand-surface .h-96 {
            height: min(72vh, 46rem);
        }

        #mapLegend {
            max-height: 8rem;
            overflow: auto;
        }

        #mapLoadingOverlay.is-visible {
            display: flex;
        }

        #mapLoadingOverlay .map-loading-track {
            overflow: hidden;
        }

        #mapLoadingOverlay.is-indeterminate #mapLoadingProgressBar {
            width: 40% !important;
            animation: map-loading-indeterminate 1.2s ease-in-out infinite;
        }

        @keyframes map-loading-indeterminate {
            0% {
                transform: translateX(-120%);
            }

            100% {
                transform: translateX(320%);
            }
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
