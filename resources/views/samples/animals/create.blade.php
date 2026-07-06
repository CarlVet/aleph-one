<x-layout>
    @php
        $defaultRegisterMode = $register_mode ?? old('register_mode', 'form');
    @endphp
    <div class="mt-2 md:col-span-2 md:mt-0">

        <!-- Buttons for Create, Edit, and Delete -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/animals/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/animals/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>

        <div class="mx-auto mb-6 w-full max-w-2xl rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-sm font-semibold text-gray-800">
                    Register animal samples by:
                </div>
                <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                    <a href="{{ url('/samples/animals/create?mode=form') }}"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition
                        {{ $defaultRegisterMode === 'form' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}">
                        <i class="fas fa-pen-to-square text-xs"></i>
                        Form
                    </a>
                    <a href="{{ url('/samples/animals/create?mode=import') }}"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition
                        {{ $defaultRegisterMode === 'import' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}">
                        <i class="fas fa-file-csv text-xs"></i>
                        Import CSV
                    </a>
                    <a href="{{ url('/samples/animals/create?mode=table') }}"
                        class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition
                        {{ $defaultRegisterMode === 'table' ? 'border-blue-600 bg-blue-600 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-50' }}">
                        <i class="fas fa-table text-xs"></i>
                        Table
                    </a>
                </div>
            </div>
        </div>

        @if ($defaultRegisterMode === 'import')
            <div class="mt-6">
                <livewire:imports.animal-samples-import />
            </div>
        @endif

        @if ($defaultRegisterMode === 'table')
        <div class="mt-6">
            @php
                $tableRows = old('table_rows', [
                    [
                        'animal_species' => '',
                        'field_label' => '',
                        'sex' => 'Male',
                        'age' => 'Adult',
                        'owner_type' => 'individual',
                        'owner_person' => '',
                        'owner_organization' => '',
                        'sample_type' => '',
                        'date' => now()->toDateString(),
                        'sampling_site' => '',
                        'area' => '',
                        'latitude' => '',
                        'longitude' => '',
                        'location' => '',
                        'preservant' => '',
                        'date_received' => now()->toDateString(),
                        'reason_immobilization' => '',
                        'collector' => (string) ($locked_registrar_people_id ?? ''),
                    ],
                ]);
            @endphp
            <x-forms.form method="POST" action="/samples/animals" id="animal-table-form">
                @csrf
                <input type="hidden" name="register_mode" value="table">

                <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                        <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                        <div class="text-center mb-2">
                            <h2 class="text-2xl font-bold text-gray-800">Animal + Animal Samples Table Registration</h2>
                            <p class="mt-2 text-sm text-gray-600">
                                Register new animals and their samples row by row in spreadsheet style.
                            </p>
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <button type="button" id="animal-table-add-row"
                                class="inline-flex items-center rounded-lg border border-blue-600 bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700">
                                <i class="fas fa-plus mr-2"></i>Add row
                            </button>
                            <span class="text-sm text-gray-600">Each row creates one animal and one sample.</span>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="min-w-[2100px] w-full text-sm" id="animal-registration-table">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Animal species*</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Field label*</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Sex*</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Age*</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Owner type*</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Owner (person)</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Owner (organization)</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Sample type*</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">New sample type category</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Collection date*</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Sampling site*</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Area</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Latitude</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Longitude</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Location*</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Preservant</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Date received</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Immobilization reason</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Collected by</th>
                                        <th class="px-3 py-2 text-left font-semibold text-gray-700">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="animal-registration-table-body" class="divide-y divide-gray-100">
                                    @foreach ($tableRows as $rowIndex => $row)
                                        <tr class="align-top" data-table-row>
                                            <td class="px-2 py-2 min-w-[240px]">
                                                <div class="flex items-center gap-2">
                                                    <select name="table_rows[{{ $rowIndex }}][animal_species]" class="table-animal-species table-selectized w-full rounded-md border-gray-300" required>
                                                        <option value=""></option>
                                                        @foreach ($species_by_family as $family => $species_list)
                                                            <optgroup label="{{ $family }}">
                                                                @foreach ($species_list as $species)
                                                                    <option value="{{ $species['common'] }}" @selected(($row['animal_species'] ?? '') === $species['common'])>{{ $species['common'] }}</option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="table-open-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" data-modal-target="table_animal_species_form_modal" title="Create new animal species">
                                                        <i class="fas fa-plus text-xs"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2 min-w-[170px]">
                                                <div class="space-y-1">
                                                    <input type="text" name="table_rows[{{ $rowIndex }}][field_label]" value="{{ $row['field_label'] ?? '' }}" class="table-field-label w-full rounded-md border-gray-300" required>
                                                    <div class="table-field-label-warning hidden text-xs text-amber-700">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i>
                                                        <span class="table-field-label-warning-text"></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2 min-w-[130px]">
                                                <select name="table_rows[{{ $rowIndex }}][sex]" class="table-selectized w-full rounded-md border-gray-300" required>
                                                    @foreach (['Male', 'Female', 'NA'] as $sexOption)
                                                        <option value="{{ $sexOption }}" @selected(($row['sex'] ?? 'Male') === $sexOption)>{{ $sexOption }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-2 py-2 min-w-[130px]">
                                                <select name="table_rows[{{ $rowIndex }}][age]" class="table-selectized w-full rounded-md border-gray-300" required>
                                                    @foreach (['Juvenile', 'Sub-adult', 'Adult', 'Old', 'NA'] as $ageOption)
                                                        <option value="{{ $ageOption }}" @selected(($row['age'] ?? 'Adult') === $ageOption)>{{ $ageOption }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-2 py-2 min-w-[160px]">
                                                <select name="table_rows[{{ $rowIndex }}][owner_type]" class="table-owner-type table-selectized w-full rounded-md border-gray-300" required>
                                                    <option value="individual" @selected(($row['owner_type'] ?? 'individual') === 'individual')>individual</option>
                                                    <option value="organization" @selected(($row['owner_type'] ?? 'individual') === 'organization')>organization</option>
                                                </select>
                                            </td>
                                            <td class="px-2 py-2 min-w-[220px] owner-person-cell">
                                                <div class="flex items-center gap-2">
                                                    <select name="table_rows[{{ $rowIndex }}][owner_person]" class="table-owner-person table-selectized w-full rounded-md border-gray-300">
                                                        <option value=""></option>
                                                        @foreach ($humans as $human)
                                                            <option value="{{ $human->id }}" @selected((string) ($row['owner_person'] ?? '') === (string) $human->id)>
                                                                {{ trim(($human->first_name ?? '') . ' ' . ($human->last_name ?? '')) }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="table-open-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" data-modal-target="table_animal_humans_form_modal" title="Create new owner (person)">
                                                        <i class="fas fa-plus text-xs"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2 min-w-[220px] owner-org-cell">
                                                <div class="flex items-center gap-2">
                                                    <select name="table_rows[{{ $rowIndex }}][owner_organization]" class="table-owner-organization table-selectized w-full rounded-md border-gray-300">
                                                        <option value=""></option>
                                                        @foreach ($organizations as $organization)
                                                            <option value="{{ $organization->id }}" @selected((string) ($row['owner_organization'] ?? '') === (string) $organization->id)>
                                                                {{ $organization->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="table-open-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" data-modal-target="table_animal_organization_form_modal" title="Create new owner (organization)">
                                                        <i class="fas fa-plus text-xs"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2 min-w-[200px]">
                                                <select name="table_rows[{{ $rowIndex }}][sample_type]" class="table-selectized w-full rounded-md border-gray-300" required>
                                                    <option value=""></option>
                                                    @foreach ($sample_types as $sample_type)
                                                        <option value="{{ $sample_type->name }}" @selected(($row['sample_type'] ?? '') === $sample_type->name)>{{ $sample_type->name }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-2 py-2 min-w-[200px]">
                                                <select name="table_rows[{{ $rowIndex }}][sample_type_category]" class="table-sample-type-category table-selectized w-full rounded-md border-gray-300">
                                                    <option value=""></option>
                                                    <option value="host_derived" @selected(($row['sample_type_category'] ?? '') === 'host_derived')>host derived</option>
                                                    <option value="non_host_derived" @selected(($row['sample_type_category'] ?? '') === 'non_host_derived')>non-host derived</option>
                                                </select>
                                            </td>
                                            <td class="px-2 py-2 min-w-[170px]">
                                                <input type="date" name="table_rows[{{ $rowIndex }}][date]" value="{{ $row['date'] ?? now()->toDateString() }}" class="w-full rounded-md border-gray-300" required>
                                            </td>
                                            <td class="px-2 py-2 min-w-[220px]">
                                                <div class="flex items-center gap-2">
                                                    <select name="table_rows[{{ $rowIndex }}][sampling_site]" class="table-selectized w-full rounded-md border-gray-300" required>
                                                        <option value=""></option>
                                                        @foreach ($sampling_sites_available as $country => $sites_list)
                                                            <optgroup label="{{ $country }}">
                                                                @foreach ($sites_list as $site)
                                                                    <option value="{{ $site['name'] }}" @selected(($row['sampling_site'] ?? '') === $site['name'])>{{ $site['name'] }}</option>
                                                                @endforeach
                                                            </optgroup>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="table-open-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" data-modal-target="animal_site_form_modal" title="Create new sampling site">
                                                        <i class="fas fa-plus text-xs"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2 min-w-[130px]">
                                                <input type="text" name="table_rows[{{ $rowIndex }}][area]" value="{{ $row['area'] ?? '' }}" class="w-full rounded-md border-gray-300">
                                            </td>
                                            <td class="px-2 py-2 min-w-[140px]">
                                                <input type="number" step="any" name="table_rows[{{ $rowIndex }}][latitude]" value="{{ $row['latitude'] ?? '' }}" class="w-full rounded-md border-gray-300">
                                            </td>
                                            <td class="px-2 py-2 min-w-[140px]">
                                                <input type="number" step="any" name="table_rows[{{ $rowIndex }}][longitude]" value="{{ $row['longitude'] ?? '' }}" class="w-full rounded-md border-gray-300">
                                            </td>
                                            <td class="px-2 py-2 min-w-[220px]">
                                                <div class="flex items-center gap-2">
                                                    <select name="table_rows[{{ $rowIndex }}][location]" class="table-selectized w-full rounded-md border-gray-300" required>
                                                        <option value=""></option>
                                                        @foreach ($locations as $location)
                                                            <option value="{{ $location->name }}" @selected(($row['location'] ?? '') === $location->name)>{{ $location->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="button" class="table-open-modal inline-flex h-8 w-8 items-center justify-center rounded-md border border-amber-300 bg-amber-50 text-amber-700 hover:bg-amber-100" data-modal-target="animal_location_form_modal" title="Create new location">
                                                        <i class="fas fa-plus text-xs"></i>
                                                    </button>
                                                </div>
                                            </td>
                                            <td class="px-2 py-2 min-w-[180px]">
                                                <select name="table_rows[{{ $rowIndex }}][preservant]" class="table-selectized w-full rounded-md border-gray-300">
                                                    <option value=""></option>
                                                    @foreach (($storage_state_options ?? []) as $opt)
                                                        <option value="{{ $opt }}" @selected(($row['preservant'] ?? '') === $opt)>{{ $opt }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-2 py-2 min-w-[170px]">
                                                <input type="date" name="table_rows[{{ $rowIndex }}][date_received]" value="{{ $row['date_received'] ?? now()->toDateString() }}" class="w-full rounded-md border-gray-300">
                                            </td>
                                            <td class="px-2 py-2 min-w-[220px]">
                                                <select name="table_rows[{{ $rowIndex }}][reason_immobilization]" class="table-selectized w-full rounded-md border-gray-300">
                                                    <option value=""></option>
                                                    @foreach ($unique_reasons as $reason)
                                                        <option value="{{ $reason }}" @selected(($row['reason_immobilization'] ?? '') === $reason)>{{ $reason }}</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td class="px-2 py-2 min-w-[220px]">
                                                <select name="table_rows[{{ $rowIndex }}][collector]" class="table-selectized w-full rounded-md border-gray-300" @if (!$can_assign_registrar) disabled @endif>
                                                    <option value=""></option>
                                                    @foreach ($people as $person)
                                                        @php
                                                            $personLabel = trim(($person->title ?? '') . ' ' . ($person->first_name ?? '') . ' ' . ($person->last_name ?? ''));
                                                            $selectedCollector = (string) ($row['collector'] ?? ($locked_registrar_people_id ?? ''));
                                                        @endphp
                                                        <option value="{{ $person->id }}" @selected($selectedCollector === (string) $person->id)>{{ $personLabel }}</option>
                                                    @endforeach
                                                </select>
                                                @if (!$can_assign_registrar)
                                                    <input type="hidden" name="table_rows[{{ $rowIndex }}][collector]" value="{{ $locked_registrar_people_id }}">
                                                @endif
                                            </td>
                                            <td class="px-2 py-2 min-w-[90px]">
                                                <button type="button" class="animal-table-remove-row inline-flex items-center rounded-md border border-red-200 px-3 py-2 text-xs font-medium text-red-700 hover:bg-red-50">Remove</button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <p class="text-xs text-gray-500">* Required columns. If owner type is individual, fill Owner (person). If organization, fill Owner (organization). If sample type is new, select its category.</p>
                    </div>

                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                        <x-forms.submit
                            class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                            <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                            Register Table Rows
                        </x-forms.submit>
                    </div>
                </div>
            </x-forms.form>
        </div>
        @endif

        @if ($defaultRegisterMode === 'form')
        <div>
            <x-forms.form method="POST" action="/samples/animals" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="register_mode" value="form">
                <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                        <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                        <div class="text-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-800">Animal Samples Registration Form</h2>
                            <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new animal sample
                            </p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-paw text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Animal Information</h2>
                            </div>

                            <!-- Animal ID selection for existing animals -->
                            <div class="flex items-center space-x-4">
                                <x-forms.table-button id="animals_table_btn"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg shadow-md hover:shadow-lg border border-rose-600">
                                    <i
                                        class="fas fa-paw mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                    Select Animals
                                </x-forms.table-button>
                                <span id="animals_count" class="text-sm text-gray-600">(0 selected)</span>
                            </div>
                            <x-forms.field label="Select animal:" name="animal_id[]">
                                <x-forms.select-input id="animal_id" name="animal_id[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($selected_animals as $animal)
                                        <option value="{{ $animal->id }}" selected>
                                            {{ $animal->code }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>
                            <button id="animals_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Animals
                            </button>

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

                                <button id="animal_location_form_btn" type="button"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                    <i
                                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                    Create New Storage Location
                                </button>

                                <x-forms.field label="Preservant:" name="preservant">
                                    <x-forms.select-input id="preservant" name="preservant"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach (($storage_state_options ?? []) as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>

                                <x-forms.field label="Date received:" name="date_received">
                                    <x-forms.date-input id="date_received" name="date_received" value="{{ now()->toDateString() }}"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.date-input>
                                </x-forms.field>


                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-flask text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Sample Information</h2>
                            </div>

                            <!-- Sample type input -->
                            <x-forms.field label="Sample type:" name="sample_type">
                                <x-forms.select-input id="sample_type" name="sample_type[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($sample_types as $sample_type)
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

                            <button id="animal_site_form_btn" type="button"
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

                            <!-- Reason input -->
                            <x-forms.field label="Immobilization reason:" name="reason_immobilization">
                                <x-forms.select-input id="reason_immobilization" name="reason_immobilization"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($unique_reasons as $reason)
                                        <option value="{{ $reason }}">
                                            {{ $reason }}</option>
                                    @endforeach
                                </x-forms.select-input>
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

        </div>
        @endif

        <x-table-modal id="animals_table_modal" title="Animals" closeButtonId="animals_table_close_btn">
            <div data-modal-content class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="animals_form_modal" title="Animals Registration Form"
            closeButtonId="animals_form_close_btn">
            @include('samples.animals.forms.form_animals')
        </x-table-modal>

        <x-table-modal id="animal_site_form_modal" title="Sampling Site Registration Form"
            closeButtonId="animal_site_form_close_btn">
            @include('modals.form_sampling_sites')
        </x-table-modal>

        <x-table-modal id="animal_location_form_modal" title="Storage Location Registration Form"
            closeButtonId="animal_location_form_close_btn">
            @include('modals.form_locations')
        </x-table-modal>

        @include('partials.lookup.locations-modal')
        @include('partials.lookup.sampling-sites-modal')

        <x-table-modal id="table_animal_species_form_modal" title="Animal Species Registration Form"
            closeButtonId="table_animal_species_form_close_btn">
            @include('samples.animals.forms.form_animal_species')
        </x-table-modal>

        <x-table-modal id="table_animal_humans_form_modal" title="Human Registration Form"
            closeButtonId="table_animal_humans_form_close_btn">
            @include('samples.humans.forms.form_human')
        </x-table-modal>

        <x-table-modal id="table_animal_organization_form_modal" title="Organization Registration Form"
            closeButtonId="table_animal_organization_form_close_btn">
            @include('modals.form_organizations')
        </x-table-modal>

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <!-- Selectize scripts -->
        <script>
            window.locationLookupRows = @json($location_lookup_rows ?? []);
            window.samplingSiteLookupRows = @json($sampling_site_lookup_rows ?? []);
        </script>

        @push('scripts')
            <script>
                window.animalTableExistingAnimals = @json($table_existing_animals ?? []);
                window.animalTableKnownSampleTypes = @json(collect($sample_types ?? [])->pluck('name')->values());
            </script>
            <script src="/js/lookup-table.js?v={{ filemtime(public_path('js/lookup-table.js')) }}"></script>
            <script src="/js/create-animal-samples.js?v={{ filemtime(public_path('js/create-animal-samples.js')) }}"></script>
            <script src="/js/create-animal-samples-table.js?v={{ filemtime(public_path('js/create-animal-samples-table.js')) }}"></script>
        @endpush
    </div>
</x-layout>
