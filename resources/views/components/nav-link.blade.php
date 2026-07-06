@props(['active' => false])

<a class="{{ $active ? 'border border-white text-white' : 'text-gray-300' }} hover:bg-gray-800 hover:text-white rounded-md px-3 py-2 text-sm font-medium" 
aria-current="{{ $active ? 'page' : 'false' }}"
{{ $attributes }}>
{{$slot}}</a>