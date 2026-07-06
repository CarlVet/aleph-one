<x-layout-plain>
    <x-slot:heading>
        Two-factor authentication
    </x-slot:heading>
    @push('styles')
        <style>[x-cloak]{display:none!important}</style>
    @endpush
    <main>
        <div class="flex min-h-full items-center justify-center px-4 py-1 sm:px-6 lg:px-8">
            <div class="w-full max-w-sm space-y-6" x-data="{ recovery: false }">
                <div>
                    <a href="/" class="mx-auto flex items-center justify-center gap-3 py-6">
                        <img class="h-16 w-auto" src="/images/aleph-one-logo.png" alt="">
                        <span class="text-4xl font-extrabold tracking-tight" style="color:#0d2b45">Aleph<span style="color:#008E9A">∞</span>One</span>
                    </a>
                    <h2 class="mt-1 text-center text-2xl/9 font-bold tracking-tight text-gray-900">Two-factor authentication</h2>
                    <p class="mt-2 text-center text-sm text-gray-500">Your role requires a second factor to continue.</p>
                </div>

                @if ($hasTotp)
                    <form class="space-y-4" action="{{ route('two-factor.verify') }}" method="POST">
                        @csrf
                        <div x-show="! recovery">
                            <label for="code" class="block text-sm font-medium text-gray-700">Authentication code</label>
                            <p class="mt-1 text-xs text-gray-500">Enter the 6-digit code from your authenticator app.</p>
                            <input id="code" name="code" type="text" inputmode="numeric" autocomplete="one-time-code" autofocus
                                class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm">
                        </div>
                        <div x-show="recovery" x-cloak>
                            <label for="recovery_code" class="block text-sm font-medium text-gray-700">Recovery code</label>
                            <p class="mt-1 text-xs text-gray-500">Enter one of your one-time recovery codes.</p>
                            <input id="recovery_code" name="recovery_code" type="text" autocomplete="one-time-code"
                                class="mt-2 block w-full rounded-md border-0 py-1.5 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm">
                        </div>
                        @if ($errors->has('code'))
                            <p class="text-red-500 text-xs">{{ $errors->first('code') }}</p>
                        @endif
                        <button type="submit" class="flex w-full justify-center rounded-md bg-[#008E9A] px-3 py-1.5 text-sm/6 font-semibold text-white hover:bg-[#00727d]">Verify</button>
                        <div class="text-center">
                            <button type="button" class="text-xs text-[#008E9A] hover:underline" x-on:click="recovery = ! recovery">
                                <span x-show="! recovery">Use a recovery code instead</span>
                                <span x-show="recovery" x-cloak>Use an authentication code instead</span>
                            </button>
                        </div>
                    </form>
                @endif

                @if ($hasPasskey)
                    <div data-passkey-section class="space-y-3">
                        @if ($hasTotp)
                            <div class="relative">
                                <div class="absolute inset-0 flex items-center" aria-hidden="true"><div class="w-full border-t border-gray-200"></div></div>
                                <div class="relative flex justify-center text-xs"><span class="bg-gray-100 px-2 text-gray-400">or</span></div>
                            </div>
                        @endif
                        <button type="button" data-passkey-login class="flex w-full items-center justify-center gap-2 rounded-md bg-white px-3 py-1.5 text-sm/6 font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                            <svg class="h-4 w-4 text-[#008E9A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                            Sign in with a passkey
                        </button>
                        <p data-passkey-status class="text-center text-xs text-gray-500" role="status" aria-live="polite"></p>
                    </div>
                @endif

                @unless ($hasTotp || $hasPasskey)
                    <div class="rounded-md border border-gray-200 bg-white p-4 text-sm text-gray-700">
                        <p>You haven't set up a second factor yet. Add an authenticator app or a passkey to secure your account.</p>
                        <a href="{{ route('profile.settings') }}" class="mt-2 inline-block font-medium text-[#008E9A] hover:text-[#00727d]">Set up two-factor authentication</a>
                    </div>
                @endunless

                @if ($canPostpone)
                    <form action="{{ route('two-factor.postpone') }}" method="POST" class="text-center">
                        @csrf
                        <button type="submit" class="text-xs text-gray-500 hover:text-gray-700 hover:underline">
                            Remind me later @if ($daysLeft !== null)({{ $daysLeft }} {{ \Illuminate\Support\Str::plural('day', $daysLeft) }} left)@endif
                        </button>
                    </form>
                @endif

                <form action="{{ route('logout') }}" method="POST" class="text-center">
                    @csrf
                    <button type="submit" class="text-xs text-gray-400 hover:text-gray-600 hover:underline">Sign out</button>
                </form>
            </div>
        </div>
    </main>
</x-layout-plain>
