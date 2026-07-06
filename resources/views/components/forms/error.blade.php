@props(['error' => false])

@if($error)
    <p class="text-xs text-red-500 font-semibold mt-1">{{ $error }}</p>
@endif
