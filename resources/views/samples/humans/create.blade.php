<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0" x-data="{ registerMode: 'form' }">

        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/humans/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/humans/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>
        <div class="mx-auto mb-6 w-full max-w-2xl rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-sm font-semibold text-gray-800">
                    Register human samples by:
                </div>
                <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'form' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="human_samples_register_mode" value="form" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-pen-to-square text-xs"></i>
                        Form
                    </label>
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'import' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="human_samples_register_mode" value="import" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-file-csv text-xs"></i>
                        Import CSV
                    </label>
                </div>
            </div>
        </div>

        <div x-show="registerMode === 'import'" x-cloak class="mt-6">
            <livewire:imports.human-samples-import />
        </div>

        <div x-show="registerMode === 'form'" x-cloak>
        <x-forms.form method="POST" action="/samples/humans" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Human Samples Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new human sample
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-person text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Sample Information</h2>
                            </div>

                            <!-- Human patients -->
                            <div class="flex items-center space-x-4">
                                <x-forms.table-button id="humans_btn"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-rose-500 to-rose-600 text-white rounded-lg shadow-md hover:shadow-lg border border-rose-600">
                                    <i
                                        class="fas fa-person mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                    Select Patients
                                </x-forms.table-button>
                                <span id="humans_count" class="text-sm text-gray-600">(0 selected)</span>
                            </div>
                            <x-forms.field name="humans_id[]">
                                <x-forms.select-input id="humans_id" name="humans_id[]" multiple
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($selected_humans as $human)
                                        <option value="{{ $human->id }}" selected>{{ $human->code }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>
                            <button id="humans_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Patient
                            </button>

                            <!-- Sample type input -->
                            <x-forms.field label="Sample type:" name="human_sample_type">
                                <x-forms.select-input id="human_sample_type" name="human_sample_type[]" multiple
                                    required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($sample_types_available as $sample_type)
                                        <option value="{{ $sample_type->name }}">
                                            {{ $sample_type->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <div id="new_sample_types_section" class="hidden rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-tag text-blue-500"></i>
                                    <h3 class="text-sm font-semibold text-gray-800">New sample types</h3>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">
                                    For each new sample type you add, specify if it is <span class="font-medium">host derived</span> or
                                    <span class="font-medium">non-host derived</span>.
                                </p>

                                <div id="new_sample_types_container" class="mt-3 flex flex-col gap-3"></div>
                            </div>

                            <!-- Sample purpose input -->
                                <x-forms.field label="Sampling purpose:" name="sampling_purpose">
                                    <x-forms.select-input id="sampling_purpose" name="sampling_purpose">
                                        <option value="Diagnostic">Diagnostic</option>
                                        <option value="Research">Research</option>
                                        <option value="Surveillance">Surveillance</option>
                                        <option value="Other">Other</option>
                                    </x-forms.select-input>
                                </x-forms.field>

                                <!-- Storage state input -->
                                <x-forms.field label="Storage state:" name="storage_state">
                                    <x-forms.select-input id="storage_state" name="storage_state">
                                        <option value="No preservative">No preservative</option>
                                        <option value="Formalin">Formalin</option>
                                        <option value="RNAlater">RNAlater</option>
                                    </x-forms.select-input>
                                </x-forms.field>

                                <div class="pt-4 border-t border-gray-100">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                                        <h2 class="text-lg font-semibold text-gray-800">Raw Storage Information</h2>
                                    </div>
    
                                    <!-- Locations input -->
                                    <x-forms.field label="Enter sample location:" name="human_location">
                                        <div class="flex items-start gap-3">
                                            <div class="flex-1">
                                                <x-forms.select-input id="human_location" name="human_location" required
                                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                    @foreach ($locations as $location)
                                                        <option value="{{ $location->id }}">
                                                            {{ $location->name }}</option>
                                                    @endforeach
                                                </x-forms.select-input>
                                            </div>
                                            <x-lookup.button id="locations_lookup_btn" title="Browse storage locations table" />
                                        </div>
                                    </x-forms.field>
    
                                    <button id="location_form_btn" type="button"
                                        class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                        <i
                                            class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                        Create New Storage Location
                                    </button>
    
                                </div>
                                
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                    <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Ancillary Information</h2>
                                </div>

                                <!-- Collection date input -->
                                <x-forms.field label="Collection date:" name="date" class="mt-4">
                                    <x-forms.date-input id="date" name="date"
                                        value="{{ now()->toDateString() }}" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    </x-forms.date-input>
                                </x-forms.field>

                                <!-- Human site input -->
                                <x-forms.field label="Sampling site:" name="human_site" class="mt-4">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-1">
                                            <x-forms.select-input id="human_site" name="human_site" onchange="checkPlaceValue()"
                                                required
                                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                @foreach ($sampling_sites_available as $country => $places_list)
                                                    <optgroup label="{{ $country }}">
                                                        @foreach ($places_list as $place)
                                                            <option value="{{ $place['name'] }}">{{ $place['name'] }}</option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </x-forms.select-input>
                                        </div>
                                        <x-lookup.button id="sampling_sites_lookup_btn" title="Browse sampling sites table" />
                                    </div>
                                </x-forms.field>

                                <button id="human_site_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Sampling Site
                            </button>

                                <!-- Sampling area input -->
                                <x-forms.field label="Sampling area:" name="human_area">
                                    <x-forms.text-input id="human_area" name="human_area"></x-forms.text-input>
                                </x-forms.field>

                                <!-- Latitude input -->
                                <x-forms.field label="Latitude:" name="human_latitude">
                                    <x-forms.numeric-input id="human_latitude" name="human_latitude" min="-90"
                                        max="90" step="any" value="-25.3524"></x-forms.numeric-input>
                                </x-forms.field>

                                <!-- Longitude input -->
                                <x-forms.field label="Longitude:" name="human_longitude">
                                    <x-forms.numeric-input id="human_longitude" name="human_longitude" min="-180"
                                        max="180" step="any" value="31.8817"></x-forms.numeric-input>
                                </x-forms.field>

                                <!-- Collector input -->
                                <x-forms.field label="Collected by:" name="scientist" class="mt-4">
                                    @if ($can_assign_registrar)
                                        <x-forms.select-input id="scientist" name="scientist" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($people_available as $person)
                                                <option value="{{ $person->id }}">
                                                    {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                                </option>
                                            @endforeach
                                        </x-forms.select-input>
                                    @else
                                        <x-forms.select-input id="scientist_locked" name="scientist_locked" disabled
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($people_available as $person)
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
                </div>

                <div
                    class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i
                            class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Human Sample
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <x-table-modal id="humans_modal" title="Humans" closeButtonId="humans_close_btn">
            <div data-modal-content class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="humans_form_modal" title="Patient Registration Form"
            closeButtonId="humans_form_close_btn">
            @include('samples.humans.forms.form_human')
        </x-table-modal>

        <x-table-modal id="human_site_form_modal" title="Patient Registration Form"
            closeButtonId="human_site_form_close_btn">
            @include('modals.form_sampling_sites')
        </x-table-modal>

        <x-table-modal id="location_form_modal" title="Location Registration Form"
            closeButtonId="location_form_close_btn">
            @include('modals.form_locations')
        </x-table-modal>

        @include('partials.lookup.locations-modal')
        @include('partials.lookup.sampling-sites-modal')

            @if (session('success'))
                <div id="humanSampleSuccessMessage" class="hidden">{{ session('success') }}</div>
            @endif

            @if (session('error'))
                <div id="humanSampleErrorMessage" class="hidden">{{ session('error') }}</div>
            @endif

            <script>
                window.locationLookupRows = @json($location_lookup_rows ?? []);
                window.samplingSiteLookupRows = @json($sampling_site_lookup_rows ?? []);
            </script>

            <!-- Selectize scripts -->
            @push('scripts')
                <script src="/js/lookup-table.js?v={{ filemtime(public_path('js/lookup-table.js')) }}"></script>
                <script src="/js/create-human-samples.js?v={{ filemtime(public_path('js/create-human-samples.js')) }}"></script>
            @endpush
        </div>
    </div>
</x-layout>
