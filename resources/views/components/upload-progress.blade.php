@props([
    'wireModel' => null,
])

<div
    {{ $attributes->merge(['class' => 'w-full']) }}
    x-data="{ isUploading: false, progress: 0 }"
    x-on:livewire-upload-start.window="if (!@js($wireModel) || $event.detail.name === @js($wireModel)) { isUploading = true; progress = 0 }"
    x-on:livewire-upload-progress.window="if (!@js($wireModel) || $event.detail.name === @js($wireModel)) { progress = $event.detail.progress }"
    x-on:livewire-upload-finish.window="if (!@js($wireModel) || $event.detail.name === @js($wireModel)) { isUploading = false }"
    x-on:livewire-upload-error.window="if (!@js($wireModel) || $event.detail.name === @js($wireModel)) { isUploading = false }"
>
    <div x-show="isUploading" x-cloak class="w-full">
        <div class="flex items-center justify-between mb-1 text-xs text-gray-600">
            <span>Uploading…</span>
            <span x-text="progress + '%'"></span>
        </div>
        <div class="w-full h-2 bg-gray-200 rounded">
            <div class="h-2 bg-blue-600 rounded" x-bind:style="`width: ${progress}%`"></div>
        </div>
    </div>
</div>
