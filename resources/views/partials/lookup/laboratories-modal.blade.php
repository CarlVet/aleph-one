<x-lookup.table-modal
    id="laboratories_lookup_modal"
    title="Laboratories Table"
    empty-message="No laboratories match the current filters."
    :columns="[
        ['key' => 'name', 'label' => 'Laboratory name', 'minWidth' => '220px'],
        ['key' => 'lab_type', 'label' => 'Type', 'minWidth' => '160px'],
        ['key' => 'country', 'label' => 'Country', 'minWidth' => '160px'],
        ['key' => 'city', 'label' => 'City', 'minWidth' => '160px'],
        ['key' => 'address', 'label' => 'Address', 'minWidth' => '260px'],
        ['key' => 'latitude', 'label' => 'Latitude', 'minWidth' => '120px'],
        ['key' => 'longitude', 'label' => 'Longitude', 'minWidth' => '120px'],
    ]"
/>
