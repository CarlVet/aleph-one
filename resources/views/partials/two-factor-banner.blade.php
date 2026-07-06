@php
    $twoFactorUser = auth()->user();
    $showTwoFactorBanner = $twoFactorUser
        && \App\Support\AdminAccess::requiresTwoFactor($twoFactorUser)
        && ! $twoFactorUser->hasSatisfiedTwoFactor();
    $twoFactorDaysLeft = null;
    if ($showTwoFactorBanner && $twoFactorUser->two_factor_grace_until) {
        $twoFactorDaysLeft = max(0, (int) ceil(now()->floatDiffInDays($twoFactorUser->two_factor_grace_until, false)));
    }
@endphp

@if ($showTwoFactorBanner)
    <div class="mb-4 rounded-md border border-amber-300 bg-amber-50 px-4 py-3">
        <div class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 flex-shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 9v4"/><path d="M12 17h.01"/><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/></svg>
            <div class="text-sm">
                <p class="font-medium text-amber-800">Two-factor authentication required for your role</p>
                <p class="text-amber-700">
                    @if ($twoFactorDaysLeft === null)
                        Set up an authenticator app or add a passkey to keep access to admin areas.
                    @elseif ($twoFactorDaysLeft > 0)
                        You have {{ $twoFactorDaysLeft }} {{ \Illuminate\Support\Str::plural('day', $twoFactorDaysLeft) }} left to set up an authenticator app or add a passkey before access to admin areas is restricted.
                    @else
                        Access to admin areas is now restricted until you set up an authenticator app or add a passkey.
                    @endif
                </p>
                <a href="{{ route('profile.settings') }}" class="mt-1 inline-block font-medium text-amber-800 underline hover:text-amber-900">Set up two-factor authentication</a>
            </div>
        </div>
    </div>
@endif
