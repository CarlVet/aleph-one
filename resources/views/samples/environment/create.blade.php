<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0" x-data="{ registerMode: 'form' }">

        <!-- Buttons for Create, Edit, and Delete -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/environment/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/environment/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>
        <div class="mx-auto mb-6 w-full max-w-2xl rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-sm font-semibold text-gray-800">
                    Register environment samples by:
                </div>
                <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'form' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="environment_samples_register_mode" value="form" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-pen-to-square text-xs"></i>
                        Form
                    </label>
                    <label
                        class="inline-flex cursor-pointer items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition"
                        :class="registerMode === 'import' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50'">
                        <input type="radio" name="environment_samples_register_mode" value="import" x-model="registerMode" class="sr-only" />
                        <i class="fas fa-file-csv text-xs"></i>
                        Import CSV
                    </label>
                </div>
            </div>
        </div>

        <div x-show="registerMode === 'import'" x-cloak class="mt-6">
            <livewire:imports.environment-samples-import />
        </div>

        <div x-show="registerMode === 'form'" x-cloak>
        <x-forms.form method="POST" action="/samples/environment" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Environmental Samples Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new environment sample
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-leaf text-green-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Environment Information</h2>
                            </div>

                            <!-- Environment sample type input -->
                            <x-forms.field label="Environment sample type:" name="environment_sample_type">
                                <x-forms.select-input id="environment_sample_type" name="environment_sample_type[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($environment_sample_types as $sample_type)
                                        <option value="{{ $sample_type->name }}">
                                            {{ $sample_type->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            @php
                                $selectedEnvironmentTypes = collect(old('environment_sample_type', []))
                                    ->map(fn ($value) => (string) $value)
                                    ->filter()
                                    ->values();
                                $fieldLabelsByType = old('field_labels_by_type', []);
                            @endphp
                            <div id="environment_field_labels_section" class="rounded-lg border border-gray-200 bg-gray-50 p-4">
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-tags text-blue-500"></i>
                                    <h3 class="text-sm font-semibold text-gray-800">Field label assignment</h3>
                                </div>
                                <p class="mt-1 text-sm text-gray-600">
                                    Assign one field label for each selected environment sample type.
                                </p>
                                <div id="environment_field_labels_assignment_list" class="mt-3 flex flex-col gap-3">
                                    @foreach ($selectedEnvironmentTypes as $sampleTypeName)
                                        <div class="flex items-center justify-between gap-4 rounded-lg border border-gray-200 bg-white p-3">
                                            <span class="text-sm font-medium text-gray-700">{{ $sampleTypeName }}</span>
                                            <input type="text"
                                                name="field_labels_by_type[{{ $sampleTypeName }}]"
                                                value="{{ $fieldLabelsByType[$sampleTypeName] ?? '' }}"
                                                placeholder="Optional (leave empty to keep empty)"
                                                class="w-64 rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                        </div>
                                    @endforeach
                                </div>
                            </div>


                            <div class="pt-4 border-t border-gray-100">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Raw Storage Information</h2>
                                </div>

                                <!-- Locations input -->
                                <x-forms.field label="Enter sample location:" name="location">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-1">
                                            <x-forms.select-input id="location" name="location" required
                                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                @foreach ($locations as $location)
                                                    <option value="{{ $location->name }}">
                                                        {{ $location->name }}</option>
                                                @endforeach
                                            </x-forms.select-input>
                                        </div>
                                        <x-lookup.button id="locations_lookup_btn" title="Browse storage locations table" />
                                    </div>
                                </x-forms.field>

                                <button id="environment_location_form_btn" type="button"
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
                                <i class="fas fa-flask text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Sample Information</h2>
                            </div>

                            <!-- Collection date input -->
                            <x-forms.field label="Collection date:" name="date">
                                <x-forms.date-input id="date" name="date" value="{{ now()->toDateString() }}"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.date-input>
                            </x-forms.field>

                            <!-- Sampling site input -->
                            <x-forms.field label="Sampling site:" name="sampling_site">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <x-forms.select-input id="sampling_site" name="sampling_site" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($sampling_sites_available as $country => $sites_list)
                                                <optgroup label="{{ $country }}">
                                                    @foreach ($sites_list as $site)
                                                        <option value="{{ $site['name'] }}">
                                                            {{ $site['name'] }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </x-forms.select-input>
                                    </div>
                                    <x-lookup.button id="sampling_sites_lookup_btn" title="Browse sampling sites table" />
                                </div>
                            </x-forms.field>

                            <button id="environment_site_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Sampling Site
                            </button>


                            <!-- Sampling area input -->
                            <x-forms.field label="Sampling area:" name="area">
                                <x-forms.text-input id="area" name="area"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.text-input>
                            </x-forms.field>

                            <!-- Latitude input -->
                            <x-forms.field label="Latitude:" name="latitude">
                                <x-forms.numeric-input id="latitude" name="latitude" min="-90" max="90"
                                    step="any" value="-25.3524"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.numeric-input>
                            </x-forms.field>

                            <!-- Longitude input -->
                            <x-forms.field label="Longitude:" name="longitude">
                                <x-forms.numeric-input id="longitude" name="longitude" min="-180" max="180"
                                    step="any" value="31.8817"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.numeric-input>
                            </x-forms.field>

                            <!-- Collector input -->
                            <x-forms.field label="Collected by:" name="collector">
                                @if ($can_assign_registrar)
                                    <x-forms.select-input id="collector" name="collector" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}">
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <x-forms.select-input id="collector_locked" name="collector_locked" disabled
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) $person->id === (int) ($locked_registrar_people_id ?? 0))>
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                    <input type="hidden" name="collector" value="{{ $locked_registrar_people_id }}">
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
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <x-table-modal id="environment_site_form_modal" title="Sampling Site Registration Form"
            closeButtonId="environment_site_form_close_btn">
            @include('modals.form_sampling_sites')
        </x-table-modal>

        <x-table-modal id="environment_location_form_modal" title="Storage location Registration Form"
            closeButtonId="environment_location_form_close_btn">
            @include('modals.form_locations')
        </x-table-modal>

        @include('partials.lookup.locations-modal')
        @include('partials.lookup.sampling-sites-modal')

        <script>
            window.locationLookupRows = @json($location_lookup_rows ?? []);
            window.samplingSiteLookupRows = @json($sampling_site_lookup_rows ?? []);
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
                <script src="/js/create-environment-samples.js?v={{ filemtime(public_path('js/create-environment-samples.js')) }}"></script>
            @endpush
        </div>
    </div>
</x-layout>
