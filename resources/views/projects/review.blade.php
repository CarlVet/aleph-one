<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Review Project</h1>
            <p class="mt-2 text-sm text-gray-600">Please review all project information before final submission</p>
        </div>

        <form action="{{ route('projects.store') }}" method="POST" class="space-y-8">
            @csrf
            <input type="hidden" name="step" value="final">

            <!-- General Information -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                        <h2 class="text-xl font-semibold text-gray-800">General Information</h2>
                    </div>
                </div>
                <div class="p-8">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-sm text-gray-600">Project Code</p>
                            <p class="font-medium text-blue-600">{{ $nextCode ?? 'A1A1' }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Project Type</p>
                            <p class="font-medium">{{ $general['project_type'] }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Start Date</p>
                            <p class="font-medium">{{ \Carbon\Carbon::parse($general['start_date'])->format('d M Y') }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-600">Intended End Date</p>
                            <p class="font-medium">{{ \Carbon\Carbon::parse($general['intended_end_date'])->format('d M Y') }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600">Title</p>
                            <p class="font-medium">{{ $general['title'] }}</p>
                        </div>
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600">Description</p>
                            <p class="font-medium">{{ $general['description'] }}</p>
                        </div>
                        @if(isset($general['ethics_reference']))
                        <div class="md:col-span-2">
                            <p class="text-sm text-gray-600">Ethics Reference</p>
                            <p class="font-medium">{{ $general['ethics_reference'] }}</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Team Members -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-users text-blue-500 text-xl"></i>
                        <h2 class="text-xl font-semibold text-gray-800">Team Members</h2>
                    </div>
                </div>
                <div class="p-8">
                    <div class="space-y-6">
                        @foreach($team as $member)
                        <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div>
                                    <p class="text-sm text-gray-600">Team Member</p>
                                    <p class="font-medium">
                                        {{ $member['title'] }} {{ $member['first_name'] }} {{ $member['last_name'] }}
                                        <br><span class="text-sm text-gray-500">{{ $member['email'] }}</span>
                                    </p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Role</p>
                                    <p class="font-medium">{{ $member['role'] }}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-600">Date Joined</p>
                                    <p class="font-medium">{{ \Carbon\Carbon::parse($member['date_joined'])->format('d M Y') }}</p>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Funding Sources -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-money-bill-wave text-blue-500 text-xl"></i>
                        <h2 class="text-xl font-semibold text-gray-800">Funding Sources</h2>
                    </div>
                </div>
                <div class="p-8">
                    @if(count($funding) > 0)
                        <div class="space-y-6">
                            @foreach($funding as $source)
                            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <p class="text-sm text-gray-600">Source</p>
                                        <p class="font-medium">{{ $source['source'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Recipient</p>
                                        <p class="font-medium">
                                            @php
                                                $recipientId = $source['recipient_id'];
                                                $recipientName = '';
                                                
                                                // Check if it's a new team member reference (format: new_0, new_1, etc.)
                                                if (str_starts_with($recipientId, 'new_')) {
                                                    $teamIndex = (int) str_replace('new_', '', $recipientId);
                                                    if (isset($team[$teamIndex])) {
                                                        $teamMember = $team[$teamIndex];
                                                        $recipientName = $teamMember['title'] . ' ' . $teamMember['first_name'] . ' ' . $teamMember['last_name'];
                                                    } else {
                                                        $recipientName = 'Unknown Recipient';
                                                    }
                                                } else {
                                                    // It's an existing person ID
                                                    $person = \App\Models\People::find($recipientId);
                                                    if ($person) {
                                                        $recipientName = $person->first_name . ' ' . $person->last_name;
                                                    } else {
                                                        $recipientName = 'Unknown Recipient';
                                                    }
                                                }
                                            @endphp
                                            {{ $recipientName }}
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Amount</p>
                                        <p class="font-medium">{{ number_format($source['amount'], 2) }} {{ $source['currency'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Reference</p>
                                        <p class="font-medium">{{ $source['reference'] ?? 'N/A' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Start Date</p>
                                        <p class="font-medium">{{ \Carbon\Carbon::parse($source['start_date'])->format('d M Y') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">End Date</p>
                                        <p class="font-medium">{{ \Carbon\Carbon::parse($source['end_date'])->format('d M Y') }}</p>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-info-circle text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No funding sources added</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Documents -->
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="px-8 py-6 border-b border-gray-100">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-file-alt text-blue-500 text-xl"></i>
                        <h2 class="text-xl font-semibold text-gray-800">Documents</h2>
                    </div>
                </div>
                <div class="p-8">
                    @if(count($documents) > 0)
                        <div class="space-y-6">
                            @foreach($documents as $document)
                            <div class="bg-gray-50 rounded-lg p-6 border border-gray-200">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <p class="text-sm text-gray-600">Title</p>
                                        <p class="font-medium">{{ $document['title'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Type</p>
                                        <p class="font-medium">{{ $document['type'] }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">Document Date</p>
                                        <p class="font-medium">{{ \Carbon\Carbon::parse($document['document_date'])->format('d M Y') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-600">File</p>
                                        <p class="font-medium">
                                            @if(isset($document['file_info']) && $document['file_info'])
                                                {{ $document['file_info']['original_name'] }}
                                            @else
                                                No file uploaded
                                            @endif
                                        </p>
                                    </div>
                                    @if(isset($document['description']))
                                    <div class="md:col-span-2">
                                        <p class="text-sm text-gray-600">Description</p>
                                        <p class="font-medium">{{ $document['description'] }}</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8">
                            <i class="fas fa-file-alt text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No documents added</p>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-between rounded-xl border border-gray-200">
                <a href="{{ route('projects.create', ['step' => 4]) }}" 
                   class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-arrow-left mr-2 text-lg group-hover:-translate-x-1 transition-transform duration-300"></i>
                    Back
                </a>

                <button type="submit"
                    class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i class="fas fa-check-circle mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Create Project
                </button>
            </div>
        </form>
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