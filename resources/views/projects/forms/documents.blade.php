<form action="{{ route('projects.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="step" value="4">

    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Project Documents</h2>
            <p class="mt-2 text-sm text-gray-600">Add documents to your project below</p>
        </div>

        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center space-x-2">
                <i class="fas fa-file-alt text-blue-500 text-xl"></i>
                <h2 class="text-lg font-semibold text-gray-800">Project Documents</h2>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <div>
                        <p class="text-sm font-medium text-blue-800">Optional Step</p>
                        <p class="text-sm text-blue-600">Project documents are optional. You can skip this step if no
                            documents are available.</p>
                    </div>
                </div>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Validation Errors:</strong>
                    <ul class="mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="documents-container">
                <div class="document-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-forms.field label="Document Title:" name="documents[0][title]">
                            <x-forms.text-input id="documents_0_title" name="documents[0][title]"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.text-input>
                        </x-forms.field>

                        <x-forms.field label="Document Type:" name="documents[0][type]">
                            <input id="documents_0_type" name="documents[0][type]" list="document_type_options"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                            <datalist id="document_type_options">
                                <option value="Project Proposal">
                                <option value="Ethics Approval">
                                <option value="Contract">
                                <option value="Progress Report">
                                <option value="Amendment">
                                <option value="Budget Statement">
                                <option value="Funding Letter">
                                <option value="Collaboration Agreement">
                                <option value="Consent Form">
                                <option value="Final Report">
                                <option value="Presentation">
                                <option value="Publication Draft">
                                <option value="Monitoring Report">
                                <option value="Letter of Support">
                            </datalist>
                        </x-forms.field>

                        <x-forms.field label="Document Date:" name="documents[0][document_date]">
                            <x-forms.date-input id="documents_0_document_date" name="documents[0][document_date]"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.date-input>
                        </x-forms.field>

                        <x-forms.field label="Document File:" name="documents[0][file]">
                            <x-forms.file-input id="documents_0_file" name="documents[0][file]"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.file-input>
                        </x-forms.field>
                    </div>

                    <x-forms.field label="Description:" name="documents[0][description]">
                        <x-forms.textarea id="documents_0_description" name="documents[0][description]" rows="3"
                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        </x-forms.textarea>
                    </x-forms.field>
                </div>
            </div>

            <div class="mt-6">
                <button type="button" onclick="addDocument()"
                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                    <i class="fas fa-plus mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Add Document
                </button>
            </div>
        </div>

        <!-- Action Buttons -->
        <div
            class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-between rounded-b-xl border-t border-gray-200">
            <a href="{{ route('projects.create', ['step' => 3]) }}"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i
                    class="fas fa-arrow-left mr-2 text-lg group-hover:-translate-x-1 transition-transform duration-300"></i>
                Back
            </a>

            <button type="submit"
                class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i
                    class="fas fa-arrow-right mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                Review Project
            </button>
        </div>
    </div>
</form>

<script>
    function removeDocument(button) {
        const container = document.getElementById('documents-container');
        const entry = button.closest('.document-entry');
        if (!container || !entry) {
            return;
        }

        if (container.querySelectorAll('.document-entry').length <= 1) {
            return;
        }

        entry.remove();
    }

    function addDocument() {
        const container = document.getElementById('documents-container');
        const entries = container.getElementsByClassName('document-entry');
        const newIndex = entries.length;

        const template = `
        <div class="document-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200 mt-4">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-700">Additional document</h4>
                <button type="button" onclick="removeDocument(this)"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-500 transition hover:bg-red-50 hover:text-red-700"
                    title="Remove document" aria-label="Remove document">
                    <i class="fas fa-trash-alt text-sm"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-forms.field label="Document Title:" name="documents[${newIndex}][title]">
                    <x-forms.text-input id="documents_${newIndex}_title" name="documents[${newIndex}][title]"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.text-input>
                </x-forms.field>

                <x-forms.field label="Document Type:" name="documents[${newIndex}][type]">
                    <input list="document_type_options" id="documents_${newIndex}_type" name="documents[${newIndex}][type]"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"/>
                    <datalist id="document_type_options">
                        <option value="Project Proposal">
        <option value="Ethics Approval">
        <option value="Contract">
        <option value="Progress Report">
        <option value="Amendment">
        <option value="Budget Statement">
        <option value="Funding Letter">
        <option value="Collaboration Agreement">
        <option value="Consent Form">
        <option value="Final Report">
        <option value="Presentation">
        <option value="Publication Draft">
        <option value="Monitoring Report">
        <option value="Letter of Support">
                    </datalist>
                </x-forms.field>

                <x-forms.field label="Document Date:" name="documents[${newIndex}][document_date]">
                    <x-forms.date-input id="documents_${newIndex}_document_date" name="documents[${newIndex}][document_date]"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.date-input>
                </x-forms.field>

                <x-forms.field label="Document File:" name="documents[${newIndex}][file]">
                    <x-forms.file-input id="documents_${newIndex}_file" name="documents[${newIndex}][file]"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.file-input>
                </x-forms.field>
            </div>

            <x-forms.field label="Description:" name="documents[${newIndex}][description]">
                <x-forms.textarea id="documents_${newIndex}_description" name="documents[${newIndex}][description]" rows="3"
                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                </x-forms.textarea>
            </x-forms.field>
        </div>
    `;

        container.insertAdjacentHTML('beforeend', template);
    }
</script>
