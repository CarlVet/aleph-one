<div>
<div class="text-center mt-2">
    <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
        @if (!$isGuestMode)
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Register (Viewer)
                </div>
            @else
                <a href="/bank/tubes/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Register
                </a>
            @endif
            @if ($isEditing)
                <a href="/bank/tubes/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            @else
                @if (!$canEdit)
                    <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                        <i class="fas fa-lock mr-2"></i>
                        Edit (Viewer)
                    </div>
                @else
                    <button wire:click="toggleEditMode"
                        class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                        <i class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Edit
                    </button>
                @endif
            @endif
        @else
            <div class="flex items-center space-x-4">
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Register (Project Mode)
                </div>
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Edit (Project Mode)
                </div>
            </div>
        @endif
        <a href="/bank/tubes/dashboard"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
            Dashboard
        </a>
    </div>

    <div class="mt-2 flex items-center justify-center w-full relative mb-2">
        <!-- Icons Section (Right-Aligned) -->
        <div class="flex flex-col">
            <h2 class="text-xl font-bold mb-4 text-gray-700">Select content type:</h2>
            <div class="flex items-center space-x-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                <button wire:click="$set('selectedTable', 'tube_positions_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view All Tube Positions">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'tube_positions_table' ? 'bg-blue-100 ring-2 ring-blue-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-vial text-3xl {{ $selectedTable === 'tube_positions_table' ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'tube_positions_table' ? 'text-blue-600' : 'text-gray-500 group-hover:text-gray-600' }}">All</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'tube_positions_human_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Human Tube Positions">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'tube_positions_human_table' ? 'bg-rose-100 ring-2 ring-rose-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-person text-3xl {{ $selectedTable === 'tube_positions_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'tube_positions_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}">Humans</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'tube_positions_animal_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Animal Tube Positions">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'tube_positions_animal_table' ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-paw text-3xl {{ $selectedTable === 'tube_positions_animal_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'tube_positions_animal_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Animals</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'tube_positions_environment_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Environmental Tube Positions">
                    <div class="flex flex-col items-center">
                        <div class="p-3 rounded-full {{ $selectedTable === 'tube_positions_environment_table' ? 'bg-emerald-100 ring-2 ring-emerald-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i class="fa-solid fa-leaf text-3xl {{ $selectedTable === 'tube_positions_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span class="mt-2 text-xs font-medium {{ $selectedTable === 'tube_positions_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}">Environment</span>
                    </div>
                </button>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'tube_positions_parasite_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Parasite Tube Positions">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['tube_positions_parasite_table','tube_positions_parasite_human_table','tube_positions_parasite_animal_table','tube_positions_parasite_environment_table']) ? 'bg-purple-100 ring-2 ring-purple-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-spider text-3xl {{ in_array($selectedTable, ['tube_positions_parasite_table','tube_positions_parasite_human_table','tube_positions_parasite_animal_table','tube_positions_parasite_environment_table']) ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['tube_positions_parasite_table','tube_positions_parasite_human_table','tube_positions_parasite_animal_table','tube_positions_parasite_environment_table']) ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}">Parasites</span>
                        </div>
                    </button>

                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'tube_positions_parasite_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Parasite Human Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Parasite + Human</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'tube_positions_parasite_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Parasite Animal Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Parasite + Animal</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'tube_positions_parasite_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Parasite Environmental Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Parasite + Environmental</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'tube_positions_nucleic_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Nucleic Acid Tube Positions">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['tube_positions_nucleic_table','tube_positions_nucleic_human_table','tube_positions_nucleic_animal_table','tube_positions_nucleic_parasite_table','tube_positions_nucleic_environment_table','tube_positions_nucleic_culture_table','tube_positions_nucleic_pool_table']) ? 'bg-indigo-100 ring-2 ring-indigo-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-dna text-3xl {{ in_array($selectedTable, ['tube_positions_nucleic_table','tube_positions_nucleic_human_table','tube_positions_nucleic_animal_table','tube_positions_nucleic_parasite_table','tube_positions_nucleic_environment_table','tube_positions_nucleic_culture_table','tube_positions_nucleic_pool_table']) ? 'text-indigo-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['tube_positions_nucleic_table','tube_positions_nucleic_human_table','tube_positions_nucleic_animal_table','tube_positions_nucleic_parasite_table','tube_positions_nucleic_environment_table','tube_positions_nucleic_culture_table','tube_positions_nucleic_pool_table']) ? 'text-indigo-600' : 'text-gray-500 group-hover:text-gray-600' }}">Nucleic Acids</span>
                        </div>
                    </button>

                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'tube_positions_nucleic_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Nucleic + Human</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_positions_nucleic_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Nucleic + Animal</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_positions_nucleic_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Nucleic + Environmental</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_positions_nucleic_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">Nucleic + Parasite</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_positions_nucleic_culture_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-dna text-lg text-blue-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-flask text-sm text-orange-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-orange-600 transition-colors">Nucleic + Culture</span>
                            </button>
                            <button wire:click="$set('selectedTable', 'tube_positions_nucleic_pool_table')"
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
                    <button wire:click="$set('selectedTable', 'tube_positions_culture_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Culture Tube Positions">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['tube_positions_culture_table','tube_positions_culture_human_table','tube_positions_culture_animal_table','tube_positions_culture_environment_table','tube_positions_culture_parasite_table','tube_positions_culture_pool_table']) ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-flask text-3xl {{ in_array($selectedTable, ['tube_positions_culture_table','tube_positions_culture_human_table','tube_positions_culture_animal_table','tube_positions_culture_environment_table','tube_positions_culture_parasite_table','tube_positions_culture_pool_table']) ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['tube_positions_culture_table','tube_positions_culture_human_table','tube_positions_culture_animal_table','tube_positions_culture_environment_table','tube_positions_culture_parasite_table','tube_positions_culture_pool_table']) ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Cultures</span>
                        </div>
                    </button>

                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-52 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'tube_positions_culture_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Culture + Human Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-flask text-lg text-orange-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-rose-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-rose-600 transition-colors">Culture + Human</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'tube_positions_culture_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Culture + Animal Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-flask text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-paw text-sm text-orange-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-orange-600 transition-colors">Culture + Animal</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'tube_positions_culture_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Culture + Environment Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-flask text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-leaf text-sm text-emerald-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-emerald-600 transition-colors">Culture + Environment</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'tube_positions_culture_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Culture + Parasite Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-flask text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">Culture + Parasite</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'tube_positions_culture_pool_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Culture + Pool Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-flask text-lg text-orange-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-layer-group text-sm text-cyan-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-cyan-600 transition-colors">Culture + Pool</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'tube_positions_pool_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Pool Tube Positions">
                        <div class="flex flex-col items-center">
                            <div class="p-3 rounded-full {{ in_array($selectedTable, ['tube_positions_pool_table','tube_positions_pool_human_table','tube_positions_pool_animal_table','tube_positions_pool_environment_table','tube_positions_pool_parasite_table','tube_positions_pool_nucleic_table']) ? 'bg-cyan-100 ring-2 ring-cyan-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i class="fa-solid fa-layer-group text-3xl {{ in_array($selectedTable, ['tube_positions_pool_table','tube_positions_pool_human_table','tube_positions_pool_animal_table','tube_positions_pool_environment_table','tube_positions_pool_parasite_table','tube_positions_pool_nucleic_table']) ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['tube_positions_pool_table','tube_positions_pool_human_table','tube_positions_pool_animal_table','tube_positions_pool_environment_table','tube_positions_pool_parasite_table','tube_positions_pool_nucleic_table']) ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}">Pools</span>
                        </div>
                    </button>

                    <div class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-52 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'tube_positions_pool_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool + Human Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-rose-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-rose-600 transition-colors">Pool + Human</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'tube_positions_pool_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool + Animal Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-paw text-sm text-orange-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-orange-600 transition-colors">Pool + Animal</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'tube_positions_pool_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool + Environment Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-leaf text-sm text-emerald-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-emerald-600 transition-colors">Pool + Environment</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'tube_positions_pool_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool + Parasite Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">Pool + Parasite</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'tube_positions_pool_nucleic_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool + Nucleic Tube Positions">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0 transform translate-x-[-2px]"></i>
                                    <i class="fa-solid fa-dna text-sm text-indigo-600 absolute -bottom-1 -right-1 transform translate-x-[2px]"></i>
                                </div>
                                <span class="group-hover/item:text-indigo-600 transition-colors">Pool + Nucleic</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@include('livewire.partials.tube-positions-origin-table', [
    'tube_positions' => $tube_positions,
    'tableConfig' => $tableConfig,
    'isEditing' => $isEditing,
    'canEdit' => $canEdit ?? true,
    'isGuestMode' => $isGuestMode ?? false,
])

</div>

