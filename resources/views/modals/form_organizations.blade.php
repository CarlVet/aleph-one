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

        <x-forms.form method="POST" action="/organizations" enctype="multipart/form-data" class="organization-registration-form">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Organizations Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new organization</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-industry text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Organization Information</h2>
                            </div>

                            <x-forms.field label="Name of organization:" name="organization_name">
                                <x-forms.text-input id="organization_name" name="organization_name" class="organization-name-input" />
                                <div class="organization-name-status mt-1 text-sm hidden"></div>
                            </x-forms.field>

                            <x-forms.field label="Description:" name="description">
                                <x-forms.textarea name="description" />
                            </x-forms.field>

                            <x-forms.field label="Type of organization:" name="organization_type">
                                <x-forms.select-input id="organization_type" name="organization_type">
                                    <option value="">Select or enter new organization type</option>
                                    @foreach (($organization_types ?? collect()) as $organizationType)
                                        <option value="{{ $organizationType }}">{{ $organizationType }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Website:" name="website">
                                <x-forms.textarea name="website" />
                            </x-forms.field>

                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-globe text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Geographic Information</h2>
                            </div>

                            <x-forms.field label="Country:" name="organization_country">
                                <x-forms.select-input id="organization_country" name="organization_country">
                                    <option value="">Select a country</option>
                                    @foreach (($countries ?? collect()) as $country)
                                        <option value="{{ $country->name }}">{{ $country->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Region:" name="region">
                                <x-forms.textarea name="region" />
                            </x-forms.field>

                            <x-forms.field label="City:" name="city">
                                <x-forms.textarea name="city" />
                            </x-forms.field>
                            
                            <x-forms.field label="Address:" name="address">
                                <x-forms.textarea name="address" />
                            </x-forms.field>

                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit id="organization_submit_btn" class="organization-submit-btn group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Organization
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>


        @if (session('success'))
            <div id="organizationSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="organizationErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <!-- Selectize scripts -->
        @pushOnce('scripts')
            <script src="/js/create-organizations.js?v={{ filemtime(public_path('js/create-organizations.js')) }}"></script>
        @endPushOnce
    </div>


</div>


