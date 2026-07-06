<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <!-- Buttons for Create, Edit, and Delete -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/meta/list/animal"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/meta/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>

        <x-forms.form method="POST" action="/meta" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Literature Data Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register new literature data
                        </p>
                    </div>

                    <!-- Model Selection -->
                    <x-forms.field label="Select data type:" name="model">
                        <x-forms.select-input id="model" name="model" required
                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="MetaHuman">Human Data</option>
                            <option value="MetaAnimal">Animal Data</option>
                            <option value="MetaEnvironment">Environment Data</option>
                            <option value="MetaParasite">Parasite Data</option>
                        </x-forms.select-input>
                    </x-forms.field>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fa-solid fa-book text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Study Information</h2>
                            </div>

                            <!-- Study selection -->
                            <div class="flex items-center">
                                <x-forms.table-button id="studies_select_btn"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg shadow-md hover:shadow-lg border border-blue-600">
                                    <i
                                        class="fa-solid fa-book mr-2 text-lg group-hover:rotate-12 transition-transform duration-300 ml-1"></i>
                                    Select Study
                                </x-forms.table-button>
                            </div>

                            <x-forms.field name="studies_id">
                                <x-forms.select-input id="studies_id" name="studies_id" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach (($selected_study ?? collect()) as $study)
                                        <option value="{{ $study->id }}" selected>
                                            {{ $study->ref_key }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <button id="study_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                New Study
                            </button>

                            <!-- Country selection -->
                            <x-forms.field label="Country:" name="countries_id">
                                <x-forms.select-input id="countries_id" name="countries_id" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->name }}">{{ $country->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Date sampling -->
                            <x-forms.field label="Sampling date:" name="date_sampling">
                                <x-forms.date-input id="date_sampling" name="date_sampling"
                                    value="{{ now()->toDateString() }}"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.date-input>
                            </x-forms.field>

                            <!-- Location -->
                            <x-forms.field label="Location:" name="location">
                                <x-forms.textarea id="location" name="location"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.textarea>
                            </x-forms.field>

                            <div class="flex items-center space-x-2 mt-6">
                                <i class="fas fa-microscope text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Analysis Information</h2>
                            </div>

                            <!-- Pathogen species -->
                            <x-forms.field label="Pathogen species:" name="pathogens_id">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <x-forms.select-input id="pathogens_id" name="pathogens_id" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($pathogens as $pathogen)
                                                <option value="{{ $pathogen->id }}">{{ $pathogen->species }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </div>
                                    <x-lookup.button id="pathogens_lookup_btn" title="Browse pathogen species table" />
                                </div>
                            </x-forms.field>

                            <button id="pathogen_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Pathogen
                            </button>

                            <!-- Technique -->
                            <x-forms.field label="Technique:" name="techniques_id">
                                <x-forms.select-input id="techniques_id" name="techniques_id" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($techniques as $technique)
                                        <option value="{{ $technique->name }}">{{ $technique->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <div id="additional-type" class="mt-4" style="display: none;">
                                <x-forms.field label="Select technique category:" name="technique_new">
                                    <x-forms.select-input id="technique_new" name="technique_new" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="Nucleic acid detection test">Nucleic acid detection test</option>
                                        <option value="Antigen detection test">Antigen detection test</option>
                                        <option value="Antibody detection test">Antibody detection test</option>
                                        <option value="Parasitological test">Parasitological test</option>
                                        <option value="Microbiological test">Microbiological test</option>
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Tested number -->
                            <x-forms.field label="Number of samples tested:" name="tested_n">
                                <x-forms.numeric-input id="tested_n" name="tested_n" min="0" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.numeric-input>
                            </x-forms.field>

                            <!-- Positive number -->
                            <x-forms.field label="Number of positive samples:" name="pos_n">
                                <x-forms.numeric-input id="pos_n" name="pos_n" min="0" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.numeric-input>
                            </x-forms.field>

                            <!-- Risk factors -->
                            <x-forms.field label="Risk factors:" name="risk_factors_id[]">
                                <x-forms.select-input id="risk_factors_id" name="risk_factors_id[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($risk_factors as $factor)
                                        <option value="{{ $factor->name }}">{{ $factor->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Person -->
                            <x-forms.field label="Reviewer:" name="people_id">
                                @if ($can_assign_registrar)
                                    <x-forms.select-input id="people_id" name="people_id" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}">
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <x-forms.select-input id="people_id_locked" name="people_id_locked" disabled
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) $person->id === (int) ($locked_registrar_people_id ?? 0))>
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                    <input type="hidden" name="people_id" value="{{ $locked_registrar_people_id }}">
                                @endif
                            </x-forms.field>

                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <!-- Animal-specific fields -->
                            <div id="animal_fields" style="display: none;">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-paw text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Animal Information</h2>
                                </div>

                                <!-- Animal species -->
                                <x-forms.field label="Animal species:" name="animal_species_id">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-1">
                                            <x-forms.select-input id="animal_species_id" name="animal_species_id"
                                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                @foreach ($animal_species as $species)
                                                    <option value="{{ $species->id }}">
                                                        {{ $species->name_common . ' (' . $species->name_scientific . ')' }}
                                                    </option>
                                                @endforeach
                                            </x-forms.select-input>
                                        </div>
                                        <x-lookup.button id="animal_species_lookup_btn" title="Browse animal species table" />
                                    </div>
                                </x-forms.field>

                                <button id="animal_species_form_btn" type="button"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                    <i
                                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                    Create New Animal Species
                                </button>

                                <!-- Sample type -->
                                <x-forms.field label="Sample type:" name="sample_types_id">
                                    <x-forms.select-input id="sample_types_id" name="sample_types_id"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($sample_types as $type)
                                            <option value="{{ $type->name }}">{{ $type->name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                                <div id="additional-animal-sample-type" class="mt-4" style="display: none;">
                                    <x-forms.field label="Sample type category:" name="animal_sample_category">
                                        <x-forms.select-input id="animal_sample_category" name="animal_sample_category"
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">Select category</option>
                                            <option value="host_derived">Host derived</option>
                                            <option value="non_host_derived">Non host derived</option>
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>

                                <!-- Clinical signs -->
                                <x-forms.field label="Clinical signs:" name="clinical_signs_id[]">
                                    <x-forms.select-input id="clinical_signs_id" name="clinical_signs_id[]" multiple
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($clinical_signs as $sign)
                                            <option value="{{ $sign->name }}">{{ $sign->name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>

                                <!-- Lesions -->
                                <x-forms.field label="Lesions:" name="lesions_id[]">
                                    <x-forms.select-input id="lesions_id" name="lesions_id[]" multiple
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($lesions as $lesion)
                                            <option value="{{ $lesion->name }}">{{ $lesion->name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Human-specific fields -->
                            <div id="human_fields" style="display: none;">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-user text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Human Information</h2>
                                </div>

                                <!-- Sample type -->
                                <x-forms.field label="Sample type:" name="human_sample_types_id">
                                    <x-forms.select-input id="human_sample_types_id" name="human_sample_types_id"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($sample_types as $type)
                                            <option value="{{ $type->name }}">{{ $type->name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                                <div id="additional-human-sample-type" class="mt-4" style="display: none;">
                                    <x-forms.field label="Sample type category:" name="human_sample_category">
                                        <x-forms.select-input id="human_sample_category" name="human_sample_category"
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">Select category</option>
                                            <option value="host_derived">Host derived</option>
                                            <option value="non_host_derived">Non host derived</option>
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>

                                <!-- Clinical signs -->
                                <x-forms.field label="Clinical signs:" name="human_signs_id[]">
                                    <x-forms.select-input id="human_signs_id" name="human_signs_id[]" multiple
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($clinical_signs as $sign)
                                            <option value="{{ $sign->name }}">{{ $sign->name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>

                                <!-- Lesions -->
                                <x-forms.field label="Lesions:" name="human_lesions_id[]">
                                    <x-forms.select-input id="human_lesions_id" name="human_lesions_id[]" multiple
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($lesions as $lesion)
                                            <option value="{{ $lesion->name }}">{{ $lesion->name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Parasite-specific fields -->
                            <div id="parasite_fields" style="display: none;">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-spider text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Parasite Information</h2>
                                </div>

                                <!-- Parasite species -->
                                <x-forms.field label="Parasite species:" name="parasite_species_id">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-1">
                                            <x-forms.select-input id="parasite_species_id" name="parasite_species_id"
                                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                @foreach ($parasite_species as $species)
                                                    <option value="{{ $species->id }}">{{ $species->name_scientific }}
                                                    </option>
                                                @endforeach
                                            </x-forms.select-input>
                                        </div>
                                        <x-lookup.button id="parasite_species_lookup_btn" title="Browse parasite species table" />
                                    </div>
                                </x-forms.field>

                                <button id="parasite_species_form_btn" type="button"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                    <i
                                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                    Create New Parasite Species
                                </button>

                                <!-- Parasite sample type -->
                                <x-forms.field label="Sample type:" name="parasite_sample_types_id">
                                    <x-forms.select-input id="parasite_sample_types_id"
                                        name="parasite_sample_types_id"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($parasite_sample_types as $type)
                                            <option value="{{ $type->name }}">{{ $type->name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Environment-specific fields -->
                            <div id="environment_fields" style="display: none;">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-leaf text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Environment Information</h2>
                                </div>

                                <!-- Environment sample type -->
                                <x-forms.field label="Sample type:" name="environment_sample_types_id">
                                    <x-forms.select-input id="environment_sample_types_id"
                                        name="environment_sample_types_id"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($environment_sample_types as $type)
                                            <option value="{{ $type->name }}">{{ $type->name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>

                                <div id="additional-environment-sample" class="mt-4" style="display: none;">
                                    <x-forms.field label="Select sample category:" name="environment_sample_category">
                                        <x-forms.select-input id="environment_sample_category" name="environment_sample_category" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="Water">Water</option>
                                            <option value="Soil">Soil</option>
                                            <option value="Air">Air</option>
                                            <option value="Vegatation">Vegatation</option>
                                            <option value="Food">Food</option>
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i
                            class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Register Literature Data
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <x-table-modal id="studies_modal" title="Select Study" closeButtonId="studies_close_btn">
            <div id="studies_modal_body" class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="study_form_modal" title="Study Registration Form" closeButtonId="form_close_btn">
            @include('modals.form_study')
        </x-table-modal>

        <x-table-modal id="pathogen_form_modal" title="Study Registration Form"
            closeButtonId="pathogen_form_close_btn">
            @include('modals.form_pathogen')
        </x-table-modal>

        @include('partials.lookup.pathogens-modal')
        @include('partials.lookup.animal-species-modal')
        @include('partials.lookup.parasite-species-modal')

        <script>
            window.pathogenLookupRows = @json($pathogen_lookup_rows ?? []);
            window.animalSpeciesLookupRows = @json($animal_species_lookup_rows ?? []);
            window.parasiteSpeciesLookupRows = @json($parasite_species_lookup_rows ?? []);
        </script>

        <x-table-modal id="parasite_species_form_modal" title="Parasite Species Registration Form"
            closeButtonId="parasite_species_form_close_btn">
            @include('samples.parasites.forms.form_parasite_species')
        </x-table-modal>

        <x-table-modal id="animal_species_form_modal" title="Animal Species Registration Form"
            closeButtonId="animal_species_form_close_btn">
            @include('samples.animals.forms.form_animal_species')
        </x-table-modal>


        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div id="validationErrors" class="hidden">{{ json_encode($errors->all()) }}</div>
        @endif

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
            <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.3/js/standalone/selectize.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <script src="/js/lookup-table.js?v={{ filemtime(public_path('js/lookup-table.js')) }}"></script>
            <script src="/js/create-meta.js?v={{ filemtime(public_path('js/create-meta.js')) }}"></script>
            <script>
                window.metaStudiesModalUrl = @json(route('meta.create.studies'));
                window.metaStudiesSearchUrl = @json(route('meta.create.studies.search'));

                var techniquesList = @json($techniques);
                var environmentSampleList = @json($environment_sample_types);
                var sampleTypeList = @json($sample_types);

                document.addEventListener('DOMContentLoaded', function() {
                    const modelSelect = document.getElementById('model');
                    const animalFields = document.getElementById('animal_fields');
                    const humanFields = document.getElementById('human_fields');
                    const parasiteFields = document.getElementById('parasite_fields');
                    const environmentFields = document.getElementById('environment_fields');

                    function updateFields() {
                        const selectedModel = modelSelect.value;
                        animalFields.classList.add('hidden');
                        humanFields.classList.add('hidden');
                        parasiteFields.classList.add('hidden');
                        environmentFields.classList.add('hidden');

                        switch (selectedModel) {
                            case 'MetaAnimal':
                                animalFields.classList.remove('hidden');
                                break;
                            case 'MetaHuman':
                                humanFields.classList.remove('hidden');
                                break;
                            case 'MetaParasite':
                                parasiteFields.classList.remove('hidden');
                                break;
                            case 'MetaEnvironment':
                                environmentFields.classList.remove('hidden');
                                break;
                        }
                    }

                    modelSelect.addEventListener('change', updateFields);
                    updateFields(); // Initial call to set the correct fields
                });
            </script>
        @endpush
    </div>
</x-layout>
