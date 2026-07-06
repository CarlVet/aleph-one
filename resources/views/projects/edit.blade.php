<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <!-- Progress Steps -->
        <div class="mb-8">
            <div class="flex items-center justify-between max-w-4xl mx-auto">
                @foreach($sections as $key => $title)
                <a href="{{ route('projects.edit', ['project' => $project->id, 'section' => $key]) }}">
                    <div class="flex flex-col items-center">
                        <div class="rounded-full h-12 w-12 flex items-center justify-center {{ $section === $key ? 'bg-blue-500 text-white' : 'bg-gray-200 text-gray-500' }}">
                            @switch($key)
                                @case('general')
                                <i class="fas fa-info-circle text-xl"></i>
                                    @break
                                @case('team')
                                    <i class="fas fa-users text-xl"></i>
                                    @break
                                @case('funding')
                                    <i class="fas fa-money-bill-wave text-xl"></i>
                                    @break
                                @case('documents')
                                    <i class="fas fa-file-alt text-xl"></i>
                                    @break
                            @endswitch
                        </div>
                        <span class="mt-2 text-xs font-medium text-gray-500">{{ $title }}</span>
                    </div>
                </a>
                @endforeach
            </div>

            <!-- Progress Bar -->
            <div class="max-w-4xl mx-auto mt-4">
                <div class="relative">
                    <div class="overflow-hidden h-2 text-xs flex rounded bg-gray-200">
                        <div class="transition-all duration-500 ease-out shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center bg-blue-500"
                            style="width: {{ (array_search($section, array_keys($sections)) + 1) / count($sections) * 100 }}%">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Content -->
        <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
            @if($section === 'general')
                @include('projects.steps.general')
            @elseif($section === 'team')
                @include('projects.steps.team')
            @elseif($section === 'funding')
                @include('projects.steps.funding')
            @elseif($section === 'documents')
                @include('projects.steps.documents')
            @endif
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