<x-lookup.table-modal
    id="sampling_sites_lookup_modal"
    title="Sampling Sites Table"
    empty-message="No sampling sites match the current filters."
    :columns="[
        ['key' => 'name', 'label' => 'Site name', 'minWidth' => '180px'],
        ['key' => 'site_type', 'label' => 'Site type', 'minWidth' => '140px'],
        ['key' => 'country', 'label' => 'Country', 'minWidth' => '140px'],
        ['key' => 'region', 'label' => 'Region', 'minWidth' => '140px'],
        ['key' => 'city', 'label' => 'City', 'minWidth' => '140px'],
        ['key' => 'organization', 'label' => 'Organization', 'minWidth' => '180px'],
        ['key' => 'latitude', 'label' => 'Latitude', 'minWidth' => '120px'],
        ['key' => 'longitude', 'label' => 'Longitude', 'minWidth' => '120px'],
    ]"
/>
