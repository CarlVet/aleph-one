<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0" x-data="{ registerMode: '{{ old('register_mode', 'form') }}' }">
        <!-- Buttons for List and Dashboard -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-home mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                Samples Home
            </a>
            <a href="/samples/process/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-vial mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Tubes
            </a>
        </div>

        <div class="mx-auto mb-6 w-full max-w-2xl rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-sm font-semibold text-gray-800">
                    Register processed tubes by:
                </div>
                <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'form' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="process_register_mode" value="form" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-pen-to-square text-xs"></i>
                        Form
                    </label>
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'import' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="process_register_mode" value="import" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-file-csv text-xs"></i>
                        Import CSV
                    </label>
                </div>
            </div>
        </div>

        <div x-show="registerMode === 'form'" x-cloak>
        <x-forms.form method="POST" action="/samples/process" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Samples Processing Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Select samples and specify processing parameters to create tubes</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-flask text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Sample Selection</h2>
                            </div>

                            <!-- Sample Selection -->
                            <div class="space-y-4">
                                <!-- Sample Type Selection -->
                                <x-forms.field label="Sample Type:" name="sample_type">
                                    <x-forms.select-input id="sample_type" name="sample_type" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="">Select sample type</option>
                                        <option value="human">Human Samples</option>
                                        <option value="animal">Animal Samples</option>
                                        <option value="environment">Environmental Samples</option>
                                        <option value="parasite">Parasite Samples</option>
                                        <option value="nucleic">Nucleic Acids</option>
                                        <option value="culture">Cultures</option>
                                        <option value="pool">Pools</option>
                                    </x-forms.select-input>
                                </x-forms.field>

                                <!-- Human Samples -->
                                <div id="human_samples_section" style="display: none;" class="space-y-4">
                                    <div class="flex items-center space-x-4">
                                        <x-forms.table-button id="human_samples_btn"
                                            class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg shadow-md hover:shadow-lg border border-pink-600">
                                            <i class="fas fa-person mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                            Select Human Samples
                                        </x-forms.table-button>
                                        <span id="human_sample_select_count" class="text-sm text-gray-600">(0 selected)</span>
                                    </div>
                                    <x-forms.field name="human_sample_select[]">
                                        <x-forms.select-input id="human_sample_select" name="human_sample_select[]" multiple
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($selected_human_samples ?? [] as $sample)
                                                <option value="{{ $sample->id }}" selected>{{ $sample->code }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>

                                <!-- Animal Samples -->
                                <div id="animal_samples_section" style="display: none;" class="space-y-4">
                                    <div class="flex items-center space-x-4">
                                        <x-forms.table-button id="animal_samples_btn"
                                            class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg shadow-md hover:shadow-lg border border-yellow-600">
                                            <i class="fas fa-paw mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                            Select Animal Samples
                                        </x-forms.table-button>
                                        <span id="animal_sample_select_count" class="text-sm text-gray-600">(0 selected)</span>
                                    </div>
                                    <x-forms.field name="animal_sample_select[]">
                                        <x-forms.select-input id="animal_sample_select" name="animal_sample_select[]" multiple
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($selected_animal_samples ?? [] as $sample)
                                                <option value="{{ $sample->id }}" selected>{{ $sample->code }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>

                                <!-- Environment Samples -->
                                <div id="environment_samples_section" style="display: none;" class="space-y-4">
                                    <div class="flex items-center space-x-4">
                                        <x-forms.table-button id="environment_samples_btn"
                                            class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                                            <i class="fas fa-seedling mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                            Select Environmental Samples
                                        </x-forms.table-button>
                                        <span id="environment_sample_select_count" class="text-sm text-gray-600">(0 selected)</span>
                                    </div>
                                    <x-forms.field name="environment_sample_select[]">
                                        <x-forms.select-input id="environment_sample_select" name="environment_sample_select[]" multiple
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($selected_environment_samples ?? [] as $sample)
                                                <option value="{{ $sample->id }}" selected>{{ $sample->code }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>

                                <!-- Parasite Samples -->
                                <div id="parasite_samples_section" style="display: none;" class="space-y-4">
                                    <div class="flex items-center space-x-4">
                                        <x-forms.table-button id="parasite_samples_btn"
                                            class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg border border-purple-600">
                                            <i class="fas fa-bug mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                            Select Parasite Samples
                                        </x-forms.table-button>
                                        <span id="parasite_sample_select_count" class="text-sm text-gray-600">(0 selected)</span>
                                    </div>
                                    <x-forms.field name="parasite_sample_select[]">
                                        <x-forms.select-input id="parasite_sample_select" name="parasite_sample_select[]" multiple
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($selected_parasite_samples ?? [] as $sample)
                                                <option value="{{ $sample->id }}" selected>{{ $sample->code }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>
                                <!-- Nucleic Acids -->
                                <div id="nucleic_acids_section" style="display: none;" class="space-y-4">
                                    <div class="flex items-center space-x-4">
                                        <x-forms.table-button id="nucleic_acids_btn"
                                            class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg shadow-md hover:shadow-lg border border-blue-600">
                                            <i class="fas fa-dna mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                            Select Nucleic Acids
                                        </x-forms.table-button>
                                        <span id="nucleic_acid_select_count" class="text-sm text-gray-600">(0 selected)</span>
                                    </div>
                                    <x-forms.field name="nucleic_acid_select[]">
                                        <x-forms.select-input id="nucleic_acid_select" name="nucleic_acid_select[]" multiple
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($selected_nucleic_acids ?? [] as $sample)
                                                <option value="{{ $sample->id }}" selected>{{ $sample->code }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>
                                <!-- Cultures -->
                                <div id="cultures_section" style="display: none;" class="space-y-4">
                                    <div class="flex items-center space-x-4">
                                        <x-forms.table-button id="cultures_btn"
                                            class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg shadow-md hover:shadow-lg border border-orange-600">
                                            <i class="fas fa-bacteria mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                            Select Cultures
                                        </x-forms.table-button>
                                        <span id="culture_select_count" class="text-sm text-gray-600">(0 selected)</span>
                                    </div>
                                    <x-forms.field name="culture_select[]">
                                        <x-forms.select-input id="culture_select" name="culture_select[]" multiple
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($selected_cultures ?? [] as $sample)
                                                <option value="{{ $sample->id }}" selected>{{ $sample->code }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>
                                <!-- Pools -->
                                <div id="pools_section" style="display: none;" class="space-y-4">
                                    <div class="flex items-center space-x-4">
                                        <x-forms.table-button id="pools_btn"
                                            class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-cyan-500 to-blue-400 text-white rounded-lg shadow-md hover:shadow-lg border border-cyan-600">
                                            <i class="fas fa-layer-group mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                            Select Pools
                                        </x-forms.table-button>
                                        <span id="pool_select_count" class="text-sm text-gray-600">(0 selected)</span>
                                    </div>
                                    <x-forms.field name="pool_select[]">
                                        <x-forms.select-input id="pool_select" name="pool_select[]" multiple
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($selected_pools ?? [] as $sample)
                                                <option value="{{ $sample->id }}" selected>{{ $sample->code }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>



                                <!-- Sample Collection Type -->
                                <x-forms.field label="Timing of Sampling Event:" name="is_historical">
                                    <div class="space-y-2">
                                        <label class="flex items-center">
                                            <input type="radio" name="is_historical" value="0" checked
                                                class="mr-2 text-blue-600 focus:ring-blue-500">
                                            <span class="text-sm text-gray-700">Prospective (Current Collection)</span>
                                        </label>
                                        <label class="flex items-center">
                                            <input type="radio" name="is_historical" value="1"
                                                class="mr-2 text-blue-600 focus:ring-blue-500">
                                            <span class="text-sm text-gray-700">Retrospective (Historical Collection)</span>
                                        </label>
                                    </div>
                                </x-forms.field>

                                <!-- Alias Code Assignment (shown only for historical samples) -->
                                <div id="alias_code_section" style="display: none;" class="space-y-4">
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                        <div class="flex items-center mb-3">
                                            <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                            <h3 class="text-sm font-semibold text-yellow-800">Historical Samples - Alias Code Assignment</h3>
                                        </div>
                                        <p class="text-sm text-yellow-700 mb-4">
                                            For historical/retrospective samples, you need to assign alias codes to each tube. 
                                            The system will generate <span id="total_tubes_count" class="font-semibold">0</span> tubes total.
                                        </p>
                                        <label class="mb-3 inline-flex items-center gap-2 text-sm text-yellow-800">
                                            <input type="checkbox" id="auto_alias_from_source"
                                                class="h-4 w-4 rounded border-yellow-300 text-yellow-600 focus:ring-yellow-500">
                                            <span>Auto-populate alias codes from selected primary sample field labels</span>
                                        </label>
                                        <div id="alias_code_assignments" class="space-y-2">
                                            <!-- Dynamic alias code inputs will be generated here -->
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-cogs text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Processing Parameters</h2>
                            </div>

                            <!-- Tube type input -->
                            <x-forms.field label="Tube Type:" name="tube_type">
                                <x-forms.select-input id="tube_type" name="tube_type" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select tube type</option>
                                    @foreach (($tube_type_options ?? []) as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Purpose input -->
                            <x-forms.field label="Purpose:" name="purpose">
                                <x-forms.select-input id="purpose" name="purpose" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select purpose</option>
                                    @foreach (($purpose_options ?? []) as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Preservant input -->
                            <x-forms.field label="Preservant:" name="preservant">
                                <x-forms.select-input id="preservant" name="preservant"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select preservant (optional)</option>
                                    @foreach (($preservant_options ?? []) as $opt)
                                        <option value="{{ $opt }}">{{ $opt }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Amount and unit inputs -->
                            <div class="grid grid-cols-2 gap-4">
                                <x-forms.field label="Amount (optional):" name="amount">
                                    <x-forms.numeric-input id="amount" name="amount" min="0" step="0.01"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    </x-forms.numeric-input>
                                </x-forms.field>
                                <x-forms.field label="Unit (optional):" name="amount_unit">
                                    <x-forms.select-input id="amount_unit" name="amount_unit"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="">Select unit</option>
                                        @foreach (($amount_unit_options ?? []) as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Date processed input -->
                            <x-forms.field label="Date Processed:" name="date_processed">
                                <x-forms.date-input id="date_processed" name="date_processed" value="{{ now()->toDateString() }}" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.date-input>
                            </x-forms.field>

                            <!-- Number of aliquots input -->
                            <x-forms.field label="Number of Aliquots:" name="aliquots">
                                <x-forms.numeric-input id="aliquots" name="aliquots" min="1" max="50" value="1" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.numeric-input>
                            </x-forms.field>

                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-purple-600">
                        <i class="fas fa-play mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Process Samples
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>
        </div>

        <div x-show="registerMode === 'import'" x-cloak>
            <livewire:imports.process-samples-import />
        </div>

        <!-- Sample Selection Modals -->
        <x-table-modal id="tableModal" title="Sample Selection" closeButtonId="closeTableBtn">
            <div id="modalContent" class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <!-- Success/Error Messages -->
        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif



        <!-- Selectize scripts -->
        @push('scripts')
        <script src="{{ asset('js/process-field-samples.js') }}?v={{ filemtime(public_path('js/process-field-samples.js')) }}"></script>
        @endpush
    </div>
</x-layout> 