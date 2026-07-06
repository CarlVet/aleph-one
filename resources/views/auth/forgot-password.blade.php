<x-layout-plain>
    <x-slot:heading>
        Forgot password
    </x-slot:heading>
    <main>
        <div class="flex min-h-full items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
            <div class="w-full max-w-sm">
                @include('partials.auth-brand', ['subtitle' => 'Reset your password'])

                <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                    <p class="text-sm text-gray-600">Enter your email and we’ll send you a reset link.</p>

                    <form class="mt-5 space-y-5" action="{{ route('password.email') }}" method="POST">
                        @csrf

                        <div class="space-y-1.5">
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input id="email" name="email" type="email" required autocomplete="username" value="{{ old('email') }}"
                                class="block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm">
                        </div>

                        @if ($errors->has('email'))
                            <p class="text-sm text-red-600">{{ $errors->first('email') }}</p>
                        @endif
                        @if (session('status'))
                            <p class="text-sm text-green-600">{{ session('status') }}</p>
                        @endif

                        <button type="submit" class="flex w-full justify-center rounded-lg bg-[#008E9A] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#00727d] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#008E9A]">Send reset link</button>
                    </form>
                </div>

                <p class="mt-6 text-center text-sm text-gray-500">
                    <a href="/login" class="font-medium text-[#008E9A] hover:text-[#00727d]">Back to sign in</a>
                </p>
            </div>
        </div>
    </main>
</x-layout-plain>
