@props(['label', 'name'])

@php
    $defaults = [
        'id' => $name,
        'name' => $name,
        'class' => 'block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6 pl-2'
    ];
@endphp

<select {{ $attributes($defaults) }} onchange="{{ $onchange ?? '' }}">
        {{ $slot }}
</select>