<x-layout>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            @if (session('error'))
                <div class="mb-4 rounded-md border border-red-300 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="mb-4 rounded-md border border-green-300 bg-green-50 px-4 py-3 text-sm text-green-700">{{ session('success') }}</div>
            @endif

            <div class="bg-white shadow overflow-hidden sm:rounded-lg">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Settings</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage your account settings and preferences.</p>
                </div>
                <div class="border-t border-gray-200">
                    <form action="{{ route('profile.settings.update') }}" method="POST" class="space-y-6 p-6">
                        @csrf
                        
                        <!-- Email Settings -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Email Settings</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email address</label>
                                    <input type="email" name="email" id="email" value="{{ $user->email }}" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        </div>

                        <!-- Password Change -->
                        <div>
                            <h4 class="text-md font-medium text-gray-900 mb-4">Change Password</h4>
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="password" class="block text-sm font-medium text-gray-700">New password</label>
                                    <input type="password" name="password" id="password" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                                <div>
                                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm new password</label>
                                    <input type="password" name="password_confirmation" id="password_confirmation" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="bg-white shadow overflow-hidden sm:rounded-lg mt-6">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Authenticator app</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Use an app like Google Authenticator, Authy or 1Password to generate a one-time code when you sign in.</p>
                </div>
                <div class="border-t border-gray-200 p-6 space-y-4">
                    @if (! $user->two_factor_secret)
                        <p class="text-sm text-gray-600">Two-factor authentication is not enabled.</p>
                        <form action="{{ route('two-factor.enable') }}" method="POST">
                            @csrf
                            <button type="submit" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                Enable authenticator app
                            </button>
                        </form>
                    @elseif (! $user->two_factor_confirmed_at)
                        <p class="text-sm text-gray-700">Scan this QR code with your authenticator app, then enter the 6-digit code to finish setup. We’ll show your recovery codes once it’s confirmed.</p>
                        <div class="inline-block rounded-md border border-gray-200 p-3">{!! $user->twoFactorQrCodeSvg() !!}</div>

                        <div class="space-y-2">
                            <form action="{{ route('two-factor.confirm') }}" method="POST" id="two-factor-confirm-form" class="space-y-2">
                                @csrf
                                <label for="code" class="block text-sm font-medium text-gray-700">Confirmation code</label>
                                <input type="text" inputmode="numeric" autocomplete="one-time-code" name="code" id="code" class="block w-40 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @error('code')
                                    <p class="text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </form>
                            <div class="flex items-center gap-3">
                                <button type="submit" form="two-factor-confirm-form" class="inline-flex justify-center rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                    Confirm
                                </button>
                                <form action="{{ route('two-factor.disable') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="text-sm font-medium text-gray-500 hover:text-gray-700">Cancel</button>
                                </form>
                            </div>
                        </div>
                    @else
                        <div class="flex items-center gap-2">
                            <svg class="h-5 w-5 text-green-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
                            <p class="text-sm font-medium text-gray-900">Authenticator app is enabled.</p>
                        </div>

                        @if (session('recovery_codes'))
                            <div class="rounded-md border border-amber-300 bg-amber-50 p-4">
                                <p class="text-sm font-medium text-amber-800">Save your recovery codes now</p>
                                <p class="text-sm text-amber-700">Store them somewhere safe — each can be used once if you lose your device. They’re hashed and can’t be shown again, but you can generate a new set anytime.</p>
                                <div class="mt-3 grid grid-cols-2 gap-2 font-mono text-sm text-gray-800 sm:grid-cols-4">
                                    @foreach (session('recovery_codes') as $code)
                                        <span class="rounded bg-white px-2 py-1 ring-1 ring-amber-200">{{ $code }}</span>
                                    @endforeach
                                </div>
                            </div>
                        @else
                            <p class="text-sm text-gray-600">You have {{ $user->recoveryCodesCount() }} unused recovery codes. They’re hashed for security — regenerate them if you’ve lost them.</p>
                        @endif

                        <div class="flex items-center gap-4">
                            <form action="{{ route('two-factor.recovery-codes') }}" method="POST">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-[#008E9A] hover:text-[#00727d]">Regenerate recovery codes</button>
                            </form>
                            <form action="{{ route('two-factor.disable') }}" method="POST" onsubmit="return confirm('Disable the authenticator app?');">
                                @csrf
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Disable</button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            <div data-passkey-section class="bg-white shadow overflow-hidden sm:rounded-lg mt-6">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Passkeys</h3>
                    <p class="mt-1 max-w-2xl text-sm text-gray-500">Sign in without a password using your device's fingerprint, face or screen lock.</p>
                </div>
                <div class="border-t border-gray-200 p-6 space-y-4">
                    @if (session('status'))
                        <p class="text-sm text-green-600">{{ session('status') }}</p>
                    @endif

                    @forelse ($passkeys as $passkey)
                        <div class="flex items-center justify-between rounded-md border border-gray-200 px-4 py-3">
                            <div class="flex items-center gap-3 text-sm">
                                <svg class="h-5 w-5 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                <div>
                                    <p class="font-medium text-gray-900">{{ $passkey->alias ?: 'Passkey' }}</p>
                                    <p class="text-gray-500">Added {{ $passkey->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                            <form action="{{ route('webauthn.passkeys.destroy', $passkey->id) }}" method="POST" onsubmit="return confirm('Remove this passkey?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-sm font-medium text-red-600 hover:text-red-500">Remove</button>
                            </form>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">You have no passkeys yet.</p>
                    @endforelse

                    <div class="flex items-center gap-4">
                        <button type="button" data-passkey-register class="inline-flex items-center gap-2 rounded-md border border-transparent bg-indigo-600 py-2 px-4 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                            Add a passkey
                        </button>
                        <p data-passkey-status class="text-sm text-gray-500" role="status" aria-live="polite"></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layout> 