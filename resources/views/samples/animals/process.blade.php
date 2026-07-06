<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">

        <!-- Buttons for List and Dashboard -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/animals/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/animals/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>

        <x-forms.form method="POST" action="/samples/animals/process" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Animal Samples Processing Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Select animal samples and specify processing parameters to create tubes</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-flask text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Sample Selection</h2>
                            </div>

                            <!-- Animal samples selection -->
                            <div class="flex items-center space-x-4">
                                <x-forms.table-button id="showTableBtn"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                                    <i class="fas fa-table mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                    Select Animal Samples
                                </x-forms.table-button>
                                <span id="sample_select_count" class="text-sm text-gray-600">(0 selected)</span>
                            </div>

                            <x-forms.field label="Selected samples:" name="sample_select[]">
                                <x-forms.select-input id="sample_select" name="sample_select[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($animal_samples as $sample)
                                        <option value="{{ $sample->id }}">
                                            {{ $sample->code }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-cogs text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Processing Parameters</h2>
                            </div>

                            <!-- Sample state input -->
                            <x-forms.field label="State of the samples:" name="sample_state">
                                <x-forms.select-input id="sample_state" name="sample_state" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select sample state</option>
                                    <option value="Untreated">Untreated</option>
                                    <option value="For DNA extraction">For DNA extraction</option>
                                    <option value="Preserved in PBS">Preserved in PBS</option>
                                    <option value="Preserved in Glycerol">Preserved in Glycerol</option>
                                    <option value="Frozen">Frozen</option>
                                    <option value="Fixed in Formalin">Fixed in Formalin</option>
                                    <option value="Dried">Dried</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Number of aliquots input -->
                            <x-forms.field label="Number of aliquots:" name="aliquots">
                                <x-forms.numeric-input id="aliquots" name="aliquots" min="1" max="20" value="3" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.numeric-input>
                            </x-forms.field>
                            
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                        <i class="fas fa-play mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Process Samples
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <!-- Modals -->
        <x-table-modal id="tableModal" title="Animal Samples Selection" closeButtonId="closeTableBtn">
            @include('samples.animals.modals.animal_samples_table')
        </x-table-modal>


        <!-- Success/Error Messages -->
        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            // Pass the PHP arrays to JavaScript
            var animalSamplesList = @json($animal_samples);
        </script>

        <!-- Selectize scripts -->
        @push('scripts')
        <script src="{{ asset('js/process-animal-samples.js') }}"></script>
        @endpush
    </div>
</x-layout>
