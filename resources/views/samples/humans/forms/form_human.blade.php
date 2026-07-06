<div class="mt-2 md:col-span-2 md:mt-0">
        <x-forms.form method="POST" action="/humans" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Patient Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new patient</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Personal Information -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-user text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Personal Information</h2>
                            </div>

                            <x-forms.field label="First Name:" name="first_name" required>
                                <x-forms.text-input id="first_name" name="first_name" required />
                            </x-forms.field>

                            <x-forms.field label="Last Name:" name="last_name" required>
                                <x-forms.text-input id="last_name" name="last_name" required />
                                <div id="patient_name_status" class="mt-1 text-sm hidden"></div>
                            </x-forms.field>

                            <x-forms.field label="Field label (optional):" name="field_label">
                                <x-forms.text-input id="field_label" name="field_label"
                                    placeholder="Optional (leave empty to keep empty)" />
                            </x-forms.field>

                            <x-forms.field label="Sex:" name="sex">
                                <x-forms.select-input id="sex" name="sex">
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Date of Birth:" name="date_of_birth">
                                <x-forms.date-input id="date_of_birth" name="date_of_birth" />
                            </x-forms.field>

                            <x-forms.field label="Ethnicity:" name="ethnicity">
                                <x-forms.select-input name="ethnicity" id="ethnicity">
                                    @foreach ($ethnicities as $ethnicity)
                                        <option value="{{ $ethnicity }}">{{ $ethnicity }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Occupation:" name="occupation">
                                <x-forms.select-input name="occupation" id="occupation">
                                    @foreach ($occupations as $occupation)
                                        <option value="{{ $occupation }}">{{ $occupation }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Marital Status:" name="marital_status">
                                <x-forms.select-input id="marital_status" name="marital_status">
                                    <option value="Single">Single</option>
                                    <option value="Married">Married</option>
                                    <option value="Divorced">Divorced</option>
                                    <option value="Widowed">Widowed</option>
                                    <option value="Not disclosed">Not disclosed</option>
                                </x-forms.select-input>
                            </x-forms.field>
                        </div>

                        <!-- Contact Information -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-address-card text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Contact Information</h2>
                            </div>

                            <x-forms.field label="Country:" name="human_country">
                                <x-forms.select-input name="human_country" id="human_country">
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->name }}">{{ $country->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="City:" name="city">
                                <x-forms.text-input id="city" name="city" />
                            </x-forms.field>

                            <x-forms.field label="Province:" name="province">
                                <x-forms.text-input id="province" name="province" />
                            </x-forms.field>

                            <x-forms.field label="Street:" name="street">
                                <x-forms.text-input id="street" name="street" />
                            </x-forms.field>

                            <x-forms.field label="Postal Code:" name="postal_code">
                                <x-forms.text-input id="postal_code" name="postal_code" />
                            </x-forms.field>

                            <x-forms.field label="Preferred Contact Method:" name="preferred_contact_method">
                                <x-forms.select-input id="preferred_contact_method" name="preferred_contact_method">
                                    <option value="phone">Phone</option>
                                    <option value="email">Email</option>
                                    <option value="sms">SMS</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Phone:" name="phone">
                                <x-forms.text-input id="phone" name="phone" />
                            </x-forms.field>

                            <x-forms.field label="Alternate Phone:" name="alternate_phone">
                                <x-forms.text-input id="alternate_phone" name="alternate_phone" />
                            </x-forms.field>

                            <x-forms.field label="Email:" name="email">
                                <x-forms.email-input id="email" name="email" />
                            </x-forms.field>

                            <x-forms.field label="Alternate Email:" name="alternate_email">
                                <x-forms.email-input id="alternate_email" name="alternate_email" />
                            </x-forms.field>
                        </div>

                        <!-- Insurance Information -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-shield-alt text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Insurance Information</h2>
                            </div>

                            <x-forms.field label="Insurance Provider:" name="insurance_provider">
                                <x-forms.text-input id="insurance_provider" name="insurance_provider" />
                            </x-forms.field>

                            <x-forms.field label="Insurance ID:" name="insurance_id">
                                <x-forms.text-input id="insurance_id" name="insurance_id" />
                            </x-forms.field>
                        </div>

                        <!-- Photo Upload -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-camera text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Photo</h2>
                            </div>

                            <x-forms.field label="Upload Photo:" name="photo">
                                <x-forms.photo-upload id="human_photo" name="photo" label="Upload file" />
                            </x-forms.field>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit id="human_submit_btn"
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Register Human
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        @if (session('success'))
            <div id="humanSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="humanErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <!-- Required Scripts -->
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

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="/js/create-humans.js"></script>
        @endpush
    </div>