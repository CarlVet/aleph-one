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

        <x-forms.form method="POST" action="/pathogens" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Pathogen Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new pathogen</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-bug text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Basic Information</h2>
                            </div>

                            <x-forms.field label="NCBI Taxonomy ID:" name="ncbi_tax_id">
                                <x-forms.numeric-input id="ncbi_tax_id" name="ncbi_tax_id" type="number" min="1"
                                    placeholder="e.g., 234 for Brucella abortus"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.numeric-input>
                            </x-forms.field>

                            <x-forms.field label="Species (full scientific name):" name="pathogen_species">
                                <x-forms.text-input id="pathogen_species" name="pathogen_species" type="text" required
                                    placeholder="e.g., Brucella abortus"
                                    class="pathogen-species-input w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                                <div id="pathogen_species_status" class="pathogen-species-status mt-1 text-sm hidden"></div>
                            </x-forms.field>

                            <x-forms.field label="Genus:" name="pathogen_genus">
                                <x-forms.text-input id="pathogen_genus" name="pathogen_genus" type="text" required
                                    placeholder="e.g., Brucella"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                            </x-forms.field>

                            <x-forms.field label="Family:" name="pathogen_family">
                                <x-forms.text-input id="pathogen_family" name="pathogen_family" type="text" required
                                    placeholder="e.g., Brucellaceae"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                            </x-forms.field>

                            <x-forms.field label="Order:" name="pathogen_order">
                                <x-forms.text-input id="pathogen_order" name="pathogen_order" type="text" required
                                    placeholder="e.g., Rhizobiales"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                            </x-forms.field>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-sitemap text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Taxonomic Classification</h2>
                            </div>

                            <x-forms.field label="Class:" name="pathogen_class">
                                <x-forms.text-input id="pathogen_class" name="pathogen_class" type="text" required
                                    placeholder="e.g., Alphaproteobacteria"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                            </x-forms.field>

                            <x-forms.field label="Phylum:" name="pathogen_phylum">
                                <x-forms.text-input id="pathogen_phylum" name="pathogen_phylum" type="text" required
                                    placeholder="e.g., Proteobacteria"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                            </x-forms.field>

                            <x-forms.field label="Kingdom:" name="pathogen_kingdom">
                                <x-forms.text-input id="pathogen_kingdom" name="pathogen_kingdom" type="text" required
                                    placeholder="e.g., Bacteria"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                            </x-forms.field>

                            <x-forms.field label="Domain:" name="pathogen_domain">
                                <x-forms.text-input id="pathogen_domain" name="pathogen_domain" type="text" required
                                    placeholder="e.g., Bacteria, Riboviria, Eukaryota"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.text-input>
                            </x-forms.field>

                            <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <div class="flex items-start">
                                    <i class="fas fa-lightbulb text-blue-500 mt-1 mr-3"></i>
                                    <div>
                                        <h3 class="text-sm font-medium text-blue-800 mb-1">Taxonomic Guidelines</h3>
                                        <p class="text-sm text-blue-700">
                                            Please use the complete scientific name for the species and ensure all taxonomic 
                                            classifications are accurate according to current NCBI taxonomy standards. 
                                            The NCBI Taxonomy ID helps verify the classification.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit id="pathogen_submit_btn" class="pathogen-submit-btn group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Pathogen
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        @if (session('success'))
            <div id="pathogenSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="pathogenErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            var familiesList = @json($pathogens->unique('family'));
        </script>

        <!-- Custom scripts -->
        <script src="/js/create-pathogens.js"></script>
    </div>
</div>


