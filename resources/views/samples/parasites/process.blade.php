<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">

        <x-forms.form method="POST" action="/samples/animals/process" enctype="multipart/form-data">
            @csrf
            <div class="shadow sm:overflow-hidden sm:rounded-md">
                <div class="space-y-4 bg-white px-4 pt-2">
                    <div class="text-center mb-2">
                        <h2 class="text-lg font-bold mt-2">Animal Samples Processing Form</h2>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">

                        <!-- Left Column -->
                        <div>

                            <h2 class="block text-sm font-bold leading-6 text-gray-900">Samples Information</h2>

                            <!-- Animal ID selection for existing animals -->
                            <div id="existing_animal_fields">
                                <x-forms.field label="Select samples:" name="sample_id">
                                    <x-forms.select-input id="sample_id" name="sample_id[]" multiple required>
                                        @foreach ($animal_samples as $sample)
                                            <option value="{{ $sample->id }}">
                                                {{ $sample->id . ': ' . $sample->sample_types->name . ', Animal ID ' . $sample->animals->id . ' (' . $sample->animals->field_label . '), ' . $sample->animals->animal_species->name_common . ', ' . $sample->places->name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <div class="flex items-center">
                                <x-forms.table-button id="showTableBtn">View Animal samples</x-forms.table-button>
                            </div>

                        </div>

                        <!-- Right Column -->
                        <div>

                            <h2 class="block text-sm font-bold leading-6 text-gray-900">Processing Information</h2>

                            <!-- Sample state input -->
                            <x-forms.field label="State of the samples:" name="sample_state">
                                <x-forms.select-input id="sample_state" name="sample_state" required>
                                    <option value="Untreated">Untreated</option>
                                    <option value="For DNA extraction">For DNA extraction</option>
                                    <option value="Preserved in PBS">Preserved in PBS</option>
                                    <option value="Preserved in Glycerol">Preserved in Glycerol</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Nr of aliquots input -->
                            <x-forms.field label="Nr. of aliquots:" name="aliquots">
                                <x-forms.numeric-input id="aliquots" name="aliquots" min="1" max="10"
                                    value="3"></x-forms.numeric-input>
                            </x-forms.field>

                            <div class="flex items-center">
                                <x-forms.table-button id="animal_tubes_btn">View Animal Tubes</x-forms.table-button>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 px-4 py-3 flex items-center justify-center">
                <x-forms.submit></x-forms.submit>
            </div>

        </x-forms.form>

        <x-table-modal id="tableModal" title="Animal Samples" closeButtonId="closeTableBtn">
            @include('samples.animals.modals.animal_samples_table')
        </x-table-modal>

        <x-table-modal id="animal_tubes_modal" title="Animal Tubes" closeButtonId="animal_tubes_close_btn">
            @include('samples.animals.modals.animal_tubes_table')
        </x-table-modal>

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            // Pass the PHP array to JavaScript
            var speciesList = @json($animal_species);
            var placesList = @json($places);
        </script>

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="/js/process-animal-samples.js"></script>
        @endpush
    </div>
</x-layout>
