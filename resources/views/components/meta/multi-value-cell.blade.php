@props([
    'values' => [],
    'label' => 'Items',
    'limit' => 3,
    'empty' => 'N/A',
])

@php
    $items = collect($values)
        ->filter(fn ($value) => is_string($value) && trim($value) !== '')
        ->map(fn ($value) => trim($value))
        ->unique()
        ->values();
    $preview = $items->take((int) $limit)->implode(', ');
@endphp

<div x-data="{ open: false }" class="inline-flex items-center gap-1 text-xs text-gray-800">
    <span>{{ $preview !== '' ? $preview : $empty }}</span>
    @if ($items->count() > (int) $limit)
        <button
            type="button"
            @click="open = true"
            class="font-semibold text-blue-600 hover:text-blue-800 hover:underline"
            aria-label="Show all {{ strtolower($label) }}"
        >
            ...
        </button>
    @endif

    <div
        x-show="open"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4"
    >
        <div class="w-full max-w-lg rounded-xl bg-white p-5 shadow-2xl" @click.outside="open = false">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">{{ $label }}</h3>
                <button type="button" @click="open = false" class="text-gray-500 hover:text-gray-700">
                    Close
                </button>
            </div>
            <div class="max-h-72 overflow-y-auto rounded-lg border border-gray-200 p-3">
                @if ($items->isEmpty())
                    <div class="text-sm text-gray-500">{{ $empty }}</div>
                @else
                    <ul class="space-y-1 text-sm text-gray-800">
                        @foreach ($items as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
