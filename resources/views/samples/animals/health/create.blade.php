<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">

        <!-- Buttons for Create, Edit, and Delete -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/animals/health/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/animals/health/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>
        <form id="animalHealthForm" method="POST" action="/samples/animals/health" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Animal Health Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new animal health assessment
                        </p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-paw text-red-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Animal Information</h2>
                            </div>

                            <!-- Animal ID selection -->
                            <div class="flex items-center space-x-4">
                                <x-forms.table-button id="animals_table_btn"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-lg shadow-md hover:shadow-lg border border-rose-600">
                                    <i
                                        class="fas fa-paw mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                    Select Animals
                                </x-forms.table-button>
                                <span id="animals_count" class="text-sm text-gray-600">(0 selected)</span>
                            </div>
                            <x-forms.field label="Select animals:" name="animal_id">
                                <x-forms.select-input id="animal_id" name="animal_id[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($animals as $animal)
                                        <option value="{{ $animal->id }}">
                                            {{ $animal->code }} - {{ $animal->animal_species->name_common ?? 'Unknown Species' }}
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
                                    <i class="fas fa-heartbeat text-red-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Health Assessment</h2>
                                </div>

                                <!-- Health status input -->
                                <x-forms.field label="Health status:" name="health_status">
                                    <x-forms.select-input id="health_status" name="health_status" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="">Select health status...</option>
                                        <option value="Healthy">Healthy</option>
                                        <option value="Sick">Sick</option>
                                        <option value="Recovering">Recovering</option>
                                        <option value="Under Treatment">Under Treatment</option>
                                        <option value="Critical">Critical</option>
                                        <option value="Stable">Stable</option>
                                    </x-forms.select-input>
                                </x-forms.field>

                                <!-- Check date input -->
                                <x-forms.field label="Check date:" name="check_date">
                                    <x-forms.date-input id="check_date" name="check_date" value="{{ now()->toDateString() }}"
                                        required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.date-input>
                                </x-forms.field>

                                <!-- Check type input -->
                                <x-forms.field label="Check type:" name="check_type">
                                    <x-forms.select-input id="check_type" name="check_type" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="">Select check type...</option>
                                        <option value="Routine">Routine</option>
                                        <option value="Follow-up">Follow-up</option>
                                        <option value="Emergency">Emergency</option>
                                        <option value="Treatment">Treatment</option>
                                        <option value="Pre-release">Pre-release</option>
                                        <option value="Post-treatment">Post-treatment</option>
                                    </x-forms.select-input>
                                </x-forms.field>

                                <!-- Alive status input -->
                                <x-forms.field label="Animal status:" name="alive">
                                    <x-forms.select-input id="alive" name="alive" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="1">Alive</option>
                                        <option value="0">Deceased</option>
                                    </x-forms.select-input>
                                </x-forms.field>
                            </div>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-stethoscope text-red-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Clinical Assessment</h2>
                            </div>

                            <!-- Clinical signs input (multiple selection) -->
                            <x-forms.field label="Clinical signs:" name="clinical_signs">
                                <x-forms.select-input id="clinical_signs" name="clinical_signs[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($clinical_signs as $sign)
                                        <option value="{{ $sign->name }}">
                                            {{ $sign->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Lesions input (multiple selection) -->
                            <x-forms.field label="Lesions:" name="lesions">
                                <x-forms.select-input id="lesions" name="lesions[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($lesions as $lesion)
                                        <option value="{{ $lesion->name }}">
                                            {{ $lesion->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Additional notes input -->
                            <x-forms.field label="Additional notes:" name="notes">
                                <x-forms.textarea id="notes" name="notes"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Enter any additional notes about the health assessment..."></x-forms.textarea>
                            </x-forms.field>
                        </div>
                    </div>
                </div>

                <div
                    class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <button type="submit" id="submitBtn"
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-red-600">
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
                    // Initialize Selectize for clinical signs
                    $('#clinical_signs').selectize({
                        delimiter: ',',
                        persist: false,
                        create: function(input) {
                            return {
                                value: input,
                                text: input
                            }
                        }
                    });

                    // Initialize Selectize for lesions
                    $('#lesions').selectize({
                        delimiter: ',',
                        persist: false,
                        create: function(input) {
                            return {
                                value: input,
                                text: input
                            }
                        }
                    });

                    // Initialize Selectize for health status
                    $('#health_status').selectize({
                        create: true,
                        sortField: 'text'
                    });

                    // Initialize Selectize for check type
                    $('#check_type').selectize({
                        create: true,
                        sortField: 'text'
                    });

                    // Handle form submission with AJAX and SweetAlert
                    $('#animalHealthForm').on('submit', function(e) {
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