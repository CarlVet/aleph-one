@props([
    'id',
    'title',
    'columns' => [],
    'emptyMessage' => 'No records match the current filters.',
])

@php
    $prefix = str_replace('_modal', '', $id);
    $tableId = $prefix.'_table';
    $emptyStateId = $prefix.'_empty_state';
    $closeButtonId = $prefix.'_close_btn';
    $sortClass = str_replace('_lookup', '', $prefix).'-lookup-sort';
    $filterClass = str_replace('_lookup', '', $prefix).'-lookup-filter';
    $columnCount = count($columns) + 1;
@endphp

<x-table-modal :id="$id" :title="$title" :closeButtonId="$closeButtonId">
    <div class="overflow-x-auto">
        <table id="{{ $tableId }}" class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    @foreach ($columns as $column)
                        @php
                            $key = $column['key'];
                            $label = $column['label'];
                            $minWidth = $column['minWidth'] ?? '160px';
                            $placeholder = $column['placeholder'] ?? ('Filter '.strtolower($label));
                        @endphp
                        <th class="px-4 py-3 text-left font-semibold text-gray-700">
                            <div class="flex flex-col gap-2" style="min-width: {{ $minWidth }}">
                                <button type="button"
                                    class="{{ $sortClass }} flex items-center justify-between gap-2 text-left"
                                    data-sort-key="{{ $key }}">
                                    <span>{{ $label }}</span>
                                    <span class="text-xs text-gray-400" data-sort-indicator="{{ $key }}">-</span>
                                </button>
                                <input type="text"
                                    class="{{ $filterClass }} rounded-md border border-gray-200 px-2 py-1 text-xs font-normal text-gray-700 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                                    data-filter-key="{{ $key }}"
                                    placeholder="{{ $placeholder }}">
                            </div>
                        </th>
                    @endforeach
                    <th class="px-4 py-3 text-left font-semibold text-gray-700">Select</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                <tr id="{{ $emptyStateId }}" class="hidden">
                    <td colspan="{{ $columnCount }}" class="px-4 py-6 text-center text-sm text-gray-500">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</x-table-modal>
