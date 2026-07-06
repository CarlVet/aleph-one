<div>

    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public literature studies from all projects</p>
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
            <a href="/meta"
                class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                <i class="fas fa-arrow-left text-2xl mr-2"></i>
                <span class="text-sm font-medium">Back to Meta Home</span>
            </a>
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/meta/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @endif
            <a href="/meta/list/animal"
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
                <a href="/meta/list/animal"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            </div>
        @endif
    </div>

    <!-- Header Section -->
    <div class="text-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Literature Dashboard</h1>
        <p class="text-gray-600">Overview of all literature studies and their findings</p>
    </div>

    <!-- Filters Section -->
    <div data-dashboard-filter-root class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8" x-data="{ open: true }">
        <div class="flex items-center justify-between gap-4 mb-4">
            <button type="button"
                class="inline-flex items-center gap-2 text-lg font-semibold text-gray-800 hover:text-gray-900"
                x-on:click="open = !open" x-bind:aria-expanded="open.toString()">
                <i class="fas fa-chevron-down text-sm transition-transform duration-200"
                    x-bind:class="open ? 'rotate-180' : ''"></i>
                <span>Filters</span>
            </button>
            <button wire:click="resetFilters"
                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-200 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                <i class="fas fa-rotate-left mr-2"></i>
                Reset filters
            </button>
        </div>
        <div x-show="open" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <!-- Meta Type Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Study Type</label>
                    <select wire:model.live="metaTypeFilter" data-dashboard-placeholder-current="true"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="MetaAnimal" @selected($metaTypeFilter === 'MetaAnimal')>MetaAnimal</option>
                        <option value="MetaHuman" @selected($metaTypeFilter === 'MetaHuman')>MetaHuman</option>
                        <option value="MetaParasite" @selected($metaTypeFilter === 'MetaParasite')>MetaParasite</option>
                        <option value="MetaEnvironment" @selected($metaTypeFilter === 'MetaEnvironment')>MetaEnvironment</option>
                    </select>
                </div>

                <!-- Publication year (range) -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Publication year</label>
                    <div class="flex space-x-2">
                        <input type="number" inputmode="numeric" min="0" step="1"
                            wire:model.live.debounce.300ms="startYear"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Start year">
                        <input type="number" inputmode="numeric" min="0" step="1"
                            wire:model.live.debounce.300ms="endYear"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="End year">
                    </div>
                </div>

                <!-- Pathogen Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Pathogen</label>
                    <select wire:model.live="pathogenFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Pathogens</option>
                        @foreach ($allPathogens as $pathogen)
                            <option value="{{ $pathogen }}" @selected($pathogenFilter === $pathogen)>{{ $pathogen }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Technique Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Technique</label>
                    <select wire:model.live="techniqueFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Techniques</option>
                        @foreach ($allTechniques as $technique)
                            <option value="{{ $technique }}" @selected($techniqueFilter === $technique)>{{ $technique }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Country Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Country</label>
                    <select wire:model.live="countryFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Countries</option>
                        @foreach ($allCountries as $country)
                            <option value="{{ $country }}" @selected($countryFilter === $country)>{{ $country }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sub-project</label>
                    <select wire:model.live="subProjectFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Sub-projects</option>
                        @foreach ($allSubProjects as $subProjectCode)
                            <option value="{{ $subProjectCode }}" @selected($subProjectFilter === $subProjectCode)>{{ $subProjectCode }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Risk Factor Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Risk Factor</label>
                    <select wire:model.live="riskFactorFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All Risk Factors</option>
                        @foreach ($allRiskFactors as $riskFactor)
                            <option value="{{ $riskFactor }}" @selected($riskFactorFilter === $riskFactor)>{{ $riskFactor }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Clinical Sign</label>
                    <select wire:model.live="clinicalSignFilter"
                        @disabled(!in_array($metaTypeFilter, ['MetaAnimal', 'MetaHuman'], true))
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200 disabled:bg-gray-100 disabled:text-gray-500">
                        <option value="">
                            {{ in_array($metaTypeFilter, ['MetaAnimal', 'MetaHuman'], true) ? 'All Clinical Signs' : 'Not available for this study type' }}
                        </option>
                        @foreach ($allClinicalSigns as $clinicalSign)
                            <option value="{{ $clinicalSign }}" @selected($clinicalSignFilter === $clinicalSign)>{{ $clinicalSign }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Lesion</label>
                    <select wire:model.live="lesionFilter"
                        @disabled(!in_array($metaTypeFilter, ['MetaAnimal', 'MetaHuman'], true))
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200 disabled:bg-gray-100 disabled:text-gray-500">
                        <option value="">
                            {{ in_array($metaTypeFilter, ['MetaAnimal', 'MetaHuman'], true) ? 'All Lesions' : 'Not available for this study type' }}
                        </option>
                        @foreach ($allLesions as $lesion)
                            <option value="{{ $lesion }}" @selected($lesionFilter === $lesion)>{{ $lesion }}
                            </option>
                        @endforeach
                    </select>
                </div>

            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Studies -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('studiesModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Studies</p>
                    <p class="text-3xl font-bold text-gray-800">{{ $descriptive_stats['total_studies'] }}</p>
                </div>
                <div class="p-3 bg-blue-100 rounded-full">
                    <i class="fas fa-book text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <!-- Total Tested -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('testedModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Tested</p>
                    <p class="text-3xl font-bold text-green-600">{{ number_format($descriptive_stats['total_tested']) }}
                    </p>
                </div>
                <div class="p-3 bg-green-100 rounded-full">
                    <i class="fas fa-flask text-2xl text-green-600"></i>
                </div>
            </div>
        </div>

        <!-- Total Positive -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('positiveModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Total Positive</p>
                    <p class="text-3xl font-bold text-red-600">
                        {{ number_format($descriptive_stats['total_positive']) }}</p>
                </div>
                <div class="p-3 bg-red-100 rounded-full">
                    <i class="fas fa-virus text-2xl text-red-600"></i>
                </div>
            </div>
        </div>

        <!-- Positivity Rate -->
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 cursor-pointer"
            onclick="openModal('positivityModal')">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Positivity Rate</p>
                    <p class="text-3xl font-bold text-purple-600">{{ $descriptive_stats['positivity_rate'] }}%</p>
                </div>
                <div class="p-3 bg-purple-100 rounded-full">
                    <i class="fas fa-percentage text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Top Pathogens</h3>
            <div class="h-64">
                <canvas id="pathogensChart"></canvas>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">Studies by Country</h3>
            <div class="h-64">
                <canvas id="countriesPieChart"></canvas>
            </div>
        </div>
    </div>

    <!-- Country Statistics Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
        <h3 class="text-lg font-semibold text-gray-800 mb-4">Country Statistics (Positivity Rate)</h3>
        <div class="h-96 overflow-y-auto">
            @if (count($country_stats) > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-6">
                    @foreach ($country_stats as $country => $stats)
                        @php
                            $positivity_rate = $stats['positivity_rate'];
                            $colorClass =
                                $positivity_rate > 50
                                    ? 'bg-red-600'
                                    : ($positivity_rate > 20
                                        ? 'bg-orange-400'
                                        : ($positivity_rate > 5
                                            ? 'bg-yellow-400'
                                            : 'bg-green-500'));
                            $textColorClass = str_replace('bg-', 'text-', $colorClass);
                        @endphp
                        <div
                            class="bg-white rounded-xl shadow-lg border-l-4 {{ $colorClass }} hover:shadow-xl transition-shadow duration-300">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-lg font-bold text-gray-800">{{ $country }}</h3>
                                    <span
                                        class="px-3 py-1 rounded-full text-sm font-bold {{ $colorClass }} text-white">
                                        {{ $positivity_rate }}%
                                    </span>
                                </div>

                                <div class="space-y-3">
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Studies</span>
                                        <span class="font-semibold text-gray-800">{{ $stats['studies'] }}</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Total Tested</span>
                                        <span
                                            class="font-semibold text-gray-800">{{ number_format($stats['tested']) }}</span>
                                    </div>

                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-600">Total Positive</span>
                                        <span
                                            class="font-semibold text-gray-800">{{ number_format($stats['positive']) }}</span>
                                    </div>

                                    <div class="pt-2 border-t border-gray-200">
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-600 font-medium">Positivity Rate</span>
                                            <span
                                                class="font-bold text-lg {{ $textColorClass }}">{{ $positivity_rate }}%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="flex items-center justify-center h-full text-gray-500 text-lg">
                    No country data available for current filters
                </div>
            @endif
        </div>
    </div>

    <!-- Processing Status and Timeline Section -->
    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-4">

        <!-- Testing Timeline Chart -->
        <div>
            <h4 class="text-md font-semibold text-gray-700 mb-4">Studies Timeline</h4>
            <div class="timeline-chart-container">
                <canvas id="timelineChart" class="w-full h-48"></canvas>
            </div>
        </div>
    </div>

    <!-- Modal for All Studies -->
    <div id="studiesModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">All Studies in Filtered Dataset</h3>
                <button onclick="closeModal('studiesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Study ref key</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Publication year</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Country</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                DOI</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                PDF</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($studies_modal_rows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-500 italic">
                                    <a href="/studies/{{ $row['id'] }}" target="_blank"
                                        class="text-blue-500 hover:text-blue-700">
                                        {{ $row['ref_key'] ?? 'N/A' }}
                                    </a>
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row['publication_year'] ?? 'N/A' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    @php($countries = $row['countries'] ?? [])
                                    {{ count($countries) ? implode(', ', $countries) : 'N/A' }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    @if (!empty($row['doi']))
                                        <a href="https://doi.org/{{ $row['doi'] }}" target="_blank"
                                            class="text-blue-500 hover:text-blue-700 hover:underline">
                                            {{ $row['doi'] }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">
                                    @if (!empty($row['pdf_path']))
                                        <a href="{{ \Illuminate\Support\Facades\Storage::url($row['pdf_path']) }}"
                                            target="_blank"
                                            class="inline-flex items-center text-red-600 hover:text-red-700"
                                            title="View PDF">
                                            <i class="fas fa-file-pdf text-lg"></i>
                                        </a>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Total Tested (all filtered meta animals) -->
    <div id="testedModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">All filtered Meta Animals (tested)</h3>
                <button onclick="closeModal('testedModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Study</th>
                            @if ($metaTypeFilter === 'MetaAnimal')
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Animal species</th>
                            @endif
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pathogen</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Technique</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Country</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tested</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Positive</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($tested_modal_rows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-500 italic">
                                    @if ($row->studies)
                                        <a href="/studies/{{ $row->studies->id }}" target="_blank"
                                            class="text-blue-500 hover:text-blue-700">
                                            {{ $row->studies->ref_key ?? 'N/A' }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                @if ($metaTypeFilter === 'MetaAnimal')
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        {{ $row->animal_species->name_common ?? 'N/A' }}</td>
                                @endif
                                <td class="px-4 py-2 text-sm text-gray-500 italic">
                                    {{ $row->pathogens->species ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->techniques->type ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->countries->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ number_format((int) $row->tested_n) }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ number_format((int) $row->pos_n) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Total Positive (only positive meta animals) -->
    <div id="positiveModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-6xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Filtered Meta Animals (positive only)</h3>
                <button onclick="closeModal('positiveModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Study</th>
                            @if ($metaTypeFilter === 'MetaAnimal')
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Animal species</th>
                            @endif
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pathogen</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Technique</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Country</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tested</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Positive</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($positive_modal_rows as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm text-gray-500 italic">
                                    @if ($row->studies)
                                        <a href="/studies/{{ $row->studies->id }}" target="_blank"
                                            class="text-blue-500 hover:text-blue-700">
                                            {{ $row->studies->ref_key ?? 'N/A' }}
                                        </a>
                                    @else
                                        <span class="text-gray-400">N/A</span>
                                    @endif
                                </td>
                                @if ($metaTypeFilter === 'MetaAnimal')
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        {{ $row->animal_species->name_common ?? 'N/A' }}</td>
                                @endif
                                <td class="px-4 py-2 text-sm text-gray-500 italic">
                                    {{ $row->pathogens->species ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->techniques->type ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $row->countries->name ?? 'N/A' }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ number_format((int) $row->tested_n) }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ number_format((int) $row->pos_n) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Positivity Rate by Pathogen -->
    <div id="positivityModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Prevalence by pathogen (filtered Meta Animals)</h3>
                <button onclick="closeModal('positivityModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Study ref key</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Pathogen</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tested (sum)</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Positive (sum)</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Prevalence</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($positivity_modal_rows as $row)
                            @if (!$pathogenFilter || $pathogenFilter === $row['pathogen'])
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-2 text-sm text-gray-500 italic">
                                        <a href="/studies/{{ $row['study_id'] }}" target="_blank"
                                            class="text-blue-500 hover:text-blue-700">
                                            {{ $row['ref_key'] }}
                                        </a>
                                    </td>
                                    <td class="px-4 py-2 text-sm font-medium text-gray-900 italic">
                                        {{ $row['pathogen'] }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        {{ number_format((int) $row['tested']) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500">
                                        {{ number_format((int) $row['positive']) }}</td>
                                    <td class="px-4 py-2 text-sm text-gray-500 font-semibold">
                                        {{ $row['prevalence'] }}%</td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Pathogens -->
    <div id="pathogensModal"
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
                                Studies</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($studiesByPathogen as $pathogen => $count)
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

    <!-- Modal for Techniques -->
    <div id="techniquesModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Techniques in Filtered Dataset</h3>
                <button onclick="closeModal('techniquesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Technique Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Studies</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($studiesByTechnique as $technique => $count)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $technique }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal for Countries -->
    <div id="countriesModal"
        class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-4xl w-full mx-4 max-h-[80vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-800">Countries in Filtered Dataset</h3>
                <button onclick="closeModal('countriesModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Country Name</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Studies</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach ($studiesByCountry as $country => $count)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-sm font-medium text-gray-900">{{ $country }}</td>
                                <td class="px-4 py-2 text-sm text-gray-500">{{ $count }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        const samples = @json($samples);
        const timelineData = @json($descriptive_stats['studies_timeline']);
        const studiesByPathogen = @json($studiesByPathogen);
        const studiesByTechnique = @json($studiesByTechnique);
        const studiesByCountry = @json($studiesByCountry);
        const allMetaData = @json($all_meta_data);
        const countryStats = @json($country_stats);

        window.timelineData = timelineData;
        window.studiesByPathogen = studiesByPathogen;
        window.studiesByTechnique = studiesByTechnique;
        window.studiesByCountry = studiesByCountry;
        window.samples = samples;
        window.allMetaData = allMetaData;
        window.countryStats = countryStats;

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
            const modals = ['studiesModal', 'testedModal', 'positiveModal', 'positivityModal', 'pathogensModal',
                'techniquesModal', 'countriesModal'
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
        <script src="/js/show-meta-dashboard.js"></script>
    @endpush

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

    <style>
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
