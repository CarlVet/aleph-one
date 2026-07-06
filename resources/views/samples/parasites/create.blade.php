<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0" x-data="{ registerMode: 'form' }">
        <!-- Create, Edit, Dashboard (Centered) -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/parasites/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/parasites/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
            <a href="/samples/parasites/dissection/create"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-purple-500 to-violet-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-purple-600">
                <i class="fas fa-cut mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                Dissect
            </a>
        </div>

        <div class="mx-auto mb-6 w-full max-w-2xl rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-sm font-semibold text-gray-800">
                    Register parasite samples by:
                </div>
                <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'form' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="parasite_samples_register_mode" value="form" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-pen-to-square text-xs"></i>
                        Form
                    </label>
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'import' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="parasite_samples_register_mode" value="import" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-file-csv text-xs"></i>
                        Import CSV
                    </label>
                </div>
            </div>
        </div>

        <div x-show="registerMode === 'import'" x-cloak class="mt-6">
            <livewire:imports.parasite-samples-import />
        </div>

        <div x-show="registerMode === 'form'" x-cloak>
        <x-forms.form method="POST" action="/samples/parasites" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Parasites Identification Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to identify a new parasite</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-flask text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Sample Information</h2>
                            </div>

                            <!-- Sample Origin selection -->
                            <x-forms.field label="Select sample origin:" name="model">
                                <x-forms.select-input id="model" name="model" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Human samples">Human samples</option>
                                    <option value="Animal samples">Animal samples</option>
                                    <option value="Environmental samples">Environmental samples</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Human Samples -->
                            <div id="human_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4">
                                    <x-forms.table-button id="human_samples_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-pink-500 to-pink-600 text-white rounded-lg shadow-md hover:shadow-lg border border-pink-600">
                                        <i class="fas fa-person mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Human Samples
                                    </x-forms.table-button>
                                    <span id="human_sample_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="human_sample_id[]">
                                    <x-forms.select-input id="human_sample_id" name="human_sample_id[]" multiple
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach (($selected_human_samples ?? []) as $sample)
                                            <option value="{{ $sample->id }}" selected>{{ $sample->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Animal Samples -->
                            <div id="animal_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4">
                                    <x-forms.table-button id="animal_samples_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg shadow-md hover:shadow-lg border border-yellow-600">
                                        <i class="fas fa-paw mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Animal Samples
                                    </x-forms.table-button>
                                    <span id="animal_sample_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="animal_sample_id[]">
                                    <x-forms.select-input id="animal_sample_id" name="animal_sample_id[]" multiple
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach (($selected_animal_samples ?? []) as $sample)
                                            <option value="{{ $sample->id }}" selected>{{ $sample->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Environmental Samples -->
                            <div id="environment_model" style="display: none;" class="space-y-4">
                                <div class="flex items-center space-x-4">
                                    <x-forms.table-button id="environment_samples_btn"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                                        <i class="fas fa-leaf mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                        Select Environmental Samples
                                    </x-forms.table-button>
                                    <span id="environment_sample_id_count" class="text-sm text-gray-600">(0 selected)</span>
                                </div>
                                <x-forms.field name="environment_sample_id[]">
                                    <x-forms.select-input id="environment_sample_id" name="environment_sample_id[]" multiple
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach (($selected_environment_samples ?? []) as $sample)
                                            <option value="{{ $sample->id }}" selected>{{ $sample->code }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>

                            <!-- Pooling Option -->
                            <x-forms.field label="How will the parasites be stored?" name="storage_mode">
                                <div class="flex items-center space-x-6">
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="storage_mode" value="individual" checked class="form-radio text-blue-600">
                                        <span class="ml-2 text-gray-700">Individually</span>
                                    </label>
                                    <label class="inline-flex items-center">
                                        <input type="radio" name="storage_mode" value="pool" class="form-radio text-blue-600">
                                        <span class="ml-2 text-gray-700">As a pool</span>
                                    </label>
                                </div>
                            </x-forms.field>

                            <div id="pool_code_section" class="mt-4 hidden">
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                    <div class="flex items-center gap-2 mb-2">
                                        <i class="fas fa-layer-group text-blue-600"></i>
                                        <h3 class="text-sm font-semibold text-blue-900">Pool code</h3>
                                    </div>
                                    <p class="text-sm text-blue-800 mb-3">
                                        When storing as a pool, choose the resulting pool code (individual parasite sample codes will be assigned automatically).
                                    </p>
                                    <x-forms.field label="Resulting pool code:" name="pool_code">
                                        <x-forms.select-input id="pool_code" name="pool_code"
                                            class="w-full px-4 py-2 text-sm border border-blue-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            <option value="">Select pool code</option>
                                            @foreach (($available_pool_codes ?? []) as $poolCode)
                                                <option value="{{ $poolCode }}" @selected(old('pool_code') === $poolCode)>{{ $poolCode }}</option>
                                            @endforeach
                                        </x-forms.select-input>
                                    </x-forms.field>
                                </div>
                            </div>

                            <!-- Code input -->
                            <x-forms.field name="code[]">
                                <div id="selected_samples_assignment" class="mt-4 space-y-4" style="display: none;">
                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                        <h3 class="text-sm font-medium text-gray-700 mb-3">Code Assignment</h3>
                                        <div id="samples_assignment_list" class="space-y-3">
                                            <!-- Assignment items will be dynamically added here -->
                                        </div>
                                    </div>
                                </div>
                            </x-forms.field>

                            


                            
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">

                            <!-- Parasite Information -->
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-bug text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Parasite Information</h2>
                            </div>

                            <!-- Input for parasite species -->
                            <x-forms.field label="Parasite Species:" name="parasite_species">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <x-forms.select-input id="parasite_species" name="parasite_species" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($species_by_family as $family => $species_list)
                                                <optgroup label="{{ $family }}">
                                                    @foreach ($species_list as $species)
                                                        <option value="{{ $species['scientific'] }}">
                                                            {{ $species['scientific'] }}
                                                        </option>
                                                    @endforeach
                                                </optgroup>
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

                            <!-- Sex input -->
                            <x-forms.field label="Sex:" name="sex">
                                <x-forms.radio-input name="sex" :options="['Male' => 'Male', 'Female' => 'Female', 'NA' => 'N/A']"
                                    checked="Male"></x-forms.radio-input>
                            </x-forms.field>

                            <!-- Stage input -->
                            <x-forms.field label="Stage:" name="stage">
                                <x-forms.radio-input name="stage" :options="[
                                    'Egg' => 'Egg',
                                    'Larva' => 'Larva',
                                    'Nymph' => 'Nymph',
                                    'Adult' => 'Adult',
                                    'NA' => 'N/A',
                                ]"
                                    checked="Adult"></x-forms.radio-input>
                            </x-forms.field>

                            <!-- State input -->
                            <x-forms.field label="Repletion state:" name="state">
                                <x-forms.radio-input name="state" :options="[
                                    'Engorged' => 'Engorged',
                                    'Partially engorged' => 'Partially engorged',
                                    'Not engorged' => 'Not engorged',
                                    'NA' => 'N/A',
                                ]"
                                    checked="Partially engorged"></x-forms.radio-input>
                            </x-forms.field>


                            <!-- Photo input -->
                            <x-forms.field label="Upload photos of the parasite (optional):" name="photos">
                                <x-forms.multi-photo-upload id="parasite_photos" name="photos[]" label="Upload files" />
                            </x-forms.field>
                            
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-circle-info text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Ancillary Information</h2>
                            </div>

                            <!-- Collection date input -->
                            <x-forms.field label="Identification date:" name="date">
                                <x-forms.date-input id="date" name="date" value="{{ now()->toDateString() }}" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.date-input>
                            </x-forms.field>

                            <!-- Identificator input -->
                            <x-forms.field label="Identified by:" name="identificator">
                                @if ($can_assign_registrar)
                                    <x-forms.select-input id="identificator" name="identificator" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}">
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <x-forms.select-input id="identificator_locked" name="identificator_locked" disabled
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) $person->id === (int) ($locked_registrar_people_id ?? 0))>
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                    <input type="hidden" name="identificator" value="{{ $locked_registrar_people_id }}">
                                @endif
                            </x-forms.field>

                            <!-- Laboratory input -->
                            <x-forms.field label="Identified at:" name="parasite_lab" class="mt-4">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <x-forms.select-input id="parasite_lab" name="parasite_lab"
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

                            <button id="parasite_lab_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Laboratory
                            </button>

                            <!-- Sample state input -->
                            <x-forms.field label="State of the samples:" name="parasite_state">
                                <x-forms.select-input id="parasite_state" name="parasite_state" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Untreated/Unpreserved">Untreated/Unpreserved</option>
                                    <option value="Preserved in 100% ethanol">100% ethanol</option>
                                    <option value="Preserved in 70% ethanol">70% ethanol</option>
                                    <option value="Preserved in PBS">Preserved in PBS</option>
                                    <option value="Preserved in Glycerol">Preserved in Glycerol</option>
                                    <option value="Preserved in DMSO">Preserved in DMSO</option>
                                    <option value="Preserved in RNAlater">Preserved in RNAlater</option>
                                    <option value="Preserved in 10% formalin">Preserved in 10% formalin</option>
                                    <option value="Preserved in 4% PFA">Preserved in 4% PFA</option>
                                    <option value="Preserved in 70% isopropanol">Preserved in 70% isopropanol</option>
                                    <option value="Preserved in 4% paraformaldehyde">Preserved in 4% paraformaldehyde</option>
                                </x-forms.select-input>
                            </x-forms.field>

                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Parasite
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>
        </div>

        <!-- Sample Selection Modals -->
        <x-table-modal id="human_samples_modal" title="Human Samples" closeButtonId="human_samples_close_btn">
            <div data-modal-content class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="animal_samples_modal" title="Animal Samples" closeButtonId="animal_samples_close_btn">
            <div data-modal-content class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="environment_samples_modal" title="Environmental Samples" closeButtonId="environment_samples_close_btn">
            <div data-modal-content class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="parasite_species_form_modal" title="Parasite Species Registration Form"
        closeButtonId="parasite_species_form_close_btn">
        @include('samples.parasites.forms.form_parasite_species')
    </x-table-modal>

        <x-table-modal id="parasite_lab_form_modal" title="Parasite Laboratory Registration Form"
        closeButtonId="parasite_lab_form_close_btn">
        @include('modals.form_laboratories')
    </x-table-modal>

        @include('partials.lookup.parasite-species-modal')
        @include('partials.lookup.laboratories-modal')

        <script>
            window.parasiteSpeciesLookupRows = @json($parasite_species_lookup_rows ?? []);
            window.laboratoryLookupRows = @json($laboratory_lookup_rows ?? []);
        </script>

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="/js/lookup-table.js?v={{ filemtime(public_path('js/lookup-table.js')) }}"></script>
            <script src="/js/create-parasite-samples.js?v={{ filemtime(public_path('js/create-parasite-samples.js')) }}"></script>
            <script>
                var availableParasiteCodes = @json($available_codes);
                var projectCode = @json($project_code);
                var availablePoolCodes = @json($available_pool_codes ?? []);
            </script>
        @endpush
    </div>
</x-layout>
