@props(['disabled' => false])

<input type="number" {{ $disabled ? 'disabled' : '' }} {!! $attributes->merge(['class' => 'w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200']) !!}>
