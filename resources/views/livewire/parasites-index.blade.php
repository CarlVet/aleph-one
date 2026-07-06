<div class="text-center mt-2">
    <div class="space-x-4">
        <a href="/samples/parasites/create"
            class="bg-green-500 text-white hover:bg-green-600 hover:text-white rounded-md px-3 py-2 text-sm font-medium border border-black"
            aria-current="page">
            Create
        </a>
        @if ($isEditing)
            <a href="/samples/parasites/list"
                class="bg-gray-400 text-white hover:bg-gray-500 hover:text-white rounded-md px-3 py-2 text-sm font-medium border border-black"
                aria-current="page">
                Index
            </a>
        @else
            <button wire:click="toggleEditMode"
                class="bg-yellow-500 text-white hover:bg-yellow-600 hover:text-white rounded-md px-3 py-2 text-sm font-medium border border-black">
                Edit
            </button>
        @endif
    </div>
    <div class="mt-4 border p-2">
        <h2 class="text-lg font-bold text-center">{{ $isEditing ? 'Edit Parasites' : 'Parasites Index' }}</h2>
        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $parasites])

        <div class="index-table-container overflow-x-auto">
        <table id="parasite_samples_table" class="index-data-table sticky-code-at-2 min-w-full divide-y divide-gray-200 mt-2">
            <thead>
                <tr>
                    <th>Parasite ID</th>
                    <th><x-sort-button field="code" :active="$sortField" :direction="$sortDirection">Code</x-sort-button></th>
                    <th><x-sort-button field="species" :active="$sortField" :direction="$sortDirection">Species</x-sort-button></th>
                    <th><x-sort-button field="stage" :active="$sortField" :direction="$sortDirection">Stage</x-sort-button></th>
                    <th><x-sort-button field="sex" :active="$sortField" :direction="$sortDirection">Sex</x-sort-button></th>
                    <th><x-sort-button field="state" :active="$sortField" :direction="$sortDirection">State</x-sort-button></th>
                    <th><x-sort-button field="date_identified" :active="$sortField" :direction="$sortDirection">Date identified</x-sort-button></th>
                    <th>Sample ID</th>
                    <th><x-sort-button field="animal_species" :active="$sortField" :direction="$sortDirection">Animal species</x-sort-button></th>
                    <th><x-sort-button field="park" :active="$sortField" :direction="$sortDirection">Park</x-sort-button></th>
                    <th>Photo</th>
                </tr>
            </thead>
            <thead>
                <tr>
                    <th><input type="text" wire:model.live.debounce.300ms="parasiteIdFilter" style="width: 80px;"
                            placeholder="Filter"></th>
                    <th><input type="text" wire:model.live.debounce.300ms="codeFilter" style="width: 80px;"
                            placeholder="Filter"></th>
                    <th><input type="text" wire:model.live.debounce.300ms="speciesFilter" style="width: 80px;"
                            placeholder="Filter"></th>
                    <th><input type="text" wire:model.live.debounce.300ms="stageFilter" style="width: 80px;"
                            placeholder="Filter"></th>
                    <th><input type="text" wire:model.live.debounce.300ms="sexFilter" style="width: 80px;"
                            placeholder="Filter"></th>
                    <th><input type="text" wire:model.live.debounce.300ms="stateFilter" style="width: 80px;"
                            placeholder="Filter"></th>
                    <th>
                        <div class="flex items-center space-x-2">
                            <input type="date" wire:model.live.debounce.300ms="startDate" style="width: 80px;"
                                placeholder="Start Date">
                            <span>to</span>
                            <input type="date" wire:model.live.debounce.300ms="endDate" style="width: 80px;"
                                placeholder="End Date">
                        </div>
                    </th>
                    <th><input type="text" wire:model.live.debounce.300ms="sampleIdFilter" style="width: 80px;"
                            placeholder="Filter"></th>
                    <th><input type="text" wire:model.live.debounce.300ms="animalSpeciesFilter" style="width: 80px;"
                            placeholder="Filter"></th>
                    <th><input type="text" wire:model.live.debounce.300ms="parkFilter" style="width: 80px;"
                            placeholder="Filter"></th>
                </tr>
            </thead>
            <tbody>
                @php
                    $currentPeopleId = (int) ($this->currentPeopleId() ?? 0);
                @endphp
                @foreach ($parasites as $parasite)
                    @php
                        $canEditRow = ($canEdit ?? false) && (int) ($parasite->people_id ?? 0) === $currentPeopleId;
                    @endphp
                    <tr wire:key="{{ $parasite->id }}">
                        <td>{{ $parasite->id }}</td>
                        <td>
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $parasite->code ?? 'N/A' }}"
                                    class="block w-1/3 max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    style="width: 80px;"
                                    wire:change="updateField({{ $parasite->id }}, 'code', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Code of the parasite?">
                            @else
                                {{ $parasite->code ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="parasite_species" name="parasite_species"
                                    wire:change="updateField({{ $parasite->id }}, 'species', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Parasite Species?">
                                    @foreach ($species_by_family as $family => $species_list)
                                        <optgroup label="{{ $family }}">
                                            @foreach ($species_list as $species)
                                                <option value="{{ $species['scientific'] }}"
                                                    {{ $species['scientific'] === ($parasite->parasite_species->name_scientific ?? '') ? 'selected' : '' }}>
                                                    {{ $species['scientific'] }}
                                                </option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </x-forms.select-input>
                            @else
                                {{ $parasite->parasite_species->name_scientific ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="stage" name="stage"
                                    wire:change="updateField({{ $parasite->id }}, 'stage', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Stage of the parasite?">
                                    <option value="Adult"
                                        {{ 'Adult' === $parasite->stage ? 'selected' : '' }}>
                                        Adult
                                    </option>
                                    <option value="Nymph"
                                        {{ 'Nymph' === $parasite->stage ? 'selected' : '' }}>
                                        Nymph
                                    </option>
                                    <option value="Larva" {{ 'Larva' === $parasite->stage ? 'selected' : '' }}>
                                        Larva
                                    </option>
                                    <option value="Egg" {{ 'Egg' === $parasite->stage ? 'selected' : '' }}>
                                        Egg
                                    </option>
                                </x-forms.select-input>
                            @else
                                {{ $parasite->stage ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="sex" name="sex"
                                    wire:change="updateField({{ $parasite->id }}, 'sex', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Sex of the parasite?">
                                    <option value="Male" {{ 'Male' === $parasite->sex ? 'selected' : '' }}>
                                        Male
                                    </option>
                                    <option value="Female" {{ 'Female' === $parasite->sex ? 'selected' : '' }}>
                                        Female
                                    </option>
                                </x-forms.select-input>
                            @else
                                {{ $parasite->sex ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            @if ($isEditing && $canEditRow)
                                <x-forms.select-input id="state" name="state"
                                    wire:change="updateField({{ $parasite->id }}, 'state', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the State of the parasite?">
                                    <option value="Engorged"
                                        {{ 'Engorged' === $parasite->state ? 'selected' : '' }}>
                                        Engorged
                                    </option>
                                    <option value="Partially engorged"
                                        {{ 'Partially engorged' === $parasite->state ? 'selected' : '' }}>
                                        Partially engorged
                                    </option>
                                    <option value="Non engorged" {{ 'Non engorged' === $parasite->state ? 'selected' : '' }}>
                                        Non engorged
                                    </option>
                                </x-forms.select-input>
                            @else
                                {{ $parasite->state ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            @if ($isEditing && $canEditRow)
                                <input type="date" value="{{ $parasite->date_identified ? \Carbon\Carbon::parse($parasite->date_identified)->format('Y-m-d') : '' }}"
                                    style="width: 80px;"
                                    class="block w-1/3 max-w-xs border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
                                    wire:change="updateField({{ $parasite->id }}, 'date_identified', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Date of parasite Collection?">
                            @else
                                {{ $parasite->date_identified ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            @if ($isEditing && $canEditRow)
                            <x-forms.select-input id="sample_id" name="sample_id"
                                wire:change="updateField({{ $parasite->id }}, 'sample_id', $event.target.value)"
                                wire:confirm="Are you sure you want to edit the Sample ID origin of the parasite?"
                                class="px-2">
                                @foreach ($animal_samples as $sample)
                                    <option value="{{ $sample['id'] }}"
                                        {{ $sample['id'] === ($parasite->animal_samples->id ?? '') ? 'selected' : '' }}>
                                        {{ $sample['id'] }}
                                    </option>
                                @endforeach
                            </x-forms.select-input>
                            @else
                                {{ $parasite->animal_samples->animals->id ?? 'N/A' }}
                            @endif
                        </td>
                        <td>
                            {{ $parasite->animal_samples->animals->animal_species->name_common ?? 'N/A' }}
                        </td>
                        <td>
                            {{ $parasite->animal_samples->places->name ?? 'N/A' }}
                        </td>
                        <td>
                            @if ($parasite->photo_path)
                                <button class="bg-indigo-500 text-white hover:bg-indigo-600 px-3 py-2 rounded-md"
                                    type="button" wire:click="downloadPhoto({{ $parasite->id }})">Photo</button>
                            @endif
                        </td>
                        <td>
                            @if ($isEditing && $canEditRow)
                                <button class="bg-red-500 text-white hover:bg-red-600 px-3 py-2 rounded-md" type="button"
                                    wire:click="delete({{ $parasite->id }})"
                                    wire:confirm="Are you sure you want to delete this parasite?">Delete</button>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    <div class="mt-3">
        @include('livewire.partials.index-pagination-bar', ['paginator' => $parasites])
    </div>
</div>
