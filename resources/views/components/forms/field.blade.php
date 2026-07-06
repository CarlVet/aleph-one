@props(['label' => '', 'name' => ''])

<div {{ $attributes->merge(['class'=>'mb-2 mt-2']) }}>
    
    @if ($label)
        <x-forms.label :$name :$label />
    @endif

    {{ $slot }}
    
    <x-forms.error :error="$errors->first($name)" />
</div>