<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <i class="{{ $dataTypeInfo['icon'] }} text-{{ $dataTypeInfo['color'] }}-500 text-3xl"></i>
                </div>
                <div class="ml-4">
                    <h1 class="text-2xl font-bold text-gray-900">{{ $dataTypeInfo['title'] }}</h1>
                    <p class="text-gray-600">{{ $dataTypeInfo['description'] }}</p>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label for="dataType" class="block text-sm font-medium text-gray-700 mb-2">Select Data Type</label>
                <select wire:model.live="dataType" id="dataType"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Choose data type...</option>
                    <option value="tubes">Tubes</option>
                    <option value="experiments">Experiments</option>
                    <option value="literature">Literature</option>
                    <option value="sequences">Sequences</option>
                    <option value="microplastics">Microplastics</option>
                </select>
            </div>

            @if ($dataType === 'literature')
                <div>
                    <label for="literatureType" class="block text-sm font-medium text-gray-700 mb-2">Literature type</label>
                    <select wire:model.live="literatureType" id="literatureType"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="animal">Animal</option>
                        <option value="human">Human</option>
                        <option value="environment">Environment</option>
                        <option value="parasite">Parasite</option>
                    </select>
                </div>
            @else
                <div></div>
            @endif

            <div>
                <label for="perPage" class="block text-sm font-medium text-gray-700 mb-2">Rows per page</label>
                <select wire:model.live="perPage" id="perPage"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="10">10</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="200">200</option>
                    <option value="500">500</option>
                    <option value="1000">1000</option>
                </select>
            </div>
        </div>

        @if ($dataType && $items)
            <div class="mt-8 bg-gray-50 rounded-lg p-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900">Private items</h2>
                        <p class="text-sm text-gray-600">Select items to submit for publication review</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <span class="text-sm text-gray-600">Selected: {{ $this->selectedCount }}</span>
                        @if ($items->total() > 0)
                            <button wire:click="submitSelectedForReview"
                                class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-md text-sm font-medium transition-colors duration-200">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Submit for Review
                            </button>
                        @endif
                    </div>
                </div>

                <div class="mt-4">
                    <label for="submissionMessage" class="mb-2 block text-sm font-medium text-gray-700">Message for data reviewers (optional)</label>
                    <textarea id="submissionMessage" wire:model.live.debounce.300ms="submissionMessage" rows="3"
                        class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                        placeholder="Add context for the data reviewers if needed."></textarea>
                </div>

                <div class="mt-4 overflow-x-auto rounded-lg border border-gray-200 bg-white">
                    <table class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    <label class="inline-flex items-center gap-2">
                                        <input type="checkbox" wire:model.live="selectAll"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span>Select page</span>
                                    </label>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                                    {{ $dataType === 'literature' ? 'Ref key' : 'Code' }}
                                </th>

                                @if ($dataType === 'tubes')
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content details</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Purpose</th>
                                @elseif ($dataType === 'experiments')
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Protocol</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Pathogen</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Tested</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Result</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content details</th>
                                @elseif ($dataType === 'literature')
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Sample type</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Species</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Pathogen</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Country</th>
                                @elseif ($dataType === 'sequences')
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Accession</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Method</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Instrument</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date sequenced</th>
                                @elseif ($dataType === 'microplastics')
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">MPS type</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Protocol</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Identified by</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Identification date</th>
                                @endif
                            </tr>

                            <tr class="bg-white">
                                <th class="px-4 py-3"></th>
                                <th class="px-4 py-3">
                                    @if ($dataType === 'literature')
                                        <input type="text" wire:model.live.debounce.300ms="literatureRefKeyFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter ref key">
                                    @else
                                        <input type="text" wire:model.live.debounce.300ms="codeFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter code">
                                    @endif
                                </th>

                                @if ($dataType === 'tubes')
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="tubeContentTypeFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter content">
                                    </th>
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="tubeContentDetailsFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter content details">
                                    </th>
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="tubePurposeFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter purpose">
                                    </th>
                                @elseif ($dataType === 'experiments')
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="experimentProtocolFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter protocol">
                                    </th>
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="experimentPathogenFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter pathogen">
                                    </th>
                                    <th class="px-4 py-3">
                                        <input type="date" wire:model.live.debounce.300ms="experimentDateTestedFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                    </th>
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="experimentOutcomeFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter outcome">
                                    </th>
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="experimentContentDetailsFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter content details">
                                    </th>
                                @elseif ($dataType === 'literature')
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="literatureSampleTypeFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter sample type">
                                    </th>
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="literatureSpeciesFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter species">
                                    </th>
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="literaturePathogenFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter pathogen">
                                    </th>
                                    <th class="px-4 py-3">
                                        <input type="text" wire:model.live.debounce.300ms="literatureCountryFilter"
                                            class="w-full rounded-md border border-gray-200 px-3 py-2 text-sm text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                            placeholder="Filter country">
                                    </th>
                                @elseif ($dataType === 'sequences')
                                    <th class="px-4 py-3"></th>
                                    <th class="px-4 py-3"></th>
                                    <th class="px-4 py-3"></th>
                                    <th class="px-4 py-3"></th>
                                @elseif ($dataType === 'microplastics')
                                    <th class="px-4 py-3"></th>
                                    <th class="px-4 py-3"></th>
                                    <th class="px-4 py-3"></th>
                                    <th class="px-4 py-3"></th>
                                @endif
                            </tr>
                        </thead>

                        <tbody class="divide-y divide-gray-100 bg-white">
                            @forelse ($items as $row)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3">
                                        <input type="checkbox" wire:model.live="selectedItems"
                                            value="{{ $row->id }}"
                                            class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                    </td>
                                    <td class="px-4 py-3 text-sm font-medium text-gray-900">
                                        @if ($dataType === 'literature')
                                            {{ $row->studies->ref_key ?? 'N/A' }}
                                        @else
                                            {{ $row->code ?? 'N/A' }}
                                        @endif
                                    </td>

                                    @if ($dataType === 'tubes')
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ class_basename($row->tubes_content_type ?? '') ?: 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            @php
                                                $details = $this->tubeContentDetails($row);
                                            @endphp
                                            <div class="overflow-hidden rounded border border-gray-200">
                                                <div class="index-table-container overflow-x-auto">
                                                    <table class="min-w-full text-xs">
                                                        <thead class="bg-white">
                                                            <tr class="border-t border-gray-100">
                                                                @foreach ($details['columns'] as $detail)
                                                                    <th class="px-2 py-1 font-medium text-gray-600 whitespace-nowrap text-left">{{ $detail['label'] }}</th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr class="border-t border-gray-100">
                                                                @foreach ($details['columns'] as $detail)
                                                                    <td class="px-2 py-1 text-gray-800 whitespace-nowrap">{{ $detail['value'] }}</td>
                                                                @endforeach
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row->purpose ?? 'N/A' }}</td>
                                    @elseif ($dataType === 'experiments')
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row->protocols->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row->pathogens->species ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $row->date_tested ? \Carbon\Carbon::parse($row->date_tested)->format('Y-m-d') : 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            @if ($row->outcome_binary === null)
                                                N/A
                                            @else
                                                {{ $row->outcome_binary ? 'Positive' : 'Negative' }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            @php
                                                $details = $this->experimentContentDetails($row);
                                            @endphp
                                            <div class="overflow-hidden rounded border border-gray-200">
                                                <div class="index-table-container overflow-x-auto">
                                                    <table class="min-w-full text-xs">
                                                        <thead class="bg-white">
                                                            <tr class="border-t border-gray-100">
                                                                @foreach ($details['columns'] as $detail)
                                                                    <th class="px-2 py-1 font-medium text-gray-600 whitespace-nowrap text-left">{{ $detail['label'] }}</th>
                                                                @endforeach
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr class="border-t border-gray-100">
                                                                @foreach ($details['columns'] as $detail)
                                                                    <td class="px-2 py-1 text-gray-800 whitespace-nowrap">{{ $detail['value'] }}</td>
                                                                @endforeach
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </td>
                                    @elseif ($dataType === 'literature')
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            @if ($this->literatureType === 'environment')
                                                {{ $row->environment_sample_types->name ?? 'N/A' }}
                                            @elseif ($this->literatureType === 'parasite')
                                                {{ $row->parasite_sample_types->name ?? 'N/A' }}
                                            @else
                                                {{ $row->sample_types->name ?? 'N/A' }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            @if ($this->literatureType === 'animal')
                                                {{ $row->animal_species->name_common ?? 'N/A' }}
                                            @elseif ($this->literatureType === 'parasite')
                                                {{ $row->parasite_species->name_scientific ?? 'N/A' }}
                                            @else
                                                N/A
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row->pathogens->species ?? $row->pathogens->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row->countries->name ?? 'N/A' }}</td>
                                    @elseif ($dataType === 'sequences')
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row->accession_number ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row->method ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row->instrument ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $row->date_sequenced ? \Carbon\Carbon::parse($row->date_sequenced)->format('Y-m-d') : 'N/A' }}
                                        </td>
                                    @elseif ($dataType === 'microplastics')
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row->mps_types?->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ $row->protocols?->name ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">{{ trim(($row->people?->title ?? '').' '.($row->people?->first_name ?? '').' '.($row->people?->last_name ?? '')) ?: 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            {{ $row->identification_date ? \Carbon\Carbon::parse($row->identification_date)->format('Y-m-d') : 'N/A' }}
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $dataType === 'tubes' ? 5 : ($dataType === 'experiments' ? 7 : ($dataType === 'sequences' ? 6 : ($dataType === 'microplastics' ? 6 : 6))) }}" class="px-4 py-10 text-center text-sm text-gray-600">
                                        No private items match the current filters or all matching items are already pending review.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($items->total() > 0)
                    <div class="mt-4">
                        {{ $items->links(data: ['scrollTo' => false]) }}
                    </div>
                @endif
            </div>
        @else
            <div class="text-center py-12">
                <i class="fas fa-database text-gray-400 text-4xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Select Data Type</h3>
                <p class="text-gray-600">Choose a data type above to view and submit your private data for publication review.</p>
            </div>
        @endif

        @if ($recentReviewRequests->isNotEmpty())
            <div class="mt-8 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="border-b border-gray-200 px-6 py-4">
                    <h2 class="text-lg font-semibold text-gray-900">Your publication review history for this project</h2>
                    <p class="mt-1 text-sm text-gray-600">Check review timing, reviewer feedback, and load eligible requests back into the form for resubmission.</p>
                </div>
                <div class="index-table-container overflow-x-auto">
                    <table class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Requested</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Reviewed</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Review scope</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Items</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Reviewer feedback</th>
                                <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-600">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 bg-white">
                            @foreach ($recentReviewRequests as $reviewRequest)
                                @php
                                    $badgeClass = match ($reviewRequest->status) {
                                        'approved' => 'bg-green-100 text-green-800',
                                        'changes_requested' => 'bg-blue-100 text-blue-800',
                                        'rejected' => 'bg-red-100 text-red-800',
                                        default => 'bg-amber-100 text-amber-800',
                                    };
                                    $badgeLabel = match ($reviewRequest->status) {
                                        'changes_requested' => 'Changes requested',
                                        default => ucfirst(str_replace('_', ' ', $reviewRequest->status)),
                                    };
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ optional($reviewRequest->submitted_at)->format('Y-m-d H:i') ?? 'N/A' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ optional($reviewRequest->reviewed_at)->format('Y-m-d H:i') ?? '—' }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ \App\Support\PublicationReviewRegistry::label($reviewRequest->data_type, $reviewRequest->literature_type) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">{{ number_format((int) $reviewRequest->items_count) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">{{ $badgeLabel }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700">{{ $reviewRequest->reviewer_message ?: '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                        @if (in_array($reviewRequest->status, ['changes_requested', 'rejected'], true))
                                            <button type="button"
                                                wire:click="loadRequestForResubmission({{ $reviewRequest->id }})"
                                                class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-100">
                                                <i class="fa-solid fa-rotate-right"></i>
                                                Load for resubmission
                                            </button>
                                        @else
                                            <span class="text-gray-400">—</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Information Panel -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-blue-800">About Publishing Data</h3>
                    <div class="mt-2 text-sm text-blue-700">
                        <p class="mb-2">- Data submitted here stays private until an Admin reviews and approves it</p>
                        <p class="mb-2">- Only private data that is not already pending review is shown in this interface</p>
                        <p class="mb-2">- You will receive the Admin decision in the app notifications window and by email</p>
                        <p>- You can only submit data from your currently selected project</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
