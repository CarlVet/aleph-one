<div>
    @if($showModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="modal">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Request Tube Access</h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    @if($tube)
                        <div class="mb-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Tube Details</h4>
                                <p class="text-sm text-gray-600"><strong>Code:</strong> {{ $tube->code }}</p>
                                <p class="text-sm text-gray-600"><strong>Type:</strong> {{ $tube->tube_type }}</p>
                                <p class="text-sm text-gray-600"><strong>Source Project:</strong> {{ $sourceProject->code ?? 'N/A' }}</p>
                                @if($principalInvestigator)
                                    <p class="text-sm text-gray-600">
                                        <strong>Principal Investigator:</strong> 
                                        {{ $principalInvestigator->first_name }} {{ $principalInvestigator->last_name }}
                                    </p>
                                @endif
                            </div>
                        </div>

                        <form wire:submit.prevent="submitRequest">
                            <div class="mb-4">
                                <label for="targetProjectId" class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Target Project *
                                </label>
                                <select 
                                    wire:model="targetProjectId"
                                    id="targetProjectId"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    required
                                >
                                    <option value="">Choose a project...</option>
                                    @foreach($userProjects as $project)
                                        <option value="{{ $project->id }}">
                                            {{ $project->code }} - {{ $project->title }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('targetProjectId') 
                                    <span class="text-red-500 text-xs">{{ $message }}</span> 
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="requestMessage" class="block text-sm font-medium text-gray-700 mb-2">
                                    Request Message (Optional)
                                </label>
                                <textarea 
                                    wire:model="requestMessage"
                                    id="requestMessage"
                                    rows="3"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Explain why you need access to this tube..."
                                ></textarea>
                                @error('requestMessage') 
                                    <span class="text-red-500 text-xs">{{ $message }}</span> 
                                @enderror
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button 
                                    type="button"
                                    wire:click="closeModal"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200"
                                >
                                    Cancel
                                </button>
                                <button 
                                    type="submit"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-200"
                                >
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center">
                            <p class="text-gray-500">Tube not found.</p>
                            <button 
                                wire:click="closeModal"
                                class="mt-3 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200"
                            >
                                Close
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div>
