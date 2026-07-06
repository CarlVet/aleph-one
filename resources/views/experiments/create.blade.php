<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0" x-data="{ registerMode: '{{ old('register_mode', 'form') }}' }">

         <!-- Create, Edit, Dashboard (Centered) -->
         <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/experiments/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            <a href="/experiments/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>
        <div class="mx-auto mb-6 w-full max-w-2xl rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-sm font-semibold text-gray-800">
                    Register experiments by:
                </div>
                <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'form' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="experiments_register_mode" value="form" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-pen-to-square text-xs"></i>
                        Form
                    </label>
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'import' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="experiments_register_mode" value="import" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-file-csv text-xs"></i>
                        Import CSV
                    </label>
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'table' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="experiments_register_mode" value="table" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-table text-xs"></i>
                        Table
                    </label>
                </div>
            </div>
        </div>

        <div x-show="registerMode === 'import'" x-cloak class="mt-6">
            <livewire:imports.experiments-import />
        </div>

        @include('experiments.partials.table-registration')

        <div x-show="registerMode === 'form'" x-cloak>
        <x-forms.form method="POST" action="/experiments" enctype="multipart/form-data">
            @csrf
            <input type="hidden" name="register_mode" value="form">
            <input type="hidden" id="suitability_override" name="suitability_override" value="0">
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Experiments Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new experiment</p>
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
                                    <option value="Nucleic acids">Nucleic acids</option>
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
                                        @foreach (($selected_human_tubes ?? []) as $tube)
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
                                        @foreach (($selected_animal_tubes ?? []) as $tube)
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
                                    <x-forms.select-input id="environment_tube_id" name="environment_tube_id[]"
                                        multiple data-tube-badge-toggle="1" class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach (($selected_environment_tubes ?? []) as $tube)
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
                                        @foreach (($selected_parasite_tubes ?? []) as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Nucleic acids -->
                            <div id="nucleic_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="nucleic_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-lg shadow-md hover:shadow-lg border border-indigo-600">
                                        <i class="fas fa-dna mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Nucleic Tubes
                                    </x-forms.table-button>
                                    <span id="nucleic_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="nucleic_tube_id[]">
                                    <x-forms.select-input id="nucleic_tube_id" name="nucleic_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach (($selected_nucleic_tubes ?? []) as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
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
                                        @foreach (($selected_culture_tubes ?? []) as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Pools -->
                            <div id="pool_model" style="display: none;" class="space-y-4">
                                <div class="flex items center space-x-4" data-tube-display-anchor="1">
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
                                        @foreach (($selected_pool_tubes ?? []) as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <div class="pt-4 border-t border-gray-100">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Ancillary Information</h2>
                                </div>

                                <!-- Experiment date input -->
                                <x-forms.field label="Experiment date:" name="date" class="mt-4">
                                    <x-forms.date-input id="date" name="date" value="{{ now()->toDateString() }}"
                                        required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    </x-forms.date-input>
                                </x-forms.field>

                                <!-- Laboratory input -->
                                <x-forms.field label="Laboratory where performed:" name="lab" class="mt-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-1">
                                            <x-forms.select-input id="lab" name="lab" required
                                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                @foreach ($laboratories_by_country as $country => $labs_list)
                                                    <optgroup label="{{ $country }}">
                                                        @foreach ($labs_list as $lab)
                                                            <option value="{{ $lab['name'] }}">{{ $lab['name'] }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </x-forms.select-input>
                                        </div>
                                        <button id="laboratory_lookup_btn" type="button"
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition-colors duration-200 hover:bg-blue-100"
                                            title="Browse laboratories table">
                                            <i class="fas fa-table text-sm"></i>
                                        </button>
                                    </div>
                                </x-forms.field>

                                <button id="laboratory_btn" type="button"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                    <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                    Create New Laboratory
                                </button>

                                <!-- Collector input -->
                                <x-forms.field label="Performed by:" name="scientist" class="mt-4">
                                    @if ($can_assign_registrar)
                                        <x-forms.select-input id="scientist" name="scientist" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($people as $person)
                                                <option value="{{ $person->id }}">
                                                    {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                                </option>
                                            @endforeach
                                        </x-forms.select-input>
                                    @else
                                        <x-forms.select-input id="scientist_locked" name="scientist_locked" disabled
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($people as $person)
                                                <option value="{{ $person->id }}"
                                                    @selected((int) $person->id === (int) ($locked_registrar_people_id ?? 0))>
                                                    {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                                </option>
                                            @endforeach
                                        </x-forms.select-input>
                                        <input type="hidden" name="scientist" value="{{ $locked_registrar_people_id }}">
                                    @endif
                                </x-forms.field>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-microscope text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Experiment Information</h2>
                            </div>

                            <!-- Input for protocol -->
                            <x-forms.field label="Protocol:" name="protocol">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <x-forms.select-input id="protocol" name="protocol" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($exp_protocols as $protocol)
                                                <option value="{{ $protocol->name }}">{{ $protocol->name }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </div>
                                    <button id="protocol_lookup_btn" type="button"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition-colors duration-200 hover:bg-blue-100"
                                        title="Browse protocols table">
                                        <i class="fas fa-table text-sm"></i>
                                    </button>
                                </div>
                            </x-forms.field>

                            <div id="experiment_suitability_warning" class="hidden rounded-lg border border-yellow-200 bg-yellow-50 p-4">
                                <div class="flex items-start gap-3">
                                    <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5"></i>
                                    <div class="flex-1">
                                        <div class="text-sm font-semibold text-yellow-800">Sample suitability warning</div>
                                        <div class="mt-1 text-sm text-yellow-800">
                                            The selected protocol/technique may not be appropriate for the selected samples.
                                            You can still proceed, but please double check.
                                        </div>
                                        <ul id="experiment_suitability_warning_list" class="mt-2 list-disc pl-5 text-sm text-yellow-800"></ul>

                                        <label class="mt-3 inline-flex items-center gap-2 text-sm text-yellow-900 cursor-pointer select-none">
                                            <input id="experiment_suitability_ack" type="checkbox" class="text-yellow-600 focus:ring-yellow-500">
                                            <span>I understand and want to proceed anyway</span>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <button id="protocol_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Protocol
                            </button>

                            <!-- Input for pathogen -->
                            <x-forms.field label="Pathogen:" name="pathogen[]" class="mt-4">
                                <x-forms.select-input id="pathogen" name="pathogen[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($pathogens as $pathogen)
                                        <option value="{{ $pathogen->species }}">{{ $pathogen->species }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <button id="pathogen_protocol_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i class="fas fa-link mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                Create New Protocol-Pathogen Association
                            </button>

                            <!-- Test purpose -->
                            <div class="mt-4" x-data="{ showPurposeInfo: false }">
                                <div class="mb-2 mt-2 flex items-center gap-1">
                                    <label for="purpose" class="block text-sm font-medium text-gray-700">Test purpose:</label>
                                    <button type="button" x-on:click="showPurposeInfo = !showPurposeInfo"
                                        class="text-blue-500 hover:text-blue-700 focus:outline-none"
                                        title="Why is test purpose important?">
                                        <i class="fas fa-info-circle"></i>
                                    </button>
                                </div>
                                <div x-show="showPurposeInfo" x-cloak x-transition
                                    class="mb-2 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                                    <p class="font-semibold">Why specify the test purpose?</p>
                                    <p class="mt-1">
                                        A <strong>screening</strong> test is used to identify potentially positive samples,
                                        while a <strong>confirmation</strong> test verifies those results with a more specific method.
                                        Recording this lets the dashboard compute accurate prevalence: it can isolate screening-only
                                        or confirmation-only results, or report only samples that were both screened
                                        <em>and</em> confirmed (screening with confirmation).
                                    </p>
                                </div>
                                <x-forms.field name="purpose">
                                    <x-forms.select-input id="purpose" name="purpose" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="">Select test purpose</option>
                                        @foreach (\App\Enums\ExperimentPurpose::options() as $purposeValue => $purposeLabel)
                                            <option value="{{ $purposeValue }}" @selected(old('purpose') === $purposeValue)>{{ $purposeLabel }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Outcome Type Selection -->
                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <div class="flex items-center space-x-2 mb-4">
                                    <i class="fas fa-chart-bar text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Outcome Type</h2>
                                </div>
                                
                                <div class="space-y-3">
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="radio" name="outcome_type" value="qualitative" class="form-radio text-blue-600 focus:ring-blue-500" checked>
                                        <span class="text-sm font-medium text-gray-700">Qualitative outcome only</span>
                                    </label>
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="radio" name="outcome_type" value="both" class="form-radio text-blue-600 focus:ring-blue-500">
                                        <span class="text-sm font-medium text-gray-700">Both qualitative and quantitative outcomes</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Qualitative outcome input -->
                            <div id="qualitative_outcome_section" class="mt-4">
                                <x-forms.field label="Qualitative outcome:" name="outcome_qual">
                                    <x-forms.select-input id="outcome_qual" name="outcome_qual"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="">Select qualitative outcome</option>
                                        @foreach (($outcome_qual_options ?? []) as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Quantitative outcome input -->
                            <div id="quantitative_outcome_section" class="mt-4" style="display: none;">
                                <x-forms.field label="Quantitative outcome:" name="outcome_quant">
                                    <x-forms.numeric-input id="outcome_quant" name="outcome_quant" min="-10000"
                                        max="10000" step="any" value="0.0000"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    </x-forms.numeric-input>
                                </x-forms.field>
                            </div>

                            <!-- Photo upload -->
                            <div class="mt-6 pt-4 border-t border-gray-100">
                                <div class="flex items-center space-x-2 mb-4">
                                    <i class="fas fa-camera text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Experiment Photo</h2>
                                </div>

                                <x-forms.field label="Upload photo (optional):" name="photo">
                                    <x-forms.photo-upload id="experiment_photo" name="photo" label="Upload file" />
                                </x-forms.field>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Experiment
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>
        </div>

        <x-table-modal id="human_tubes_modal" title="Human Tubes" closeButtonId="human_tubes_close_btn">
            @include('samples.humans.modals.human_tubes_selection')
        </x-table-modal>

        <x-table-modal id="animal_tubes_modal" title="Animal Tubes" closeButtonId="animal_tubes_close_btn">
            @include('samples.animals.modals.animal_tubes_selection')
        </x-table-modal>

        <x-table-modal id="environment_tubes_modal" title="Environmental Tubes" closeButtonId="environment_tubes_close_btn">
            @include('samples.environment.modals.environment_tubes_selection')
        </x-table-modal>

        <x-table-modal id="parasite_tubes_modal" title="Parasite Tubes" closeButtonId="parasite_tubes_close_btn">
            @include('samples.parasites.modals.parasite_tubes_selection')
        </x-table-modal>

        <x-table-modal id="nucleic_tubes_modal" title="Nucleic Acids Tubes" closeButtonId="nucleic_tubes_close_btn">
            @include('samples.nucleic_acids.modals.nucleic_tubes_table')
        </x-table-modal>

        <x-table-modal id="culture_tubes_modal" title="Cultures" closeButtonId="culture_tubes_close_btn">
            @include('samples.cultures.modals.culture_tubes_selection')
        </x-table-modal>

        <x-table-modal id="pool_tubes_modal" title="Pools" closeButtonId="pool_tubes_close_btn">
            @include('samples.pools.modals.pool_tubes_selection')
        </x-table-modal>

        <x-table-modal id="form_modal" title="Study Registration Form" closeButtonId="form_close_btn">
            @include('modals.form_study')
        </x-table-modal>

        <x-table-modal id="protocol_form_modal" title="Protocol Registration Form"
            closeButtonId="protocol_form_close_btn">
            @include('modals.form_protocol')
        </x-table-modal>

        <x-table-modal id="protocol_lookup_modal" title="Protocols Table" closeButtonId="protocol_lookup_close_btn">
            <div class="overflow-x-auto">
                <table id="protocol_lookup_table" class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[150px] flex-col gap-2">
                                    <button type="button" class="protocol-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="code">
                                        <span>Protocol code</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="code">-</span>
                                    </button>
                                    <input type="text" class="protocol-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="code" placeholder="Filter code">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[220px] flex-col gap-2">
                                    <button type="button" class="protocol-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="name">
                                        <span>Protocol name</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="name">-</span>
                                    </button>
                                    <input type="text" class="protocol-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="name" placeholder="Filter protocol">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[180px] flex-col gap-2">
                                    <button type="button" class="protocol-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="techniqueName">
                                        <span>Technique name</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="techniqueName">-</span>
                                    </button>
                                    <input type="text" class="protocol-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="techniqueName" placeholder="Filter technique">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[200px] flex-col gap-2">
                                    <button type="button" class="protocol-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="techniqueType">
                                        <span>Technique category</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="techniqueType">-</span>
                                    </button>
                                    <input type="text" class="protocol-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="techniqueType" placeholder="Filter category">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[220px] flex-col gap-2">
                                    <button type="button" class="protocol-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="pathogens">
                                        <span>Target pathogens</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="pathogens">-</span>
                                    </button>
                                    <input type="text" class="protocol-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="pathogens" placeholder="Filter pathogens">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[220px] flex-col gap-2">
                                    <button type="button" class="protocol-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="projects">
                                        <span>Projects using protocol</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="projects">-</span>
                                    </button>
                                    <input type="text" class="protocol-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="projects" placeholder="Filter projects">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Select</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white" data-lookup-rows="protocols">
                        <tr id="protocol_lookup_empty_state" class="hidden">
                            <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">
                                No protocols match the current filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-table-modal>

        <x-table-modal id="laboratory_lookup_modal" title="Laboratories Table" closeButtonId="laboratory_lookup_close_btn">
            <div class="overflow-x-auto">
                <table id="laboratory_lookup_table" class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[220px] flex-col gap-2">
                                    <button type="button" class="laboratory-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="name">
                                        <span>Laboratory name</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="name">-</span>
                                    </button>
                                    <input type="text" class="laboratory-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="name" placeholder="Filter laboratory">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[160px] flex-col gap-2">
                                    <button type="button" class="laboratory-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="labType">
                                        <span>Type</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="labType">-</span>
                                    </button>
                                    <input type="text" class="laboratory-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="labType" placeholder="Filter type">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[160px] flex-col gap-2">
                                    <button type="button" class="laboratory-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="country">
                                        <span>Country</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="country">-</span>
                                    </button>
                                    <input type="text" class="laboratory-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="country" placeholder="Filter country">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[160px] flex-col gap-2">
                                    <button type="button" class="laboratory-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="city">
                                        <span>City</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="city">-</span>
                                    </button>
                                    <input type="text" class="laboratory-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="city" placeholder="Filter city">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[260px] flex-col gap-2">
                                    <button type="button" class="laboratory-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="address">
                                        <span>Address</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="address">-</span>
                                    </button>
                                    <input type="text" class="laboratory-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="address" placeholder="Filter address">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[120px] flex-col gap-2">
                                    <button type="button" class="laboratory-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="latitude">
                                        <span>Latitude</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="latitude">-</span>
                                    </button>
                                    <input type="text" class="laboratory-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="latitude" placeholder="Filter latitude">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">
                                <div class="flex min-w-[120px] flex-col gap-2">
                                    <button type="button" class="laboratory-lookup-sort flex items-center justify-between gap-2 text-left" data-sort-key="longitude">
                                        <span>Longitude</span>
                                        <span class="text-xs text-gray-400" data-sort-indicator="longitude">-</span>
                                    </button>
                                    <input type="text" class="laboratory-lookup-filter rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500" data-filter-key="longitude" placeholder="Filter longitude">
                                </div>
                            </th>
                            <th class="px-4 py-3 text-left font-semibold text-gray-700">Select</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white" data-lookup-rows="laboratories">
                        <tr id="laboratory_lookup_empty_state" class="hidden">
                            <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">
                                No laboratories match the current filters.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </x-table-modal>

        <x-table-modal id="pathogen_protocol_modal" title="Pathogen-Protocol Association Form"
            closeButtonId="pathogen_protocol_close_btn">
            @include('modals.form_pathogen_protocol')
        </x-table-modal>

        <x-table-modal id="pathogen_import_modal" title="Pathogen Registration Form"
            closeButtonId="pathogen_import_close_btn">
            @include('modals.form_pathogen')
        </x-table-modal>

        <x-table-modal id="laboratory_modal" title="Laboratory Registration Form"
            closeButtonId="laboratory_close_btn">
            @include('modals.form_laboratories', [
                'organizations' => $organizations,
                'organizations_by_country' => $organizations_by_country ?? collect(),
                'countries' => $countries,
                'lab_types' => $lab_types ?? collect(),
                'organization_types' => $organization_types ?? [],
            ])
        </x-table-modal>

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            // Pass the PHP array to JavaScript
            var protocolsList = @json($exp_protocols);
            var techniquesList = @json($techniques);
            var protocolPathogenMap = @json($protocol_pathogen_map);
            window.protocolLookupRows = @json($protocol_lookup_rows ?? []);
            window.laboratoryLookupRows = @json($laboratory_lookup_rows ?? []);
            window.openLaboratoryModalOnLoad = @json(
                old('place_name') !== null ||
                old('lab_description') !== null ||
                old('lab_type') !== null ||
                old('lab_organization') !== null ||
                old('lab_country') !== null ||
                old('lab_region') !== null ||
                old('lab_city') !== null ||
                old('lab_address') !== null ||
                $errors->has('place_name') ||
                $errors->has('lab_description') ||
                $errors->has('lab_type') ||
                $errors->has('lab_organization') ||
                $errors->has('lab_country') ||
                $errors->has('lab_region') ||
                $errors->has('lab_city') ||
                $errors->has('lab_address')
            );
            window.openProtocolFormModalOnLoad = @json(
                old('protocol_name') !== null ||
                old('protocol_new') !== null ||
                old('technique_new') !== null ||
                old('pathogens_protocol') !== null ||
                old('ref_new') !== null ||
                $errors->has('protocol_name') ||
                $errors->has('protocol_new') ||
                $errors->has('technique_new') ||
                $errors->has('pathogens_protocol') ||
                $errors->has('pathogens_protocol.*') ||
                $errors->has('ref_new') ||
                $errors->has('ref_new.*') ||
                $errors->has('protocol_pdf')
            );

            window.openStudyModalOnLoad = @json(
                old('study_doi') !== null ||
                old('study_ref') !== null ||
                old('study_title') !== null ||
                old('study_abstract') !== null ||
                old('study_year') !== null ||
                old('study_design') !== null ||
                $errors->has('study_doi') ||
                $errors->has('study_ref') ||
                $errors->has('study_title') ||
                $errors->has('study_abstract') ||
                $errors->has('study_year') ||
                $errors->has('study_design') ||
                $errors->has('study_pdf')
            );

            document.addEventListener('DOMContentLoaded', function () {
                const pathogenImportModal = document.getElementById('pathogen_import_modal');
                const pathogenImportCloseButton = document.getElementById('pathogen_import_close_btn');

                if (pathogenImportCloseButton && pathogenImportModal) {
                    pathogenImportCloseButton.addEventListener('click', function () {
                        pathogenImportModal.classList.remove('flex');
                        pathogenImportModal.classList.add('hidden');
                    });
                }

                if (window.openProtocolFormModalOnLoad) {
                    document.getElementById('protocol_form_modal')?.classList.remove('hidden');
                }

                if (window.openStudyModalOnLoad) {
                    document.getElementById('study_modal')?.classList.remove('hidden');
                }

                if (window.openLaboratoryModalOnLoad) {
                    document.getElementById('laboratory_modal')?.classList.remove('hidden');
                }

                if (!window.__experimentsFlashHandled && typeof Swal !== 'undefined') {
                    const successMessageElement = document.getElementById('successMessage');
                    const errorMessageElement = document.getElementById('errorMessage');

                    if (successMessageElement) {
                        window.__experimentsFlashHandled = true;
                        Swal.fire({
                            icon: 'success',
                            title: 'Success',
                            text: successMessageElement.textContent,
                        });
                    } else if (errorMessageElement) {
                        window.__experimentsFlashHandled = true;
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessageElement.textContent,
                        });
                    }
                }
            });
        </script>

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="{{ asset('js/create-experiments.js') }}?v={{ filemtime(public_path('js/create-experiments.js')) }}"></script>
            <script>
                window.experimentsTableTubes = @json(collect($table_tube_options ?? [])->values());
                window.experimentsTableProtocols = @json(collect($exp_protocols ?? [])->pluck('name')->values());
                window.experimentsTableAllPathogens = @json(collect($pathogens ?? [])->pluck('species')->values());
                window.experimentsTableProtocolPathogenMap = @json($protocol_pathogen_map ?? []);
                window.experimentsTableLaboratories = @json(collect($laboratories_by_country ?? [])->flatMap(fn ($labs) => collect($labs)->pluck('name'))->filter()->unique()->values());
                window.experimentsTablePeople = @json(collect($people ?? [])->map(function ($person) {
                    return [
                        'id' => $person->id,
                        'label' => trim(($person->title ?? '') . ' ' . ($person->first_name ?? '') . ' ' . ($person->last_name ?? '')),
                    ];
                })->values());
                window.experimentsTableLockedRegistrarId = @json($locked_registrar_people_id ?? null);
                window.experimentsTableCanAssignRegistrar = @json($can_assign_registrar ?? false);
            </script>
            <script src="{{ asset('js/create-experiments-table.js') }}?v={{ filemtime(public_path('js/create-experiments-table.js')) }}"></script>
        @endpush
    </div>
</x-layout>
