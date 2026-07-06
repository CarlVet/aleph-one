@props([
    'id' => 'photos',
    'name' => 'photos[]',
    'label' => 'Upload files',
    'help' => 'Max 50MB each. Formats: JPG, PNG, WEBP, TIFF, PDF. Multiple files allowed.',
    'accept' => '.jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf',
    'maxMb' => 50,
])

@php
    $previewListId = $id.'_preview_list';
@endphp

<div class="space-y-3" data-photo-upload="1">
    <input type="file" id="{{ $id }}" name="{{ $name }}" accept="{{ $accept }}" multiple class="hidden">

    <div class="flex items-center gap-3">
        <label for="{{ $id }}"
            class="cursor-pointer group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
            <i class="fas fa-upload mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
            {{ $label }}
        </label>
        <span class="text-xs text-gray-500">{{ $help }}</span>
    </div>

    <div id="{{ $previewListId }}" class="hidden space-y-2"></div>
</div>

<script>
    (function () {
        const input = document.getElementById(@js($id));
        const previewList = document.getElementById(@js($previewListId));

        if (!input || !previewList) {
            return;
        }

        const maxBytes = Number(@js((int) $maxMb)) * 1024 * 1024;
        const allowedExt = ['jpg', 'jpeg', 'png', 'webp', 'tif', 'tiff', 'pdf'];
        const objectUrls = [];

        function showError(title, text) {
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'error', title, text });
                return;
            }
            alert(title + "\n" + text);
        }

        function clearObjectUrls() {
            objectUrls.forEach((url) => URL.revokeObjectURL(url));
            objectUrls.length = 0;
        }

        function clear() {
            input.value = '';
            clearObjectUrls();
            previewList.innerHTML = '';
            previewList.classList.add('hidden');
        }

        function renderPreview(files) {
            clearObjectUrls();
            previewList.innerHTML = '';

            if (!files || files.length === 0) {
                previewList.classList.add('hidden');
                return;
            }

            previewList.classList.remove('hidden');

            Array.from(files).forEach((file) => {
                const card = document.createElement('div');
                card.className = 'relative inline-flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3 shadow-sm mr-2 mb-2';

                if (file.type && file.type.startsWith('image/')) {
                    const url = URL.createObjectURL(file);
                    objectUrls.push(url);
                    const img = document.createElement('img');
                    img.src = url;
                    img.alt = 'Preview';
                    img.className = 'max-w-[140px] max-h-28 object-cover rounded-lg border border-gray-200';
                    card.appendChild(img);
                } else if (file.type === 'application/pdf' || (file.name.split('.').pop() || '').toLowerCase() === 'pdf') {
                    const box = document.createElement('div');
                    box.className = 'flex h-28 w-[140px] flex-col items-center justify-center rounded-lg border border-red-200 bg-red-50 px-2 text-center';
                    box.innerHTML = '<i class="fas fa-file-pdf text-2xl text-red-600"></i><span class="mt-2 text-[11px] text-gray-700 break-all">' + file.name + '</span>';
                    card.appendChild(box);
                } else {
                    const box = document.createElement('div');
                    box.className = 'flex h-28 w-[140px] flex-col items-center justify-center rounded-lg border border-gray-200 bg-gray-50 px-2 text-center';
                    box.innerHTML = '<i class="fas fa-file-alt text-xl text-gray-500"></i><span class="mt-2 text-[11px] text-gray-700 break-all">' + file.name + '</span>';
                    card.appendChild(box);
                }

                previewList.appendChild(card);
            });
        }

        input.addEventListener('change', function (e) {
            const files = e.target && e.target.files ? Array.from(e.target.files) : [];
            if (files.length === 0) {
                clear();
                return;
            }

            for (const file of files) {
                if (file.size > maxBytes) {
                    showError('File Too Large', 'Please select files smaller than {{ (int) $maxMb }}MB.');
                    clear();
                    return;
                }

                const ext = (file.name.split('.').pop() || '').toLowerCase();
                if (!allowedExt.includes(ext)) {
                    showError('Invalid File Type', 'Allowed formats: JPG, PNG, WEBP, TIFF, PDF.');
                    clear();
                    return;
                }
            }

            renderPreview(files);
        });
    })();
</script>
