<!-- Table content -->
<table id="animal_species_table" class="display w-full">
    <thead>
        <tr>
            <th>Animal Species ID</th>
            <th>Common Name</th>
            <th>Scientific name</th>
            <th>Family name</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($animal_species_existing as $sample)
        <tr>
            <td>{{ $sample->animals->animal_species->id }}</td>
            <td>{{ $sample->animals->animal_species->name_common}}</td>
            <td>{{ $sample->animals->animal_species->name_scientific ?? 'N/A' }}</td>
            <td>{{ $sample->animals->animal_species->family ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>