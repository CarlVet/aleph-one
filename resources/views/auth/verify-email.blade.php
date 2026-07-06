<x-layout-plain>
    <x-slot:heading>
        Verify your email
    </x-slot:heading>
    <main>
        <div class="flex min-h-full items-center justify-center px-4 py-8 sm:px-6 lg:px-8">
            <div class="w-full max-w-sm">
                @include('partials.auth-brand', ['subtitle' => 'Verify your email'])

                <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                    <p class="text-sm text-gray-600">We’ve sent a verification code to your email address. Enter it below to complete your registration.</p>

                    <form class="mt-5 space-y-5" action="/verify-email" method="POST">
                        @csrf

                        <div class="space-y-1.5">
                            <label for="verification_code" class="block text-sm font-medium text-gray-700">Verification code</label>
                            <input id="verification_code" name="verification_code" type="text" required maxlength="5" pattern="[0-9]{5}"
                                placeholder="Enter 5-digit code" value="{{ old('verification_code') }}"
                                class="block w-full rounded-lg border-0 py-2 px-3 text-center text-lg font-mono tracking-widest text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-sm placeholder:font-sans placeholder:tracking-normal placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A]">
                        </div>

                        @if ($errors->has('verification_code'))
                            <p class="text-sm text-red-600">{{ $errors->first('verification_code') }}</p>
                        @endif
                        @if (session('error'))
                            <p class="text-sm text-red-600">{{ session('error') }}</p>
                        @endif
                        @if (session('success'))
                            <p class="text-sm text-green-600">{{ session('success') }}</p>
                        @endif

                        <button type="submit" class="flex w-full justify-center rounded-lg bg-[#008E9A] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#00727d] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#008E9A]">Verify email</button>

                        <div class="text-center">
                            <button type="button" id="resend-code" class="text-xs font-medium text-[#008E9A] hover:underline">Didn’t receive the code? Resend</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
        const verificationInput = document.getElementById('verification_code');
        const resendButton = document.getElementById('resend-code');
        let resendTimeout;

        // Auto-format verification code input
        verificationInput.addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
            if (this.value.length > 5) {
                this.value = this.value.slice(0, 5);
            }
        });

        // Handle resend code
        resendButton.addEventListener('click', function() {
            if (resendTimeout) return;

            this.disabled = true;
            this.textContent = 'Sending...';

            fetch('/resend-verification', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.textContent = 'Code sent!';
                    let countdown = 60;
                    resendTimeout = setInterval(() => {
                        countdown--;
                        if (countdown > 0) {
                            this.textContent = `Resend in ${countdown}s`;
                        } else {
                            this.textContent = 'Didn\'t receive the code? Resend';
                            this.disabled = false;
                            clearInterval(resendTimeout);
                            resendTimeout = null;
                        }
                    }, 1000);
                } else {
                    this.textContent = 'Error sending code';
                    setTimeout(() => {
                        this.textContent = 'Didn\'t receive the code? Resend';
                        this.disabled = false;
                    }, 3000);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                this.textContent = 'Error sending code';
                setTimeout(() => {
                    this.textContent = 'Didn\'t receive the code? Resend';
                    this.disabled = false;
                }, 3000);
            });
        });
    </script>
</x-layout-plain>
