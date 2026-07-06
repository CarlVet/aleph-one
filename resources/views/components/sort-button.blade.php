@props([
    'field',
    'active' => null,
    'direction' => 'asc',
])

@php
    $isActive = $active !== null && $active === $field;
@endphp

<button type="button" wire:click="sortBy('{{ $field }}')"
    {{ $attributes->merge(['class' => 'group inline-flex items-center justify-center gap-1 focus:outline-none select-none cursor-pointer']) }}
    title="Sort by this column">
    <span>{{ $slot }}</span>
    <span class="text-[10px] leading-none {{ $isActive ? 'text-blue-600' : 'text-gray-300 group-hover:text-gray-500' }}">
        @if ($isActive)
            {{ $direction === 'asc' ? '▲' : '▼' }}
        @else
            ⇅
        @endif
    </span>
</button>
