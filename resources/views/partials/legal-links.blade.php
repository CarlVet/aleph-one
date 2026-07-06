@php
    $termsUrl = config('legal.terms_url');
    $privacyUrl = config('legal.privacy_url');
@endphp

@if ($termsUrl || $privacyUrl)
    <p class="mt-4 text-center text-xs text-gray-400">
        @if ($termsUrl)
            <a href="{{ $termsUrl }}" target="_blank" rel="noopener" class="hover:text-gray-600">Terms of Service</a>
        @endif
        @if ($termsUrl && $privacyUrl)
            <span aria-hidden="true"> · </span>
        @endif
        @if ($privacyUrl)
            <a href="{{ $privacyUrl }}" target="_blank" rel="noopener" class="hover:text-gray-600">Privacy Policy</a>
        @endif
    </p>
@endif
