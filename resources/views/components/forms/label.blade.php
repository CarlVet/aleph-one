@props(['name' => '', 'label' => ''])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium leading-6 text-gray-900']) }} for="{{ $name }}">
    {{ $label }}
</label>
