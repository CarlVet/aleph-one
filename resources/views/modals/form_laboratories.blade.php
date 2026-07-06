<div>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Selectize -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.3/js/standalone/selectize.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.3/css/selectize.default.min.css">
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
        </style>

        <x-forms.form method="POST" action="/laboratories" enctype="multipart/form-data">
            @csrf
            @php
                $organizationGroups = ($organizations_by_country ?? collect());
                if ($organizationGroups->isEmpty() && !empty($organizations)) {
                    $organizationGroups = collect(['Unassigned Country' => collect($organizations)]);
                }
            @endphp
            
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Laboratory Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new laboratory</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-building text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Laboratory Information</h2>
                            </div>

                            <x-forms.field label="Laboratory name:" name="place_name">
                                <x-forms.text-input id="place_name" name="place_name" type="text" required
                                    placeholder="e.g., Hans Hoheisen Wildlife Research Station"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                                <div id="place_name_status" class="mt-1 text-sm hidden"></div>
                            </x-forms.field>

                            <x-forms.field label="Description:" name="lab_description">
                                <x-forms.textarea id="lab_description" name="lab_description" type="text"
                                    placeholder="e.g., This is a laboratory for wildlife research"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.textarea>
                            </x-forms.field>

                            <x-forms.field label="Type:" name="lab_type">
                                <x-forms.select-input id="lab_type" name="lab_type"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select or enter laboratory type</option>
                                    @foreach (($lab_types ?? collect()) as $labType)
                                        <option value="{{ $labType }}">{{ $labType }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Organization:" name="lab_organization">
                                <x-forms.select-input id="lab_organization" name="lab_organization"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select organization</option>
                                    @foreach ($organizationGroups as $countryName => $countryOrganizations)
                                        <optgroup label="{{ $countryName }}">
                                            @foreach ($countryOrganizations as $organization)
                                                <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <button id="lab_organization_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Organization
                            </button>


                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-globe text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Geographic Information</h2>
                            </div>

                            <x-forms.field label="Country:" name="lab_country">
                                <x-forms.select-input id="lab_country" name="lab_country" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select country</option>
                                    @foreach (($countries ?? collect()) as $country)
                                        <option value="{{ $country->name }}">{{ $country->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Region/State:" name="lab_region">
                                <x-forms.text-input id="lab_region" name="lab_region" type="text"
                                    placeholder="e.g., Gauteng"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                            </x-forms.field>

                            <x-forms.field label="City:" name="lab_city">
                                <x-forms.text-input id="lab_city" name="lab_city" type="text"
                                    placeholder="e.g., Pretoria"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                            </x-forms.field>

                            <x-forms.field label="Laboratory address:" name="lab_address">
                                <x-forms.text-input id="lab_address" name="lab_address" type="text" required
                                    placeholder="e.g., 123 Main St, Anytown, USA"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                            </x-forms.field>

                            <x-forms.field label="Latitude:" name="lab_latitude">
                                <x-forms.numeric-input id="lab_latitude" name="lab_latitude" min="-90" max="90"
                                    step="any" placeholder="e.g., -25.3524"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                            </x-forms.field>

                            <x-forms.field label="Longitude:" name="lab_longitude">
                                <x-forms.numeric-input id="lab_longitude" name="lab_longitude" min="-180" max="180"
                                    step="any" placeholder="e.g., 31.8817"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                            </x-forms.field>

                            <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                                    <div>
                                        <h3 class="text-sm font-medium text-blue-800 mb-1">Laboratory Information</h3>
                                        <p class="text-sm text-blue-700">
                                            This form allows you to register new laboratories where experiments are performed. 
                                            The type is automatically set to "Laboratory" and you can select from existing 
                                            organizations or enter a new one.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit id="laboratory_submit_btn" class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Laboratory
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <x-table-modal id="lab_organization_form_modal" title="Organization Registration Form"
            closeButtonId="lab_organization_form_close_btn">
            @include('modals.form_organizations', [
                'organization_types' => $organization_types ?? [],
                'countries' => $countries ?? collect(),
            ])
        </x-table-modal>

        @if (session('success'))
            <div id="laboratorySuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="laboratoryErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            // Pass the PHP arrays to JavaScript
            var organizationsList = @json($organizations ?? []);
            var countriesList = @json($countries ?? []);
        </script>

        <!-- Custom scripts -->
        <script src="/js/create-laboratories.js"></script>
    </div>
</div> 