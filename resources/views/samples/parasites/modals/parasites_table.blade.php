<!-- Table content -->
<table id="parasites_table" class="display w-full">
    <thead>
        <tr>
            <th>Parasite ID</th>
            <th>Internal code</th>
            <th>Species</th>
            <th>Sex</th>
            <th>Stage</th>
            <th>Repletion state</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($parasites as $parasite)
        <tr>
            <td>{{ $parasite->id }}</td>
            <td>{{ $parasite->code ?? 'N/A' }}</td>
            <td>{{ $parasite->parasite_species->name_scientific ?? 'N/A' }}</td>
            <td>{{ $parasite->sex ?? 'N/A' }}</td>
            <td>{{ $parasite->stage ?? 'N/A' }}</td>
            <td>{{ $parasite->state ?? 'N/A' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>