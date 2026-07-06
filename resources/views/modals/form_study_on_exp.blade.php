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

        <x-forms.form method="POST" action="/studies" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    @php($studyErrorFields = ['study_doi', 'study_ref', 'study_title', 'study_abstract', 'study_year', 'study_design', 'study_pdf'])
                    @if (collect($studyErrorFields)->contains(fn ($field) => $errors->has($field)))
                        <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <div class="font-semibold">Study registration could not be saved.</div>
                            <ul class="mt-2 list-disc pl-5">
                                @foreach ($studyErrorFields as $field)
                                    @error($field)
                                        <li>{{ $message }}</li>
                                    @enderror
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Study Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new study</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-book text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Study Information</h2>
                            </div>

                            <x-forms.field label="Enter DOI:" name="study_doi">
                                <x-forms.textarea id="study_exp_doi" name="study_doi" rows="1" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="e.g., 10.1000/182">{{ old('study_doi') }}</x-forms.textarea>
                            </x-forms.field>

                            <x-forms.field label="Choose a reference key:" name="study_ref">
                                <x-forms.textarea id="study_exp_ref" name="study_ref" rows="1" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="e.g., Smith2023">{{ old('study_ref') }}</x-forms.textarea>
                            </x-forms.field>

                            <x-forms.field label="Title:" name="study_title">
                                <x-forms.textarea id="study_exp_title" name="study_title" rows="2" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Enter the full title of the study">{{ old('study_title') }}</x-forms.textarea>
                            </x-forms.field>

                            <x-forms.field label="Abstract:" name="study_abstract">
                                <x-forms.textarea id="study_exp_abstract" name="study_abstract" rows="4"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Enter the study abstract">{{ old('study_abstract') }}</x-forms.textarea>
                            </x-forms.field>

                            <x-forms.field label="Publication year:" name="study_year">
                                <x-forms.numeric-input id="study_exp_year" name="study_year" min="1800"
                                    max="2200" step="1" value="{{ old('study_year', now()->year) }}" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.numeric-input>
                            </x-forms.field>

                            <x-forms.field label="Study design:" name="study_design">
                                <x-forms.select-input id="study_exp_design" name="study_design" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Cross-sectional study" @selected(old('study_design') === 'Cross-sectional study')>Cross-sectional study</option>
                                    <option value="Longitudinal study" @selected(old('study_design') === 'Longitudinal study')>Longitudinal study</option>
                                    <option value="Survey" @selected(old('study_design') === 'Survey')>Survey</option>
                                    <option value="Case-control study" @selected(old('study_design') === 'Case-control study')>Case-control study</option>
                                    <option value="Diagnostic development study" @selected(old('study_design') === 'Diagnostic development study')>Diagnostic development study</option>
                                    <option value="Experimental study" @selected(old('study_design') === 'Experimental study')>Experimental study</option>
                                    <option value="Miscellaneous" @selected(old('study_design') === 'Miscellaneous')>Miscellaneous</option>
                                </x-forms.select-input>
                            </x-forms.field>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-file-pdf text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Study Document</h2>
                            </div>
                            
                            <div class="mt-4">
                                <label for="study_exp_pdf" class="block text-sm font-medium text-gray-700 mb-2">Upload PDF Document</label>
                                <input type="file" id="study_exp_pdf" name="study_pdf" accept=".pdf" 
                                       class="mt-1 block w-full text-sm text-gray-500
                                              file:mr-4 file:py-2 file:px-4
                                              file:rounded-full file:border-0
                                              file:text-sm file:font-semibold
                                              file:bg-blue-50 file:text-blue-700
                                              hover:file:bg-blue-100
                                              border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </div>

                            <div id="study_exp_preview" class="mt-4 hidden">
                                <h3 class="text-sm font-medium text-gray-700 mb-2">PDF Preview</h3>
                                <div class="border rounded-lg p-4 bg-gray-50">
                                    <iframe id="study_exp_viewer" class="w-full h-96" frameborder="0"></iframe>
                                </div>
                            </div>

                            <div class="p-4 bg-green-50 rounded-lg border border-green-200">
                                <div class="flex items-start">
                                    <i class="fas fa-magic text-green-500 mt-1 mr-3"></i>
                                    <div>
                                        <h3 class="text-sm font-medium text-green-800 mb-1">Auto-fill Feature</h3>
                                        <p class="text-sm text-green-700">
                                            Enter a valid DOI and the form will automatically populate with publication details 
                                            from CrossRef. This saves time and ensures accuracy.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Study
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        @if (session('success'))
            <div id="studySuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="studyErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            // Pass the PHP arrays to JavaScript
            var studiesList = @json($studies->unique('study_design')->pluck('study_design'));

            // PDF Preview functionality
            document.getElementById('study_exp_pdf').addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type === 'application/pdf') {
                    const url = URL.createObjectURL(file);
                    document.getElementById('study_exp_viewer').src = url;
                    document.getElementById('study_exp_preview').classList.remove('hidden');
                }
            });

            // DOI-based auto-fill functionality
            document.getElementById('study_exp_doi').addEventListener('blur', async function(e) {
                const doi = e.target.value.trim();
                if (!doi) return;

                try {
                    const response = await fetch(`https://api.crossref.org/works/${doi}`);
                    if (!response.ok) throw new Error('DOI not found');
                    
                    const data = await response.json();
                    const work = data.message;

                    // Fill in the form fields
                    document.getElementById('study_exp_title').value = work.title?.[0] || '';
                    document.getElementById('study_exp_abstract').value = work.abstract || '';
                    document.getElementById('study_exp_year').value = work.published?.['date-parts']?.[0]?.[0] || '';
                    
                    // Generate reference key from first author's last name and year
                    const firstAuthor = work.author?.[0];
                    if (firstAuthor) {
                        const lastName = firstAuthor.family || '';
                        const year = work.published?.['date-parts']?.[0]?.[0] || '';
                        document.getElementById('study_exp_ref').value = `${lastName}${year}`;
                    }
                } catch (error) {
                    console.error('Error fetching DOI data:', error);
                    // Optionally show an error message to the user
                }
            });
        </script>
    </div>
</div>
