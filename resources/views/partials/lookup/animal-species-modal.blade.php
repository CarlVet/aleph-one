<x-lookup.table-modal
    id="animal_species_lookup_modal"
    title="Animal Species Table"
    empty-message="No animal species match the current filters."
    :columns="[
        ['key' => 'name_common', 'label' => 'Common name', 'minWidth' => '180px'],
        ['key' => 'name_scientific', 'label' => 'Scientific name', 'minWidth' => '220px'],
        ['key' => 'genus', 'label' => 'Genus', 'minWidth' => '140px'],
        ['key' => 'family', 'label' => 'Family', 'minWidth' => '140px'],
        ['key' => 'order', 'label' => 'Order', 'minWidth' => '140px'],
        ['key' => 'class', 'label' => 'Class', 'minWidth' => '140px'],
        ['key' => 'phylum', 'label' => 'Phylum', 'minWidth' => '140px'],
    ]"
/>
