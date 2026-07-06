<div class="text-center mt-2">
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public experiments from all projects</p>
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
            @if (!$isGuestMode)
                <!-- Left Arrow Home Link -->
                <a href="/experiments"
                    class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                    <i class="fas fa-arrow-left text-2xl mr-2"></i>
                    <span class="text-sm font-medium">Back to EX Home</span>
                </a>
            @endif
            <h2 class="text-xl font-bold mb-4 text-gray-700">Select content type:</h2>
            <div class="flex items-center space-x-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                <button wire:click="$set('selectedTable', 'experiments_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view All Experiments">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'experiments_table' ? 'bg-blue-100 ring-2 ring-blue-900' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-flask text-3xl {{ $selectedTable === 'experiments_table' ? 'text-blue-900' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'experiments_table' ? 'text-blue-900' : 'text-gray-500 group-hover:text-gray-600' }}">All
                            experiments</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'experiment_human_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Human Experiments">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'experiment_human_table' ? 'bg-rose-100 ring-2 ring-rose-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-person text-3xl {{ $selectedTable === 'experiment_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'experiment_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}">Humans</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'experiment_animal_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Animal Experiments">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'experiment_animal_table' ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-paw text-3xl {{ $selectedTable === 'experiment_animal_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'experiment_animal_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Animals</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'experiment_environment_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Environment Experiments">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'experiment_environment_table' ? 'bg-emerald-100 ring-2 ring-emerald-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-leaf text-3xl {{ $selectedTable === 'experiment_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'experiment_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}">Environment</span>
                    </div>
                </button>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'experiment_parasite_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Parasite Experiments">
                        <div class="flex flex-col items-center">
                            <div
                                class="p-3 rounded-full {{ in_array($selectedTable, ['experiment_parasite_table', 'experiment_parasite_human_table', 'experiment_parasite_animal_table', 'experiment_parasite_environment_table']) ? 'bg-purple-100 ring-2 ring-purple-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i
                                    class="fa-solid fa-spider text-3xl {{ in_array($selectedTable, ['experiment_parasite_table', 'experiment_parasite_human_table', 'experiment_parasite_animal_table', 'experiment_parasite_environment_table']) ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span
                                class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['experiment_parasite_table', 'experiment_parasite_human_table', 'experiment_parasite_animal_table', 'experiment_parasite_environment_table']) ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}">Parasites</span>
                        </div>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'experiment_parasite_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Parasite Human Experiments">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Parasite + Human</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_parasite_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Parasite Animal Experiments">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Parasite +
                                    Animal</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_parasite_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Parasite Environment Experiments">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Parasite +
                                    Environment</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'experiment_nucleic_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Nucleic Acid Experiments">
                        <div class="flex flex-col items-center">
                            <div
                                class="p-3 rounded-full {{ in_array($selectedTable, ['experiment_nucleic_table', 'experiment_nucleic_animal_table', 'experiment_nucleic_human_table', 'experiment_nucleic_environment_table', 'experiment_nucleic_culture_table', 'experiment_nucleic_pool_table']) ? 'bg-indigo-100 ring-2 ring-indigo-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i
                                    class="fa-solid fa-dna text-3xl {{ in_array($selectedTable, ['experiment_nucleic_table', 'experiment_nucleic_animal_table', 'experiment_nucleic_human_table', 'experiment_nucleic_environment_table', 'experiment_nucleic_culture_table', 'experiment_nucleic_pool_table']) ? 'text-indigo-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span
                                class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['experiment_nucleic_table', 'experiment_nucleic_animal_table', 'experiment_nucleic_human_table', 'experiment_nucleic_environment_table', 'experiment_nucleic_culture_table', 'experiment_nucleic_pool_table']) ? 'text-indigo-600' : 'text-gray-500 group-hover:text-gray-600' }}">Nucleic
                                Acids</span>
                        </div>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'experiment_nucleic_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Nucleic Human Experiments">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Nucleic + Human</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_nucleic_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Nucleic Animal Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i
                                        class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Nucleic + Animal</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_nucleic_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Nucleic Environment Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i
                                        class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Nucleic +
                                    Environment</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_nucleic_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Nucleic Parasite Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i
                                        class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">Nucleic +
                                    Parasite</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_nucleic_culture_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Nucleic Culture Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i
                                        class="fa-solid fa-bacteria text-sm text-orange-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-orange-600 transition-colors">Nucleic +
                                    Culture</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_nucleic_pool_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Nucleic Pool Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i
                                        class="fa-solid fa-layer-group text-sm text-cyan-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">Nucleic + Pool</span>
                            </button>

                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'experiment_culture_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Culture Experiments">
                        <div class="flex flex-col items-center">
                            <div
                                class="p-3 rounded-full {{ in_array($selectedTable, ['experiment_culture_table']) ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i
                                    class="fa-solid fa-bacteria text-3xl {{ in_array($selectedTable, ['experiment_culture_table']) ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span
                                class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['experiment_culture_table']) ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Cultures</span>
                        </div>
                    </button>

                    <!-- Culture Dropdown Menu -->
                    <div
                        class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">

                            <button wire:click="$set('selectedTable', 'experiment_culture_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Culture Human Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-3px]"></i>
                                    <i
                                        class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1 transform translate-x-[3px]"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Culture + Human</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_culture_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Culture Animal Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-4px]"></i>
                                    <i
                                        class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1 transform translate-x-[4px]"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Culture +
                                    Animal</span>
                            </button>



                            <button wire:click="$set('selectedTable', 'experiment_culture_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Culture Environment Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-4px]"></i>
                                    <i
                                        class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1 transform translate-x-[4px]"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Culture +
                                    Environment</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_culture_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Culture Parasite Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-4px]"></i>
                                    <i
                                        class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1 transform translate-x-[4px]"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">Culture +
                                    Parasite</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_culture_pool_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Culture Pool Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-4px]"></i>
                                    <i
                                        class="fa-solid fa-layer-group text-sm text-cyan-600 absolute -bottom-1 -right-1 transform translate-x-[4px]"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">Culture + Pool</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'experiment_pool_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Pool Experiments">
                        <div class="flex flex-col items-center">
                            <div
                                class="p-3 rounded-full {{ in_array($selectedTable, ['experiment_pool_table']) ? 'bg-cyan-100 ring-2 ring-cyan-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i
                                    class="fa-solid fa-layer-group text-3xl {{ in_array($selectedTable, ['experiment_pool_table']) ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span
                                class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['experiment_pool_table']) ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}">Pools</span>
                        </div>
                    </button>

                    <!-- Pool Dropdown Menu -->
                    <div
                        class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">

                            <button wire:click="$set('selectedTable', 'experiment_pool_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool Human Experiments">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i
                                        class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">Pool + Human</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_pool_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool Animal Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i
                                        class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">Pool + Animal</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_pool_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool Environment Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i
                                        class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">Pool +
                                    Environment</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_pool_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool Parasite Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i
                                        class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">Pool + Parasite</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'experiment_pool_nucleic_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool Nucleic Experiments">
                                <div class="relative w-6 h-6">
                                    <i
                                        class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i
                                        class="fa-solid fa-dna text-sm text-blue-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">Pool + Nucleic</span>
                            </button>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    @if ($selectedTable === 'experiments_table')
        <!-- Create, Edit, Dashboard (Centered) -->
        <div class="text-center flex justify-center space-x-4 mt-6">
            @if (!$isGuestMode)
                @if (!$canEdit)
                    <div
                        class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                        <i class="fas fa-lock mr-2"></i>
                        Create (Viewer)
                    </div>
                @else
                    <a href="/experiments/create"
                        class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                        <i
                            class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                        Create
                    </a>
                @endif
                @if ($isEditing)
                    <a href="/experiments/list"
                        class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                        <i
                            class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                        List
                    </a>
                @else
                    <button wire:click="toggleEditMode"
                        class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                        <i
                            class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Edit
                    </button>
                @endif
            @else
                <div class="flex items-center space-x-4">
                    <div
                        class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                        <i class="fas fa-lock mr-2"></i>
                        Create (Guest Mode)
                    </div>
                    <div
                        class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                        <i class="fas fa-lock mr-2"></i>
                        Edit (Guest Mode)
                    </div>
                </div>
            @endif
            <a href="/experiments/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i
                    class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>
        <!-- Table Section -->
        <div class="index-table-container mt-8 overflow-x-auto border border-gray-200 rounded-xl shadow-xl bg-white">
            <div class="flex flex-col items-center w-full p-4">
                <!-- Index Title (Centered) -->
                <h2 class="text-2xl font-bold text-gray-800 mb-4">
                    @if ($isGuestMode)
                        <i class="fas fa-eye text-purple-600 mr-2"></i>
                        Public Experiments
                    @else
                        {{ $isEditing ? 'Edit Experiments' : 'List of Experiments' }}
                    @endif
                </h2>

                <!-- Export Button (Centered) -->
                <button wire:click="export"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                    <i
                        class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                    Export to CSV
                </button>
            </div>

            @include('livewire.partials.index-per-page-toolbar', ['paginator' => $experiments])

            <table id="experiments_table" wire:key="experiments-table-{{ $isEditing ? 'editing' : 'viewing' }}"
                data-sticky-cols="{{ ($showBulkActions ?? false) ? '2,3' : '1,2' }}"
                class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
                <thead>
                    <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            EXPERIMENT CODE</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            CONTENT CODE</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            CONTENT TYPE</th>
                        @if ($isGuestMode)
                            <th
                                class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                                PROJECT</th>
                        @endif
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            PROTOCOL</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            PROTOCOL TYPE</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            PATHOGEN SPECIES</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            OUTCOME (DISCRETE)</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            OUTCOME (QUANTITATIVE)</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            PHOTO</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            DATE TESTED</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 index-people-cell">
                            PERFORMED BY</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            PERFORMED AT</th>
                        @if ($isEditing && !$isGuestMode)
                            <th
                                class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                                DELETE</th>
                        @endif
                    </tr>
                </thead>
                <thead class="bg-gradient-to-r from-gray-100 to-gray-50">
                    <tr>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="experimentIdFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="contentCodeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3"></th>
                        @if ($isGuestMode)
                            <th class="px-6 py-3">
                                <input type="text" wire:model.live.debounce.300ms="projectFilter"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Filter">
                            </th>
                        @endif
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="protocolFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="protocolTypeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="pathogenFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="discreteFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="quantitativeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <select wire:model.live="photoFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                <option value="">All</option>
                                <option value="has">Has photo</option>
                                <option value="none">No photo</option>
                                @if ($this->canFilterBrokenPhotos())
                                    <option value="broken">Broken link</option>
                                @endif
                            </select>
                        </th>
                        <th class="px-6 py-3">
                            <div class="flex items-center space-x-2">
                                <input type="date" wire:model.live.debounce.300ms="startDate"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Start Date">
                                <span class="text-gray-500 font-medium">to</span>
                                <input type="date" wire:model.live.debounce.300ms="endDate"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="End Date">
                            </div>
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="scientistFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="placeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        @if ($isEditing && !$isGuestMode)
                            <th class="px-6 py-3"></th>
                        @endif
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @if (session()->has('error'))
                        <tr>
                            <td colspan="12"
                                class="px-6 py-4 text-red-500 text-center bg-red-50 border-l-4 border-red-500">
                                {{ session('error') }}
                            </td>
                        </tr>
                    @endif
                    @if (session()->has('message'))
                        <tr>
                            <td colspan="12"
                                class="px-6 py-4 text-green-500 text-center bg-green-50 border-l-4 border-green-500">
                                {{ session('message') }}
                            </td>
                        </tr>
                    @endif
                    @forelse ($experiments as $experiment)
                        @php
                            $canEditRow = $canEdit && $this->canMutateExperimentRecord((int) ($experiment->people_id ?? 0));
                        @endphp
                        <tr wire:key="{{ $experiment->id }}"
                            class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="/experiments/{{ $experiment->code }}"
                                    class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                    {{ $experiment->code }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($experiment->experiments_content)
                                    @php
                                        $contentCode = $experiment->experiments_content->code ?? 'N/A';
                                    @endphp
                                    @if ($experiment->experiments_content_type === 'App\Models\ParasiteSamples')
                                        @if ($isGuestMode)
                                            <span class="text-gray-900 font-medium">{{ $contentCode }}</span>
                                        @else
                                            <a href="/samples/parasites/{{ $contentCode }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                {{ $contentCode }}
                                            </a>
                                        @endif
                                    @elseif($experiment->experiments_content_type === 'App\Models\AnimalSamples')
                                        @if ($isGuestMode)
                                            <span class="text-gray-900 font-medium">{{ $contentCode }}</span>
                                        @else
                                            <a href="/samples/animals/{{ $contentCode }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                {{ $contentCode }}
                                            </a>
                                        @endif
                                    @elseif($experiment->experiments_content_type === 'App\Models\HumanSamples')
                                        @if ($isGuestMode)
                                            <span class="text-gray-900 font-medium">{{ $contentCode }}</span>
                                        @else
                                            <a href="/samples/humans/{{ $contentCode }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                {{ $contentCode }}
                                            </a>
                                        @endif
                                    @elseif($experiment->experiments_content_type === 'App\Models\EnvironmentSamples')
                                        @if ($isGuestMode)
                                            <span class="text-gray-900 font-medium">{{ $contentCode }}</span>
                                        @else
                                            <a href="/samples/environment/{{ $contentCode }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                {{ $contentCode }}
                                            </a>
                                        @endif
                                    @elseif($experiment->experiments_content_type === 'App\Models\NucleicAcids')
                                        @if ($isGuestMode)
                                            <span class="text-gray-900 font-medium">{{ $contentCode }}</span>
                                        @else
                                            <a href="/samples/nucleic/{{ $contentCode }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                {{ $contentCode }}
                                            </a>
                                        @endif
                                    @elseif($experiment->experiments_content_type === 'App\Models\Cultures')
                                        @if ($isGuestMode)
                                            <span class="text-gray-900 font-medium">{{ $contentCode }}</span>
                                        @else
                                            <a href="/samples/cultures/{{ $contentCode }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                {{ $contentCode }}
                                            </a>
                                        @endif
                                    @elseif($experiment->experiments_content_type === 'App\Models\Pools')
                                        @if ($isGuestMode)
                                            <span class="text-gray-900 font-medium">{{ $contentCode }}</span>
                                        @else
                                            <a href="/samples/pools/{{ $contentCode }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                                {{ $contentCode }}
                                            </a>
                                        @endif
                                    @else
                                        <span
                                            class="text-gray-500">{{ $contentCode }}</span>
                                    @endif
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($experiment->experiments_content_type === 'App\Models\ParasiteSamples')
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm">
                                        Parasite Sample
                                    </span>
                                @elseif($experiment->experiments_content_type === 'App\Models\AnimalSamples')
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-sm">
                                        Animal Sample
                                    </span>
                                @elseif($experiment->experiments_content_type === 'App\Models\HumanSamples')
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-pink-100 to-pink-200 text-pink-800 shadow-sm">
                                        Human Sample
                                    </span>
                                @elseif($experiment->experiments_content_type === 'App\Models\EnvironmentSamples')
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-green-100 to-green-200 text-green-800 shadow-sm">
                                        Environment Sample
                                    </span>
                                @elseif($experiment->experiments_content_type === 'App\Models\NucleicAcids')
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800 shadow-sm">
                                        Nucleic Acid
                                    </span>
                                @elseif($experiment->experiments_content_type === 'App\Models\Cultures')
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-sm">
                                        Culture
                                    </span>
                                @elseif($experiment->experiments_content_type === 'App\Models\Pools')
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-cyan-100 to-cyan-200 text-cyan-800 shadow-sm">
                                        Pool
                                    </span>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 shadow-sm">
                                        Other sample
                                    </span>
                                @endif
                            </td>
                            @if ($isGuestMode)
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if ($experiment->projects?->code)
                                        <a href="{{ route('projects.profile', $experiment->projects->code) }}"
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm hover:underline">
                                            {{ $experiment->projects->code }}
                                        </a>
                                    @else
                                        <span
                                            class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm">N/A</span>
                                    @endif
                                </td>
                            @endif
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <div class="relative min-w-[200px]">
                                        <input type="text" list="protocol-list"
                                            wire:change="updateField({{ $experiment->id }}, 'protocol', $event.target.value)"
                                            value="{{ $experiment->protocols->name ?? '' }}"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                            placeholder="Search protocol..."
                                            wire:confirm="Are you sure you want to edit the Protocol?"
                                            autocomplete="off">
                                        <datalist id="protocol-list">
                                            @foreach ($exp_protocols as $protocol)
                                                <option value="{{ $protocol->name }}">
                                                    {{ $protocol->name }}
                                                </option>
                                            @endforeach
                                        </datalist>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <i class="fas fa-search text-gray-400"></i>
                                        </div>
                                    </div>
                                @else
                                    <a href="/protocols/{{ $experiment->protocols->code }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ $experiment->protocols->name ?? 'N/A' }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="text-gray-900 font-medium">{{ $experiment->protocols->techniques->type ?? 'N/A' }}</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <div class="relative min-w-[170px]">
                                        <input type="text" list="pathogen-list"
                                            wire:change="updateField({{ $experiment->id }}, 'pathogen', $event.target.value)"
                                            value="{{ $experiment->pathogens->species ?? '' }}"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                            placeholder="Search pathogen..."
                                            wire:confirm="Are you sure you want to edit the Pathogen Species?"
                                            autocomplete="off">
                                        <datalist id="pathogen-list">
                                            @foreach ($pathogens as $pathogen)
                                                <option value="{{ $pathogen->species }}">
                                                    {{ $pathogen->species }}
                                                </option>
                                            @endforeach
                                        </datalist>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <i class="fas fa-search text-gray-400"></i>
                                        </div>
                                    </div>
                                @else
                                    {!! '<i>' . e($experiment->pathogens->species) . '</i>' ?? 'N/A' !!}
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <div class="relative min-w-[150px]">
                                        <input type="text" list="discrete-outcome-list"
                                            wire:change="updateField({{ $experiment->id }}, 'outcome_discrete', $event.target.value)"
                                            value="{{ $experiment->outcome_discrete ?? '' }}"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                            placeholder="Select outcome..."
                                            wire:confirm="Are you sure you want to edit the Discrete Outcome of the experiment?"
                                            autocomplete="off">
                                        <datalist id="discrete-outcome-list">
                                            <option value="Strong positive">Strong positive</option>
                                            <option value="Positive">Positive</option>
                                            <option value="Suspect">Suspect</option>
                                            <option value="Negative">Negative</option>
                                        </datalist>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <i class="fas fa-chevron-down text-gray-400"></i>
                                        </div>
                                    </div>
                                @else
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm
                                        {{ $experiment->outcome_discrete === 'Strong positive'
                                            ? 'bg-gradient-to-r from-red-700 to-red-900 text-white'
                                            : ($experiment->outcome_discrete === 'Positive'
                                                ? 'bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800'
                                                : ($experiment->outcome_discrete === 'Suspect'
                                                    ? 'bg-gradient-to-r from-yellow-100 to-yellow-200 text-yellow-800'
                                                    : 'bg-gradient-to-r from-green-100 to-green-200 text-green-800')) }}">
                                        {{ $experiment->outcome_discrete ?? 'N/A' }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="number" step="any"
                                        value="{{ $experiment->outcome_quant ?? '' }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $experiment->id }}, 'outcome_quant', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Quantitative Outcome?">
                                @else
                                    <span
                                        class="text-gray-900 font-medium">{{ $experiment->outcome_quant ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @php
                                    $hasPhotoPath = !empty($experiment->photo_path);
                                    $photoExists = $hasPhotoPath
                                        && \Illuminate\Support\Facades\Storage::disk('local')->exists($experiment->photo_path);
                                @endphp
                                <div class="flex items-center space-x-3">
                                    @if (!$isGuestMode)
                                        <label for="photo-upload-{{ $experiment->id }}" class="cursor-pointer group">
                                            <i
                                                class="fas fa-camera text-blue-500 group-hover:text-blue-600 text-xl transition-all duration-200 transform group-hover:scale-110"></i>
                                            <input type="file" id="photo-upload-{{ $experiment->id }}"
                                                class="hidden" accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf"
                                                wire:model.live="photo" wire:loading.attr="disabled"
                                                wire:change="uploadPhoto({{ $experiment->id }})" x-data
                                                x-init="$watch('$wire.photo', value => {
                                                    if (value && $wire.currentPhotoId === {{ $experiment->id }}) {
                                                        $wire.uploadPhoto({{ $experiment->id }});
                                                    }
                                                })"
                                                x-on:change="
                                                if ($el.files[0] && $el.files[0].size > 52428800) {
                                                    alert('File size exceeds 50MB limit');
                                                    $el.value = '';
                                                    return;
                                                }
                                            "
                                                x-on:photo-uploaded.window="
                                                if ($wire.currentPhotoId === {{ $experiment->id }}) {
                                                    $el.value = '';
                                                }
                                            ">
                                        </label>
                                        @if ($uploadingPhotoId === $experiment->id)
                                            <div wire:loading wire:target="photo" class="text-sm text-gray-500">
                                                <i class="fas fa-spinner fa-spin"></i> Uploading...
                                            </div>
                                        @endif
                                        @if (isset($uploadErrors[$experiment->id]))
                                            <div class="text-sm text-red-500">
                                                {{ $uploadErrors[$experiment->id] }}
                                            </div>
                                        @endif
                                    @endif
                                    @if ($photoExists)
                                        <div class="flex items-center space-x-2">
                                            <button type="button"
                                                wire:click="openPhotoPreview({{ $experiment->id }})"
                                                class="text-green-500 hover:text-green-600 transition-all duration-200 transform hover:scale-110"
                                                title="Preview photo">
                                                <i class="fas fa-eye text-lg"></i>
                                            </button>
                                        </div>
                                    @elseif ($hasPhotoPath)
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs font-semibold text-red-600">Missing file</span>
                                            @if (!$isGuestMode && $canEditRow)
                                                <button type="button" wire:click="clearBrokenPhotoPath({{ $experiment->id }})"
                                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 underline underline-offset-2">
                                                    Clear path
                                                </button>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="date"
                                        value="{{ $experiment->date_tested ? \Carbon\Carbon::parse($experiment->date_tested)->format('Y-m-d') : '' }}"
                                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $experiment->id }}, 'date_tested', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Date of Experiment?">
                                @else
                                    <span
                                        class="text-gray-900 font-medium">{{ \Carbon\Carbon::parse($experiment->date_tested)->format('Y-m-d') ?? 'N/A' }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap index-people-cell">
                                @if ($isEditing && !$isGuestMode && $this->canMutateAnyExperimentRecord())
                                    <select wire:change="updateField({{ $experiment->id }}, 'people_id', $event.target.value)"
                                        class="w-full min-w-[220px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:confirm="Are you sure you want to edit the Performed by field?">
                                        <option value="">Select person</option>
                                        @foreach ($people as $personOption)
                                            @php
                                                $personLabel = trim(($personOption->title ?? '') . ' ' . ($personOption->first_name ?? '') . ' ' . ($personOption->last_name ?? '')) ?: 'N/A';
                                            @endphp
                                            <option value="{{ $personOption->id }}" @selected((int) ($experiment->people_id ?? 0) === (int) $personOption->id)>
                                                {{ $personLabel }}
                                            </option>
                                        @endforeach
                                    </select>
                                @else
                                    <div class="flex items-center space-x-3">
                                        <x-people-logo :person="$experiment->people" width="30"
                                            class="rounded-full ring-2 ring-gray-100" />
                                        @if ($experiment->people)
                                            <a href="/profile/{{ $experiment->people->id }}"
                                                class="text-blue-600 hover:text-blue-800 transition-colors duration-200 font-medium">
                                                {{ trim(($experiment->people->title ?? '') . ' ' . ($experiment->people->first_name ?? '') . ' ' . ($experiment->people->last_name ?? '')) ?: 'N/A' }}
                                            </a>
                                        @else
                                            <span class="text-gray-500">N/A</span>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <div class="relative min-w-[250px]">
                                        <input type="text" list="laboratory-list"
                                            wire:change="updateField({{ $experiment->id }}, 'lab', $event.target.value)"
                                            value="{{ $experiment->laboratories->name ?? '' }}"
                                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                            placeholder="Search laboratory..."
                                            wire:confirm="Are you sure you want to edit the Location of the Experiment?"
                                            autocomplete="off">
                                        <datalist id="laboratory-list">
                                            @foreach ($laboratories_by_country as $country => $laboratories)
                                                @foreach ($laboratories as $lab)
                                                    <option value="{{ $lab['name'] }}">
                                                        {{ $country }}
                                                    </option>
                                                @endforeach
                                            @endforeach
                                        </datalist>
                                        <div
                                            class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
                                            <i class="fas fa-search text-gray-400"></i>
                                        </div>
                                    </div>
                                @else
                                    <span
                                        class="text-gray-900 font-medium">{{ $experiment->laboratories->name ?? 'N/A' }}</span>
                                @endif
                            </td>
                            @if ($isEditing && $canEditRow && !$isGuestMode)
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button
                                        class="text-red-500 hover:text-red-600 transition-all duration-200 transform hover:scale-110"
                                        type="button" wire:click="delete({{ $experiment->id }})"
                                        wire:confirm="Are you sure you want to delete this experiment?">
                                        <i class="fas fa-trash text-xl"></i>
                                    </button>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isGuestMode ? 14 : 13 }}" class="px-6 py-12 text-center">
                                <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                    <span class="text-sm text-gray-600">No experiments found.</span>
                                    @if (! $isGuestMode)
                                        <a href="/experiments/create"
                                            class="inline-flex items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white hover:bg-green-700 transition-colors">
                                            <i class="fas fa-plus-circle"></i>
                                            Register experiment
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse

                    @if ($experiments->count() === 0)
                        <tr>
                            <td colspan="{{ $isEditing ? 15 : 14 }}" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center space-y-4">
                                    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
                                        <i class="fas fa-flask text-gray-400 text-2xl"></i>
                                    </div>
                                    <div class="text-center">
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                                            @if ($isGuestMode)
                                                No Public Experiments Found
                                            @else
                                                No Experiments Found
                                            @endif
                                        </h3>
                                        <p class="text-gray-500 mb-4">
                                            @if ($isGuestMode)
                                                There are currently no public experiments available for viewing.
                                            @else
                                                No experiments have been registered yet for this project.
                                            @endif
                                        </p>
                                        @if (!$isGuestMode)
                                            <a href="/experiments/create"
                                                class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                                <i class="fas fa-plus mr-2"></i>
                                                Create First Experiment
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endif
                </tbody>
            </table>

            @include('livewire.partials.index-pagination-bar', ['paginator' => $experiments])

            <!-- Flash Messages -->
            <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)" class="fixed bottom-4 right-4 z-50">
                @if (session()->has('message'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative"
                        role="alert">
                        <span class="block sm:inline">{{ session('message') }}</span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                            <svg class="fill-current h-6 w-6 text-green-500" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <title>Close</title>
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                            </svg>
                        </span>
                    </div>
                @endif

                @if (session()->has('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative"
                        role="alert">
                        <span class="block sm:inline">{{ session('error') }}</span>
                        <span class="absolute top-0 bottom-0 right-0 px-4 py-3" @click="show = false">
                            <svg class="fill-current h-6 w-6 text-red-500" role="button"
                                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                <title>Close</title>
                                <path
                                    d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z" />
                            </svg>
                        </span>
                    </div>
                @endif
            </div>
        </div>
    @else
        @php
            $tableConfig = $this->selectedTableConfig();
        @endphp

        @include('livewire.partials.experiments-origin-table', [
            'subtitle' => $tableConfig['subtitle'] ?? '',
            'tableId' => $tableConfig['tableId'] ?? 'experiments_table',
            'experiments' => $experiments,
            'extraColumns' => $tableConfig['extraColumns'] ?? [],
            'showPhoto' => (bool) ($tableConfig['showPhoto'] ?? false),
            'showProjectColumnInGuestMode' => (bool) ($tableConfig['showProjectColumnInGuestMode'] ?? false),
            'canEdit' => $canEdit ?? true,
            'exp_protocols' => $exp_protocols ?? collect(),
            'pathogens' => $pathogens ?? collect(),
            'laboratories_by_country' => $laboratories_by_country ?? [],
        ])
    @endif

    @if ($photoPreviewExperimentId && $photoPreviewUrl)
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center"
            wire:click.self="closePhotoPreview">
            <div class="bg-white rounded-lg shadow-xl max-w-4xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">
                        Experiment Photo Preview
                    </h3>
                    <button type="button" wire:click="closePhotoPreview"
                        class="text-gray-500 hover:text-gray-700 transition-colors duration-200">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>
                <div class="p-4 max-h-[70vh] overflow-y-auto">
                    <img src="{{ $photoPreviewUrl }}" alt="Experiment photo preview"
                        class="max-w-full max-h-[70vh] object-contain rounded-lg shadow-md">
                </div>
                <div class="flex items-center justify-end gap-3 px-4 py-3 border-t border-gray-200 bg-gray-50">
                    <a href="{{ $photoPreviewUrl }}" download
                        class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                        <i class="fas fa-download mr-2"></i>
                        Download
                    </a>
                    @if ($photoPreviewCanDelete)
                        <button type="button" wire:click="deletePreviewPhoto"
                            wire:confirm="Delete this photo? This will remove the file from server and clear the photo path for all experiments sharing the same photo path."
                            class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-md text-white bg-red-600 hover:bg-red-700 transition-colors duration-200">
                            <i class="fas fa-trash mr-2"></i>
                            Delete Photo
                        </button>
                    @endif
                    <button type="button" wire:click="closePhotoPreview"
                        class="inline-flex items-center px-3 py-2 text-sm font-semibold rounded-md text-gray-700 bg-white border border-gray-300 hover:bg-gray-100 transition-colors duration-200">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
