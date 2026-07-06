<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h2 class="text-2xl font-bold text-gray-900">
                <i class="fas fa-handshake text-blue-600 mr-2"></i>
                Tube Requests
            </h2>
        </div>

        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-6">
            <nav class="-mb-px flex space-x-8">
                @if($isPI)
                    <button 
                        wire:click="setTab('incoming')"
                        class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'incoming' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                    >
                        <i class="fas fa-inbox mr-2"></i>
                        Incoming Requests
                        @if($incomingRequests->where('status', 'pending')->count() > 0)
                            <span class="ml-2 bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                                {{ $incomingRequests->where('status', 'pending')->count() }}
                            </span>
                        @endif
                    </button>
                @endif
                <button 
                    wire:click="setTab('outgoing')"
                    class="py-2 px-1 border-b-2 font-medium text-sm {{ $activeTab === 'outgoing' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}"
                >
                    <i class="fas fa-paper-plane mr-2"></i>
                    My Requests
                </button>
            </nav>
        </div>

        <!-- Incoming Requests Table -->
        @if($isPI && $activeTab === 'incoming')
            @if($incomingRequests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tube Information</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Requester</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Original Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Original PI</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($incomingRequests as $request)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">Request #{{ $request->id }}</div>
                                            <div class="text-gray-500">{{ $request->created_at->format('M d, Y H:i') }}</div>
                                            @if($request->request_message)
                                                <div class="mt-2 text-xs text-gray-600">
                                                    <strong>Message:</strong> {{ $request->request_message }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">{{ $request->tube->code }}</div>
                                            <div class="text-gray-500">{{ $request->tube->tube_type }}</div>
                                            <div class="text-xs text-gray-400">From: {{ $request->sourceProject->code }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">
                                                {{ $request->requester->first_name }} {{ $request->requester->last_name }}
                                            </div>
                                            <div class="text-gray-500">{{ $request->requester->email ?? 'N/A' }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">{{ $request->sourceProject->code }}</div>
                                            <div class="text-gray-500">{{ Str::limit($request->sourceProject->title, 50) }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            @php
                                                $principalInvestigator = $request->sourceProject->people()
                                                    ->wherePivot('role', 'Principal Investigator')
                                                    ->first();
                                            @endphp
                                            @if($principalInvestigator)
                                                <div class="font-medium">
                                                    {{ $principalInvestigator->first_name }} {{ $principalInvestigator->last_name }}
                                                </div>
                                                <div class="text-gray-500">{{ $principalInvestigator->email ?? 'N/A' }}</div>
                                            @else
                                                <div class="text-gray-500 italic">No PI assigned</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">{{ $request->targetProject->code }}</div>
                                            <div class="text-gray-500">{{ Str::limit($request->targetProject->title, 50) }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($request->status === 'pending')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i> Pending
                                            </span>
                                        @elseif($request->status === 'approved')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i> Approved
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-times mr-1"></i> Rejected
                                            </span>
                                        @endif
                                        @if($request->responded_at)
                                            <div class="text-xs text-gray-500 mt-1">{{ $request->responded_at->format('M d, Y H:i') }}</div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        @if($request->status === 'pending')
                                            <div class="flex space-x-2">
                                                <button 
                                                    wire:click="$set('selectedRequestId', {{ $request->id }})"
                                                    class="text-green-600 hover:text-green-900 transition-colors duration-200"
                                                    title="Approve Request">
                                                    <i class="fas fa-check-circle text-lg"></i>
                                                </button>
                                                <button 
                                                    wire:click="$set('selectedRequestId', {{ $request->id }})"
                                                    class="text-red-600 hover:text-red-900 transition-colors duration-200"
                                                    title="Reject Request">
                                                    <i class="fas fa-times-circle text-lg"></i>
                                                </button>
                                            </div>
                                        @else
                                            @if($request->response_message)
                                                <div class="text-xs text-gray-600">
                                                    <strong>Response:</strong> {{ $request->response_message }}
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6">{{ $incomingRequests->links() }}</div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Incoming Requests</h3>
                    <p class="text-gray-500">You don't have any pending tube requests to review.</p>
                </div>
            @endif
        @endif

        <!-- Outgoing Requests Table -->
        @if($activeTab === 'outgoing')
            @if($outgoingRequests->count() > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Details</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tube Information</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Original Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Original PI</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Target Project</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @foreach($outgoingRequests as $request)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">Request #{{ $request->id }}</div>
                                            <div class="text-gray-500">{{ $request->created_at->format('M d, Y H:i') }}</div>
                                            @if($request->request_message)
                                                <div class="mt-2 text-xs text-gray-600">
                                                    <strong>Your Message:</strong> {{ $request->request_message }}
                                                </div>
                                            @endif
                                            @if($request->response_message)
                                                <div class="mt-2 text-xs text-gray-600">
                                                    <strong>Response:</strong> {{ $request->response_message }}
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">{{ $request->tube->code }}</div>
                                            <div class="text-gray-500">{{ $request->tube->tube_type }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">{{ $request->sourceProject->code }}</div>
                                            <div class="text-gray-500">{{ Str::limit($request->sourceProject->title, 50) }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            @php
                                                $principalInvestigator = $request->sourceProject->people()
                                                    ->wherePivot('role', 'Principal Investigator')
                                                    ->first();
                                            @endphp
                                            @if($principalInvestigator)
                                                <div class="font-medium">
                                                    {{ $principalInvestigator->first_name }} {{ $principalInvestigator->last_name }}
                                                </div>
                                                <div class="text-gray-500">{{ $principalInvestigator->email ?? 'N/A' }}</div>
                                            @else
                                                <div class="text-gray-500 italic">No PI assigned</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <div class="font-medium">{{ $request->targetProject->code }}</div>
                                            <div class="text-gray-500">{{ Str::limit($request->targetProject->title, 50) }}</div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if($request->status === 'pending')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i> Pending
                                            </span>
                                        @elseif($request->status === 'approved')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i> Approved
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                <i class="fas fa-times mr-1"></i> Rejected
                                            </span>
                                        @endif
                                        @if($request->responded_at)
                                            <div class="text-xs text-gray-500 mt-1">{{ $request->responded_at->format('M d, Y H:i') }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="mt-6">{{ $outgoingRequests->links() }}</div>
            @else
                <div class="text-center py-12">
                    <i class="fas fa-paper-plane text-4xl text-gray-400 mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Outgoing Requests</h3>
                    <p class="text-gray-500">You haven't made any tube requests yet.</p>
                    <div class="mt-4">
                        <a href="/guest/animal-samples" 
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors duration-200">
                            <i class="fas fa-search mr-2"></i>
                            Browse Public Tubes
                        </a>
                    </div>
                </div>
            @endif
        @endif
    </div>

    <!-- Response Modal -->
    @if($selectedRequestId && $activeTab === 'incoming')
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="responseModal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Respond to Request</h3>
                        <button wire:click="$set('selectedRequestId', null)" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    <div class="mb-4">
                        <label for="responseMessage" class="block text-sm font-medium text-gray-700 mb-2">
                            Response Message (Optional)
                        </label>
                        <textarea 
                            wire:model="responseMessage"
                            id="responseMessage"
                            rows="3"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Add a response message...">
                        </textarea>
                        @error('responseMessage') 
                            <span class="text-red-500 text-xs">{{ $message }}</span> 
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-3">
                        <button 
                            wire:click="$set('selectedRequestId', null)"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                            Cancel
                        </button>
                        <button 
                            wire:click="rejectRequest({{ $selectedRequestId }})"
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-red-600 rounded-lg hover:bg-red-700 transition-colors duration-200">
                            Reject
                        </button>
                        <button 
                            wire:click="approveRequest({{ $selectedRequestId }})"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-green-600 rounded-lg hover:bg-green-700 transition-colors duration-200">
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
