<div class="text-center mt-2">

    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing tubes with public access from all
                        projects</p>
                </div>
                <a href="/my-projects"
                    class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif
    
    <!-- Polymorphic Icon Navigation Bar (like cultures-index) -->
    <div class="mt-2 flex items-center justify-center w-full relative mb-2">
        <!-- Icons Section (Right-Aligned) -->
        <div class="flex flex-col">
            @if (!$isGuestMode)
            <!-- Left Arrow Home Link -->
            <a href="/samples"
                class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                <i class="fas fa-arrow-left text-2xl mr-2"></i>
                <span class="text-sm font-medium">Back to Samples Home</span>
            </a>
            @endif
            <h2 class="text-xl font-bold mb-4 text-gray-700">Select tube type:</h2>
            <div class="flex items-center space-x-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                <button wire:click="$set('selectedTable', 'tubes_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view All Tubes">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'tubes_table' ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-vial text-3xl {{ $selectedTable === 'tubes_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'tubes_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">All Tubes</span>
                    </div>
                </button>
                <button wire:click="$set('selectedTable', 'tube_human_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Human Tubes">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'tube_human_table' ? 'bg-rose-100 ring-2 ring-rose-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-person text-3xl {{ $selectedTable === 'tube_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'tube_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}">Humans</span>
                    </div>
                </button>
                <button wire:click="$set('selectedTable', 'tube_animal_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Animal Tubes">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'tube_animal_table' ? 'bg-yellow-100 ring-2 ring-yellow-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-paw text-3xl {{ $selectedTable === 'tube_animal_table' ? 'text-yellow-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'tube_animal_table' ? 'text-yellow-600' : 'text-gray-500 group-hover:text-gray-600' }}">Animals</span>
                    </div>
                </button>
                <button wire:click="$set('selectedTable', 'tube_environment_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Environment Tubes">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'tube_environment_table' ? 'bg-emerald-100 ring-2 ring-emerald-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-leaf text-3xl {{ $selectedTable === 'tube_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'tube_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}">Environment</span>
                    </div>
                </button>
                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'tube_parasite_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Parasite Tubes">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['tube_parasite_table','tube_parasite_human_table','tube_parasite_animal_table','tube_parasite_environment_table'], true) ? 'bg-purple-100 ring-2 ring-purple-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-spider text-3xl {{ in_array($selectedTable, ['tube_parasite_table','tube_parasite_human_table','tube_parasite_animal_table','tube_parasite_environment_table'], true) ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['tube_parasite_table','tube_parasite_human_table','tube_parasite_animal_table','tube_parasite_environment_table'], true) ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}">Parasites</span>
                        </div>
                    </button>
                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-56 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'tube_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view all parasite tubes">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">All</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_parasite_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Parasite tubes from humans">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Parasite + Human</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_parasite_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Parasite tubes from animals">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Parasite + Animal</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_parasite_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Parasite tubes from environment">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Parasite + Environment</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'tube_nucleic_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Nucleic Tubes">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['tube_nucleic_table','tube_nucleic_human_table','tube_nucleic_animal_table','tube_nucleic_environment_table','tube_nucleic_parasite_table','tube_nucleic_culture_table','tube_nucleic_pool_table'], true) ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-dna text-3xl {{ in_array($selectedTable, ['tube_nucleic_table','tube_nucleic_human_table','tube_nucleic_animal_table','tube_nucleic_environment_table','tube_nucleic_parasite_table','tube_nucleic_culture_table','tube_nucleic_pool_table'], true) ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['tube_nucleic_table','tube_nucleic_human_table','tube_nucleic_animal_table','tube_nucleic_environment_table','tube_nucleic_parasite_table','tube_nucleic_culture_table','tube_nucleic_pool_table'], true) ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}">Nucleic Acids</span>
                        </div>
                    </button>
                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'tube_nucleic_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0"></i>
                                </div>
                                <span class="group-hover/item:text-blue-600 transition-colors">All</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_nucleic_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">From humans</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_nucleic_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">From animals</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_nucleic_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">From environment</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_nucleic_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">From parasites</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_nucleic_culture_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-bacteria text-sm text-orange-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-orange-600 transition-colors">From cultures</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_nucleic_pool_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-layer-group text-sm text-cyan-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">From pools</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'tube_culture_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Culture Tubes">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['tube_culture_table','tube_culture_human_table','tube_culture_animal_table','tube_culture_environment_table','tube_culture_parasite_table','tube_culture_pool_table'], true) ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-bacteria text-3xl {{ in_array($selectedTable, ['tube_culture_table','tube_culture_human_table','tube_culture_animal_table','tube_culture_environment_table','tube_culture_parasite_table','tube_culture_pool_table'], true) ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['tube_culture_table','tube_culture_human_table','tube_culture_animal_table','tube_culture_environment_table','tube_culture_parasite_table','tube_culture_pool_table'], true) ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Cultures</span>
                        </div>
                    </button>
                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'tube_culture_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0"></i>
                                </div>
                                <span class="group-hover/item:text-orange-600 transition-colors">All</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_culture_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">From humans</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_culture_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">From animals</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_culture_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">From environment</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_culture_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">From parasites</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_culture_pool_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-bacteria text-lg text-orange-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-layer-group text-sm text-cyan-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">From pools</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'tube_pool_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Pool Tubes">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['tube_pool_table','tube_pool_human_table','tube_pool_animal_table','tube_pool_environment_table','tube_pool_parasite_table','tube_pool_nucleic_table','tube_pool_culture_table'], true) ? 'bg-cyan-100 ring-2 ring-cyan-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-layer-group text-3xl {{ in_array($selectedTable, ['tube_pool_table','tube_pool_human_table','tube_pool_animal_table','tube_pool_environment_table','tube_pool_parasite_table','tube_pool_nucleic_table','tube_pool_culture_table'], true) ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['tube_pool_table','tube_pool_human_table','tube_pool_animal_table','tube_pool_environment_table','tube_pool_parasite_table','tube_pool_nucleic_table','tube_pool_culture_table'], true) ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}">Pools</span>
                        </div>
                    </button>
                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-64 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'tube_pool_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">All</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_pool_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Pool + Human</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_pool_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Pool + Animal</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_pool_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Pool + Environment</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_pool_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">Pool + Parasite</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_pool_nucleic_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-dna text-sm text-blue-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-blue-600 transition-colors">Pool + Nucleic</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_pool_culture_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-bacteria text-sm text-orange-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-orange-600 transition-colors">Pool + Culture</span>
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

    @include('livewire.partials.tubes-origin-table', [
        'subtitle' => $tableConfig['subtitle'] ?? '',
        'tableId' => $tableConfig['tableId'] ?? 'tubes_table',
        'tubes' => $tubes,
        'extraColumns' => $tableConfig['extraColumns'] ?? [],
        'canEdit' => $canEdit ?? true,
        'selectedTable' => $selectedTable ?? 'tubes_table',
    ])

    <!-- Flash Messages -->
    <div x-data="{ show: true }"
        x-show="show"
        x-init="setTimeout(() => show = false, 5000)"
        class="fixed bottom-4 right-4 z-50">
        @if (session()->has('message'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('message') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" x-on:click="show = false">
                    <svg class="fill-current h-6 w-6 text-green-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
                <span class="absolute top-0 bottom-0 right-0 px-4 py-3" x-on:click="show = false">
                    <svg class="fill-current h-6 w-6 text-red-500" role="button" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                        <title>Close</title>
                        <path d="M14.348 14.849a1.2 1.2 0 0 1-1.697 0L10 11.819l-2.651 3.029a1.2 1.2 0 1 1-1.697-1.697l2.758-3.15-2.759-3.152a1.2 1.2 0 1 1 1.697-1.697L10 8.183l2.651-3.031a1.2 1.2 0 1 1 1.697 1.697l-2.758 3.152 2.758 3.15a1.2 1.2 0 0 1 0 1.698z"/>
                    </svg>
                </span>
            </div>
        @endif
    </div>

    <!-- Tube Request Modal -->
    @if ($isGuestMode && $showTubeRequestModal)
        <div wire:key="tube-request-modal-{{ $selectedTubeId }}"
            class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
            <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                <div class="mt-3">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">Request Tube Access</h3>
                        <button wire:click="closeTubeRequestModal" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>

                    @if ($selectedTube)
                        <div class="mb-4">
                            <div class="bg-gray-50 p-3 rounded-lg">
                                <h4 class="font-medium text-gray-900 mb-2">Tube Details</h4>
                                <p class="text-sm text-gray-600"><strong>Code:</strong> {{ $selectedTube->code }}</p>
                                <p class="text-sm text-gray-600"><strong>Type:</strong> {{ $selectedTube->tube_type }}</p>
                                <p class="text-sm text-gray-600"><strong>Source Project:</strong> {{ $sourceProject->code ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <form>
                            <div class="mb-4">
                                <label for="targetProjectId" class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Target Project *
                                </label>
                                <select wire:model="targetProjectId" id="targetProjectId"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    required>
                                    <option value="">Choose a project...</option>
                                    @foreach ($userProjects as $project)
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
                                <textarea wire:model="requestMessage" id="requestMessage" rows="3"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Explain why you need access to this tube..."></textarea>
                                @error('requestMessage')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="flex justify-end space-x-3">
                                <button type="button" wire:click="closeTubeRequestModal"
                                    class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                    Cancel
                                </button>
                                <button type="button" wire:click="submitTubeRequest"
                                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg hover:bg-blue-700 transition-colors duration-200">
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="text-center">
                            <p class="text-gray-500">Tube not found.</p>
                            <button wire:click="closeTubeRequestModal"
                                class="mt-3 px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-lg hover:bg-gray-200 transition-colors duration-200">
                                Close
                            </button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</div> 