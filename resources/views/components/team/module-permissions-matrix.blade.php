@props([
    'modulePermissionOptions' => [],
    'moduleMatrix' => [],
    'namePrefix' => 'module_permissions',
    'showSaveButton' => false,
    'formAction' => null,
])

@php
    $modulePermissionOptions = $modulePermissionOptions ?: \App\Support\ProjectPermission::moduleOptions();
    $moduleMatrix = is_array($moduleMatrix) ? $moduleMatrix : [];
@endphp

<div {{ $attributes->merge(['class' => 'space-y-2']) }}>
    <p class="text-xs font-medium text-gray-600">Module access</p>
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full text-xs text-gray-700">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold">Module</th>
                    <th class="px-3 py-2 text-center font-semibold">View</th>
                    <th class="px-3 py-2 text-center font-semibold">Edit</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @foreach($modulePermissionOptions as $moduleKey => $moduleLabel)
                    @php
                        $access = $moduleMatrix[$moduleKey] ?? ['view' => false, 'edit' => false];
                    @endphp
                    <tr>
                        <td class="px-3 py-2">{{ $moduleLabel }}</td>
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox"
                                name="{{ $namePrefix }}[{{ $moduleKey }}][view]"
                                value="1"
                                {{ ($access['view'] ?? false) ? 'checked' : '' }}
                                class="rounded border-gray-300 module-view-checkbox"
                                data-module="{{ $moduleKey }}">
                        </td>
                        <td class="px-3 py-2 text-center">
                            <input type="checkbox"
                                name="{{ $namePrefix }}[{{ $moduleKey }}][edit]"
                                value="1"
                                {{ ($access['edit'] ?? false) ? 'checked' : '' }}
                                class="rounded border-gray-300 module-edit-checkbox"
                                data-module="{{ $moduleKey }}">
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <p class="text-xs text-gray-500">Edit access includes create, update, and delete within that module. View hides module navigation and read-only pages when unchecked.</p>

    @if($showSaveButton)
        <button type="submit"
            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 text-xs font-semibold text-white shadow-md shadow-blue-500/25 transition hover:from-blue-700 hover:to-indigo-700">
            <i class="fas fa-save text-[10px]"></i>
            Save module access
        </button>
    @endif
</div>

<script>
    document.addEventListener('change', function (event) {
        const target = event.target;
        if (!(target instanceof HTMLInputElement) || !target.classList.contains('module-edit-checkbox')) {
            return;
        }

        const module = target.dataset.module;
        if (!module) {
            return;
        }

        const viewCheckbox = document.querySelector(`input.module-view-checkbox[data-module="${module}"]`);
        if (viewCheckbox instanceof HTMLInputElement && target.checked) {
            viewCheckbox.checked = true;
        }
    });
</script>
