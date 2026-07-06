<x-layout-plain>
    <x-slot:heading>
        Sign in
    </x-slot:heading>
    <main>
        <div class="flex min-h-full items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
            <div class="w-full max-w-sm">
                @include('partials.auth-brand', ['subtitle' => 'Sign in to your account'])

                <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                    <form class="space-y-5" action="/login" method="POST">
                        @csrf

                        <div class="space-y-1.5">
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input id="email" name="email" type="email" autocomplete="username" required value="{{ old('email') }}"
                                class="block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm">
                        </div>

                        <div class="space-y-1.5">
                            <div class="flex items-baseline justify-between">
                                <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                                <a href="{{ route('password.request') }}" class="text-xs font-medium text-[#008E9A] hover:text-[#00727d]">Forgot?</a>
                            </div>
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                class="block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm">
                        </div>

                        @if ($errors->has('email') || $errors->has('password'))
                            <p class="text-sm text-red-600">{{ $errors->first('email') ?: $errors->first('password') }}</p>
                        @endif

                        <button type="submit" class="flex w-full justify-center rounded-lg bg-[#008E9A] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#00727d] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#008E9A]">Sign in</button>
                    </form>

                    <div data-passkey-section class="mt-6 space-y-6">
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center" aria-hidden="true"><div class="w-full border-t border-gray-200"></div></div>
                            <div class="relative flex justify-center text-xs"><span class="bg-white px-2 text-gray-400">or</span></div>
                        </div>
                        <div class="space-y-2">
                            <button type="button" data-passkey-login class="flex w-full items-center justify-center gap-2 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-gray-900 ring-1 ring-inset ring-gray-300 hover:bg-gray-50">
                                <svg class="h-4 w-4 text-[#008E9A]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                                Sign in with a passkey
                            </button>
                            <p data-passkey-status class="text-center text-xs text-gray-500" role="status" aria-live="polite"></p>
                        </div>
                    </div>
                </div>

                <p class="mt-6 text-center text-sm text-gray-500">
                    New here? <a href="/register" class="font-medium text-[#008E9A] hover:text-[#00727d]">Create an account</a>
                </p>

                @include('partials.legal-links')
            </div>
        </div>
    </main>
</x-layout-plain>
