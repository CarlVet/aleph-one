<x-layout>
    <x-slot:heading>
        Animal Samples Dashboard
    </x-slot:heading>

    <!-- Content Section -->
    <div class="container max-w-7xl mx-auto mt-4 px-4">

        <!-- Dashboard Widgets -->
        <div class="row g-4 mb-5">

            <x-dashboard-widget id="animalsCard" length="col-lg-3" theme="primary" message="Click to view Animals"
            title="Total Animals" data="{{$totals['animals'] }}"></x-dashboard-widget>
            
            <x-table-modal id="animalsTableModal" title="Animals" closeButtonId="closeAnimalsTable">
                @include('samples.animals.modals.animals_table')
            </x-table-modal>
            
            <x-dashboard-widget id="animalSpeciesCard" length="col-lg-3" theme="success" message="Click to view Animal Species"
            title="Total Animal Species" data="{{$totals['animals.animal_species'] }}"></x-dashboard-widget>

            <x-table-modal id="animalSpeciesTableModal" title="Animal Species" closeButtonId="closeAnimalSpeciesTable">
                @include('samples.animals.modals.animal_species_table')
            </x-table-modal>

            <x-dashboard-widget id="sitesCard" length="col-lg-3" theme="warning" message="Click to view Sampling Sites"
            title="Total Sampling Sites" data="{{$totals['places'] }}"></x-dashboard-widget>

            <x-table-modal id="samplingSitesTableModal" title="Sampling sites" closeButtonId="closesamplingSitesTable">
                @include('samples.animals.modals.sampling_sites_table')
            </x-table-modal>

            <x-dashboard-widget id="typesCard" length="col-lg-3" theme="danger" message="Click to view Sample Types"
            title="Total Sample Types" data="{{$totals['sample_types'] }}"></x-dashboard-widget>

            <x-table-modal id="sampleTypesTableModal" title="Sample types" closeButtonId="closesampleTypesTable">
                @include('samples.animals.modals.sample_types_table')
            </x-table-modal>
        </div>

        <!-- Map and Bar Plot Section -->
        <div class="row mb-5">
            <x-dashboard-container title="Samples Location Map" length="col-md-6">
                <div id="map" style="height: 400px; border-radius: 5px;"></div>
            </x-dashboard-container>     
            
            <x-dashboard-container title="Animals Distribution by Species" length="col-md-6">
                <canvas id="speciesBarChart" style="height: 400px;"></canvas>
            </x-dashboard-container>  
        </div>

        <!-- Data Table Section -->
        <div class="row mt-5">
            <x-dashboard-container title="Animal Samples Table" length="col-12">
                <div class="table-responsive">
                    @include('samples.animals.modals.animal_samples_table')
                </div>
            </x-dashboard-container>
        </div>

    </div>
    
    <script>
        const animalSamples = @json($animals_existing);
    </script>

    @push('scripts')
    <script src="/js/show-animal-samples.js"></script>
    @endpush

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">

</x-layout>