@props(['person' => null, 'width' => 90])

@php
    $initial = $person ? strtoupper(substr($person->first_name, 0, 1)) : '?';
    $colors = ['bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-red-500', 'bg-purple-500', 'bg-pink-500'];
    $colorIndex = $person ? ord(strtolower(substr($person->first_name, 0, 1))) % count($colors) : 0;
    $bgColor = $colors[$colorIndex];

    $picPath = $person ? (string) ($person->pic_path ?? '') : '';
    $picPath = trim($picPath);
    $src = null;

    if ($picPath !== '') {
        if (str_starts_with($picPath, 'http://') || str_starts_with($picPath, 'https://')) {
            $src = $picPath;
        } elseif (str_starts_with($picPath, 'storage/')) {
            $src = asset($picPath);
        } elseif (str_starts_with($picPath, '/storage/')) {
            $src = $picPath;
        } elseif (str_starts_with($picPath, 'profile-photos/') || str_starts_with($picPath, 'people-photos/') || str_starts_with($picPath, 'uploads/')) {
            $src = Storage::url($picPath);
        } elseif (file_exists(public_path($picPath))) {
            $src = asset($picPath);
        } elseif (Storage::disk('local')->exists($picPath)) {
            $src = Storage::url($picPath);
        }
    }
@endphp

@if ($person && $src)
    <img
        src="{{ $src }}"
        alt="{{ $person->first_name . ' ' . $person->last_name }}"
        {{ $attributes->merge(['class' => 'rounded-full object-cover'])->merge(['style' => "width: {$width}px; height: {$width}px;"]) }}>
@else
    <div
        {{ $attributes->merge(['class' => $bgColor.' rounded-full flex items-center justify-center text-white font-medium'])->merge(['style' => "width: {$width}px; height: {$width}px;"]) }}>
        {{ $initial }}
    </div>
@endif
