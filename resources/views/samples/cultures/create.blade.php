<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">

        <!-- Create, Edit, Dashboard (Centered) -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/cultures/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/cultures/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>
        <x-forms.form method="POST" action="/samples/cultures" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Cultures Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new culture</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">

                            <div class="flex items-center space-x-2">
                                <i class="fas fa-flask text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Sample Information</h2>
                            </div>

                            <x-forms.field label="Is this a primary culture?" name="culture_step">
                                <x-forms.radio-input name="culture_step" :options="['Yes' => 'Yes', 'No' => 'No']"
                                    checked="Yes"></x-forms.radio-input>
                            </x-forms.field>

                            <!-- Tube ID selection -->
                            <div id="culture_primary" style="display: none;" class="space-y-4">
                            <x-forms.field label="Select sample origin:" name="model">
                                <x-forms.select-input id="model" name="model" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Human samples">Human samples</option>
                                    <option value="Animal samples">Animal samples</option>
                                    <option value="Environment samples">Environment samples</option>
                                    <option value="Parasite samples">Parasite samples</option>
                                    <option value="Pools">Pools</option>
                                </x-forms.select-input>
                            </x-forms.field>
                            @include('partials.tube-badge-display-toggle')
                            </div>

                            <div id="subculture" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="cultures_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg shadow-md hover:shadow-lg border border-orange-600">
                                        <i
                                            class="fas fa-bacteria mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Cultures
                                    </x-forms.table-button>
                                    <span id="culture_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="culture_id[]">
                                    <x-forms.select-input id="culture_id" name="culture_id[]" multiple
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($selected_cultures ?? [] as $culture)
                                            <option value="{{ $culture->id }}" selected>{{ $culture->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Animal Samples -->
                            <div id="animal_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="animal_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg shadow-md hover:shadow-lg border border-yellow-600">
                                        <i
                                            class="fas fa-paw mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
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

                            <!-- Parasite Samples -->
                            <div id="parasite_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="parasite_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-lg shadow-md hover:shadow-lg border border-purple-600">
                                        <i
                                            class="fas fa-spider mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
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

                            <!-- Human Samples -->
                            <div id="human_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="human_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg shadow-md hover:shadow-lg border border-pink-600">
                                        <i
                                            class="fas fa-person mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
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

                            <!-- Environment Samples -->
                            <div id="environment_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="environment_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                                        <i
                                            class="fas fa-leaf mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Environment Tubes
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

                            <!-- Pools -->
                            <div id="pools_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="pool_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-cyan-500 to-cyan-600 text-white rounded-lg shadow-md hover:shadow-lg border border-cyan-600">
                                        <i
                                            class="fas fa-layer-group mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
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

                            <!-- Nucleic acids -->
                            <div id="nucleic_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4">
                                    <x-forms.table-button id="nucleic_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-lg shadow-md hover:shadow-lg border border-indigo-600">
                                        <i
                                            class="fas fa-dna mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Nucleic Tubes
                                    </x-forms.table-button>
                                    <span id="nucleic_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="nucleic_tube_id[]">
                                    <x-forms.select-input id="nucleic_tube_id" name="nucleic_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($selected_nucleic_tubes ?? [] as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <div class="pt-4 border-t border-gray-100">

                                <!-- Selected Tubes and Culture Code Assignments -->
                            <div id="selected_tubes_assignment" class="space-y-4" style="display: none;">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h3 class="text-md font-semibold text-gray-700">Tube &amp; culture code assignment</h3>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" id="auto_alias_from_parent" class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                        <span>Auto-populate culture alias from parent tube/culture</span>
                                    </label>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                <th class="pb-2 pr-4">Source</th>
                                                <th class="pb-2 pr-4">Type</th>
                                                <th class="pb-2 pr-4">Tube / parent alias</th>
                                                <th class="pb-2 pr-4">Culture code*</th>
                                                <th class="pb-2">Culture alias</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tubes_assignment_list" class="divide-y divide-gray-200">
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                                
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-bacteria text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Culture Information</h2>
                            </div>

                            <!-- Input for culture type -->
                            <x-forms.field label="Type:" name="culture_type">
                                <x-forms.select-input id="culture_type" name="culture_type" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($culture_type_options ?? [] as $type)
                                        <option value="{{ $type }}" @selected(old('culture_type') === $type)>{{ $type }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>


                            <!-- Input for medium -->
                            <x-forms.field label="Medium:" name="culture_medium">
                                <x-forms.select-input id="culture_medium" name="culture_medium" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($medium_options ?? [] as $medium)
                                        <option value="{{ $medium }}" @selected(old('culture_medium') === $medium)>{{ $medium }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Input for athmosphere -->
                            <x-forms.field label="Athmosphere:" name="culture_athmosphere">
                                <x-forms.select-input id="culture_athmosphere" name="culture_athmosphere" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($atmosphere_options ?? [] as $atmosphere)
                                        <option value="{{ $atmosphere }}" @selected(old('culture_athmosphere') === $atmosphere)>{{ $atmosphere }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Input for incubation temperature -->
                            <x-forms.field label="Incubation temperature (°C):" name="incubation_temp">
                                <x-forms.numeric-input id="incubation_temp" name="incubation_temp" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    min="0" max="100" value="37">
                                </x-forms.numeric-input>
                            </x-forms.field>

                            <!-- Culture codes assignment -->
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Ancillary Information</h2>
                            </div>

                            <!-- Culture start date input -->
                            <x-forms.field label="Culture date:" name="date" class="mt-4">
                                <x-forms.date-input id="date" name="date"
                                    value="{{ now()->toDateString() }}" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.date-input>
                            </x-forms.field>

                            <!-- Laboratory input -->
                            <x-forms.field label="Laboratory where cultured:" name="lab" class="mt-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <x-forms.select-input id="lab" name="lab" onchange="checkPlaceValue()"
                                            required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($labs_available as $country => $labs_list)
                                                <optgroup label="{{ $country }}">
                                                    @foreach ($labs_list as $lab)
                                                        <option value="{{ $lab['name'] }}">{{ $lab['name'] }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </x-forms.select-input>
                                    </div>
                                    <x-lookup.button id="laboratories_lookup_btn" title="Browse laboratories table" />
                                </div>
                            </x-forms.field>

                            <button id="culture_lab_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Laboratory
                            </button>

                            <!-- Collector input -->
                            <x-forms.field label="Cultured by:" name="scientist" class="mt-4">
                                @if (!($can_assign_registrar ?? false) && !empty($locked_registrar_people_id))
                                    <input type="hidden" name="scientist" value="{{ (int) $locked_registrar_people_id }}">
                                @endif
                                @if ($can_assign_registrar ?? false)
                                    <x-forms.select-input id="scientist" name="scientist" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) old('scientist') === (int) $person->id)>
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <x-forms.select-input id="scientist" name="scientist" required disabled
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) old('scientist', $locked_registrar_people_id ?? null) === (int) $person->id)>
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @endif
                            </x-forms.field>

                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i
                            class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Culture
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <x-table-modal id="animal_tubes_modal" title="Animal Tubes" closeButtonId="animal_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="parasite_tubes_modal" title="Parasite Tubes" closeButtonId="parasite_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="human_tubes_modal" title="Human Tubes" closeButtonId="human_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="environment_tubes_modal" title="Environment Tubes" closeButtonId="environment_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="pool_tubes_modal" title="Pool Tubes" closeButtonId="pool_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="cultures_modal" title="Cultures Selection" closeButtonId="cultures_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="culture_lab_form_modal" title="Laboratory Registration Form"
            closeButtonId="culture_lab_form_close_btn">
            @include('modals.form_laboratories')
        </x-table-modal>

        @include('partials.lookup.laboratories-modal')

        <script>
            window.laboratoryLookupRows = @json($laboratory_lookup_rows ?? []);
        </script>

        @if (session('success'))
            <div id="cultureSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="cultureErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            window.availableCultureCodes = @json($available_codes);
            window.projectCode = @json($project_code ?? '');
        </script>

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="/js/lookup-table.js?v={{ filemtime(public_path('js/lookup-table.js')) }}"></script>
            <script src="/js/create-laboratories.js?v={{ filemtime(public_path('js/create-laboratories.js')) }}"></script>
            <script src="/js/create-cultures.js?v={{ filemtime(public_path('js/create-cultures.js')) }}"></script>
        @endpush
    </div>
</x-layout>
