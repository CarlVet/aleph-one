<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">

        <!-- Success/Error Messages -->
        @if (session('success'))
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
        @endif

        <!-- Buttons for Create, Edit, and Delete -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/animals/medication/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/animals/medication/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>
        <form id="animalMedicationForm" method="POST" action="/samples/animals/medication" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Animal Medication Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new animal medication record
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-paw text-purple-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Animal Information</h2>
                            </div>

                            <!-- Animal ID selection for existing animals -->
                            <div class="flex items-center space-x-4">
                                <x-forms.table-button id="animals_table_btn"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg shadow-md hover:shadow-lg border border-rose-600">
                                    <i
                                        class="fas fa-paw mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                    Select Animal
                                </x-forms.table-button>
                                <span id="animals_count" class="text-sm text-gray-600">(0 selected)</span>
                            </div>
                            <x-forms.field label="Select animals:" name="animal_id">
                                <x-forms.select-input id="animal_id" name="animal_id[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($animals as $animal)
                                        <option value="{{ $animal->id }}">
                                            {{ $animal->code }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <div class="pt-4 border-t border-gray-100">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-prescription-bottle text-purple-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Medication Information</h2>
                                </div>

                                <!-- Medication name input -->
                                <x-forms.field label="Medication name:" name="medication_name">
                                    <x-forms.select-input id="medication_name" name="medication_name[]" multiple required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($medications_existing as $medication)
                                            <option value="{{ $medication }}">{{ $medication }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>

                                <!-- Dosage input -->
                                <x-forms.field label="Dosage:" name="dosage">
                                    <x-forms.text-input id="dosage" name="dosage"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="e.g., 10mg/kg twice daily"></x-forms.text-input>
                                </x-forms.field>

                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-calendar-alt text-purple-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Treatment Schedule</h2>
                            </div>

                            <!-- Start date input -->
                            <x-forms.field label="Start date:" name="start_date">
                                <x-forms.date-input id="start_date" name="start_date" value="{{ now()->toDateString() }}"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.date-input>
                            </x-forms.field>

                            <!-- End date input -->
                            <x-forms.field label="End date:" name="end_date">
                                <x-forms.date-input id="end_date" name="end_date"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.date-input>
                            </x-forms.field>

                            <!-- Prescribed by input -->
                            <x-forms.field label="Prescribed by:" name="prescribed_by">
                                <x-forms.select-input id="prescribed_by" name="prescribed_by"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select prescriber...</option>
                                    @foreach ($people as $person)
                                        <option value="{{ $person->id }}">
                                            {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Additional notes input -->
                            <x-forms.field label="Additional notes:" name="notes">
                                <x-forms.textarea id="notes" name="notes"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Enter any additional notes about the medication..."></x-forms.textarea>
                            </x-forms.field>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <button type="submit" id="submitBtn"
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-purple-500 to-purple-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-purple-600">
                        <i
                            class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save
                    </button>
                </div>
            </div>
        </form>

        <x-table-modal id="animals_table_modal" title="Animals" closeButtonId="animals_table_close_btn">
            @include('samples.animals.modals.animals_selection')
        </x-table-modal>

        <x-table-modal id="animals_form_modal" title="Animals Registration Form"
            closeButtonId="animals_form_close_btn">
            @include('samples.animals.forms.form_animals')
        </x-table-modal>

        <!-- Selectize scripts -->
        @push('scripts')
            <style>
                .selectize-control.multi .item .remove {
                    display: none !important;
                }
            </style>
            <script src="{{ asset('js/create-animal-samples.js') }}"></script>
            <script>
                $(document).ready(function() {
                    // Initialize Selectize for medication name
                    $('#medication_name').selectize({
                        create: true,
                        sortField: 'text',
                        maxItems: null,
                        persist: false,
                        dropdownParent: 'body'
                    });

                    // Initialize Selectize for dosage
                    $('#dosage').selectize({
                        create: true,
                        sortField: 'text',
                        options: [
                            {text: '10mg/kg twice daily', value: '10mg/kg twice daily'},
                            {text: '5mg/kg once daily', value: '5mg/kg once daily'},
                            {text: '2mg/kg every 8 hours', value: '2mg/kg every 8 hours'},
                            {text: '1mg/kg every 12 hours', value: '1mg/kg every 12 hours'},
                            {text: '0.5mg/kg once daily', value: '0.5mg/kg once daily'},
                            {text: '15mg/kg once daily', value: '15mg/kg once daily'},
                            {text: '2mg/kg every 6 hours', value: '2mg/kg every 6 hours'}
                        ]
                    });

                    // Handle form submission with AJAX and SweetAlert
                    $('#animalMedicationForm').on('submit', function(e) {
                        e.preventDefault();
                        
                        const submitBtn = $('#submitBtn');
                        const originalText = submitBtn.html();
                        
                        // Show loading state
                        submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Saving...');
                        submitBtn.prop('disabled', true);

                        $.ajax({
                            url: $(this).attr('action'),
                            method: 'POST',
                            data: $(this).serialize(),
                            headers: {
                                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success!',
                                        text: response.message,
                                        confirmButtonColor: '#10B981',
                                        confirmButtonText: 'OK'
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: response.message,
                                        confirmButtonColor: '#EF4444'
                                    });
                                }
                            },
                            error: function(xhr) {
                                let errorMessage = 'An error occurred while saving the data.';
                                
                                if (xhr.responseJSON) {
                                    if (xhr.responseJSON.message) {
                                        errorMessage = xhr.responseJSON.message;
                                    }
                                    if (xhr.responseJSON.errors) {
                                        const errors = Object.values(xhr.responseJSON.errors).flat();
                                        errorMessage = errors.join('\n');
                                    }
                                }
                                
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: errorMessage,
                                    confirmButtonColor: '#EF4444'
                                });
                            },
                            complete: function() {
                                // Reset button state
                                submitBtn.html(originalText);
                                submitBtn.prop('disabled', false);
                            }
                        });
                    });

                    // Animal form modal functionality
                    $('#animals_form_btn').click(function() {
                        $('#animals_form_modal').removeClass('hidden');
                    });

                    $('#animals_form_close_btn').click(function() {
                        $('#animals_form_modal').addClass('hidden');
                    });
                });
            </script>
        @endpush
    </div>
</x-layout> 