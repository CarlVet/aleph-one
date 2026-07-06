<div class="text-center mt-2">
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public pools from all projects</p>
                </div>
                <a href="/my-projects"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif

    <div class="mt-2 flex items-center justify-center w-full relative mb-2">
        <!-- Icons Section (Right-Aligned) -->
        <div class="flex flex-col">
            <h2 class="text-xl font-bold mb-4 text-gray-700">Select content type:</h2>
            <div class="flex items-center space-x-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                <button wire:click="$set('selectedTable', 'pools_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view All Pools">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'pools_table' ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-layer-group text-3xl {{ $selectedTable === 'pools_table' ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'pools_table' ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}">All</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'pool_human_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Pools (Human content)">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'pool_human_table' ? 'bg-rose-100 ring-2 ring-rose-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-person text-3xl {{ $selectedTable === 'pool_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'pool_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}">Humans</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'pool_animal_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Pools (Animal content)">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'pool_animal_table' ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-paw text-3xl {{ $selectedTable === 'pool_animal_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'pool_animal_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Animals</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'pool_environment_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Pools (Environment content)">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'pool_environment_table' ? 'bg-emerald-100 ring-2 ring-emerald-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-leaf text-3xl {{ $selectedTable === 'pool_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'pool_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}">Environment</span>
                    </div>
                </button>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'pool_parasite_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Pools (Parasite content)">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['pool_parasite_table','pool_parasite_human_table','pool_parasite_animal_table','pool_parasite_environment_table']) ? 'bg-purple-100 ring-2 ring-purple-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-spider text-3xl {{ in_array($selectedTable, ['pool_parasite_table','pool_parasite_human_table','pool_parasite_animal_table','pool_parasite_environment_table']) ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['pool_parasite_table','pool_parasite_human_table','pool_parasite_animal_table','pool_parasite_environment_table']) ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}">Parasites</span>
                        </div>
                    </button>

                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'pool_parasite_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Parasite + Human</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_parasite_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Parasite + Animal</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_parasite_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Parasite + Environment</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'pool_nucleic_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Pools (Nucleic acid content)">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['pool_nucleic_table','pool_nucleic_human_table','pool_nucleic_animal_table','pool_nucleic_environment_table','pool_nucleic_parasite_table','pool_nucleic_culture_table','pool_nucleic_pool_table']) ? 'bg-indigo-100 ring-2 ring-indigo-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-dna text-3xl {{ in_array($selectedTable, ['pool_nucleic_table','pool_nucleic_human_table','pool_nucleic_animal_table','pool_nucleic_environment_table','pool_nucleic_parasite_table','pool_nucleic_culture_table','pool_nucleic_pool_table']) ? 'text-indigo-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['pool_nucleic_table','pool_nucleic_human_table','pool_nucleic_animal_table','pool_nucleic_environment_table','pool_nucleic_parasite_table','pool_nucleic_culture_table','pool_nucleic_pool_table']) ? 'text-indigo-600' : 'text-gray-500 group-hover:text-gray-600' }}">Nucleic Acids</span>
                        </div>
                    </button>

                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'pool_nucleic_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Nucleic + Human</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_nucleic_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Nucleic + Animal</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_nucleic_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Nucleic + Environment</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_nucleic_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">Nucleic + Parasite</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_nucleic_culture_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-bacteria text-sm text-orange-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-orange-600 transition-colors">Nucleic + Culture</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_nucleic_pool_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-layer-group text-sm text-cyan-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">Nucleic + Pool</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'pool_culture_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Pools (Culture content)">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['pool_culture_table','pool_culture_human_table','pool_culture_animal_table','pool_culture_environment_table','pool_culture_parasite_table','pool_culture_pool_table']) ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-bacteria text-3xl {{ in_array($selectedTable, ['pool_culture_table','pool_culture_human_table','pool_culture_animal_table','pool_culture_environment_table','pool_culture_parasite_table','pool_culture_pool_table']) ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['pool_culture_table','pool_culture_human_table','pool_culture_animal_table','pool_culture_environment_table','pool_culture_parasite_table','pool_culture_pool_table']) ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Cultures</span>
                        </div>
                    </button>

                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'pool_culture_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Culture + Human</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_culture_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Culture + Animal</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_culture_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Culture + Environment</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_culture_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">Culture + Parasite</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'pool_culture_pool_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-layer-group text-sm text-cyan-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">Culture + Pool</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $tableConfig = $tableConfig ?? $this->selectedTableConfig();
    @endphp

    @include('livewire.partials.pools-origin-table', [
        'poolTubes' => $poolTubes,
        'tableConfig' => $tableConfig,
        'isEditing' => $isEditing,
        'isGuestMode' => $isGuestMode,
        'canEdit' => $canEdit ?? true,
        'tubeCodes' => $tubeCodes ?? [],
        'animalCodes' => $animalCodes ?? [],
        'humanCodes' => $humanCodes ?? [],
        'environmentCodes' => $environmentCodes ?? [],
        'parasiteCodes' => $parasiteCodes ?? [],
        'nucleicCodes' => $nucleicCodes ?? [],
        'cultureCodes' => $cultureCodes ?? [],
        'poolCodes' => $poolCodes ?? [],
    ])
</div>