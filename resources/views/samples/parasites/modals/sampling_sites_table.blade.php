<!-- Table content -->
<table id="sampling_sites_table" class="display w-full">
    <thead>
        <tr>
            <th>Sampling site ID</th>
            <th>Name</th>
            <th>Country</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($sampling_sites_existing as $sample)
        <tr>
            <td>{{ $sample->places->id }}</td>
            <td>{{ $sample->places->name }}</td>
            <td>{{ $sample->places->country }}</td>
        </tr>
        @endforeach
    </tbody>
</table>