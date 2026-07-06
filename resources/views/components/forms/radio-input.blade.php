@props([
    'name' => '', // Name for the radio group
    'options' => [], // Array of radio options ['value' => 'label']
    'checked' => null, // Default checked value
    'class' => '', // Additional classes for the container
])

<div {{ $attributes->merge(['class' => 'flex items-center space-x-4 ' . $class]) }}>
    @foreach ($options as $value => $label)
        <div class="flex items-center">
            <input 
                type="radio" 
                id="{{ $name . '_' . $value }}" 
                name="{{ $name }}" 
                value="{{ $value }}" 
                class="h-4 w-4 text-indigo-600 border-gray-300 focus:ring-indigo-500"
                {{ $value == $checked ? 'checked' : '' }}>
            <label for="{{ $name . '_' . $value }}" class="ml-2 text-sm font-medium text-gray-700">
                {{ $label }}
            </label>
        </div>
    @endforeach
</div>
