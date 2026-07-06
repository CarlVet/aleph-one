<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-between max-w-4xl mx-auto">
                <div class="flex flex-col items-center">
                    <div class="rounded-full h-12 w-12 flex items-center justify-center {{ $step >= 1 ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                        <i class="fas fa-info-circle text-xl"></i>
                    </div>
                    <span class="mt-2 text-xs font-medium text-gray-500">General Info</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="rounded-full h-12 w-12 flex items-center justify-center {{ $step >= 2 ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <span class="mt-2 text-xs font-medium text-gray-500">Team Members</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="rounded-full h-12 w-12 flex items-center justify-center {{ $step >= 3 ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                        <i class="fas fa-money-bill-wave text-xl"></i>
                    </div>
                    <span class="mt-2 text-xs font-medium text-gray-500">Funding</span>
                </div>
                <div class="flex flex-col items-center">
                    <div class="rounded-full h-12 w-12 flex items-center justify-center {{ $step >= 4 ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                        <i class="fas fa-file-alt text-xl"></i>
                    </div>
                    <span class="mt-2 text-xs font-medium text-gray-500">Documents</span>
                </div>
            </div>

            <!-- Progress Bar -->
            <div class="max-w-4xl mx-auto mt-4">
                <div class="relative">
                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                        <div class="transition-all duration-500 ease-out shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"
                            style="width: {{ ($step / 4) * 100 }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
            @switch($step)
                @case(1)
                    @include('projects.forms.general')
                    @break
                @case(2)
                    @include('projects.forms.team')
                    @break
                @case(3)
                    @include('projects.forms.funding')
                    @break
                @case(4)
                    @include('projects.forms.documents')
                    @break
                @default
                    @include('projects.forms.general')
            @endswitch
        </div>
    </div>

    @if (session('success'))
        <div id="successMessage" class="hidden">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div id="errorMessage" class="hidden">{{ session('error') }}</div>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');

            if (successMessage) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: successMessage.textContent,
                    position: 'center',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    timer: 5000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    }
                });
            }

            if (errorMessage) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: errorMessage.textContent,
                    position: 'center',
                    showConfirmButton: true,
                    confirmButtonText: 'OK',
                    timer: 5000,
                    timerProgressBar: true,
                    customClass: {
                        popup: 'animate__animated animate__fadeInDown'
                    }
                });
            }
        });
    </script>
</x-layout> 