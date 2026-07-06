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

        <x-forms.form method="POST" action="/bank/boxes" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Box Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new box</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-box text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">General Box Information</h2>
                            </div>

                            <x-forms.field label="Box name (optional):" name="box_name">
                                <x-forms.textarea id="box_name" name="box_name" rows="1"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Enter a descriptive name for the box"></x-forms.textarea>
                                <p id="box_name_error" class="mt-1 text-sm text-red-600 hidden"></p>
                                <p id="box_name_success" class="mt-1 text-sm text-green-600 hidden"></p>
                            </x-forms.field>

                            <x-forms.field label="Box code alias (optional):" name="alias_code">
                                <x-forms.text-input id="alias_code" name="alias_code" type="text" maxlength="255"
                                    value="{{ old('alias_code') }}"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Optional custom/legacy code for this box" />
                            </x-forms.field>

                            <x-forms.field label="Number of columns:" name="n_columns">
                                <x-forms.numeric-input id="n_columns" name="n_columns" min="6" max="1000" step="1" value="6" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"/>
                            </x-forms.field>

                            <x-forms.field label="Number of rows:" name="n_rows">
                                <x-forms.numeric-input id="n_rows" name="n_rows" min="6" max="1000" step="1" value="6" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"/>
                            </x-forms.field>

                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-ruler-combined text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Box Layout Preview</h2>
                            </div>

                            <!-- Box Preview -->
                            <div class="mt-4 overflow-x-auto">
                                <div id="boxPreview" class="border border-gray-300 rounded-lg p-4 min-w-0">
                                    <div id="previewGrid" class="grid gap-1 w-max min-w-full" style="min-height: 150px;"></div>
                                </div>
                            </div>

                            <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                                <div class="flex items-start">
                                    <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                                    <div>
                                        <h3 class="text-sm font-medium text-blue-800 mb-1">Box Configuration</h3>
                                        <p class="text-sm text-blue-700">
                                            Configure the box dimensions based on your storage needs. The layout preview will update automatically 
                                            to show the grid structure. Standard boxes typically use 9x9 or 12x8 configurations.
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit id="box_submit_btn" class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Box
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        @if (session('success'))
            <div id="boxesSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="boxesErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            // Box preview functionality
            const BOX_PREVIEW_CELL_PX = 34;
            let boxNameStatus = 'empty';

            function updateBoxPreview() {
                const columns = parseInt(document.getElementById('n_columns').value) || 6;
                const rows = parseInt(document.getElementById('n_rows').value) || 6;
                const previewGrid = document.getElementById('previewGrid');

                previewGrid.style.gridTemplateColumns = `repeat(${columns}, ${BOX_PREVIEW_CELL_PX}px)`;
                previewGrid.style.gridTemplateRows = `repeat(${rows}, ${BOX_PREVIEW_CELL_PX}px)`;
                previewGrid.style.width = `${(columns * BOX_PREVIEW_CELL_PX) + Math.max(0, columns - 1) * 4}px`;
                previewGrid.innerHTML = '';

                for (let y = 1; y <= rows; y++) {
                    for (let x = 1; x <= columns; x++) {
                        const cell = document.createElement('div');
                        cell.className = 'border border-gray-300 bg-gray-50 flex items-center justify-center text-[10px] p-1';
                        cell.style.width = `${BOX_PREVIEW_CELL_PX}px`;
                        cell.style.height = `${BOX_PREVIEW_CELL_PX}px`;
                        cell.textContent = `${x},${y}`;
                        previewGrid.appendChild(cell);
                    }
                }
            }

            function updateBoxSubmitButton() {
                const submitBtn = document.getElementById('box_submit_btn');
                if (!submitBtn) {
                    return;
                }

                const blocked = boxNameStatus === 'exact';
                submitBtn.disabled = blocked;
                submitBtn.classList.toggle('opacity-50', blocked);
                submitBtn.classList.toggle('cursor-not-allowed', blocked);
                submitBtn.classList.toggle('hover:scale-105', !blocked);
            }

            function checkBoxNameDuplicate(value) {
                const errorElement = document.getElementById('box_name_error');
                const successElement = document.getElementById('box_name_success');

                if (!errorElement || !successElement) {
                    return;
                }

                const trimmed = (value || '').trim();
                if (trimmed === '') {
                    boxNameStatus = 'empty';
                    errorElement.classList.add('hidden');
                    successElement.classList.add('hidden');
                    updateBoxSubmitButton();
                    return;
                }

                fetch('/validation/name-check', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                    },
                    body: JSON.stringify({
                        type: 'box',
                        value: trimmed,
                    }),
                })
                    .then((response) => response.json())
                    .then((data) => {
                        const status = data.status || 'new';

                        if (status === 'exact') {
                            boxNameStatus = 'exact';
                            errorElement.className = 'mt-1 text-sm text-red-600';
                            errorElement.innerHTML = '<i class="fa-solid fa-circle-xmark mr-1"></i>Name already exists. Go back and choose it from dropdown.';
                            errorElement.classList.remove('hidden');
                            successElement.classList.add('hidden');
                        } else if (status === 'similar') {
                            boxNameStatus = 'similar';
                            const similarTo = Array.isArray(data.suggestions) ? (data.suggestions[0] || '') : '';
                            errorElement.className = 'mt-1 text-sm text-yellow-800';
                            errorElement.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1"></i>Input is similar to "${similarTo}" option.`;
                            errorElement.classList.remove('hidden');
                            successElement.classList.add('hidden');
                        } else {
                            boxNameStatus = 'new';
                            errorElement.classList.add('hidden');
                            successElement.className = 'mt-1 text-sm text-green-600';
                            successElement.innerHTML = '<i class="fa-solid fa-plus mr-1"></i>Name is available.';
                            successElement.classList.remove('hidden');
                        }

                        updateBoxSubmitButton();
                    })
                    .catch(() => {});
            }

            // Update preview when dimensions change
            document.getElementById('n_columns').addEventListener('input', updateBoxPreview);
            document.getElementById('n_rows').addEventListener('input', updateBoxPreview);

            const boxNameInput = document.getElementById('box_name');
            let boxNameCheckTimer = null;
            if (boxNameInput) {
                boxNameInput.addEventListener('input', function () {
                    if (boxNameCheckTimer) {
                        clearTimeout(boxNameCheckTimer);
                    }
                    boxNameCheckTimer = setTimeout(function () {
                        checkBoxNameDuplicate(boxNameInput.value);
                    }, 350);
                });
            }

            // Initialize preview
            updateBoxPreview();
            updateBoxSubmitButton();

            // Show success/error messages
            const successMessageElement = document.getElementById('boxesSuccessMessage');
            const errorMessageElement = document.getElementById('boxesErrorMessage');

            if (successMessageElement) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: successMessageElement.textContent,
                });
            }

            if (errorMessageElement) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessageElement.textContent,
                });
            }
        </script>

        <!-- Custom scripts -->
        <script src="/js/create-boxes.js"></script>
    </div>
</div>


