<x-lookup.table-modal
    id="locations_lookup_modal"
    title="Storage Locations Table"
    empty-message="No storage locations match the current filters."
    :columns="[
        ['key' => 'name', 'label' => 'Location name', 'minWidth' => '180px'],
        ['key' => 'type', 'label' => 'Type', 'minWidth' => '140px'],
        ['key' => 'room', 'label' => 'Room', 'minWidth' => '120px'],
        ['key' => 'barcode', 'label' => 'Barcode', 'minWidth' => '120px'],
        ['key' => 'laboratory', 'label' => 'Laboratory', 'minWidth' => '180px'],
        ['key' => 'country', 'label' => 'Country', 'minWidth' => '140px'],
    ]"
/>
