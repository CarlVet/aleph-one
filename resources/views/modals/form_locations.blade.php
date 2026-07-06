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

        <x-forms.form method="POST" action="/locations" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Storage Locations Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new location</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-warehouse text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Storage Location Information</h2>
                            </div>

                            <x-forms.field label="Name of storage location:" name="location_name">
                                <x-forms.text-input id="location_name" name="location_name" />
                                <div id="location_name_status" class="mt-1 text-sm hidden"></div>
                            </x-forms.field>


                            <x-forms.field label="Type of storage location:" name="location_type">
                                <x-forms.select-input name="location_type">
                                    <option value="">Select or enter new location type</option>
                                    @foreach ($location_types as $key => $value)
                                        <option value="{{ $key }}">{{ $value }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Laboratory input -->
                            <x-forms.field label="Laboratory where performed:" name="lab" class="mt-4">
                                <x-forms.select-input id="lab" name="lab"
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
                            </x-forms.field>

                            <button id="location_lab_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i
                                    class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Laboratory
                            </button>

                            <x-forms.field label="Room:" name="room">
                                <x-forms.text-input name="room" />
                            </x-forms.field>

                            <x-forms.field label="Barcode:" name="barcode">
                                <x-forms.text-input name="barcode" />
                            </x-forms.field>
                            

                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Instructions</h2>
                            </div>

                            <div class="space-y-4 text-sm text-gray-600">
                                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                                    <h3 class="font-semibold text-blue-800 mb-2">Required Fields</h3>
                                    <ul class="space-y-1 text-blue-700">
                                        <li>• <strong>Name:</strong> Enter a descriptive name for the storage location</li>
                                        <li>• <strong>Type:</strong> Select the type of storage equipment or area</li>
                                        <li>• <strong>Laboratory:</strong> Choose the laboratory where this location is situated</li>
                                    </ul>
                                </div>

                                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-400">
                                    <h3 class="font-semibold text-green-800 mb-2">Optional Fields</h3>
                                    <ul class="space-y-1 text-green-700">
                                        <li>• <strong>Room:</strong> Specify the room number or name if applicable</li>
                                        <li>• <strong>Barcode:</strong> Add a barcode identifier for easy tracking</li>
                                    </ul>
                                </div>

                                <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-400">
                                    <h3 class="font-semibold text-yellow-800 mb-2">Tips</h3>
                                    <ul class="space-y-1 text-yellow-700">
                                        <li>• Use clear, descriptive names that others can easily understand</li>
                                        <li>• Include specific details like "Freezer A" or "Shelf 3"</li>
                                        <li>• Barcodes help with inventory management and tracking</li>
                                        <li>• You can add new location types by typing in the dropdown</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit id="location_submit_btn" class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Sampling Site
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <x-table-modal id="location_lab_form_modal" title="Laboratory Registration Form"
            closeButtonId="location_lab_form_close_btn">
            @include('modals.form_laboratories')
        </x-table-modal>

        @if (session('success'))
            <div id="pathogenSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="pathogenErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="/js/create-locations.js"></script>
        @endpush
    </div>


</div>


