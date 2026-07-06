<!-- Table content -->
<table id="parasite_tubes_table" class="table table-striped">
    <thead>
        <tr>
            <th>Select<input type="checkbox" id="select_all_ps_tubes"> All</th>
            <th>Tube code</th>
            <th>State</th>
            <th>Parasite code</th>
            <th>Species</th>
            <th>Sex</th>
            <th>Stage</th>
            <th>Repletion state</th>
            <th>Sample type</th>
            <th>Date collected</th>
            <th>Animal origin</th>
            <th>Sampling site</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($parasite_tubes as $tube)
            <tr wire:key="{{ $tube->id }}">
                <td>
                    <input type="checkbox" class="select-ps-tube" value="{{ $tube->id }}">
                </td>
                <td>{{ $tube->code }}</td>
                <td>{{ $tube->state ?? 'N/A' }}</td>
                <td>{{ $tube->tubes_content->parasites->code ?? 'N/A' }}</td>
                <td>{{ $tube->tubes_content->parasites->parasite_species->name_scientific ?? 'N/A' }}</td>
                <td>{{ $tube->tubes_content->parasites->sex ?? 'N/A' }}</td>
                <td>{{ $tube->tubes_content->parasites->stage ?? 'N/A' }}</td>
                <td>{{ $tube->tubes_content->parasites->state ?? 'N/A' }}</td>
                <td>{{ $tube->tubes_content->parasite_sample_types->name ?? 'N/A' }}</td>
                <td>{{ $tube->tubes_content->parasites->animal_samples->date_collected ?? 'N/A' }}</td>
                <td>{{ $tube->tubes_content->parasites->animal_samples->animals->animal_species->name_common ?? 'N/A' }}</td>
                <td>{{ $tube->tubes_content->parasites->animal_samples->places->name ?? 'N/A' }}</td>
            </tr>
        @endforeach

    </tbody>
    <div class="mt-4 text-center">
        <button id="confirm_ps_tube_selection" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
            Confirm Selection
        </button>
    </div>
</table>
