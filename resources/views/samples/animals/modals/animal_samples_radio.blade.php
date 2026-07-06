<!-- Table content -->
<table id="animal_samples_table" class="table table-striped">
    <thead>
        <tr>
            <th>Select</th>
            <th>Sample code</th>
            <th>Animal code</th>
            <th>Field ID</th>
            <th>Species</th>
            <th>Sex</th>
            <th>Age</th>
            <th>Sample Type</th>
            <th>Date Collected</th>
            <th>Park</th>
            <th>Latitude</th>
            <th>Longitude</th>
            <th>Collector</th>
            <th>Location</th>
            <th>Processed</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($animal_samples as $sample)
            <tr wire:key="{{ $sample->id }}">
                <td>
                    <input type="radio" name="animal_selection" class="select-animal" value="{{ $sample->id }}">
                </td>
                <td>{{ $sample->code }}</td>
                <td>{{ $sample->animals->code ?? 'N/A' }}</td>
                <td>{{ $sample->animals->field_label ?? 'N/A' }}</td>
                <td>{{ $sample->animals->animal_species->name_common ?? 'N/A' }}</td>
                <td>{{ $sample->animals->sex ?? 'N/A' }}</td>
                <td>{{ $sample->animals->age ?? 'N/A' }}</td>
                <td>{{ $sample->sample_types->name ?? 'N/A' }}</td>
                <td>{{ $sample->date_collected ?? 'N/A' }}</td>
                <td>{{ $sample->places->name ?? 'N/A' }}</td>
                <td>{{ $sample->latitude ?? 'N/A' }}</td>
                <td>{{ $sample->longitude ?? 'N/A' }}</td>
                <td>{{ $sample->people->first_name . ' ' . $sample->people->last_name ?? 'N/A' }}</td>
                <td>{{ $sample->locations->name ?? 'N/A' }}</td>
                <td>{{ $sample->processed == 1 ? 'Yes' : 'No' }}</td>
            </tr>
        @endforeach
    </tbody>
    <div class="mt-4 text-center">
        <button id="confirm_sample_selection" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded">
            Confirm Selection
        </button>
    </div>
</table>
