@props([
    'path' => null,
    'url' => null,
    'alt' => 'Media preview',
    'imgClass' => 'h-full w-full object-contain',
    'iframeClass' => 'h-full w-full',
    'fallbackClass' => 'text-sm text-white',
])

@php
    use App\Support\MediaPreview;

    $mediaPath = $path;
    $mediaUrl = $url ?? MediaPreview::url($mediaPath);
    $isImage = MediaPreview::isImage($mediaPath);
    $isPdf = MediaPreview::isPdf($mediaPath);
    $extension = MediaPreview::extension($mediaPath);
@endphp

@if ($mediaUrl)
    @if ($isImage)
        <img src="{{ $mediaUrl }}" alt="{{ $alt }}" {{ $attributes->merge(['class' => $imgClass]) }}>
    @elseif ($isPdf)
        <iframe src="{{ $mediaUrl }}" title="{{ $alt }}" {{ $attributes->merge(['class' => $iframeClass]) }} frameborder="0"></iframe>
    @else
        <div {{ $attributes->merge(['class' => 'flex items-center justify-center p-4']) }}>
            <a href="{{ $mediaUrl }}" target="_blank" class="{{ $fallbackClass }} hover:underline">
                File uploaded ({{ strtoupper((string) $extension) }}) — click to open
            </a>
        </div>
    @endif
@endif
