<div class="mt-2 md:col-span-2 md:mt-0">
    @if(!$canView)
        <!-- Unauthorized Access Message -->
        <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-2xl p-8 shadow-lg">
            <div class="flex items-center justify-center">
                <div class="text-center max-w-md">
                    <div class="bg-red-100 p-4 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6 shadow-inner">
                        <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h2 class="text-2xl font-bold text-red-900 mb-3">Access Denied</h2>
                    <p class="text-red-700 text-lg mb-6 leading-relaxed">{{ $unauthorizedMessage }}</p>
                    <a href="/bank/tubes/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Back to Tube Positions List
                    </a>
                </div>
            </div>
        </div>
    @else
        <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
            <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                <div class="text-center mb-6">
                    <h2 class="text-2xl font-bold text-gray-800">Box Grid</h2>
                    <p class="mt-2 text-sm text-gray-600">Code: {{ $box->code }}</p>
                    <p class="mt-2 text-sm text-gray-600">Name: {{ $box->name }}</p>
                    <p class="mt-2 text-sm text-gray-600">Content Type: {{ $this->boxContentType }}</p>
                    <p class="mt-1 text-sm text-gray-500">Dimensions: {{ $nRows }}x{{ $nColumns }} ({{ $nRows * $nColumns }} positions)</p>

                    @php
                        $latestBoxPosition = $box->latest_box_position ?? null;
                        $latestLocation = $latestBoxPosition?->locations;
                        $latestLab = $latestLocation?->laboratories;
                        $latestCountry = $latestLab?->countries;
                    @endphp

                    <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3 text-sm">
                        <div class="bg-gray-50 rounded-lg border border-gray-200 px-4 py-3 text-left">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Last moved location</div>
                            <div class="mt-1 text-gray-800">
                                <div><span class="font-medium">Type:</span> {{ $latestLocation?->type ?? 'N/A' }}</div>
                                <div><span class="font-medium">Location:</span> {{ $latestLocation?->name ?? 'N/A' }}</div>
                                <div><span class="font-medium">Sub-location:</span> {{ $latestBoxPosition?->sublocation ?? 'N/A' }}</div>
                                <div><span class="font-medium">Room:</span> {{ $latestLocation?->room ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="bg-gray-50 rounded-lg border border-gray-200 px-4 py-3 text-left">
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Laboratory</div>
                            <div class="mt-1 text-gray-800">
                                <div><span class="font-medium">Lab:</span> {{ $latestLab?->name ?? 'N/A' }}</div>
                                <div><span class="font-medium">Facility (country):</span> {{ $latestCountry?->name ? ($latestLab?->name ? $latestLab->name . ' (' . $latestCountry->name . ')' : $latestCountry->name) : ($latestLab?->name ?? 'N/A') }}</div>
                                <div><span class="font-medium">Date moved:</span> {{ $latestBoxPosition?->date_moved ?? 'N/A' }}</div>
                            </div>
                        </div>
                    </div>
                    
                    @if(!$canEdit)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 mt-2">
                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            View Only
                        </span>
                    @endif
                    
                    @if($canEdit)
                        @include('livewire.partials.export-buttons')
                        <button 
                            onclick="window.dispatchEvent(new CustomEvent('confirm-delete-box'))"
                            class="group relative inline-flex items-center justify-center mt-4 ml-2 px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-red-500 to-red-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-red-600">
                            <i class="fas fa-trash mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                            Delete Box
                        </button>
                    @endif
                </div>

                <div class="flex items-center justify-center gap-6 mb-6">
                    <div class="text-sm text-gray-600 font-medium">Show tube codes as:</div>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" wire:model.live="tubeCodeDisplay" value="tube" class="text-blue-600 focus:ring-blue-500">
                        Tube code
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="radio" wire:model.live="tubeCodeDisplay" value="alias" class="text-blue-600 focus:ring-blue-500">
                        Alias code
                    </label>
                </div>

                <!-- Box Grid Visualization -->
                <div class="space-y-4">

                    <div class="overflow-x-auto">
                        <div class="inline-block min-w-full">
                            <table class="min-w-full border-collapse border border-gray-300 rounded-lg overflow-hidden shadow-lg">
                                <thead>
                                    <tr class="bg-gradient-to-r from-gray-100 to-gray-200">
                                        <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 bg-gray-50"></th>
                                        @for ($x = 1; $x <= $nColumns; $x++)
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700">
                                                <div class="flex flex-col items-center">
                                                    <span class="text-sm font-bold">Col {{ $x }}</span>
                                                </div>
                                            </th>
                                        @endfor
                                    </tr>
                                </thead>
                                <tbody>
                                    @for ($y = 1; $y <= $nRows; $y++)
                                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                                            <th class="border border-gray-300 px-4 py-3 text-center font-semibold text-gray-700 bg-gray-50">
                                                <div class="flex flex-col items-center">
                                                    <span class="text-sm font-bold">Row {{ $y }}</span>
                                                </div>
                                            </th>
                                            @for ($x = 1; $x <= $nColumns; $x++)
                                                @php
                                                    $key = "{$x},{$y}";
                                                    $tubeData = $tubePositions[$key] ?? null;
                                                    $position = "{$x},{$y}";
                                                @endphp
                                                                                                 <td class="border border-gray-300 px-2 py-2 text-center relative group {{ $canEdit ? 'cursor-pointer hover:bg-blue-50' : '' }} transition-all duration-200"
                                                     @if($canEdit) wire:click="selectPosition('{{ $position }}')" @endif
                                                     data-position="{{ $position }}"
                                                     data-tube="{{ $tubeData['code'] ?? '' }}">
                                                    @if ($tubeData && isset($tubeData['code']))
                                                        @php
                                                            $displayCode = $tubeCodeDisplay === 'alias'
                                                                ? ($tubeData['alias_code'] ?? null)
                                                                : ($tubeData['code'] ?? null);
                                                            $displayCode = $displayCode ?: ($tubeData['code'] ?? 'N/A');
                                                        @endphp
                                                        <div class="bg-gradient-to-br {{ $tubeData['color'] }} border-2 border-white rounded-md px-2 py-1 text-xs font-bold text-white shadow-sm hover:shadow-md transition-all duration-200 inline-flex items-center gap-2">
                                                            <span>{{ $displayCode }}</span>
                                                            <a href="/bank/tubes/{{ $tubeData['code'] }}"
                                                                onclick="event.stopPropagation();"
                                                                class="text-white/90 hover:text-white underline underline-offset-2"
                                                                title="Open tube profile">
                                                                <i class="fas fa-arrow-up-right-from-square"></i>
                                                            </a>
                                                        </div>
                                                        <div class="absolute inset-0 bg-blue-500 opacity-0 group-hover:opacity-10 transition-opacity duration-200 rounded"></div>
                                                    @else
                                                        <div class="text-gray-400 text-xs font-medium hover:text-gray-600 transition-colors duration-200">
                                                            {{ $x }},{{ $y }}
                                                        </div>
                                                        <div class="absolute inset-0 bg-gray-200 opacity-0 group-hover:opacity-20 transition-opacity duration-200 rounded"></div>
                                                    @endif
                                                </td>
                                            @endfor
                                        </tr>
                                    @endfor
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Position Info -->
                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 rounded-lg p-4 border border-blue-200">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-blue-500 mt-1 mr-3"></i>
                            <div>
                                <h3 class="text-sm font-medium text-blue-800 mb-1">Interactive Grid</h3>
                                <p class="text-sm text-blue-700">
                                    Click on any cell to edit the tube position. Empty cells show position coordinates.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                <a href="/bank/tubes/list" 
                    class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-arrow-left mr-2 text-lg group-hover:-translate-x-1 transition-transform duration-300"></i>
                    Back to Tube Positions List
                </a>
            </div>
        </div>

        <!-- Tube Selection Modal -->
        @if($showTubeModal)
        <div class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="tubeModal">
            <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Select Tube for Position {{ $selectedPosition }}</h3>
                        <button wire:click="closeTubeModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <div class="space-y-4">
                        <!-- Tube Type Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select sample origin:</label>
                            <select wire:model.live="selectedTubeType" class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                <option value="">Select sample origin</option>
                                <option value="human">Human samples</option>
                                <option value="animal">Animal samples</option>
                                <option value="environment">Environmental samples</option>
                                <option value="parasite">Parasite samples</option>
                                <option value="nucleic">Nucleic acids</option>
                                <option value="culture">Cultures</option>
                                <option value="pool">Pools</option>
                            </select>
                        </div>

                        <!-- Tube Selection -->
                        @if($selectedTubeType)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select tube:</label>
                            <input list="tubes" wire:model.live="selectedTubeCode" placeholder="Type to search tubes..." class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <datalist id="tubes">
                                @foreach($availableTubes as $tube)
                                    @php
                                        $tubeOptionValue = $tubeCodeDisplay === 'alias' && filled($tube->alias_code)
                                            ? $tube->alias_code
                                            : $tube->code;
                                    @endphp
                                    <option value="{{ $tubeOptionValue }}" data-id="{{ $tube->id }}">
                                        @if($tubeCodeDisplay === 'alias' && filled($tube->alias_code) && $tube->alias_code !== $tube->code)
                                            ({{ $tube->code }})
                                        @endif
                                    </option>
                                @endforeach
                            </datalist>
                        </div>
                        @endif

                        <!-- Current Tube Info -->
                        @if($currentTube)
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mt-1 mr-2"></i>
                                <div>
                                    <p class="text-sm text-yellow-800">
                                        <strong>Current tube:</strong> {{ $currentTube->code }}
                                        @if(filled($currentTube->alias_code))
                                            <span class="text-yellow-700"> (alias: {{ $currentTube->alias_code }})</span>
                                        @endif
                                    </p>
                                    <p class="text-xs text-yellow-700 mt-1">
                                        Selecting a new tube will replace the current one.
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                                         <div class="flex justify-end space-x-3 mt-6">
                         <button wire:click="closeTubeModal" 
                             class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                             Cancel
                         </button>
                         @if($canEdit)
                             @if($currentTube)
                             <button 
                                 onclick="window.dispatchEvent(new CustomEvent('confirm-remove-tube'))"
                                 class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-red-600 rounded-lg hover:bg-red-700 transition-colors duration-200">
                                 Remove Position
                             </button>
                             <button 
                                 onclick="window.dispatchEvent(new CustomEvent('confirm-delete-tube'))"
                                 class="px-4 py-2 text-sm font-medium text-white bg-gray-800 border border-gray-800 rounded-lg hover:bg-black transition-colors duration-200">
                                 Trash/Deplete Tube
                             </button>
                             @endif
                             @if($selectedTubeCode)
                             <button wire:click="updateTubePosition" 
                                     wire:loading.attr="disabled"
                                     class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-200 flex items-center">
                                 <span wire:loading.remove wire:target="updateTubePosition">Update Position</span>
                                 <span wire:loading wire:target="updateTubePosition">Updating...</span>
                             </button>
                             @endif
                         @endif
                     </div>
                </div>
            </div>
        </div>
        @endif

        <script>
            document.addEventListener('livewire:initialized', () => {
                @this.on('show-message', (event) => {
                    const type = event[0].type;
                    const message = event[0].message;

                    Swal.fire({
                        icon: type,
                        title: type.charAt(0).toUpperCase() + type.slice(1),
                        text: message,
                    });
                });
            });

            // Close modal when clicking outside
            document.addEventListener('click', function(event) {
                const modal = document.getElementById('tubeModal');
                if (event.target === modal) {
                    @this.closeTubeModal();
                }
            });

            // Handle datalist selection
            document.addEventListener('input', function(event) {
                if (event.target.list && event.target.list.id === 'tubes') {
                    const datalist = event.target.list;
                    const value = event.target.value;
                    
                    // Find the matching option
                    for (let option of datalist.options) {
                        if (option.value === value) {
                            // Trigger Livewire update
                            @this.set('selectedTubeCode', value);
                            break;
                        }
                    }
                }
            });

            // Confirmation dialogs for remove and delete actions
            window.addEventListener('confirm-remove-tube', function() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Remove Tube Position',
                    text: 'Are you sure you want to remove the latest position of the tube? The tube position will be reverted to its former position (if any).',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, remove position',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.removeTubeFromPosition();
                    }
                });
            });

            window.addEventListener('confirm-delete-tube', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Delete Tube',
                    text: 'Are you sure you want to permanently delete this tube? The tube will be removed from all positions and cannot be recovered. You will not be able to perform any analysis on this tube anymore.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete tube',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.deleteTube();
                    }
                });
            });

            window.addEventListener('confirm-delete-box', function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Delete Box',
                    text: 'Are you sure you want to permanently delete this box? All tube positions in this box will be deleted. This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonText: 'Yes, delete box',
                    cancelButtonText: 'Cancel'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.deleteBox();
                    }
                });
            });
        </script>
    </div>
    @endif
</div>
