<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-red-900 to-red-800 rounded-t-xl shadow-lg">
            <div class="px-6 py-8">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="bg-white/20 p-3 rounded-lg">
                                <i class="fa-solid fa-virus text-2xl text-white"></i>
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">Pathogen Details</h1>
                                <p class="text-red-100 text-lg">Species: {{ $pathogen->species }}</p>
                            </div>
                        </div>

                        <!-- Status Badge -->
                        <div class="flex items-center space-x-4">
                            <span class="text-red-100 text-sm">
                                ID: {{ $pathogen->id }} • {{ $pathogen->genus ?? 'N/A' }}
                            </span>
                        </div>
                    </div>

                    <div class="flex space-x-3">
                        <a href="/pathogens/list"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to List
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white shadow-lg rounded-b-xl">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 p-8">

                <!-- Left Column - Main Details -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Pathogen Information Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-red-100 p-2 rounded-lg mr-3">
                                <i class="fa-solid fa-virus text-2xl text-red-600"></i>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Pathogen Information</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Species</dt>
                                <dd class="text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                        {{ $pathogen->species }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Genus</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $pathogen->genus ?? 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Family</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $pathogen->family ?? 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Type</dt>
                                <dd class="text-sm text-gray-900">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        {{ $pathogen->type === 'Virus' ? 'bg-purple-100 text-purple-800' : 
                                           ($pathogen->type === 'Bacteria' ? 'bg-blue-100 text-blue-800' : 
                                           'bg-gray-100 text-gray-800') }}">
                                        {{ $pathogen->type ?? 'N/A' }}
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Notes</dt>
                                <dd class="text-sm text-gray-900">
                                    {{ $pathogen->notes ?? 'No notes available' }}
                                </dd>
                            </div>
                        </div>
                    </div>

                    <!-- Associated Protocols Section -->
                    @if($pathogen->protocols->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Associated Protocols</h2>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @foreach($pathogen->protocols as $protocol)
                                <a href="/protocols/{{ $protocol->id }}" 
                                   class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-indigo-100 text-indigo-800 hover:bg-indigo-200 transition-colors duration-200">
                                    {{ $protocol->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Associated Experiments Section -->
                    @if($pathogen->experiments->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Associated Experiments</h2>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Experiment Code
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Content Type
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Outcome
                                        </th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date Tested
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($pathogen->experiments as $experiment)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="/experiments/{{ $experiment->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                {{ $experiment->code }}
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ class_basename($experiment->experiments_content_type) ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $experiment->outcome_discrete === 'Positive' ? 'bg-green-100 text-green-800' : 
                                                   ($experiment->outcome_discrete === 'Negative' ? 'bg-red-100 text-red-800' : 
                                                   'bg-gray-100 text-gray-800') }}">
                                                {{ $experiment->outcome_discrete ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ $experiment->date_tested ? \Carbon\Carbon::parse($experiment->date_tested)->format('M d, Y') : 'N/A' }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Column - Sidebar -->
                <div class="space-y-6">

                    <!-- Quick Actions -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 100 4m0-4v2m0-6V4">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                        </div>

                        <div class="space-y-3">
                            <button
                                class="w-full flex items-center justify-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                Edit Pathogen
                            </button>
                            <button
                                class="w-full flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                Export Data
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 