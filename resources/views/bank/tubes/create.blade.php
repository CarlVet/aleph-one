<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0" x-data="{ registerMode: '{{ old('register_mode', 'form') }}' }">

        <!-- Buttons for List and Dashboard -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/bank/tubes/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/bank/tubes/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>

        <div class="mx-auto mb-6 w-full max-w-2xl rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-sm font-semibold text-gray-800">
                    Register tube positions by:
                </div>
                <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'form' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="tube_positions_register_mode" value="form" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-pen-to-square text-xs"></i>
                        Form
                    </label>
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'import' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="tube_positions_register_mode" value="import" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-file-csv text-xs"></i>
                        Import CSV
                    </label>
                </div>
            </div>
        </div>

        <div x-show="registerMode === 'import'" x-cloak class="mt-6">
            <livewire:imports.tube-positions-import />
        </div>

        <div x-show="registerMode === 'form'" x-cloak>
        <x-forms.form method="POST" action="/bank/tubes" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Tube Positions Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register new tube positions</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-vial text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Tubes Information</h2>
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
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
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
                                    <x-forms.select-input id="environment_tube_id" name="environment_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
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
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
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

                                <!-- Movement date input -->
                                <x-forms.field label="Movement date:" name="date">
                                    <x-forms.date-input id="date" name="date" value="{{ now()->toDateString() }}"
                                        required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.date-input>
                                </x-forms.field>

                                <!-- Person moving tube input -->
                                <x-forms.field label="Moved by:" name="mover">
                                    @if ($can_assign_registrar)
                                        <x-forms.select-input id="mover" name="mover" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($people as $person)
                                                <option value="{{ $person->id }}">
                                                    {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                                </option>
                                            @endforeach
                                        </x-forms.select-input>
                                    @else
                                        <x-forms.select-input id="mover_locked" name="mover_locked" disabled
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($people as $person)
                                                <option value="{{ $person->id }}"
                                                    @selected((int) $person->id === (int) ($locked_registrar_people_id ?? 0))>
                                                    {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                                </option>
                                            @endforeach
                                        </x-forms.select-input>
                                        <input type="hidden" name="mover" value="{{ $locked_registrar_people_id }}">
                                    @endif
                                </x-forms.field>

                                <!-- Reason input -->
                                <x-forms.field label="Reason of movement:" name="reason">
                                    <x-forms.select-input id="reason" name="reason" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach (($movement_reason_options ?? []) as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-box text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Box Information</h2>
                            </div>

                            <!-- Box selection -->
                            <x-forms.field label="Select box:" name="box">
                                <div class="text-xs text-gray-500 mb-2">Available boxes: {{ $boxes->count() }}</div>
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <select id="box" name="box"
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                            required>
                                            <option value="">Select a box</option>
                                            @foreach ($boxes as $box)
                                                <option value="{{ $box->id }}"
                                                    data-rows="{{ $box->n_rows }}"
                                                    data-columns="{{ $box->n_columns }}">
                                                    {{ $box->code }}@if($box->name) - {{ $box->name }}@endif ({{ $box->n_rows }}x{{ $box->n_columns }}) - {{ $box->dynamic_content_type }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <x-lookup.button id="boxes_lookup_btn" title="Browse boxes table" />
                                </div>
                            </x-forms.field>

                            <button id="boxes_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Box
                            </button>

                            <!-- X position -->
                            <x-forms.field label="Starting position on x axis:" name="x_position">
                                <x-forms.numeric-input id="x_position" name="x_position" min="1" max="1000" step="1" value="1"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                            </x-forms.field>

                            <!-- Y position -->
                            <x-forms.field label="Starting position on y axis:" name="y_position">
                                <x-forms.numeric-input id="y_position" name="y_position" min="1" max="1000" step="1" value="1"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                            </x-forms.field>

                            <!-- Box Visualization -->
                            <div class="mt-4">
                                <div class="flex flex-col gap-2 mb-2">
                                    <div class="flex flex-wrap items-center justify-between gap-2">
                                        <h3 class="text-sm font-semibold text-gray-900">Box Layout (tubes get filled from left to right)</h3>
                                        <button type="button" id="testVisualization"
                                            class="px-3 py-1 text-xs bg-blue-500 text-white rounded hover:bg-blue-600">
                                            Test Visualization
                                        </button>
                                    </div>
                                    <div class="flex flex-wrap items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 text-xs text-gray-600">
                                        <label for="boxPreviewCellSize" class="font-semibold text-gray-700">Cell size</label>
                                        <input type="range" id="boxPreviewCellSize" min="28" max="240" value="52"
                                            class="h-2 w-36 cursor-pointer accent-blue-600" />
                                        <span id="boxPreviewCellSizeLabel" class="min-w-[3rem] font-mono text-gray-800">52px</span>
                                        <button type="button" id="boxPreviewFitWidth"
                                            class="rounded border border-gray-300 bg-white px-2 py-1 text-xs font-semibold text-gray-700 hover:bg-gray-100">
                                            Fit to labels
                                        </button>
                                        <span class="text-gray-500">Auto-size uses the widest tube label; scroll for large boxes. Shrink with the slider if needed.</span>
                                    </div>
                                </div>
                                <div id="boxVisualization" class="max-h-[28rem] overflow-auto rounded-lg border border-gray-300 p-4">
                                    <div id="boxGrid" class="grid w-max gap-1" style="min-height: 200px;"></div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Tube Positions
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <x-table-modal id="animal_tubes_modal" title="Animal Tubes" closeButtonId="animal_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="human_tubes_modal" title="Human Tubes" closeButtonId="human_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="environment_tubes_modal" title="Environmental Tubes" closeButtonId="environment_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="parasite_tubes_modal" title="Parasite Tubes" closeButtonId="parasite_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="nucleic_tubes_modal" title="Nucleic Acids Tubes" closeButtonId="nucleic_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="culture_tubes_modal" title="Culture Tubes" closeButtonId="culture_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="pool_tubes_modal" title="Pool Tubes" closeButtonId="pool_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="boxes_modal" title="Box Registration Form"
            closeButtonId="boxes_close_btn">
            @include('modals.form_box')
        </x-table-modal>

        <x-lookup.table-modal
            id="boxes_lookup_modal"
            title="Boxes Table"
            empty-message="No boxes match the current filters."
            :columns="[
                ['key' => 'code', 'label' => 'Box code', 'minWidth' => '140px'],
                ['key' => 'name', 'label' => 'Box name', 'minWidth' => '160px'],
                ['key' => 'alias_code', 'label' => 'Alias', 'minWidth' => '120px'],
                ['key' => 'content_type', 'label' => 'Content type', 'minWidth' => '160px'],
                ['key' => 'dimensions', 'label' => 'Dimensions', 'minWidth' => '120px'],
            ]"
        />

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            window.boxLookupRows = @json($box_lookup_rows ?? []);
        </script>

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="{{ asset('js/lookup-table.js') }}?v={{ filemtime(public_path('js/lookup-table.js')) }}"></script>
            <script src="{{ asset('js/create-tube-positions.js') }}?v={{ filemtime(public_path('js/create-tube-positions.js')) }}"></script>
        @endpush
        </div>
    </div>
</x-layout>
