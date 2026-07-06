<div id="{{ $id }}" class="fixed inset-0 z-50 hidden items-center justify-center bg-gray-800 bg-opacity-75">
    <div {{ $attributes->merge(['class' => 'bg-white rounded-lg overflow-hidden shadow-xl w-full max-w-screen-2xl mx-auto px-4 sm:px-6 lg:px-8']) }}>
        <!-- Modal Header -->
        <div class="flex justify-between items-center border-b p-4">
            <h2 class="text-xl font-semibold">{{ $title }}</h2>
            <button id="{{ $closeButtonId }}" type= "button" class="text-gray-500 hover:text-gray-700">&times;</button>
        </div>
        <!-- Modal Body -->
        <div class="p-4 overflow-x-auto">
            <div class="max-h-[80vh] overflow-y-auto" data-modal-content>
                {{ $slot }}
            </div>
        </div>
    </div>
</div>
