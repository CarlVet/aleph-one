<div class="mt-2 md:col-span-2 md:mt-0">

    <x-forms.form id="animal-registration-form" method="POST" action="/animals" enctype="multipart/form-data">
        @csrf
        <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
            <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Animal Registration Form</h2>
                    <p class="mt-2 text-sm text-gray-600">Fill in the details below to register one or more animals with
                        the same characteristics. Assign unique field labels to each animal.</p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <!-- Left Column - Animal Information -->
                    <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-paw text-blue-500 text-xl"></i>
                            <h2 class="text-lg font-semibold text-gray-800">Animal Information</h2>
                        </div>

                        <!-- Input for animal species -->
                        <x-forms.field label="Animal Species" name="animal_species">
                            <x-forms.select-input id="animal_species" name="animal_species" required
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                @foreach ($species_by_family as $family => $species_list)
                                    <optgroup label="{{ $family }}">
                                        @foreach ($species_list as $species)
                                            <option value="{{ $species['common'] }}">
                                                {{ $species['common'] }}
                                                (<i>{{ $species['scientific'] }}</i>)
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </x-forms.select-input>
                        </x-forms.field>

                        <button id="animal_species_form_btn" type="button"
                            class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                            <i
                                class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                            Create New Animal Species
                        </button>

                        <!-- Number of animals input -->
                        <x-forms.field label="Number of Animals:" name="number_of_animals">
                            <x-forms.numeric-input id="number_of_animals" name="number_of_animals" min="1"
                                max="100" value="1" required></x-forms.numeric-input>
                        </x-forms.field>


                        <!-- Sex input -->
                        <x-forms.field label="Sex:" name="sex">
                            <x-forms.radio-input name="sex" :options="['Male' => 'Male', 'Female' => 'Female', 'NA' => 'N/A']" checked="Male"></x-forms.radio-input>
                        </x-forms.field>

                        <!-- Age input -->
                        <x-forms.field label="Age:" name="age">
                            <x-forms.radio-input name="age" :options="[
                                'Juvenile' => 'Juvenile',
                                'Sub-adult' => 'Sub-adult',
                                'Adult' => 'Adult',
                                'Old' => 'Old',
                                'NA' => 'N/A',
                            ]" checked="Adult"></x-forms.radio-input>
                        </x-forms.field>

                        <!-- Owner Type Selection -->
                        <div class="mt-6 pt-4 border-t border-gray-100">
                            <div class="flex items-center space-x-2 mb-4">
                                <i class="fas fa-chart-bar text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Owner Type</h2>
                            </div>

                            <div class="space-y-3">
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="radio" name="owner_type" value="individual"
                                        class="form-radio text-blue-600 focus:ring-blue-500" checked>
                                    <span class="text-sm font-medium text-gray-700">Individual</span>
                                </label>
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="radio" name="owner_type" value="organization"
                                        class="form-radio text-blue-600 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-700">Organization</span>
                                </label>
                            </div>
                        </div>

                        <div id="owner_type_individual">
                            <!-- Owner input -->
                            <x-forms.field label="Owner:" name="owner_person">
                                <x-forms.select-input id="owner_person" name="owner_person"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($humans as $person)
                                        <option value="{{ $person->id }}">
                                            {{ $person->first_name . ' ' . $person->last_name }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <button id="animal_humans_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Owner
                            </button>
                        </div>

                        <div id="owner_type_organization" style="display: none;">
                            <!-- Owner input -->
                            <x-forms.field label="Owner:" name="owner_organization">
                                <x-forms.select-input id="owner_organization" name="owner_organization"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($organizations as $organization)
                                        <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <button id="animal_organization_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Organization
                            </button>
                        </div>

                        <!-- Photo Upload -->
                        <div class="mt-6 pt-4 border-t border-gray-100">
                            <div class="flex items-center space-x-2 mb-4">
                                <i class="fas fa-camera text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Photo</h2>
                            </div>

                            <x-forms.field label="Upload Photo:" name="photo">
                                <x-forms.photo-upload id="animal_modal_photo" name="photo" label="Upload file" />
                                <p class="mt-1 text-xs text-gray-500">This photo will be applied to all animals being registered</p>
                            </x-forms.field>
                        </div>
                    </div>

                    <!-- Right Column - Field Label Assignment -->
                    <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-tags text-blue-500 text-xl"></i>
                            <h2 class="text-lg font-semibold text-gray-800">Field ID Assignment</h2>
                        </div>

                        <!-- Hidden inputs for JS preview -->
                        <input type="hidden" id="project_code" value="{{ $project_code ?? '' }}">
                        <input type="hidden" id="current_max_animal_serial"
                            value="{{ $current_max_animal_serial ?? 0 }}">

                        <div id="field_label_assignment" class="space-y-4">
                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <h3 class="text-sm font-medium text-gray-700 mb-3">Assign Field Labels</h3>
                                <p class="text-xs text-gray-500 mb-3">Assign a unique field label to each animal (e.g.,
                                    KNP1, AM2, LA3 etc.)</p>
                                <div id="field_labels_assignment_list" class="space-y-3">
                                    <!-- Assignment items will be dynamically added here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div
                class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                <x-forms.submit
                    class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                    <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Register Animals
                </x-forms.submit>
            </div>

        </div>
    </x-forms.form>


    <x-table-modal id="animal_species_form_modal" title="Animal Species Registration Form"
        closeButtonId="animal_species_form_close_btn">
        @include('samples.animals.forms.form_animal_species')
    </x-table-modal>

    <x-table-modal id="animal_humans_form_modal" title="Human Registration Form"
            closeButtonId="animal_humans_form_close_btn">
            @include('samples.humans.forms.form_human')
        </x-table-modal>

    <x-table-modal id="animal_organization_form_modal" title="Organization Registration Form"
        closeButtonId="animal_organization_form_close_btn">
        @include('modals.form_organizations')
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
        var locationsList = @json($locations);
    </script>

    <!-- Required Scripts -->
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Selectize -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.3/js/standalone/selectize.min.js"></script>
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.3/css/selectize.default.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        /* Ensure selectize dropdowns appear above the modal */
        .selectize-dropdown {
            z-index: 9999 !important;
        }

        /* Remove modal content overflow restrictions */
        .modal-content {
            overflow: visible !important;
        }

        /* Ensure the modal doesn't clip the dropdowns */
        .modal {
            overflow: visible !important;
        }

        /* Ensure nested modals stack properly */
        #study_form_modal {
            z-index: 60 !important;
        }
    </style>


    <!-- Animals form script -->
    <script src="/js/create-animals.js"></script>
</div>
