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

            /* Ensure nested modals stack properly */
            #study_form_modal {
                z-index: 60 !important;
            }
        </style>

        <x-forms.form method="POST" action="/sampling_sites" enctype="multipart/form-data">
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
                        <h2 class="text-2xl font-bold text-gray-800">Sampling Site Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new site</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-map-location-dot text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Site Information</h2>
                            </div>

                            <x-forms.field label="Name of sampling site:" name="site_name">
                                <x-forms.text-input id="site_name" name="site_name" />
                                <div id="site_name_status" class="mt-1 text-sm hidden"></div>
                            </x-forms.field>

                            <x-forms.field label="Description:" name="description">
                                <x-forms.textarea name="description" />
                            </x-forms.field>

                            <x-forms.field label="Type of sampling site:" name="site_type">
                                <x-forms.select-input id="site_type" name="site_type">
                                    <option value="">Select or enter new site type</option>
                                    @foreach (($site_types ?? collect()) as $siteType)
                                        <option value="{{ $siteType }}">{{ $siteType }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Organization:" name="organization_id">
                                <x-forms.select-input id="organization_id" name="organization_id">
                                    <option value="">Select an organization</option>
                                    @foreach ($organizationGroups as $countryName => $countryOrganizations)
                                        <optgroup label="{{ $countryName }}">
                                            @foreach ($countryOrganizations as $organization)
                                                <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <button id="site_organization_form_btn" type="button"
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

                            <x-forms.field label="Country:" name="sampling_country">
                                <x-forms.select-input name="sampling_country" id="sampling_country">
                                    <option value="">Select a country</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->name }}">{{ $country->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Region:" name="region">
                                <x-forms.text-input name="region" />
                            </x-forms.field>

                            <x-forms.field label="City:" name="city">
                                <x-forms.text-input name="city" />
                            </x-forms.field>

                            <x-forms.field label="Province:" name="province">
                                <x-forms.text-input name="province" />
                            </x-forms.field>

                            <x-forms.field label="Latitude:" name="latitude">
                                <x-forms.numeric-input name="latitude" value="-25.3524" min="-90" max="90"
                                    step="any" />
                            </x-forms.field>

                            <x-forms.field label="Longitude:" name="longitude">
                                <x-forms.numeric-input name="longitude" value="31.8817" min="-180" max="180"
                                    step="any" />
                            </x-forms.field>
                            
                            
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit id="sampling_site_submit_btn" class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Sampling Site
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <x-table-modal id="site_organization_form_modal" title="Organization Registration Form"
            closeButtonId="site_organization_form_close_btn">
            @include('modals.form_organizations', [
                'organization_types' => $organization_types ?? collect(),
                'countries' => $countries ?? collect(),
            ])
        </x-table-modal>

        @if (session('success'))
            <div id="samplingSiteSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="samplingSiteErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="/js/create-sampling-sites.js"></script>
        @endpush
    </div>


</div>


