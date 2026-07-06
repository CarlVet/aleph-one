<form method="POST" action="{{ route('projects.update', ['project' => $project->id, 'section' => 'documents']) }}"
    class="space-y-6" enctype="multipart/form-data" id="documents-form">
    @csrf
    @method('PATCH')
    <input type="hidden" name="section" value="documents">

    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Documents</h2>
            <p class="mt-2 text-sm text-gray-600">Update the project documents below</p>
        </div>

        <!-- Save Reminder Message -->
        <div id="save-reminder" class="hidden bg-yellow-100 text-yellow-800 p-4 rounded-lg mb-4 text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span id="save-reminder-text"></span>
        </div>

        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center space-x-2">
                <i class="fas fa-file-alt text-blue-500 text-xl"></i>
                <h2 class="text-lg font-semibold text-gray-800">Project Documents</h2>
            </div>

            <div id="documents-container">
                @foreach ($project->documents as $index => $document)
                    <div class="document-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200 {{ $index > 0 ? 'mt-4' : '' }}">
                        <div class="flex justify-between items-center">
                            <h3 class="text-sm font-medium text-gray-700">Document #{{ $index + 1 }}</h3>
                            <button type="button" onclick="markDocumentForRemoval(this, {{ $document->id }})"
                                class="text-red-500 hover:text-red-700 transition-colors duration-200">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                        <input type="hidden" name="documents[{{ $index }}][id]" value="{{ $document->id }}">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label for="documents_{{ $index }}_title" class="block text-sm font-medium text-gray-700">Document Title:</label>
                                <input type="text" id="documents_{{ $index }}_title"
                                    name="documents[{{ $index }}][title]" required value="{{ $document->title }}"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </div>

                            <div>
                                <label for="documents_{{ $index }}_type" class="block text-sm font-medium text-gray-700">Document Type:</label>
                                <select id="documents_{{ $index }}_type"
                                    name="documents[{{ $index }}][type]" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Project Proposal"
                                        {{ $document->type === 'Project Proposal' ? 'selected' : '' }}>Project Proposal
                                    </option>
                                    <option value="Ethics Approval"
                                        {{ $document->type === 'Ethics Approval' ? 'selected' : '' }}>Ethics Approval
                                    </option>
                                    <option value="Amendment" {{ $document->type === 'Amendment' ? 'selected' : '' }}>
                                        Amendment</option>
                                    <option value="Progress Report"
                                        {{ $document->type === 'Progress Report' ? 'selected' : '' }}>Progress Report
                                    </option>
                                    <option value="Final Report"
                                        {{ $document->type === 'Final Report' ? 'selected' : '' }}>Final Report
                                    </option>
                                    <option value="Publication"
                                        {{ $document->type === 'Publication' ? 'selected' : '' }}>Publication</option>
                                    <option value="Presentation"
                                        {{ $document->type === 'Presentation' ? 'selected' : '' }}>Presentation
                                    </option>
                                </select>
                            </div>

                            <div>
                                <label for="documents_{{ $index }}_document_date" class="block text-sm font-medium text-gray-700">Document Date:</label>
                                <input type="date" id="documents_{{ $index }}_document_date"
                                    name="documents[{{ $index }}][document_date]" required
                                    value="{{ $document->document_date }}"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </div>

                            <div>
                                <label for="documents_{{ $index }}_description" class="block text-sm font-medium text-gray-700">Description:</label>
                                <textarea id="documents_{{ $index }}_description"
                                    name="documents[{{ $index }}][description]" rows="2"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">{{ $document->description }}</textarea>
                            </div>

                            <div class="md:col-span-2">
                                <label for="documents_{{ $index }}_file" class="block text-sm font-medium text-gray-700">File:</label>
                                <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg dropzone" 
                                    data-dropzone-id="documents_{{ $index }}_file">
                                    <div class="space-y-1 text-center">
                                        @if ($document->file_path)
                                            <div class="mb-2">
                                                <a href="{{ Storage::url($document->file_path) }}" target="_blank"
                                                    class="text-blue-600 hover:text-blue-800">
                                                    <i class="fas fa-file-alt mr-2"></i>Current file:
                                                    {{ $document->file_name }}
                                                </a>
                                            </div>
                                        @endif
                                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor"
                                            fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                            <path
                                                d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <div class="flex text-sm text-gray-600">
                                            <label for="documents_{{ $index }}_file"
                                                class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                                <span>Upload a file</span>
                                                <input id="documents_{{ $index }}_file"
                                                    name="documents[{{ $index }}][file]" type="file"
                                                    class="sr-only file-upload" accept=".pdf,.doc,.docx">
                                            </label>
                                            <p class="pl-1">or drag and drop</p>
                                        </div>
                                        <p class="text-xs text-gray-500">PDF, DOC, or DOCX up to 55MB</p>
                                        <!-- Progress bar container -->
                                        <div class="upload-progress hidden mt-2">
                                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                                <div class="bg-blue-600 h-2.5 rounded-full progress-bar" style="width: 0%"></div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1 progress-text">0%</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <button type="button" onclick="addDocument()"
                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                Add Document
            </button>
        </div>

        <!-- Navigation Buttons -->
        <div
            class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-between rounded-b-xl border-t border-gray-200">
            <div class="flex space-x-4">
            </div>

            <button type="submit"
                class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                Save Changes
            </button>
        </div>
        </div>
</form>

<script>
    function markDocumentForRemoval(button, documentId) {
        const documentEntry = button.closest('.document-entry');
        documentEntry.style.display = 'none';

        // Add hidden input for document removal
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'removed_documents[]';
        hiddenInput.value = documentId;
        documentEntry.appendChild(hiddenInput);

        // Show informational message
        showSaveReminder('Document marked for removal. Remember to save changes.');
    }

    function showSaveReminder(message) {
        const reminder = document.getElementById('save-reminder');
        const reminderText = document.getElementById('save-reminder-text');
        if (reminder && reminderText) {
            reminderText.textContent = message;
            reminder.classList.remove('hidden');
        }
    }

    function handleFileUploadProgress() {
        const form = document.getElementById('documents-form');
        const fileInputs = document.querySelectorAll('.file-upload');
        const dropzones = document.querySelectorAll('.dropzone');
        
        // Handle drag and drop
        dropzones.forEach(dropzone => {
            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, preventDefaults, false);
            });

            function preventDefaults(e) {
                e.preventDefault();
                e.stopPropagation();
            }

            ['dragenter', 'dragover'].forEach(eventName => {
                dropzone.addEventListener(eventName, highlight, false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropzone.addEventListener(eventName, unhighlight, false);
            });

            function highlight(e) {
                dropzone.classList.add('border-blue-500', 'bg-blue-50');
            }

            function unhighlight(e) {
                dropzone.classList.remove('border-blue-500', 'bg-blue-50');
            }

            dropzone.addEventListener('drop', handleDrop, false);

            function handleDrop(e) {
                const dt = e.dataTransfer;
                const files = dt.files;
                const fileInput = dropzone.querySelector('.file-upload');
                
                if (files.length > 0) {
                    fileInput.files = files;
                    // Trigger change event to update progress bar
                    const event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                }
            }
        });
        
        fileInputs.forEach(input => {
            input.addEventListener('change', function() {
                const progressContainer = this.closest('.space-y-1').querySelector('.upload-progress');
                const progressBar = progressContainer.querySelector('.progress-bar');
                const progressText = progressContainer.querySelector('.progress-text');
                
                progressContainer.classList.remove('hidden');
                
                // Simulate progress for visual feedback
                let progress = 0;
                const interval = setInterval(() => {
                    progress += 5;
                    if (progress > 90) {
                        clearInterval(interval);
                    }
                    progressBar.style.width = `${progress}%`;
                    progressText.textContent = `${progress}%`;
                }, 100);
                
                // Reset progress when form is submitted
                form.addEventListener('submit', () => {
                    progressBar.style.width = '100%';
                    progressText.textContent = '100%';
                });
            });
        });
    }

    document.addEventListener('DOMContentLoaded', handleFileUploadProgress);

    function addDocument() {
        const container = document.getElementById('documents-container');
        const entries = container.getElementsByClassName('document-entry');
        const newIndex = entries.length;

        const template = `
        <div class="document-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200 mt-4">
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-file-alt text-blue-500 text-xl"></i>
                <h3 class="text-sm font-medium text-gray-700">Document #${newIndex + 1}</h3>
                </div>
                <button type="button" onclick="removeDocument(this)" 
                    class="text-red-500 hover:text-red-700 transition-colors duration-200">
                    <i class="fas fa-trash-alt"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="documents_${newIndex}_title" class="block text-sm font-medium text-gray-700">Document Title:</label>
                    <input type="text" id="documents_${newIndex}_title" name="documents[${newIndex}][title]" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                </div>

                <div>
                    <label for="documents_${newIndex}_type" class="block text-sm font-medium text-gray-700">Document Type:</label>
                    <select id="documents_${newIndex}_type" name="documents[${newIndex}][type]" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="Project Proposal">Project Proposal</option>
                        <option value="Ethics Approval">Ethics Approval</option>
                        <option value="Progress Report">Progress Report</option>
                        <option value="Final Report">Final Report</option>
                        <option value="Publication">Publication</option>
                        <option value="Presentation">Presentation</option>
                    </select>
                </div>

                <div>
                    <label for="documents_${newIndex}_document_date" class="block text-sm font-medium text-gray-700">Document Date:</label>
                    <input type="date" id="documents_${newIndex}_document_date" name="documents[${newIndex}][document_date]" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                </div>

                <div>
                    <label for="documents_${newIndex}_description" class="block text-sm font-medium text-gray-700">Description:</label>
                    <textarea id="documents_${newIndex}_description" name="documents[${newIndex}][description]" rows="2"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </textarea>
                </div>

                <div class="md:col-span-2">
                    <label for="documents_${newIndex}_file" class="block text-sm font-medium text-gray-700">File:</label>
                    <div class="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-lg dropzone" 
                        data-dropzone-id="documents_${newIndex}_file">
                        <div class="space-y-1 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48" aria-hidden="true">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="flex text-sm text-gray-600">
                                <label for="documents_${newIndex}_file" class="relative cursor-pointer bg-white rounded-md font-medium text-blue-600 hover:text-blue-500 focus-within:outline-none focus-within:ring-2 focus-within:ring-offset-2 focus-within:ring-blue-500">
                                    <span>Upload a file</span>
                                    <input id="documents_${newIndex}_file" name="documents[${newIndex}][file]" type="file" class="sr-only file-upload" required accept=".pdf,.doc,.docx">
                                </label>
                                <p class="pl-1">or drag and drop</p>
                            </div>
                            <p class="text-xs text-gray-500">PDF, DOC, or DOCX up to 55MB</p>
                            <!-- Progress bar container -->
                            <div class="upload-progress hidden mt-2">
                                <div class="w-full bg-gray-200 rounded-full h-2.5">
                                    <div class="bg-blue-600 h-2.5 rounded-full progress-bar" style="width: 0%"></div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1 progress-text">0%</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

        container.insertAdjacentHTML('beforeend', template);
        handleFileUploadProgress(); // Initialize progress bar for the new document
    }

    function removeDocument(button) {
        if (confirm('Are you sure you want to remove this document?')) {
            button.closest('.document-entry').remove();
            // Update the numbering of remaining documents
            document.querySelectorAll('.document-entry').forEach((entry, index) => {
                entry.querySelector('h3').textContent = `Document #${index + 1}`;
            });
        }
    }
</script>
