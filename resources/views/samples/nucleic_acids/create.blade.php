<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <!-- Create, Edit, Dashboard (Centered) -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/nucleic/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/nucleic/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>

        <x-forms.form method="POST" action="/samples/nucleic" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="project_id_snapshot" value="{{ (int) ($selected_project_id ?? session('selected_project_id')) }}">
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Nucleic Acids Extraction Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to extract a new nucleic acid</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-flask text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Sample Information</h2>
                            </div>

                            <!-- Tube ID selection -->
                            <x-forms.field label="Select sample origin:" name="model">
                                <x-forms.select-input id="model" name="model" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Human samples">Human samples</option>
                                    <option value="Animal samples">Animal samples</option>
                                    <option value="Environmental samples">Environmental samples</option>
                                    <option value="Parasite samples">Parasite samples</option>
                                    <option value="Experiments">Experiments</option>
                                    <option value="Cultures">Cultures</option>
                                    <option value="Pools">Pools</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            @include('partials.tube-badge-display-toggle')

                            <!-- Human Samples -->
                            <div id="human_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="human_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg shadow-md hover:shadow-lg border border-pink-600">
                                        <i class="fas fa-person mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Human Tubes
                                    </x-forms.table-button>
                                    <span id="human_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="human_tube_id[]">
                                    <x-forms.select-input id="human_tube_id" name="human_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($selected_human_tubes ?? [] as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Animal Samples -->
                            <div id="animal_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="animal_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg shadow-md hover:shadow-lg border border-yellow-600">
                                        <i class="fas fa-paw mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Animal Tubes
                                    </x-forms.table-button>
                                    <span id="animal_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="animal_tube_id[]">
                                    <x-forms.select-input id="animal_tube_id" name="animal_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($selected_animal_tubes ?? [] as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Environmental Samples -->
                            <div id="environment_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="environment_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                                        <i class="fas fa-leaf mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Environmental Tubes
                                    </x-forms.table-button>
                                    <span id="environment_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="environment_tube_id[]">
                                    <x-forms.select-input id="environment_tube_id" name="environment_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($selected_environment_tubes ?? [] as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Parasite Samples -->
                            <div id="parasite_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="parasite_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg border border-purple-600">
                                        <i class="fas fa-spider mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Parasite Tubes
                                    </x-forms.table-button>
                                    <span id="parasite_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="parasite_tube_id[]">
                                    <x-forms.select-input id="parasite_tube_id" name="parasite_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($selected_parasite_tubes ?? [] as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Experiments -->
                            <div id="experiment_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="experiment_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-900 to-blue-800 text-white rounded-lg shadow-md hover:shadow-lg border border-purple-600">
                                        <i class="fas fa-microscope mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Experiments
                                    </x-forms.table-button>
                                    <span id="experiment_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="experiment_id[]">
                                    <x-forms.select-input id="experiment_id" name="experiment_id[]" multiple
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($selected_experiments ?? [] as $experiment)
                                            <option value="{{ $experiment->id }}" selected>{{ $experiment->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Cultures -->
                            <div id="culture_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="culture_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg shadow-md hover:shadow-lg border border-orange-600">
                                        <i class="fas fa-bacteria mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Culture Tubes
                                    </x-forms.table-button>
                                    <span id="culture_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="culture_tube_id[]">
                                    <x-forms.select-input id="culture_tube_id" name="culture_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($selected_culture_tubes ?? [] as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Pools -->
                            <div id="pool_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4">
                                    <x-forms.table-button id="pool_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-cyan-500 to-cyan-600 text-white rounded-lg shadow-md hover:shadow-lg border border-cyan-600">
                                        <i class="fas fa-layer-group mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Pool Tubes
                                    </x-forms.table-button>
                                    <span id="pool_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="pool_tube_id[]">
                                    <x-forms.select-input id="pool_tube_id" name="pool_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($selected_pool_tubes ?? [] as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Extraction Timing -->
                            <x-forms.field label="Timing of Extraction Event:" name="is_historical">
                                <div class="space-y-2">
                                    <label class="flex items-center">
                                        <input type="radio" name="is_historical" value="0" checked
                                            class="mr-2 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Prospective (Current Extraction)</span>
                                    </label>
                                    <label class="flex items-center">
                                        <input type="radio" name="is_historical" value="1"
                                            class="mr-2 text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm text-gray-700">Retrospective (Historical Extraction)</span>
                                    </label>
                                </div>
                            </x-forms.field>

                            <!-- Alias Code Assignment (shown only for historical extractions) -->
                            <div id="alias_code_section" style="display: none;" class="space-y-4">
                                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <div class="flex items-center mb-3">
                                        <i class="fas fa-exclamation-triangle text-yellow-600 mr-2"></i>
                                        <h3 class="text-sm font-semibold text-yellow-800">Historical Extraction - Alias Code Assignment</h3>
                                    </div>
                                    <p class="text-sm text-yellow-700 mb-4">
                                        For historical/retrospective extractions, you need to assign alias codes to each nucleic acid. 
                                        The system will generate <span id="total_nucleic_count" class="font-semibold">0</span> nucleic acids total.
                                    </p>
                                    <label class="mb-3 inline-flex items-center gap-2 text-sm text-yellow-800">
                                        <input type="checkbox" id="auto_alias_from_source" class="h-4 w-4 rounded border-yellow-300 text-yellow-600 focus:ring-yellow-500">
                                        <span>Auto-populate alias codes from selected source tubes</span>
                                    </label>
                                    <div id="alias_code_assignments" class="space-y-2">
                                        <!-- Dynamic alias code inputs will be generated here -->
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-microscope text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Extraction Information</h2>
                            </div>

                            <!-- Input for type -->
                            <x-forms.field label="Type of nucleic acid:" name="type">
                                <x-forms.select-input id="type" name="type" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($nucleic_types as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Input for extraction protocol -->
                            <x-forms.field label="Protocol of nucleic acid extraction:" name="protocol">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <x-forms.select-input id="protocol" name="protocol" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($nucleic_methods_available as $protocol)
                                                <option value="{{ $protocol->name }}">{{ $protocol->name }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </div>
                                    <x-lookup.button id="protocols_lookup_btn" title="Browse protocols table" />
                                </div>
                            </x-forms.field>

                            <button id="nucleic_protocol_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Protocol
                            </button>

                            <!-- Solution reagent input -->
                            <x-forms.field label="Elution solution:" name="solution">
                                <x-forms.select-input id="solution" name="solution" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="TE buffer">TE buffer</option>
                                    <option value="Distilled H2O">Distilled H2O</option>
                                    <option value="Nuclease-free H2O">Nuclease-free H2O</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Elution volume input -->
                            <x-forms.field label="Elution volume (µl):" name="elution">
                                <x-forms.numeric-input id="elution" name="elution" min="0" max="1000" step="any"
                                    value="100"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.numeric-input>
                            </x-forms.field>

                            <!-- Extraction date input -->
                            <x-forms.field label="Extraction date:" name="date">
                                <x-forms.date-input id="date" name="date" value="{{ now()->toDateString() }}" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.date-input>
                            </x-forms.field>

                            <!-- Laboratory input -->
                            <x-forms.field label="Extracted at:" name="nucleic_lab">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <x-forms.select-input id="nucleic_lab" name="nucleic_lab" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($labs_available as $country => $labs)
                                                <optgroup label="{{ $country }}">
                                                    @foreach ($labs as $lab)
                                                        <option value="{{ $lab['name'] }}">{{ $lab['name'] }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </x-forms.select-input>
                                    </div>
                                    <x-lookup.button id="laboratories_lookup_btn" title="Browse laboratories table" />
                                </div>
                            </x-forms.field>

                            <button id="nucleic_lab_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Laboratory
                            </button>

                            <!-- Collector input -->
                            <x-forms.field label="Extracted by:" name="extractor">
                                @if (!($can_assign_registrar ?? false) && !empty($locked_registrar_people_id))
                                    <input type="hidden" name="extractor" value="{{ (int) $locked_registrar_people_id }}">
                                @endif
                                @if ($can_assign_registrar ?? false)
                                    <x-forms.select-input id="extractor" name="extractor" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) old('extractor') === (int) $person->id)>
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <x-forms.select-input id="extractor" name="extractor" required disabled
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) old('extractor', $locked_registrar_people_id ?? null) === (int) $person->id)>
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @endif
                            </x-forms.field>

                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Nucleic Acid
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <!-- Tube Selection Modals -->
        <x-table-modal id="human_tubes_modal" title="Human Tubes" closeButtonId="human_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="animal_tubes_modal" title="Animal Tubes" closeButtonId="animal_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="environment_tubes_modal" title="Environmental Tubes" closeButtonId="environment_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="parasite_tubes_modal" title="Parasite Tubes" closeButtonId="parasite_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="experiment_modal" title="Experiments" closeButtonId="experiment_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="culture_tubes_modal" title="Culture Tubes" closeButtonId="culture_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>


        <x-table-modal id="pool_tubes_modal" title="Pool Tubes" closeButtonId="pool_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="nucleic_lab_form_modal" title="Laboratory Registration Form"
            closeButtonId="nucleic_lab_form_close_btn">
            @include('modals.form_laboratories')
        </x-table-modal>

        @include('partials.lookup.protocols-modal')
        @include('partials.lookup.laboratories-modal')

        <script>
            window.protocolLookupRows = @json($protocol_lookup_rows ?? []);
            window.laboratoryLookupRows = @json($laboratory_lookup_rows ?? []);
        </script>

        <x-table-modal id="nucleic_protocol_form_modal" title="Protocol Registration Form"
            closeButtonId="nucleic_protocol_form_close_btn">
            @include('modals.form_nucleic_protocol')
        </x-table-modal>

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="/js/lookup-table.js?v={{ filemtime(public_path('js/lookup-table.js')) }}"></script>
            <script src="/js/create-nucleic-acids.js?v={{ filemtime(public_path('js/create-nucleic-acids.js')) }}"></script>
        @endpush
    </div>
</x-layout>
