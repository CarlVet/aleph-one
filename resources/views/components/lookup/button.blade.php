@props([
    'id',
    'title' => 'Browse table',
])

<button id="{{ $id }}" type="button"
    {{ $attributes->merge([
        'class' => 'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-700 transition-colors duration-200 hover:bg-blue-100',
        'title' => $title,
    ]) }}>
    <i class="fas fa-table text-sm"></i>
</button>
