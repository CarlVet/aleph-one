<div>
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public microplastics identifications from all projects</p>
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
        @if (! $isGuestMode)
            <a href="/samples/microplastics"
                class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                <i class="fas fa-arrow-left text-2xl mr-2"></i>
                <span class="text-sm font-medium">Back to Microplastics Home</span>
            </a>

            @if (! $canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/samples/microplastics/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @endif

            <a href="/samples/microplastics/list"
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
                <a href="/samples/microplastics/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            </div>
        @endif
    </div>

    <div class="text-center mb-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">Microplastics Dashboard</h1>
        <p class="text-gray-600">Overview of identified microplastics records and their linked laboratory metadata</p>
    </div>

    <div data-dashboard-filter-root class="bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8" x-data="{ open: true }">
        <div class="flex items-center justify-between mb-4">
            <button type="button"
                class="inline-flex items-center gap-2 text-lg font-semibold text-gray-800 hover:text-gray-900"
                x-on:click="open = !open" x-bind:aria-expanded="open.toString()">
                <i class="fas fa-chevron-down text-sm transition-transform duration-200"
                    x-bind:class="open ? 'rotate-180' : ''"></i>
                <span>Filters</span>
            </button>
            <button type="button" wire:click="resetFilters"
                class="inline-flex items-center gap-2 text-sm font-semibold text-slate-700 hover:text-slate-900 px-3 py-2 rounded-lg border border-gray-200 bg-white hover:bg-gray-50 transition-colors"
                x-show="open">
                <i class="fas fa-rotate-left text-xs"></i>
                Reset
            </button>
        </div>
        <div x-show="open" x-collapse>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">MPS type</label>
                    <select wire:model.live="mpsTypeFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All types</option>
                        @foreach ($allMpsTypes as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Source type</label>
                    <select wire:model.live="sourceTypeFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="all">All sources</option>
                        @foreach ($availableSourceTypes as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Sub-project</label>
                    <select wire:model.live="subProjectFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All sub-projects</option>
                        @foreach ($allSubProjects as $subProjectCode)
                            <option value="{{ $subProjectCode }}">{{ $subProjectCode }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Protocol</label>
                    <select wire:model.live="protocolFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All protocols</option>
                        @foreach ($allProtocols as $protocol)
                            <option value="{{ $protocol }}">{{ $protocol }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Laboratory</label>
                    <select wire:model.live="laboratoryFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All laboratories</option>
                        @foreach ($allLaboratories as $laboratory)
                            <option value="{{ $laboratory }}">{{ $laboratory }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Identified by</label>
                    <select wire:model.live="identifiedByFilter"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">All people</option>
                        @foreach ($allPeople as $person)
                            <option value="{{ $person }}">{{ $person }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-4 mb-8">
        @foreach ([
            ['label' => 'Records', 'value' => $totalRecords, 'icon' => 'fa-recycle', 'color' => 'from-sky-500 to-blue-600', 'modal' => 'recordsModal'],
            ['label' => 'Avg r coeff.', 'value' => number_format((float) ($averagePearson ?? 0), 3), 'icon' => 'fa-wave-square', 'color' => 'from-purple-500 to-fuchsia-600', 'modal' => 'pearsonModal'],
            ['label' => 'Concentration', 'value' => $concentrationPerSample !== null ? number_format((float) $concentrationPerSample, 2) : 'N/A', 'icon' => 'fa-recycle', 'color' => 'from-emerald-500 to-green-600', 'modal' => 'concentrationModal'],
            ['label' => 'Avg Feret', 'value' => number_format((float) ($averageFeret ?? 0), 1), 'icon' => 'fa-ruler-horizontal', 'color' => 'from-amber-500 to-orange-600', 'modal' => 'feretModal'],
        ] as $card)
            <button type="button" onclick="openModal('{{ $card['modal'] }}')"
                class="w-full bg-white rounded-xl shadow-lg p-6 border border-gray-100 transform transition-all duration-300 hover:scale-105 text-left">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">{{ $card['label'] }}</p>
                        <p class="mt-2 text-3xl font-bold text-gray-800">{{ $card['value'] }}</p>
                    </div>
                    <div class="p-3 rounded-full bg-gradient-to-r {{ $card['color'] }} text-white shadow-md">
                        <i class="fas {{ $card['icon'] }}"></i>
                    </div>
                </div>
            </button>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <div id="pieBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
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
            <div id="pieLegendScroller" class="mt-2 max-w-full overflow-x-scroll overflow-y-hidden"></div>
        </div>

        <div id="barBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100">
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

    <div id="mapBox" class="dashboard-box relative bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
        <div class="flex flex-wrap items-center justify-between gap-4 mb-4">
            <h3 class="text-lg font-semibold text-gray-800">Microplastics Distribution</h3>
            <div class="flex flex-wrap gap-2" id="mapVariableTabs">
                @foreach (($mapColorVariableOptions ?? []) as $option)
                    <button type="button" data-map-tab="{{ $option['key'] }}"
                        class="map-tab-btn px-3 py-1.5 text-xs rounded-full border border-gray-200 text-gray-600 hover:text-indigo-700 hover:border-indigo-200">
                        {{ $option['label'] }}
                    </button>
                @endforeach
            </div>
        </div>
        <div class="relative h-96">
            <div id="map" class="w-full h-full rounded-lg"></div>
        </div>
        <div id="mapLegend" class="mt-4 flex flex-wrap items-center gap-4 text-sm text-gray-600"></div>
        <div class="mt-2 text-xs text-gray-500">
            Tip: pooled or derived records are traced back to the primary sample coordinates when possible.
        </div>
    </div>

    <div class="dashboard-box bg-white rounded-xl shadow-lg p-6 border border-gray-100 mb-8">
        <div>
            <h4 class="text-md font-semibold text-gray-700 mb-4">Identification Timeline</h4>
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

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 xl:grid-cols-4">
        @foreach ([
            ['title' => 'Particle Types', 'rows' => $typeBreakdown],
            ['title' => 'Protocols', 'rows' => $protocolBreakdown],
            ['title' => 'Laboratories', 'rows' => $laboratoryBreakdown],
            ['title' => 'Source Types', 'rows' => $sourceBreakdown],
        ] as $table)
            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-5 py-4">
                    <h2 class="text-lg font-semibold text-gray-800">{{ $table['title'] }}</h2>
                </div>
                <div class="divide-y divide-gray-100">
                    @forelse ($table['rows'] as $row)
                        <div class="flex items-center justify-between px-5 py-3 text-sm">
                            <span class="text-gray-700">{{ $row['label'] }}</span>
                            <span class="rounded-full bg-gray-100 px-3 py-1 font-semibold text-gray-800">{{ $row['count'] }}</span>
                        </div>
                    @empty
                        <div class="px-5 py-6 text-sm text-gray-500">No data available.</div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    @foreach ([
        'recordsModal' => ['title' => 'Microplastics Records in Filtered Dataset', 'maxWidth' => 'max-w-6xl'],
        'pearsonModal' => ['title' => 'r Correlation Coefficients in Filtered Dataset', 'maxWidth' => 'max-w-5xl'],
        'concentrationModal' => ['title' => 'Microplastics Concentration by Sample', 'maxWidth' => 'max-w-5xl'],
        'feretModal' => ['title' => 'Feret Measurements in Filtered Dataset', 'maxWidth' => 'max-w-5xl'],
    ] as $modalId => $modal)
        <div id="{{ $modalId }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-black bg-opacity-50">
            <div class="mx-4 w-full {{ $modal['maxWidth'] }} max-h-[80vh] overflow-y-auto rounded-lg bg-white p-6">
                <div class="mb-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-800">{{ $modal['title'] }}</h3>
                    <button type="button" onclick="closeModal('{{ $modalId }}')" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                @if ($modalId === 'recordsModal')
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Code</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Source type</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Source code</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">MPS type</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Protocol</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Laboratory</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">r</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Feret</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($recordsModalRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['code'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['source_type'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['source_code'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['mps_type'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['protocol'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['laboratory'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['r_coeff'] !== null ? number_format((float) $row['r_coeff'], 4) : 'N/A' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['m_feret'] !== null ? number_format((float) $row['m_feret'], 1) : 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">No data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif ($modalId === 'protocolsModal' || $modalId === 'laboratoriesModal')
                    @php($rows = $modalId === 'protocolsModal' ? $protocolModalRows : $laboratoryModalRows)
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Value</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Count</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['label'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['count'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="px-4 py-6 text-center text-gray-500">No data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif ($modalId === 'concentrationModal')
                    <div class="mb-4 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                        Average sample-level concentration (microplastics number / sample weight in g):
                        <span class="font-semibold">{{ $concentrationPerSample !== null ? number_format((float) $concentrationPerSample, 2) : 'N/A' }}</span>
                    </div>
                    <div class="mb-4 rounded-xl border border-sky-100 bg-sky-50 px-4 py-3 text-sm text-sky-900">
                        Average sample weight per sample:
                        <span class="font-semibold">{{ number_format((float) ($averageSampleWeightPerSample ?? 0), 1) }} g</span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Source type</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Source code</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Microplastics nr.</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Avg sample weight (g)</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Concentration</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($concentrationModalRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['source_type'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['source_code'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['count'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['sample_weight'] !== null ? number_format((float) $row['sample_weight'], 1) : 'N/A' }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['concentration'] !== null ? number_format((float) $row['concentration'], 2) : 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif ($modalId === 'pearsonModal')
                    <div class="mb-4 rounded-xl border border-purple-100 bg-purple-50 px-4 py-3 text-sm text-purple-900">
                        Average r correlation coefficient:
                        <span class="font-semibold">{{ number_format((float) ($averagePearson ?? 0), 3) }}</span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Code</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Source type</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Source code</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">MPS type</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">r coeff.</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($pearsonModalRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['code'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['source_type'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['source_code'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['mps_type'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ number_format((float) $row['r_coeff'], 4) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif ($modalId === 'feretModal')
                    <div class="mb-4 rounded-xl border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Average Feret diameter:
                        <span class="font-semibold">{{ number_format((float) ($averageFeret ?? 0), 1) }}</span>
                    </div>
                    <div class="overflow-x-auto rounded-xl border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200 text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Code</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Source type</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Source code</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">MPS type</th>
                                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Feret</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($feretModalRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['code'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['source_type'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['source_code'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ $row['mps_type'] }}</td>
                                        <td class="px-4 py-3 text-gray-700">{{ number_format((float) $row['m_feret'], 1) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-6 text-center text-gray-500">No data available.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endforeach

    <script>
        function openModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeModal(modalId) {
            const modal = document.getElementById(modalId);
            if (!modal) {
                return;
            }
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }

        document.addEventListener('DOMContentLoaded', function() {
            [
                'recordsModal',
                'protocolsModal',
                'laboratoriesModal',
                'pearsonModal',
                'concentrationModal',
                'feretModal'
            ].forEach(function(modalId) {
                const modal = document.getElementById(modalId);
                if (!modal) {
                    return;
                }

                modal.addEventListener('click', function(event) {
                    if (event.target === modal) {
                        closeModal(modalId);
                    }
                });
            });
        });

        window.microplasticsTimelineData = @json($timelineData ?? []);
        window.microplasticsPieChartTabs = @json($pieChartTabs ?? []);
        window.microplasticsBarChartTabs = @json($barChartTabs ?? []);
        window.microplasticsMapColorVariableOptions = @json($mapColorVariableOptions ?? []);
        window.microplasticsMapPointsUrl = @json($mapPointsUrl ?? null);
        window.microplasticsActiveFilters = @json($activeFilters ?? []);
    </script>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
        <script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
        <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />
        <script src="{{ asset('js/show-microplastics.js') }}?v={{ filemtime(public_path('js/show-microplastics.js')) }}"></script>
    @endpush

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
