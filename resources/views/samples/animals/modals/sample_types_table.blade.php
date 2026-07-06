<!-- Table content -->
<table id="animal_samples_table" class="display w-full">
    <thead>
        <tr>
            <th>Sample Type ID</th>
            <th>Name</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sample_types_existing as $sample)
        <tr>
            <td>{{ $sample->sample_types->id }}</td>
            <td>{{ $sample->sample_types->name ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>