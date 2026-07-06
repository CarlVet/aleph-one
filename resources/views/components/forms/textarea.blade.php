@props(['value' => ''])

<div class="mt-1">
    <textarea {{ $attributes->merge(['class' => 'block w-full rounded-md border py-1.5 text-gray-900 shadow-sm ring-1 ring-gray-300 focus:ring-2 focus:ring-indigo-600 sm:text-sm sm:leading-6']) }}>{{ is_array(old($attributes->get('name'), $value)) ? '' : old($attributes->get('name'), $value) }}</textarea>
</div>