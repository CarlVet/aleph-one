<div>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Selectize -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.3/js/standalone/selectize.min.js"></script>
        <link rel="stylesheet"
            href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.3/css/selectize.default.min.css">
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

        <x-forms.form method="POST" action="/pathogens_protocols" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Protocol-Pathogen Association Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Associate protocols with their target pathogens</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-link text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Association Selection</h2>
                            </div>

                            <!-- Input for protocol -->
                            <x-forms.field label="Protocol:" name="protocol_ass">
                                <x-forms.select-input id="protocol_ass" name="protocol_ass" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($exp_protocols as $protocol)
                                        <option value="{{ $protocol->name }}" @selected(($default_protocol_ass ?? '') === $protocol->name)>
                                            {{ $protocol->name }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Input for pathogen -->
                            <x-forms.field label="Pathogens targeted:" name="pathogen_ass[]">
                                <x-forms.select-input id="pathogen_ass" name="pathogen_ass[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($pathogens as $pathogen)
                                        <option value="{{ $pathogen->id }}">
                                            {{ $pathogen->species }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <button id="pathogen_ass_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Pathogen
                            </button>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-list text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Associated Pathogens</h2>
                            </div>

                            <div id="associated-pathogens-display" class="mt-2 text-sm text-gray-700 p-4 bg-gray-50 rounded-lg border border-gray-200">
                                <!-- Pathogens will be shown here -->
                                <span class="text-gray-500 italic">Select a protocol to view associated pathogens.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Association
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <!-- Pathogen Form Modal -->
        <x-table-modal id="pathogen_ass_modal" title="Pathogen Registration Form" closeButtonId="pathogen_ass_close_btn">
            @include('modals.form_pathogen')
        </x-table-modal>

        @if (session('success'))
            <div id="pathogenProtocolSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="pathogenProtocolErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            var protocolsList = @json($exp_protocols);
            var pathogensList = @json($pathogens);
            var protocolPathogenMap = @json($protocol_pathogen_map);
        </script>

        <!-- Custom scripts -->
        <script src="/js/create-pathogens-protocols.js"></script>
    </div>
</div>
