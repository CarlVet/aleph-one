<div class="text-center mt-2">

    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing cultures with public tubes from all
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

    <div class="mt-2 flex items-center justify-center w-full relative mb-2">

        <!-- Icons Section (Right-Aligned) -->
        <div class="flex flex-col">
            @if (!$isGuestMode)
                <!-- Left Arrow Home Link -->
                <a href="/samples/cultures"
                    class="absolute left-0 flex items-center text-gray-600 hover:text-blue-600 transition-colors duration-200 pl-2">
                    <i class="fas fa-arrow-left text-2xl mr-2"></i>
                    <span class="text-sm font-medium">Back to CU Home</span>
                </a>
            @endif
            <h2 class="text-xl font-bold mb-4 text-gray-700">Select content type:</h2>
            <div class="flex items-center space-x-6 bg-white p-4 rounded-xl shadow-lg border border-gray-100">
                <button wire:click="$set('selectedTable', 'cultures_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view All Cultures">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'cultures_table' ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-bacteria text-3xl {{ $selectedTable === 'cultures_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'cultures_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">All
                            Cultures</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'culture_human_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Human Cultures">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'culture_human_table' ? 'bg-rose-100 ring-2 ring-rose-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-person text-3xl {{ $selectedTable === 'culture_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'culture_human_table' ? 'text-rose-600' : 'text-gray-500 group-hover:text-gray-600' }}">Humans</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'culture_animal_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Animal Cultures">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'culture_animal_table' ? 'bg-orange-100 ring-2 ring-orange-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-paw text-3xl {{ $selectedTable === 'culture_animal_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'culture_animal_table' ? 'text-orange-600' : 'text-gray-500 group-hover:text-gray-600' }}">Animals</span>
                    </div>
                </button>

                <button wire:click="$set('selectedTable', 'culture_environment_table')"
                    class="focus:outline-none transform transition-all duration-300 hover:scale-110 group"
                    title="Click to view Environment Cultures">
                    <div class="flex flex-col items-center">
                        <div
                            class="p-3 rounded-full {{ $selectedTable === 'culture_environment_table' ? 'bg-emerald-100 ring-2 ring-emerald-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                            <i
                                class="fa-solid fa-leaf text-3xl {{ $selectedTable === 'culture_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                        </div>
                        <span
                            class="mt-2 text-xs font-medium {{ $selectedTable === 'culture_environment_table' ? 'text-emerald-600' : 'text-gray-500 group-hover:text-gray-600' }}">Environment</span>
                    </div>
                </button>

                <div class="relative group">
                    <button wire:click="$set('selectedTable', 'culture_parasite_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Parasite Cultures">
                        <div class="flex flex-col items-center">
                            <div
                                class="p-3 rounded-full {{ in_array($selectedTable, ['culture_parasite_table', 'culture_parasite_human_table', 'culture_parasite_animal_table', 'culture_parasite_environment_table']) ? 'bg-purple-100 ring-2 ring-purple-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i
                                    class="fa-solid fa-spider text-3xl {{ in_array($selectedTable, ['culture_parasite_table', 'culture_parasite_human_table', 'culture_parasite_animal_table', 'culture_parasite_environment_table']) ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span
                                class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['culture_parasite_table', 'culture_parasite_human_table', 'culture_parasite_animal_table', 'culture_parasite_environment_table']) ? 'text-purple-600' : 'text-gray-500 group-hover:text-gray-600' }}">Parasites</span>
                        </div>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'culture_parasite_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Parasite Human Cultures">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Parasite + Human</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'culture_parasite_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Parasite Animal Cultures">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-spider text-lg text-purple-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Parasite +
                                    Animal</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'culture_parasite_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Parasite Environment Cultures">
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
                    <button wire:click="$set('selectedTable', 'culture_pool_table')"
                        class="focus:outline-none transform transition-all duration-300 hover:scale-110"
                        title="Click to view Pool Cultures">
                        <div class="flex flex-col items-center">
                            <div
                                class="p-3 rounded-full {{ in_array($selectedTable, ['culture_pool_table', 'culture_pool_human_table', 'culture_pool_animal_table', 'culture_pool_environment_table', 'culture_pool_parasite_table']) ? 'bg-cyan-100 ring-2 ring-cyan-500' : 'bg-gray-50 group-hover:bg-gray-100' }} transition-all duration-300">
                                <i
                                    class="fa-solid fa-layer-group text-3xl {{ in_array($selectedTable, ['culture_pool_table', 'culture_pool_human_table', 'culture_pool_animal_table', 'culture_pool_environment_table', 'culture_pool_parasite_table']) ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}"></i>
                            </div>
                            <span
                                class="mt-2 text-xs font-medium {{ in_array($selectedTable, ['culture_pool_table', 'culture_pool_human_table', 'culture_pool_animal_table', 'culture_pool_environment_table', 'culture_pool_parasite_table']) ? 'text-cyan-600' : 'text-gray-500 group-hover:text-gray-600' }}">Pools</span>
                        </div>
                    </button>

                    <!-- Dropdown Menu -->
                    <div
                        class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden group-hover:block z-50">
                        <div class="py-1">
                            <button wire:click="$set('selectedTable', 'culture_pool_human_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool Human Cultures">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-person text-sm text-pink-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-pink-600 transition-colors">Pool + Human</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'culture_pool_animal_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool Animal Cultures">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-paw text-sm text-yellow-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-yellow-600 transition-colors">Pool + Animal</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'culture_pool_environment_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool Environment Cultures">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i class="fa-solid fa-leaf text-sm text-green-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-green-600 transition-colors">Pool +
                                    Environment</span>
                            </button>

                            <button wire:click="$set('selectedTable', 'culture_pool_parasite_table')"
                                class="w-full px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-50 flex items-center space-x-3 group/item"
                                title="Click to view Pool Parasite Cultures">
                                <div class="relative w-6 h-6">
                                    <i class="fa-solid fa-layer-group text-lg text-cyan-600 absolute top-0 left-0"></i>
                                    <i
                                        class="fa-solid fa-spider text-sm text-purple-600 absolute -bottom-1 -right-1"></i>
                                </div>
                                <span class="group-hover/item:text-purple-600 transition-colors">Pool + Parasite</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @php
        $subtitle = match ($selectedTable) {
            'culture_human_table' => 'obtained from human samples',
            'culture_animal_table' => 'obtained from animal samples',
            'culture_environment_table' => 'obtained from environment samples',
            'culture_parasite_table' => 'obtained from parasite samples',
            'culture_parasite_human_table' => 'obtained from parasites of human samples',
            'culture_parasite_animal_table' => 'obtained from parasites of animal samples',
            'culture_parasite_environment_table' => 'obtained from parasites of environment samples',
            'culture_pool_table' => 'obtained from pooled samples',
            'culture_pool_human_table' => 'obtained from pools of human samples',
            'culture_pool_animal_table' => 'obtained from pools of animal samples',
            'culture_pool_environment_table' => 'obtained from pools of environmental samples',
            'culture_pool_parasite_table' => 'obtained from pools of parasite samples',
            default => null,
        };
    @endphp
    <!-- Create, Edit, Dashboard (Centered) -->
    <div class="text-center flex justify-center space-x-4 mt-6">
        @if (!$isGuestMode)
            @if (!$canEdit)
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Viewer)
                </div>
            @else
                <a href="/samples/cultures/create"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                    <i
                        class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                    Create
                </a>
            @endif
            @if ($isEditing)
                <a href="/samples/cultures/list"
                    class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                    <i
                        class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                    List
                </a>
            @else
                @if (!$canEdit)
                    <div
                        class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                        <i class="fas fa-lock mr-2"></i>
                        Edit (Viewer)
                    </div>
                @else
                    <button wire:click="toggleEditMode"
                        class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-yellow-500 to-yellow-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-yellow-600">
                        <i
                            class="fas fa-edit mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Edit
                    </button>
                @endif
            @endif
        @else
            <div class="flex items-center space-x-4">
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Create (Project Mode)
                </div>
                <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                    <i class="fas fa-lock mr-2"></i>
                    Edit (Project Mode)
                </div>
            </div>
        @endif
        <a href="/samples/cultures/dashboard"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
            Dashboard
        </a>
        @if ($isGuestMode)
            <a href="/tube-requests"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-indigo-600">
                <i class="fas fa-handshake mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                My Requests
            </a>
        @endif
    </div>

    <!-- Table Section -->
    <div class="mt-8 border border-gray-200 rounded-xl shadow-xl bg-white">
        @php
            $showBulkActions = $canEdit && ! $isGuestMode;
        @endphp
        <div class="flex flex-col items-center w-full p-4">
            <!-- Index Title (Centered) -->
            <h2 class="text-2xl font-bold text-gray-800 mb-4">
                @if ($isGuestMode)
                    <i class="fas fa-eye text-purple-600 mr-2"></i>
                    Public Cultures
                @else
                    {{ $isEditing ? 'Edit Cultures' : 'List of Cultures' }}
                @endif
            </h2>
            @if ($subtitle)
                <div class="text-base text-gray-600 -mt-2 mb-4">
                    {{ ucfirst($subtitle) }}
                </div>
            @endif

            <!-- Export Button (Centered) -->
            <button wire:click="export"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600">
                <i
                    class="fas fa-download mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Export to CSV
            </button>
            @if ($showBulkActions)
                <div class="mt-3 flex items-center gap-3">
                    <button type="button" wire:click="deleteSelected"
                        wire:confirm="Are you sure you want to delete the selected cultures?"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-red-100 text-red-600 hover:bg-red-200 transition-colors duration-200"
                        title="Delete selected cultures">
                        <i class="fas fa-trash"></i>
                    </button>
                    <span class="text-sm text-gray-600">
                        {{ count(array_filter($selectedCultures ?? [])) }} selected
                    </span>
                </div>
            @endif
        </div>

        @include('livewire.partials.index-per-page-toolbar', ['paginator' => $cultures])


        <div class="index-table-container overflow-x-auto">
        <table id="cultures_table" class="index-data-table min-w-full divide-y divide-gray-200 {{ ($showBulkActions ?? false) ? 'has-bulk-select' : '' }}">
            <thead>
                <tr class="bg-gradient-to-r from-gray-50 to-gray-100">
                    @if ($showBulkActions)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Select</th>
                    @endif
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="culture_code" :active="$sortField" :direction="$sortDirection">Culture code</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="alias_code" :active="$sortField" :direction="$sortDirection">Alias code</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="sub_project" :active="$sortField" :direction="$sortDirection">Sub-project</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="parent_code" :active="$sortField" :direction="$sortDirection">Parent code</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Content type</th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Content code</th>
                    @if ($isGuestMode)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Project code</th>
                    @endif
                    @if ($selectedTable === 'culture_human_table')
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Human code</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Sample type</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Sampling site</th>
                    @elseif ($selectedTable === 'culture_animal_table')
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Animal code</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Species</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Sampling site</th>
                    @elseif ($selectedTable === 'culture_environment_table')
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Environment type</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Sampling site</th>
                    @elseif (in_array(
                            $selectedTable,
                            [
                                'culture_parasite_table',
                                'culture_parasite_human_table',
                                'culture_parasite_animal_table',
                                'culture_parasite_environment_table',
                            ],
                            true))
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Parasite species</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Origin sample</th>
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Origin sampling site</th>
                    @elseif (in_array(
                            $selectedTable,
                            [
                                'culture_pool_table',
                                'culture_pool_human_table',
                                'culture_pool_animal_table',
                                'culture_pool_environment_table',
                                'culture_pool_parasite_table',
                            ],
                            true))
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Contents details</th>
                    @endif
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="culture_type" :active="$sortField" :direction="$sortDirection">Type</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="medium" :active="$sortField" :direction="$sortDirection">Medium</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="athmosphere" :active="$sortField" :direction="$sortDirection">Athmosphere</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="incubation_temp" :active="$sortField" :direction="$sortDirection">Incubation Temperature</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="date_cultured" :active="$sortField" :direction="$sortDirection">Date cultured</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200 index-people-cell">
                        <x-sort-button field="cultured_by" :active="$sortField" :direction="$sortDirection">Cultured by</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        <x-sort-button field="cultured_at" :active="$sortField" :direction="$sortDirection">Cultured at</x-sort-button></th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Discarded</th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Date discarded</th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Associated tubes</th>
                    <th
                        class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                        Photo</th>
                    @if ($isEditing)
                        <th
                            class="px-6 py-4 text-center text-xs font-bold text-gray-600 uppercase tracking-wider border-b border-gray-200">
                            Delete</th>
                    @endif
                </tr>
            </thead>
            <thead class="bg-gradient-to-r from-gray-100 to-gray-50">
                <tr>
                    @if ($showBulkActions)
                        <th class="px-6 py-3 text-center">
                            <label class="inline-flex items-center gap-2 text-[11px] font-semibold text-gray-600">
                                <input type="checkbox" wire:model.live="selectAllFiltered"
                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    title="Select all filtered rows">
                                <span>All filtered</span>
                            </label>
                        </th>
                    @endif
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="cultureIdFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="aliasCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="subProjectCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="parentCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3"></th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="contentCodeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    @if ($isGuestMode)
                        <th class="px-6 py-3"></th>
                    @endif
                    @if ($selectedTable === 'culture_human_table')
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="humanCodeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="sampleTypeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="samplingSiteFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                    @elseif ($selectedTable === 'culture_animal_table')
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="animalCodeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="speciesFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="samplingSiteFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                    @elseif ($selectedTable === 'culture_environment_table')
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="environmentSampleTypeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="samplingSiteFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                    @elseif (in_array(
                            $selectedTable,
                            [
                                'culture_parasite_table',
                                'culture_parasite_human_table',
                                'culture_parasite_animal_table',
                                'culture_parasite_environment_table',
                            ],
                            true))
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="parasiteSpeciesFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="parasiteOriginCodeFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="samplingSiteFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                    @elseif (in_array(
                            $selectedTable,
                            [
                                'culture_pool_table',
                                'culture_pool_human_table',
                                'culture_pool_animal_table',
                                'culture_pool_environment_table',
                                'culture_pool_parasite_table',
                            ],
                            true))
                        <th class="px-6 py-3">
                            <input type="text" wire:model.live.debounce.300ms="contentsDetailsFilter"
                                class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Filter">
                        </th>
                    @endif
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="cultureTypeFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="mediumFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="athmosphereFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3"></th>
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
                    <th class="px-6 py-3">
                        <select wire:model.live="discardedFilter"
                            class="w-full min-w-[110px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All</option>
                            <option value="yes">Discarded</option>
                            <option value="no">Active</option>
                        </select>
                    </th>
                    <th class="px-6 py-3"></th>
                    <th class="px-6 py-3">
                        <input type="text" wire:model.live.debounce.300ms="associatedTubesFilter"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Filter">
                    </th>
                    <th class="px-6 py-3">
                        <select wire:model.live="photoFilter"
                            class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="">All photos</option>
                            <option value="has">Has photo</option>
                            <option value="none">No photo</option>
                            @if ($this->canFilterBrokenPhotos())
                                <option value="broken">Broken link</option>
                            @endif
                        </select>
                    </th>
                    @if ($isEditing)
                        <th class="px-6 py-3"></th>
                    @endif
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @if (session()->has('error'))
                    <tr>
                        <td colspan="{{ $isEditing ? '12' : '11' }}"
                            class="px-6 py-4 text-red-500 text-center bg-red-50 border-l-4 border-red-500">
                            {{ session('error') }}
                        </td>
                    </tr>
                @endif
                @if (session()->has('message'))
                    <tr>
                        <td colspan="{{ $isEditing ? '12' : '11' }}"
                            class="px-6 py-4 text-green-500 text-center bg-green-50 border-l-4 border-green-500">
                            {{ session('message') }}
                        </td>
                    </tr>
                @endif
                @forelse ($cultures as $culture)
                    @php
                        $canEditRow = $canEdit && $this->canMutateCultureRecord((int) ($culture->people_id ?? 0));
                    @endphp
                    <tr wire:key="{{ $culture->id }}"
                        class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                        @if ($showBulkActions)
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                @if ($canEditRow)
                                    <input type="checkbox" wire:model.live="selectedCultures.{{ $culture->id }}"
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        title="Select this culture">
                                @endif
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $culture->code }}" list="culture-codes"
                                    class="w-full min-w-[150px]  px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'code', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Culture Code?">
                                <datalist id="culture-codes">
                                    @foreach ($available_culture_codes as $code)
                                        <option value="{{ $code }}">
                                    @endforeach
                                </datalist>
                            @else
                                <div class="flex items-center space-x-2">
                                    @if ($isGuestMode)
                                        <span class="text-gray-900 font-medium">{{ $culture->code }}</span>
                                    @else
                                        <a href="/samples/cultures/{{ $culture->code }}"
                                            class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                            {{ $culture->code }}
                                        </a>
                                    @endif
                                    @if ($isGuestMode)
                                        <button type="button" wire:click="openTubeRequestModal({{ $culture->id }})"
                                            class="text-indigo-500 hover:text-indigo-700 transition-colors duration-200"
                                            title="Request this culture">
                                            <i class="fas fa-handshake text-sm"></i>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $culture->alias_code ?? '' }}"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'alias_code', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Alias Code?">
                            @else
                                <span class="text-gray-900 font-medium">{{ $culture->alias_code ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if (optional($culture->subProjectAssignment?->subProject)->code)
                                <span
                                    class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-indigo-100 text-indigo-800">
                                    {{ $culture->subProjectAssignment->subProject->code }}
                                </span>
                            @else
                                <span class="text-gray-500">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $culture->parent->code ?? '' }}" list="parent-codes"
                                    class="w-full min-w-[150px]  px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'parent_code', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Parent Code?">
                                <datalist id="parent-codes">
                                    @foreach ($available_culture_codes as $code)
                                        <option value="{{ $code }}">
                                    @endforeach
                                </datalist>
                            @else
                                @if ($culture->parent)
                                    @if ($isGuestMode)
                                        <span class="text-gray-900 font-medium">{{ $culture->parent->code }}</span>
                                    @else
                                        <a href="/samples/cultures/{{ $culture->parent->code }}"
                                            class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                            {{ $culture->parent->code }}
                                        </a>
                                    @endif
                                @else
                                    <span class="text-gray-500">Primary culture</span>
                                @endif
                            @endif
                        </td>
                        @if ($culture->cultures_content_type === 'App\Models\ParasiteSamples')
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-purple-100 to-purple-200 text-purple-800 shadow-sm">
                                    Parasite Sample
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="text" value="{{ $culture->cultures_content?->code ?? '' }}"
                                        list="content-codes"
                                        class="w-full min-w-[150px]  px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $culture->id }}, 'content_code', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Content Code?">
                                    <datalist id="content-codes">
                                        @foreach ($available_content_codes as $code)
                                            <option value="{{ $code }}">
                                        @endforeach
                                    </datalist>
                                @else
                                    @if ($isGuestMode)
                                        <span class="text-gray-900 font-medium">{{ $culture->cultures_content?->code ?? 'N/A' }}</span>
                                    @else
                                        <a href="/samples/parasites/{{ $culture->cultures_content?->code ?? 'N/A' }}"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                            {{ $culture->cultures_content?->code ?? 'N/A' }}
                                        </a>
                                    @endif
                                @endif
                            </td>
                        @elseif($culture->cultures_content_type === 'App\Models\AnimalSamples')
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-orange-100 to-orange-200 text-orange-800 shadow-sm">
                                    Animal Sample
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="text" value="{{ $culture->cultures_content?->code ?? '' }}"
                                        list="content-codes"
                                        class="w-full min-w-[150px]  px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $culture->id }}, 'content_code', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Content Code?">
                                    <datalist id="content-codes">
                                        @foreach ($available_content_codes as $code)
                                            <option value="{{ $code }}">
                                        @endforeach
                                    </datalist>
                                @else
                                    @if ($isGuestMode)
                                        <span class="text-gray-900 font-medium">{{ $culture->cultures_content?->code ?? 'N/A' }}</span>
                                    @else
                                        <a href="/samples/animals/{{ $culture->cultures_content?->code ?? 'N/A' }}"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                            {{ $culture->cultures_content?->code ?? 'N/A' }}
                                        </a>
                                    @endif
                                @endif
                            </td>
                        @elseif($culture->cultures_content_type === 'App\Models\HumanSamples')
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-rose-100 to-rose-200 text-rose-800 shadow-sm">
                                    Human Sample
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="text" value="{{ $culture->cultures_content?->code ?? '' }}"
                                        list="content-codes"
                                        class="w-full min-w-[150px]  px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $culture->id }}, 'content_code', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Content Code?">
                                    <datalist id="content-codes">
                                        @foreach ($available_content_codes as $code)
                                            <option value="{{ $code }}">
                                        @endforeach
                                    </datalist>
                                @else
                                    @if ($isGuestMode)
                                        <span class="text-gray-900 font-medium">{{ $culture->cultures_content?->code ?? 'N/A' }}</span>
                                    @else
                                        <a href="/samples/humans/{{ $culture->cultures_content?->code ?? 'N/A' }}"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                            {{ $culture->cultures_content?->code ?? 'N/A' }}
                                        </a>
                                    @endif
                                @endif
                            </td>
                        @elseif($culture->cultures_content_type === 'App\Models\EnvironmentSamples')
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-emerald-100 to-emerald-200 text-emerald-800 shadow-sm">
                                    Environment Sample
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="text" value="{{ $culture->cultures_content?->code ?? '' }}"
                                        list="content-codes"
                                        class="w-full min-w-[150px]  px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $culture->id }}, 'content_code', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Content Code?">
                                    <datalist id="content-codes">
                                        @foreach ($available_content_codes as $code)
                                            <option value="{{ $code }}">
                                        @endforeach
                                    </datalist>
                                @else
                                    @if ($isGuestMode)
                                        <span class="text-gray-900 font-medium">{{ $culture->cultures_content?->code ?? 'N/A' }}</span>
                                    @else
                                        <a href="/samples/environment/{{ $culture->cultures_content?->code ?? 'N/A' }}"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                            {{ $culture->cultures_content?->code ?? 'N/A' }}
                                        </a>
                                    @endif
                                @endif
                            </td>
                        @elseif($culture->cultures_content_type === 'App\Models\Pools')
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-cyan-100 to-cyan-200 text-cyan-800 shadow-sm">
                                    Pool
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="text" value="{{ $culture->cultures_content?->code ?? '' }}"
                                        list="content-codes"
                                        class="w-full min-w-[150px]  px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $culture->id }}, 'content_code', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Content Code?">
                                    <datalist id="content-codes">
                                        @foreach ($available_content_codes as $code)
                                            <option value="{{ $code }}">
                                        @endforeach
                                    </datalist>
                                @else
                                    @if ($isGuestMode)
                                        <span class="text-gray-900 font-medium">{{ $culture->cultures_content?->code ?? 'N/A' }}</span>
                                    @else
                                        <a href="/bank/boxes/{{ $culture->cultures_content?->code ?? 'N/A' }}"
                                            class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                            {{ $culture->cultures_content?->code ?? 'N/A' }}
                                        </a>
                                    @endif
                                @endif
                            </td>
                        @else
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gradient-to-r from-gray-100 to-gray-200 text-gray-800 shadow-sm">
                                    Other sample
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isEditing && $canEditRow)
                                    <input type="text" value="{{ $culture->cultures_content?->code ?? '' }}"
                                        list="content-codes"
                                        class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        wire:change="updateField({{ $culture->id }}, 'content_code', $event.target.value)"
                                        wire:confirm="Are you sure you want to edit the Content Code?">
                                    <datalist id="content-codes">
                                        @foreach ($available_content_codes as $code)
                                            <option value="{{ $code }}">
                                        @endforeach
                                    </datalist>
                                @else
                                    <span
                                        class="text-gray-500">{{ $culture->cultures_content?->code ?? 'N/A' }}</span>
                                @endif
                            </td>
                        @endif

                        @if ($isGuestMode)
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($culture->projects && $culture->projects->code)
                                    <a href="/projects/{{ $culture->projects->code }}"
                                        class="text-blue-600 hover:text-blue-800 font-medium transition-colors duration-200">
                                        {{ $culture->projects->code }}
                                    </a>
                                @else
                                    <span class="text-gray-500">N/A</span>
                                @endif
                            </td>
                        @endif

                        @if ($selectedTable === 'culture_human_table')
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isGuestMode)
                                    <span class="text-gray-900 font-medium">{{ data_get($culture, 'cultures_content.humans.code') ?? 'N/A' }}</span>
                                @else
                                    <a href="/humans/{{ data_get($culture, 'cultures_content.humans.code') }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ data_get($culture, 'cultures_content.humans.code') ?? 'N/A' }}
                                    </a>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ data_get($culture, 'cultures_content.sample_types.name') ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ data_get($culture, 'cultures_content.sampling_sites.name') ?? 'N/A' }}
                            </td>
                        @elseif ($selectedTable === 'culture_animal_table')
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if ($isGuestMode)
                                    <span class="text-gray-900 font-medium">{{ data_get($culture, 'cultures_content.animals.code') ?? 'N/A' }}</span>
                                @else
                                    <a href="/animals/{{ data_get($culture, 'cultures_content.animals.code') }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200">
                                        {{ data_get($culture, 'cultures_content.animals.code') ?? 'N/A' }}
                                    </a>
                                @endif
                            </td>
                            @php
                                $nameCommon = data_get($culture, 'cultures_content.animals.animal_species.name_common');
                                $nameScientific = data_get(
                                    $culture,
                                    'cultures_content.animals.animal_species.name_scientific',
                                );
                            @endphp
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <div class="leading-tight">
                                    <div class="font-medium">{{ $nameCommon ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">
                                        ({{ $nameScientific ?? 'N/A' }})
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ data_get($culture, 'cultures_content.sampling_sites.name') ?? 'N/A' }}
                            </td>
                        @elseif ($selectedTable === 'culture_environment_table')
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ data_get($culture, 'cultures_content.environment_sample_types.name') ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ data_get($culture, 'cultures_content.sampling_sites.name') ?? 'N/A' }}
                            </td>
                        @elseif (in_array(
                                $selectedTable,
                                [
                                    'culture_parasite_table',
                                    'culture_parasite_human_table',
                                    'culture_parasite_animal_table',
                                    'culture_parasite_environment_table',
                                ],
                                true))
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ data_get($culture, 'cultures_content.parasites.parasite_species.name_scientific') ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-gray-900 font-medium">
                                    {{ data_get($culture, 'cultures_content.parasites.parasites_origin.code') ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ data_get($culture, 'cultures_content.parasites.parasites_origin.sampling_sites.name') ?? 'N/A' }}
                            </td>
                        @elseif (in_array(
                                $selectedTable,
                                [
                                    'culture_pool_table',
                                    'culture_pool_human_table',
                                    'culture_pool_animal_table',
                                    'culture_pool_environment_table',
                                    'culture_pool_parasite_table',
                                ],
                                true))
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                {!! $this->poolContentsDetailsHtmlForCulture($culture) !!}
                            </td>
                        @endif
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $culture->type ?? '' }}" list="culture-types"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'type', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Culture Type?">
                                <datalist id="culture-types">
                                    @foreach ($culture_types as $type)
                                        <option value="{{ $type }}">
                                    @endforeach
                                </datalist>
                            @else
                                <span class="text-gray-900 font-medium">{{ $culture->type ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $culture->medium ?? '' }}" list="mediums"
                                    class="w-full min-w-[150px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'medium', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Medium?">
                                <datalist id="mediums">
                                    @foreach ($mediums as $medium)
                                        <option value="{{ $medium }}">
                                    @endforeach
                                </datalist>
                            @else
                                <span class="text-gray-900 font-medium">{{ $culture->medium ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $culture->athmosphere ?? '' }}" list="atmospheres"
                                    class="w-full min-w-[130px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'athmosphere', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Atmosphere?">
                                <datalist id="atmospheres">
                                    @foreach ($atmospheres as $atmosphere)
                                        <option value="{{ $atmosphere }}">
                                    @endforeach
                                </datalist>
                            @else
                                <span class="text-gray-900 font-medium">{{ $culture->athmosphere ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow)
                                <input type="text" value="{{ $culture->incubation_temp ?? '' }}"
                                    list="incubation-temps"
                                    class="w-full min-w-[80px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'incubation_temp', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Incubation Temperature?">
                                <datalist id="incubation-temps">
                                    @foreach ($incubation_temps as $temp)
                                        <option value="{{ $temp }}">
                                    @endforeach
                                </datalist>
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $culture->incubation_temp ?? 'N/A' }}°C</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $dateCulturedYmd = $culture->date_cultured
                                    ? \Carbon\Carbon::parse($culture->date_cultured)->format('Y-m-d')
                                    : '';
                            @endphp
                            @if ($isEditing && $canEditRow)
                                <input type="date" value="{{ $dateCulturedYmd }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'date_cultured', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the Date Cultured?">
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $dateCulturedYmd !== '' ? $dateCulturedYmd : 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap index-people-cell">
                            @if ($isEditing && ($canEditCulturedBy ?? false))
                                <select
                                    class="w-full min-w-[180px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'people_id', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit Cultured by?">
                                    @foreach ($people as $person)
                                        <option value="{{ $person->id }}" @selected((int) $culture->people_id === (int) $person->id)>
                                            {{ trim($person->title.' '.$person->first_name.' '.$person->last_name) }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <div class="flex items-center space-x-3">
                                    <x-people-logo :person="$culture->people" width="30"
                                        class="rounded-full ring-2 ring-gray-100" />
                                    <a href="/profile/{{ $culture->people->id }}"
                                        class="text-blue-600 hover:text-blue-800 transition-colors duration-200 font-medium">
                                        {{ $culture->people->title . ' ' . $culture->people->first_name . ' ' . $culture->people->last_name ?? 'N/A' }}
                                    </a>
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $this->canEditCulturedAt((int) $culture->people_id))
                                <select
                                    class="w-full min-w-[180px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'laboratories_id', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit Cultured at?">
                                    @foreach ($laboratories as $laboratory)
                                        <option value="{{ $laboratory->id }}" @selected((int) $culture->laboratories_id === (int) $laboratory->id)>
                                            {{ $laboratory->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <span
                                    class="text-gray-900 font-medium">{{ $culture->laboratories->name ?? 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @php
                                $dateDiscardedYmd = $culture->date_discarded
                                    ? \Carbon\Carbon::parse($culture->date_discarded)->format('Y-m-d')
                                    : '';
                            @endphp
                            @if ($isEditing && $canEditRow)
                                <select
                                    class="w-full min-w-[120px] px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'is_discarded', $event.target.value)"
                                    wire:confirm="Are you sure you want to change the discard status?">
                                    <option value="0" @selected(! $culture->is_discarded)>Active</option>
                                    <option value="1" @selected($culture->is_discarded)>Discarded</option>
                                </select>
                            @elseif($culture->is_discarded)
                                <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800">Discarded</span>
                            @else
                                <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Active</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($isEditing && $canEditRow && $culture->is_discarded)
                                <input type="date" value="{{ $dateDiscardedYmd }}"
                                    class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    wire:change="updateField({{ $culture->id }}, 'date_discarded', $event.target.value)"
                                    wire:confirm="Are you sure you want to edit the discard date?">
                            @else
                                <span class="text-gray-900 font-medium">{{ $dateDiscardedYmd !== '' ? $dateDiscardedYmd : 'N/A' }}</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @php
                                $tubeRows = collect($culture->tubes ?? [])->map(function ($tube) {
                                    $code = (string) ($tube->code ?? '');
                                    $alias = trim((string) ($tube->alias_code ?? ''));
                                    $type = trim((string) ($tube->tube_type ?? ''));
                                    $privacy = (bool) ($tube->is_private ?? false) ? 'Private' : 'Public';
                                    $label = $code !== '' ? $code : 'N/A';
                                    if ($alias !== '') {
                                        $label .= ' ('.$alias.')';
                                    }
                                    if ($type !== '') {
                                        $label .= ' | '.$type;
                                    }
                                    $label .= ' | '.$privacy;

                                    return $label;
                                });
                                $tubeCount = $tubeRows->count();
                                $visibleTubeRows = $tubeRows->take(3)->all();
                                $remainingTubeCount = max(0, $tubeCount - count($visibleTubeRows));
                            @endphp
                            @if ($tubeCount > 0)
                                <div class="min-w-[260px] max-w-[360px]">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-blue-100 text-blue-800">
                                        {{ $tubeCount }} tube{{ $tubeCount > 1 ? 's' : '' }}
                                    </span>
                                    <div class="mt-2 space-y-1">
                                        @foreach ($visibleTubeRows as $tubeLabel)
                                            <div class="text-xs text-gray-700 break-words">{{ $tubeLabel }}</div>
                                        @endforeach
                                        @if ($remainingTubeCount > 0)
                                            <div class="text-xs text-gray-500">+{{ $remainingTubeCount }} more</div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <span class="text-gray-500 italic">N/A</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $coverObservation = $culture->latestObservation ?? $culture->observations->first();
                                $coverPhoto = $coverObservation?->photo ?? $culture->latestPhoto ?? $culture->photos->first();
                                $coverPath = $coverPhoto?->photo_path ?: $culture->photo_path;
                                $photoCount = $culture->observations->count() ?: ($coverPath ? 1 : 0);
                                $photoExists = $coverPath
                                    && \Illuminate\Support\Facades\Storage::disk('local')->exists($coverPath);
                            @endphp
                            @if ($photoExists)
                                <div class="flex flex-col items-center">
                                    <button type="button" wire:click="openPhotoPreview({{ $culture->id }})"
                                        title="Preview photos" class="relative">
                                        <img src="{{ Storage::url($coverPath) }}" alt="Culture photo"
                                            class="w-16 h-16 object-cover rounded shadow mb-1">
                                        @if ($photoCount > 1)
                                            <span class="absolute -top-1 -right-1 inline-flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-blue-600 px-1 text-[10px] font-bold text-white">
                                                {{ $photoCount }}
                                            </span>
                                        @endif
                                    </button>
                                </div>
                            @elseif (!empty($coverPath))
                                <div class="flex items-center gap-2">
                                    <span class="text-xs font-semibold text-red-600">Missing file</span>
                                    @if (!$isGuestMode && $canEditRow)
                                        <button type="button" wire:click="clearBrokenPhotoPath({{ $culture->id }})"
                                            class="text-xs font-semibold text-blue-600 hover:text-blue-800 underline underline-offset-2">
                                            Clear path
                                        </button>
                                    @endif
                                </div>
                            @endif
                            <div class="flex flex-col items-center mt-1">
                                <input type="file" wire:model="photo.{{ $culture->id }}"
                                    accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf" class="text-xs mb-1">
                                @if (isset($uploadingPhoto[$culture->id]) && $uploadingPhoto[$culture->id])
                                    <span class="text-blue-500 text-xs">Uploading...</span>
                                @endif
                                @if (isset($uploadError[$culture->id]) && $uploadError[$culture->id])
                                    <span class="text-red-500 text-xs">{{ $uploadError[$culture->id] }}</span>
                                @endif
                                <button type="button" wire:click="uploadPhoto({{ $culture->id }})"
                                    class="text-green-500 hover:text-green-700 text-xs mt-1">Upload</button>
                            </div>
                        </td>
                        @if ($isEditing && $canEditRow)
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    class="text-red-500 hover:text-red-600 transition-all duration-200 transform hover:scale-110"
                                    type="button" wire:click="delete({{ $culture->id }})"
                                    wire:confirm="Are you sure you want to delete this culture?">
                                    <i class="fas fa-trash text-xl"></i>
                                </button>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="30" class="px-6 py-10 text-center">
                            <div class="sticky left-1/2 flex w-full max-w-xl -translate-x-1/2 flex-col items-center justify-center gap-3">
                                <span class="text-sm text-gray-600">No cultures found.</span>
                                @if (!$isGuestMode)
                                    <a href="/samples/cultures/create"
                                        class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 transition-colors duration-200">
                                        Register culture
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        </div>

        @include('livewire.partials.index-pagination-bar', ['paginator' => $cultures])

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
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
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

    @if ($photoPreviewCultureId && $photoPreviewUrl)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm p-4"
            wire:click.self="closePhotoPreview">
            <div class="relative w-full max-w-5xl overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-orange-950 shadow-2xl ring-1 ring-white/10">
                <div class="flex items-center justify-between border-b border-white/10 px-5 py-4">
                    <div>
                        <h3 class="text-lg font-semibold text-white">
                            Culture Photos{{ $photoPreviewCode ? ' · '.$photoPreviewCode : '' }}
                        </h3>
                        @if (count($photoPreviewPhotos) > 1)
                            <p class="text-sm text-white/60">{{ $photoPreviewIndex + 1 }} of {{ count($photoPreviewPhotos) }}</p>
                        @endif
                    </div>
                    <button type="button" wire:click="closePhotoPreview"
                        class="rounded-full bg-white/10 p-2 text-white transition hover:bg-white/20">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <div class="relative aspect-[16/10] min-h-[300px] max-h-[65vh] bg-black/30">
                    <img wire:key="preview-{{ $photoPreviewIndex }}"
                        src="{{ $photoPreviewUrl }}"
                        alt="Culture photo preview"
                        class="absolute inset-0 h-full w-full object-contain transition-opacity duration-300">

                    @if (count($photoPreviewPhotos) > 1)
                        <button type="button" wire:click="previousPhotoPreview"
                            class="absolute left-3 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-3 text-white backdrop-blur-sm transition hover:bg-black/70">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <button type="button" wire:click="nextPhotoPreview"
                            class="absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-3 text-white backdrop-blur-sm transition hover:bg-black/70">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    @endif

                    @php $currentPreview = $photoPreviewPhotos[$photoPreviewIndex] ?? null; @endphp
                    @if ($currentPreview && (!empty($currentPreview['observed_at']) || !empty($currentPreview['notes']) || !empty($currentPreview['observer'])))
                        <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/90 via-black/50 to-transparent p-5 pt-12">
                            @if (!empty($currentPreview['observed_at']))
                                <p class="text-xs font-semibold uppercase tracking-wider text-orange-200">Observed</p>
                                <p class="text-base font-medium text-white">{{ $currentPreview['observed_at'] }}</p>
                            @endif
                            @if (!empty($currentPreview['observer']))
                                <p class="mt-1 text-sm text-white/80">by {{ $currentPreview['observer'] }}</p>
                            @endif
                            @if (!empty($currentPreview['notes']))
                                <p class="mt-2 max-w-3xl text-sm text-white/85">{{ $currentPreview['notes'] }}</p>
                            @endif
                        </div>
                    @endif
                </div>

                @if (count($photoPreviewPhotos) > 1)
                    <div class="flex gap-2 overflow-x-auto border-t border-white/10 bg-black/20 px-4 py-3">
                        @foreach ($photoPreviewPhotos as $index => $previewPhoto)
                            <button type="button" wire:click="showPhotoPreviewAt({{ $index }})"
                                class="flex-shrink-0 overflow-hidden rounded-lg transition-all {{ $photoPreviewIndex === $index ? 'ring-2 ring-orange-400 ring-offset-2 ring-offset-slate-900 scale-105' : 'opacity-60 hover:opacity-100' }}">
                                <img src="{{ $previewPhoto['url'] }}" alt="Thumbnail {{ $index + 1 }}"
                                    class="h-16 w-24 object-cover">
                            </button>
                        @endforeach
                    </div>
                @endif

                <div class="flex flex-wrap items-center justify-end gap-2 border-t border-white/10 bg-black/30 px-4 py-3">
                    <a href="{{ $photoPreviewUrl }}" download target="_blank"
                        class="inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                        <i class="fas fa-download mr-2"></i>Download
                    </a>
                    @if ($photoPreviewCanDelete)
                        <button type="button" wire:click="deletePreviewPhoto"
                            wire:confirm="Delete this photo?"
                            class="inline-flex items-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-red-700">
                            <i class="fas fa-trash mr-2"></i>Delete
                        </button>
                    @endif
                    <button type="button" wire:click="closePhotoPreview"
                        class="inline-flex items-center rounded-lg border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Tube Request Modal -->
    @if ($isGuestMode)
        @if ($showTubeRequestModal)
            <div wire:key="tube-request-modal-{{ $selectedTubeId }}"
                class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50" id="modal">
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
                                    <p class="text-sm text-gray-600"><strong>Code:</strong> {{ $selectedTube->code }}
                                    </p>
                                    <p class="text-sm text-gray-600"><strong>Type:</strong>
                                        {{ $selectedTube->tube_type }}</p>
                                    <p class="text-sm text-gray-600"><strong>Source Project:</strong>
                                        {{ $sourceProject->code ?? 'N/A' }}</p>
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
    @endif
</div>
