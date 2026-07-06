@props([
    'paginator',
    'perPageOptions' => [10, 20, 50, 100, 200, 500],
])

<div class="flex flex-wrap items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-3 py-2">
    <div class="flex flex-wrap items-center gap-2 text-xs text-gray-600">
        <label class="inline-flex items-center gap-2 whitespace-nowrap font-medium text-gray-700">
            <span>Rows per page</span>
            <select wire:model.live="perPage"
                class="rounded border border-gray-300 bg-white px-2 py-1 text-xs text-gray-800 shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500">
                @foreach ($perPageOptions as $option)
                    <option value="{{ $option }}">{{ $option }}</option>
                @endforeach
            </select>
        </label>
        @if ($paginator->total() > 0)
            <span class="whitespace-nowrap text-gray-500">
                {{ $paginator->firstItem() }}–{{ $paginator->lastItem() }} of {{ number_format($paginator->total()) }}
            </span>
        @endif
    </div>
</div>
