<div class="flex items-center space-x-4">
    <input type="file" wire:model="photo" id="photoUpload" class="hidden">
    <label for="photoUpload" class="cursor-pointer bg-gray-200 p-2 rounded-md hover:bg-gray-300 border border-black">
        <i class="fas fa-upload text-gray-600"></i> Upload Photo
    </label>
    @if ($photo)
        <img src="{{ $photo->temporaryUrl() }}" class="h-12 w-12 rounded-md border" alt="Preview">
    @endif

    @if (session()->has('message'))
        <span class="text-green-500">{{ session('message') }}</span>
    @endif
</div>