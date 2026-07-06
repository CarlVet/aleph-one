<div class="container mx-auto px-4 py-8">
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-6">
            <i class="fas fa-handshake text-indigo-600 mr-2"></i>
            My Tube Requests
        </h2>
        @if($requests->total() > 0)
            @include('livewire.partials.index-per-page-toolbar', ['paginator' => $requests])

            <div class="index-table-container overflow-x-auto">
                <table class="index-data-table min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Request Details</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tube Information</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($requests as $request)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div class="font-medium">Request #{{ $request->id }}</div>
                                        <div class="text-gray-500">{{ $request->created_at->format('M d, Y H:i') }}</div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <div class="font-medium">{{ $request->tube->code }}</div>
                                        <div class="text-gray-500">{{ $request->tube->tube_type }}</div>
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
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @include('livewire.partials.index-pagination-bar', ['paginator' => $requests])
        @else
            <div class="text-center py-12">
                <i class="fas fa-inbox text-4xl text-gray-400 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Tube Requests</h3>
                <p class="text-gray-500">You have not made any tube requests yet.</p>
            </div>
        @endif
    </div>
</div>
