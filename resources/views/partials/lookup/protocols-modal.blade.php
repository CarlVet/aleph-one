<x-lookup.table-modal
    id="protocols_lookup_modal"
    title="Protocols Table"
    empty-message="No protocols match the current filters."
    :columns="[
        ['key' => 'code', 'label' => 'Protocol code', 'minWidth' => '150px'],
        ['key' => 'name', 'label' => 'Protocol name', 'minWidth' => '220px'],
        ['key' => 'technique_name', 'label' => 'Technique name', 'minWidth' => '180px'],
        ['key' => 'technique_type', 'label' => 'Technique category', 'minWidth' => '200px'],
    ]"
/>
