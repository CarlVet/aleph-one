<x-lookup.table-modal
    id="pathogens_lookup_modal"
    title="Pathogen Species Table"
    empty-message="No pathogen species match the current filters."
    :columns="[
        ['key' => 'species', 'label' => 'Species', 'minWidth' => '220px'],
        ['key' => 'genus', 'label' => 'Genus', 'minWidth' => '140px'],
        ['key' => 'family', 'label' => 'Family', 'minWidth' => '140px'],
        ['key' => 'order', 'label' => 'Order', 'minWidth' => '140px'],
        ['key' => 'class', 'label' => 'Class', 'minWidth' => '140px'],
        ['key' => 'phylum', 'label' => 'Phylum', 'minWidth' => '140px'],
    ]"
/>
