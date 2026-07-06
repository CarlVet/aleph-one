<div class="text-center mt-2">
    <div class="text-center flex justify-center space-x-4">
        <a href="/samples/nucleic/create"
            class="bg-green-500 text-white hover:bg-green-600 hover:text-white rounded-md px-3 py-2 text-sm font-medium border border-black"
            aria-current="page">
            Create
        </a>
        @if ($isEditing)
            <a href="/samples/nucleic/list"
                class="bg-gray-400 text-white hover:bg-gray-500 hover:text-white rounded-md px-3 py-2 text-sm font-medium border border-black"
                aria-current="page">
                Index
            </a>
        @else
            <button wire:click="toggleEditMode"
                class="bg-yellow-500 text-white hover:bg-yellow-600 hover:text-white rounded-md px-3 py-2 text-sm font-medium border border-black">
                Edit
            </button>
        @endif
        <a href="/samples/nucleic/dashboard"
            class="bg-blue-400 text-white hover:bg-blue-500 hover:text-white rounded-md px-3 py-2 text-sm font-medium border border-black"
            aria-current="page">
            Dashboard
        </a>
    </div>
    @livewire('nucleic-table-selector')
    @livewire('nucleic-acids-index')
</div>
