<div class="space-y-6">
    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: true }">
        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
            <div class="flex items-center gap-3">
                <div class="bg-green-100 p-2 rounded-lg"><i class="fa-solid fa-vials text-green-600"></i></div>
                <h2 class="text-xl font-semibold text-gray-900">Samples Registered</h2>
            </div>
            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none"
                stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div x-show="open" x-transition class="space-y-5">
            @php
                $sampleTables = [
                    ['title' => 'Human Samples', 'icon' => 'fa-person', 'rows' => $humanPagination, 'link' => '/samples/humans/', 'filters' => ['humanCodeFilter', 'humanTypeFilter', 'humanDateFilter'], 'setPage' => 'setHumanPage'],
                    ['title' => 'Animal Samples', 'icon' => 'fa-paw', 'rows' => $animalPagination, 'link' => '/samples/animals/', 'filters' => ['animalCodeFilter', 'animalTypeFilter', 'animalDateFilter'], 'setPage' => 'setAnimalPage'],
                    ['title' => 'Environment Samples', 'icon' => 'fa-leaf', 'rows' => $environmentPagination, 'link' => '/samples/environment/', 'filters' => ['environmentCodeFilter', 'environmentTypeFilter', 'environmentDateFilter'], 'setPage' => 'setEnvironmentPage'],
                    ['title' => 'Parasite Samples', 'icon' => 'fa-bug', 'rows' => $parasitePagination, 'link' => '/samples/parasites/', 'filters' => ['parasiteCodeFilter', 'parasiteTypeFilter', 'parasiteDateFilter'], 'setPage' => 'setParasitePage'],
                    ['title' => 'Nucleic Acids', 'icon' => 'fa-dna', 'rows' => $nucleicPagination, 'link' => '/samples/nucleic-acids/', 'filters' => ['nucleicCodeFilter', 'nucleicTypeFilter', 'nucleicDateFilter'], 'setPage' => 'setNucleicPage'],
                    ['title' => 'Cultures', 'icon' => 'fa-bacteria', 'rows' => $culturePagination, 'link' => '/samples/cultures/', 'filters' => ['cultureCodeFilter', 'cultureTypeFilter', 'cultureDateFilter'], 'setPage' => 'setCulturePage'],
                    ['title' => 'Pools', 'icon' => 'fa-layer-group', 'rows' => $poolPagination, 'link' => '/samples/pools/', 'filters' => ['poolCodeFilter', 'poolCountFilter', 'poolDateFilter'], 'setPage' => 'setPoolPage'],
                ];
            @endphp

            @foreach ($sampleTables as $table)
                <div class="bg-white rounded-lg overflow-hidden border">
                    <div class="border-b px-4 py-3 font-semibold text-gray-800 flex items-center gap-2">
                        <i class="fa-solid {{ $table['icon'] }} text-gray-600"></i>
                        {{ $table['title'] }}
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Code</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Date</th>
                                </tr>
                                <tr>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="{{ $table['filters'][0] }}" class="w-full rounded border-gray-300 text-xs" placeholder="Filter code"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="{{ $table['filters'][1] }}" class="w-full rounded border-gray-300 text-xs" placeholder="Filter type"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="{{ $table['filters'][2] }}" class="w-full rounded border-gray-300 text-xs" placeholder="Filter date"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($table['rows']['items'] as $row)
                                    <tr>
                                        <td class="px-4 py-2 text-sm"><a href="{{ $table['link'].$row->code }}" class="text-blue-600 hover:text-blue-800">{{ $row->code }}</a></td>
                                        <td class="px-4 py-2 text-sm">{{ $row->sample_type ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $row->event_date ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-4 py-6 text-center text-sm text-gray-600">No matching records.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-gray-50 px-4 py-2 flex items-center justify-between text-sm">
                        <span>Showing {{ $table['rows']['from'] }} to {{ $table['rows']['to'] }} of {{ $table['rows']['total'] }}</span>
                        <div class="space-x-2">
                            <button wire:click="{{ $table['setPage'] }}({{ max(1, $table['rows']['currentPage'] - 1) }})" class="px-2 py-1 border rounded">Prev</button>
                            <span>{{ $table['rows']['currentPage'] }}/{{ $table['rows']['totalPages'] }}</span>
                            <button wire:click="{{ $table['setPage'] }}({{ min($table['rows']['totalPages'], $table['rows']['currentPage'] + 1) }})" class="px-2 py-1 border rounded">Next</button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: true }">
        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
            <div class="flex items-center gap-3">
                <div class="bg-purple-100 p-2 rounded-lg"><i class="fa-solid fa-flask text-purple-600"></i></div>
                <h2 class="text-xl font-semibold text-gray-900">Experiments Conducted</h2>
            </div>
            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div x-show="open" x-transition>
            <div class="bg-white rounded-lg overflow-hidden border">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outcome</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            </tr>
                            <tr>
                                <th class="px-6 py-2"><input wire:model.live.debounce.300ms="expCodeFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter code"></th>
                                <th class="px-6 py-2"><input wire:model.live.debounce.300ms="expContentTypeFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter content"></th>
                                <th class="px-6 py-2"><input wire:model.live.debounce.300ms="expProtocolFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter protocol"></th>
                                <th class="px-6 py-2"><input wire:model.live.debounce.300ms="expOutcomeFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter outcome"></th>
                                <th class="px-6 py-2"><input wire:model.live.debounce.300ms="expDateFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter date"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($experimentsPagination['items'] as $experiment)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="/experiments/{{ $experiment->code }}"
                                            class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $experiment->code }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ class_basename($experiment->content_type ?? 'N/A') }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $experiment->protocol_name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $experiment->outcome ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        {{ $experiment->date_tested ?? 'N/A' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-10 text-center text-sm text-gray-600">No matching experiments.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ $experimentsPagination['from'] }} to {{ $experimentsPagination['to'] }} of {{ $experimentsPagination['total'] }}
                    </div>
                    <div class="flex items-center space-x-2">
                        <button wire:click="setExpPage({{ max(1, $experimentsPagination['currentPage'] - 1) }})"
                            class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">Previous</button>
                        <span class="text-sm text-gray-600">{{ $experimentsPagination['currentPage'] }}/{{ $experimentsPagination['totalPages'] }}</span>
                        <button wire:click="setExpPage({{ min($experimentsPagination['totalPages'], $experimentsPagination['currentPage'] + 1) }})"
                            class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
            <div class="flex items-center gap-3">
                <div class="bg-indigo-100 p-2 rounded-lg"><i class="fa-solid fa-book text-indigo-600"></i></div>
                <h2 class="text-xl font-semibold text-gray-900">Literature Data</h2>
            </div>
            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div x-show="open" x-transition>
            <div class="bg-white rounded-lg overflow-hidden border">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Study Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pathogen</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Year</th>
                            </tr>
                            <tr>
                                <th class="px-6 py-2"><input wire:model.live.debounce.300ms="metaTypeFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter type"></th>
                                <th class="px-6 py-2"><input wire:model.live.debounce.300ms="metaReferenceFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter reference"></th>
                                <th class="px-6 py-2"><input wire:model.live.debounce.300ms="metaPathogenFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter pathogen"></th>
                                <th class="px-6 py-2"><input wire:model.live.debounce.300ms="metaYearFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter year"></th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($metaPagination['items'] as $study)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $study->study_type ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <a href="{{ $study->reference ? '/meta/'.$study->reference : '#' }}"
                                            class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                            {{ $study->reference ?? 'N/A' }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $study->pathogen ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $study->publication_year ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-600">No matching literature records.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="bg-gray-50 px-6 py-3 border-t border-gray-200 flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ $metaPagination['from'] }} to {{ $metaPagination['to'] }} of {{ $metaPagination['total'] }}
                    </div>
                    <div class="flex items-center space-x-2">
                        <button wire:click="setMetaPage({{ max(1, $metaPagination['currentPage'] - 1) }})"
                            class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">Previous</button>
                        <span class="text-sm text-gray-600">{{ $metaPagination['currentPage'] }}/{{ $metaPagination['totalPages'] }}</span>
                        <button wire:click="setMetaPage({{ min($metaPagination['totalPages'], $metaPagination['currentPage'] + 1) }})"
                            class="px-3 py-1 text-sm bg-white border border-gray-300 rounded-md hover:bg-gray-50">Next</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
            <div class="flex items-center gap-3">
                <div class="bg-amber-100 p-2 rounded-lg"><i class="fa-solid fa-boxes-stacked text-amber-600"></i></div>
                <h2 class="text-xl font-semibold text-gray-900">Storage</h2>
            </div>
            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }"
                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
            </svg>
        </button>
        <div x-show="open" x-transition class="space-y-6">
            <div class="bg-white rounded-lg overflow-hidden border">
                <div class="border-b px-6 py-3 font-semibold text-gray-800">Tubes</div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Alias</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Project</th>
                                </tr>
                                <tr>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="tubeCodeFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter code"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="tubeAliasFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter alias"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="tubeTypeFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter type"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="tubeProjectFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter project"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($tubesPagination['items'] as $tube)
                                    <tr>
                                        <td class="px-4 py-2 text-sm"><a href="/bank/tubes/{{ $tube->code }}" class="text-blue-600 hover:text-blue-800">{{ $tube->code }}</a></td>
                                        <td class="px-4 py-2 text-sm">{{ $tube->alias_code ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $tube->tube_type ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $tube->project_code ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-600">No matching tubes.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <span>Showing {{ $tubesPagination['from'] }} to {{ $tubesPagination['to'] }} of {{ $tubesPagination['total'] }}</span>
                        <div class="space-x-2">
                            <button wire:click="setTubePage({{ max(1, $tubesPagination['currentPage'] - 1) }})" class="px-3 py-1 border rounded">Previous</button>
                            <span>{{ $tubesPagination['currentPage'] }}/{{ $tubesPagination['totalPages'] }}</span>
                            <button wire:click="setTubePage({{ min($tubesPagination['totalPages'], $tubesPagination['currentPage'] + 1) }})" class="px-3 py-1 border rounded">Next</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg overflow-hidden border">
                <div class="border-b px-6 py-3 font-semibold text-gray-800">Tube Positions</div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tube</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Box</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pos X</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pos Y</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                </tr>
                                <tr>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="tubePositionTubeCodeFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter tube"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="tubePositionBoxCodeFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter box"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="tubePositionXFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter x"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="tubePositionYFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter y"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="tubePositionDateFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter date"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($tubePositionsPagination['items'] as $position)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $position->tube_code ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $position->box_code ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $position->position_x ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $position->position_y ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $position->date_moved ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-4 py-6 text-center text-sm text-gray-600">No matching tube positions.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <span>Showing {{ $tubePositionsPagination['from'] }} to {{ $tubePositionsPagination['to'] }} of {{ $tubePositionsPagination['total'] }}</span>
                        <div class="space-x-2">
                            <button wire:click="setTubePositionPage({{ max(1, $tubePositionsPagination['currentPage'] - 1) }})" class="px-3 py-1 border rounded">Previous</button>
                            <span>{{ $tubePositionsPagination['currentPage'] }}/{{ $tubePositionsPagination['totalPages'] }}</span>
                            <button wire:click="setTubePositionPage({{ min($tubePositionsPagination['totalPages'], $tubePositionsPagination['currentPage'] + 1) }})" class="px-3 py-1 border rounded">Next</button>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg overflow-hidden border">
                <div class="border-b px-6 py-3 font-semibold text-gray-800">Box Positions</div>
                <div class="p-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Box</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Content Type</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Moved</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                </tr>
                                <tr>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="boxCodeFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter box"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="boxContentTypeFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter type"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="boxDateFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter date"></th>
                                    <th class="px-4 py-2"><input wire:model.live.debounce.300ms="boxLocationFilter" class="w-full rounded border-gray-300 text-xs" placeholder="Filter location"></th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @forelse($boxesPagination['items'] as $box)
                                    <tr>
                                        <td class="px-4 py-2 text-sm">{{ $box->box_code ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $box->content_type ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $box->date_moved ?? 'N/A' }}</td>
                                        <td class="px-4 py-2 text-sm">{{ $box->location ?? 'N/A' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-4 py-6 text-center text-sm text-gray-600">No matching box positions.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3 flex items-center justify-between text-sm">
                        <span>Showing {{ $boxesPagination['from'] }} to {{ $boxesPagination['to'] }} of {{ $boxesPagination['total'] }}</span>
                        <div class="space-x-2">
                            <button wire:click="setBoxPage({{ max(1, $boxesPagination['currentPage'] - 1) }})" class="px-3 py-1 border rounded">Previous</button>
                            <span>{{ $boxesPagination['currentPage'] }}/{{ $boxesPagination['totalPages'] }}</span>
                            <button wire:click="setBoxPage({{ min($boxesPagination['totalPages'], $boxesPagination['currentPage'] + 1) }})" class="px-3 py-1 border rounded">Next</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>