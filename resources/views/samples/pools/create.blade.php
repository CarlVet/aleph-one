<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <!-- Create, Edit, Dashboard (Centered) -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/pools/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/pools/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>

        <x-forms.form method="POST" action="/samples/pools" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Pool Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new pool</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-flask text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Sample Information</h2>
                            </div>

                            <!-- Sample Type selection -->
                            <x-forms.field label="Select sample type:" name="model">
                                <x-forms.select-input id="model" name="model" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Human samples">Human samples</option>
                                    <option value="Animal samples">Animal samples</option>
                                    <option value="Environmental samples">Environmental samples</option>
                                    <option value="Parasite samples">Parasite samples</option>
                                    <option value="Nucleic acids">Nucleic acids</option>
                                    <option value="Cultures">Cultures</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            @include('partials.tube-badge-display-toggle')

                            <!-- Human Samples -->
                            <div id="human_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="human_tubes_btn" 
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg shadow-md hover:shadow-lg border border-red-600">
                                        <i class="fas fa-person mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Human Tubes
                                    </x-forms.table-button>
                                    <span id="human_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="human_tube_id[]">
                                    <x-forms.select-input id="human_tube_id" name="human_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent shadow-sm transition-all duration-200">
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
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
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
                            <div id="environmental_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                    <x-forms.table-button id="environment_tubes_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-blue-600">
                                        <i class="fas fa-leaf mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Environmental Tubes
                                    </x-forms.table-button>
                                    <span id="environment_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="environment_tube_id[]">
                                    <x-forms.select-input id="environment_tube_id" name="environment_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
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
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-orange-500 to-orange-600 text-white rounded-lg shadow-md hover:shadow-lg border border-yellow-600">
                                        <i class="fas fa-bacteria mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Cultures Tubes
                                    </x-forms.table-button>
                                    <span id="culture_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="culture_tube_id[]">
                                    <x-forms.select-input id="culture_tube_id" name="culture_tube_id[]" multiple data-tube-badge-toggle="1"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent shadow-sm transition-all duration-200">    
                                        @foreach (($selected_culture_tubes ?? []) as $tube)
                                            <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Ancillary Information</h2>
                            </div>

                            <!-- Pool date input -->
                            <x-forms.field label="Date pooled:" name="date_pooled" class="mt-4">
                                <x-forms.date-input id="date_pooled" name="date_pooled" value="{{ now()->toDateString() }}"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.date-input>
                            </x-forms.field>

                            <!-- Laboratory input -->
                            <x-forms.field label="Laboratory where pooled:" name="lab" class="mt-4">
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
                            </x-forms.field>

                            <!-- Pooler input -->
                            <x-forms.field label="Pooled by:" name="pooler" class="mt-4">
                                @if (!($can_assign_registrar ?? false) && !empty($locked_registrar_people_id))
                                    <input type="hidden" name="pooler" value="{{ (int) $locked_registrar_people_id }}">
                                @endif
                                @if ($can_assign_registrar ?? false)
                                    <x-forms.select-input id="pooler" name="pooler" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) old('pooler') === (int) $person->id)>
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <x-forms.select-input id="pooler" name="pooler" required disabled
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) old('pooler', $locked_registrar_people_id ?? null) === (int) $person->id)>
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
                        Save Pool
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

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

        <x-table-modal id="nucleic_tubes_modal" title="Nucleic Acids Tubes" closeButtonId="nucleic_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="culture_tubes_modal" title="Cultures Tubes" closeButtonId="culture_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif


        <!-- Selectize scripts -->
        @push('scripts')
            <script src="{{ asset('js/create-pools.js') }}?v={{ filemtime(public_path('js/create-pools.js')) }}"></script>
        @endpush
    </div>
</x-layout> 