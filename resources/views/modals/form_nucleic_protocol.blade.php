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

        <x-forms.form method="POST" action="/nucleic_protocols" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Nucleic Acid Extraction Protocol - Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new nucleic acid extraction protocol</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-flask text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">General Protocol Information</h2>
                            </div>

                            <x-forms.field label="Enter protocol name in full (e.g. PureLink™ Genomic DNA Mini Kit):"
                                name="protocol_name">
                                <x-forms.textarea id="protocol_name" name="protocol_name"
                                    rows="1" class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.textarea>
                            </x-forms.field>

                            <x-forms.field label="Select technique type:" name="protocol_new">
                                <x-forms.select-input id="protocol_new" name="protocol_new" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($techniques as $technique)
                                        <option value="{{ $technique->name }}">
                                            {{ $technique->name }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>


                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-file-alt text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Protocol Document</h2>
                            </div>
                            
                            <div class="mt-4">
                                <label for="protocol_pdf" class="block text-sm font-medium text-gray-700 mb-2">Upload Protocol Document</label>
                                <input type="file" id="protocol_pdf" name="protocol_pdf" accept=".pdf,.doc,.docx" 
                                       class="mt-1 block w-full text-sm text-gray-500
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-full file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-blue-50 file:text-blue-700
                                              hover:file:bg-blue-100
                                              border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </div>

                            <div id="file_preview" class="mt-4 hidden">
                                <div>
                                    <div id="protocol_pdf_preview" class="mt-4 hidden">
                                        <h3 class="text-sm font-medium text-gray-700 mb-2">PDF Preview</h3>
                                        <div class="border rounded-lg p-4 bg-gray-50">
                                            <iframe id="protocol_pdf_viewer" class="w-full h-[600px]" frameborder="0"></iframe>
                                        </div>
                                    </div>
                                    <div id="word_preview_container" class="hidden">
                                        <div class="flex items-center justify-center p-4 bg-white rounded-lg shadow">
                                            <div class="text-center">
                                                <i class="fas fa-file-word text-6xl text-blue-600 mb-4"></i>
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">Word Document</h3>
                                                <p class="text-sm text-gray-600 mb-4">Word documents cannot be previewed due to browser restrictions.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Protocol
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>


        @if (session('success'))
            <div id="protocolSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="protocolErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            // Pass the PHP arrays to JavaScript
            window.protocolsList = @json($protocols);
            window.techniquesList = @json($techniques);
            // Backwards compatible globals for older scripts.
            var protocolsList = window.protocolsList;
            var techniquesList = window.techniquesList;

            // PDF Preview functionality
            document.getElementById('protocol_pdf').addEventListener('change', function(e) {
                const file = e.target.files[0];
                const previewContainer = document.getElementById('file_preview');
                const wordContainer = document.getElementById('word_preview_container');
                
                if (file && file.type === 'application/pdf') {
                    const url = URL.createObjectURL(file);
                    document.getElementById('file_preview').classList.remove('hidden');
                    document.getElementById('protocol_pdf_viewer').src = url;
                    document.getElementById('protocol_pdf_preview').classList.remove('hidden');
                } else if (file.type === 'application/msword' || 
                           file.type === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                    document.getElementById('file_preview').classList.remove('hidden');
                    document.getElementById('word_preview_container').classList.remove('hidden');
                }
            });
        </script>

        <!-- Custom scripts -->
        <script src="/js/create-protocols.js"></script>
    </div>
</div>
