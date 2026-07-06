@php
    use App\Support\MediaPreview;

    $coverObservation = $sample->latestObservation ?? $sample->observations?->first();
    $coverPhoto = $coverObservation?->photo ?? $sample->latestPhoto ?? $sample->photos?->first();
    $coverPath = $coverPhoto?->photo_path ?: $sample->photo_path;
    $photoCount = $sample->observations?->count() ?: ($coverPath ? 1 : 0);
    $photoExists = $coverPath && MediaPreview::exists($coverPath);
    $coverIsImage = MediaPreview::isImage($coverPath);
    $coverIsPdf = MediaPreview::isPdf($coverPath);
    $coverUrl = MediaPreview::url($coverPath);
@endphp

<div class="flex flex-col items-center gap-1">
    @if ($photoExists)
        <button type="button" wire:click="openPhotoPreview({{ $sample->id }})"
            title="Preview photos" class="relative">
            @if ($coverIsImage)
                <img src="{{ $coverUrl }}" alt="Parasite sample photo"
                    class="w-16 h-16 object-cover rounded shadow mb-1">
            @elseif ($coverIsPdf)
                <div class="flex h-16 w-16 items-center justify-center rounded border border-red-200 bg-red-50 shadow mb-1">
                    <i class="fas fa-file-pdf text-2xl text-red-600"></i>
                </div>
            @else
                <div class="flex h-16 w-16 items-center justify-center rounded border border-gray-200 bg-gray-50 shadow mb-1">
                    <i class="fas fa-file-alt text-xl text-gray-500"></i>
                </div>
            @endif
            @if ($photoCount > 1)
                <span class="absolute -top-1 -right-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-bold text-white">
                    {{ $photoCount }}
                </span>
            @endif
        </button>
    @elseif (!empty($coverPath))
        <div class="flex items-center gap-2">
            <span class="text-xs font-semibold text-red-600">Missing file</span>
            @if (!$isGuestMode && $canEdit)
                <button type="button" wire:click="clearBrokenPhotoPath({{ $sample->id }})"
                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 underline underline-offset-2">
                    Clear path
                </button>
            @endif
        </div>
    @endif

    @if ($canEdit && !$isGuestMode)
        <label for="photo-upload-{{ $sample->id }}" class="cursor-pointer group">
            <i class="fas fa-camera text-blue-500 group-hover:text-blue-600 text-xl transition-all duration-200 transform group-hover:scale-110"></i>
            <input type="file" id="photo-upload-{{ $sample->id }}"
                class="hidden" accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf"
                wire:model.live="photo" wire:loading.attr="disabled"
                wire:change="uploadPhoto({{ $sample->id }})" x-data
                x-init="$watch('$wire.photo', value => {
                    if (value && $wire.currentPhotoId === {{ $sample->id }}) {
                        $wire.uploadPhoto({{ $sample->id }});
                    }
                })"
                x-on:change="
                    if ($el.files[0] && $el.files[0].size > 52428800) {
                        alert('File size exceeds 50MB limit');
                        $el.value = '';
                        return;
                    }
                "
                x-on:photo-uploaded.window="
                    if ($wire.currentPhotoId === {{ $sample->id }}) {
                        $el.value = '';
                    }
                ">
        </label>
    @endif

    @if ($uploadingPhotoId === $sample->id)
        <span wire:loading wire:target="photo" class="text-xs text-blue-500">
            <i class="fas fa-spinner fa-spin"></i> Uploading...
        </span>
    @endif

    @if (isset($uploadErrors[$sample->id]))
        <span class="text-xs text-red-500">{{ $uploadErrors[$sample->id] }}</span>
    @endif

    @if (!$photoExists && empty($coverPath))
        <span class="text-gray-500 italic text-xs">No photo</span>
    @endif
</div>
