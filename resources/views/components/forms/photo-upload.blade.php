@props([
    'id' => 'photo',
    'name' => 'photo',
    'label' => 'Upload file',
    'help' => 'Max 50MB. Formats: JPG, PNG, WEBP, TIFF, PDF.',
    'accept' => '.jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf',
    'maxMb' => 50,
])

@php
    $previewId = $id.'_preview';
    $imgId = $id.'_img';
    $fileId = $id.'_file';
    $removeId = $id.'_remove';
@endphp

<div class="space-y-3" data-photo-upload="1">
    <input type="file" id="{{ $id }}" name="{{ $name }}" accept="{{ $accept }}" class="hidden">

    <div class="flex items-center gap-3">
        <label for="{{ $id }}"
            class="cursor-pointer group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
            <i class="fas fa-upload mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
            {{ $label }}
        </label>
        <span class="text-xs text-gray-500">{{ $help }}</span>
    </div>

    <div id="{{ $previewId }}" class="hidden">
        <div class="relative inline-flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm">
            <img id="{{ $imgId }}" src="" alt="Preview" class="hidden max-w-xs max-h-48 object-cover rounded-lg border border-gray-200">

            <div id="{{ $fileId }}" class="hidden max-w-xs">
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center rounded-md bg-gray-100 px-2 py-1 text-xs font-medium text-gray-700">
                        <i class="fas fa-file-alt mr-2 text-gray-500"></i>
                        File selected
                    </span>
                    <span class="text-sm text-gray-700 break-all" data-file-name></span>
                </div>
            </div>

            <button type="button" id="{{ $removeId }}"
                class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center hover:bg-red-600 transition-colors duration-200"
                title="Remove">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    </div>
</div>

<script>
    (function () {
        const input = document.getElementById(@js($id));
        const preview = document.getElementById(@js($previewId));
        const img = document.getElementById(@js($imgId));
        const fileBox = document.getElementById(@js($fileId));
        const removeBtn = document.getElementById(@js($removeId));

        if (!input || !preview || !img || !fileBox || !removeBtn) {
            return;
        }

        const maxBytes = Number(@js((int) $maxMb)) * 1024 * 1024;
        const allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'tif', 'tiff', 'pdf'];

        let objectUrl = null;

        function showError(title, text) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title, text });
                return;
            }
            alert(title + "\n" + text);
        }

        function clear() {
            input.value = '';

            if (objectUrl) {
                URL.revokeObjectURL(objectUrl);
                objectUrl = null;
            }

            img.src = '';
            img.classList.add('hidden');

            const nameEl = fileBox.querySelector('[data-file-name]');
            if (nameEl) {
                nameEl.textContent = '';
            }
            fileBox.classList.add('hidden');

            preview.classList.add('hidden');
        }

        function setPreviewForFile(file) {
            preview.classList.remove('hidden');

            if (file && file.type && file.type.startsWith('image/')) {
                if (objectUrl) {
                    URL.revokeObjectURL(objectUrl);
                }
                objectUrl = URL.createObjectURL(file);
                img.src = objectUrl;
                img.classList.remove('hidden');
                fileBox.classList.add('hidden');
                return;
            }

            // Non-image (e.g. PDF)
            img.src = '';
            img.classList.add('hidden');
            fileBox.classList.remove('hidden');
            const nameEl = fileBox.querySelector('[data-file-name]');
            if (nameEl) {
                nameEl.textContent = file ? file.name : '';
            }
        }

        input.addEventListener('change', function (e) {
            const file = e.target && e.target.files && e.target.files[0] ? e.target.files[0] : null;
            if (!file) {
                clear();
                return;
            }

            if (file.size > maxBytes) {
                showError('File Too Large', 'Please select a file smaller than {{ (int) $maxMb }}MB.');
                clear();
                return;
            }

            const ext = (file.name.split('.').pop() || '').toLowerCase();
            if (!allowedExt.includes(ext)) {
                showError('Invalid File Type', 'Allowed formats: JPG, PNG, WEBP, TIFF, PDF.');
                clear();
                return;
            }

            setPreviewForFile(file);
        });

        removeBtn.addEventListener('click', function () {
            clear();
        });
    })();
</script>

