<!-- Table content -->
<table id="animals_table" class="display w-full">
    <thead>
        <tr>
            <th>Animal ID</th>
            <th>Internal code</th>
            <th>Field ID</th>
            <th>Species</th>
            <th>Sex</th>
            <th>Age</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($animals_existing as $sample)
        <tr>
            <td>{{ htmlspecialchars($sample->animals->id) }}</td>
            <td>{{ htmlspecialchars($sample->animals->code ?? 'N/A') }}</td>
            <td>{{ htmlspecialchars($sample->animals->field_label ?? 'N/A') }}</td>
            <td>{{ htmlspecialchars($sample->animals->animal_species->name_common ?? 'N/A') }}</td>
            <td>{{ htmlspecialchars($sample->animals->sex ?? 'N/A') }}</td>
            <td>{{ htmlspecialchars($sample->animals->age ?? 'N/A') }}</td>
        </tr>
        @endforeach
    </tbody>
</table>