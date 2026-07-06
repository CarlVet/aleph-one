<div>
    <!-- Guest Mode Banner -->
    @if ($isGuestMode)
        <div class="bg-gradient-to-r from-purple-100 to-blue-100 border border-purple-300 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-center space-x-3">
                <i class="fas fa-eye text-2xl text-purple-600"></i>
                <div>
                    <h3 class="text-lg font-semibold text-purple-800">Guest Mode Active</h3>
                    <p class="text-sm text-purple-600">You are viewing public animals from all projects</p>
                </div>
                <a href="/my-projects" 
                   class="inline-flex items-center px-4 py-2 text-sm font-medium text-purple-700 bg-white border border-purple-300 rounded-lg hover:bg-purple-50 transition-colors duration-200">
                    <i class="fas fa-user-lock mr-2"></i>
                    Switch to Project Mode
                </a>
            </div>
        </div>
    @endif

    <!-- Create, Edit, Dashboard (Centered) -->
    <div class="text-center flex justify-center space-x-4 mt-6">
        @if (!$isGuestMode)
            <a href="/animals/create"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                Create
            </a>
        @else
            <div class="px-6 py-3 text-sm font-medium text-gray-500 bg-gray-100 rounded-xl border border-gray-200">
                <i class="fas fa-lock mr-2"></i>
                Create (Project Mode)
            </div>
        @endif
        <a href="/animals/list"
            class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
            <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
            List
        </a>
    </div>

    <!-- Dashboard Title -->
    <div class="mt-8 text-center">
        <h2 class="text-3xl font-bold text-gray-800 mb-4">
            @if ($isGuestMode)
                <i class="fas fa-eye text-purple-600 mr-2"></i>
                Animals Dashboard
            @else
                Animals Dashboard
            @endif
        </h2>
        <p class="text-gray-600 mb-8">Comprehensive overview of animal population and distribution</p>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Total Animals -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-green-100 text-sm font-medium">Total Animals</p>
                    <p class="text-3xl font-bold">{{ $totalAnimals }}</p>
                </div>
                <div class="text-green-100">
                    <i class="fas fa-paw text-4xl"></i>
                </div>
            </div>
        </div>

        <!-- Species Count -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Species</p>
                    <p class="text-3xl font-bold">{{ $speciesCount }}</p>
                </div>
                <div class="text-blue-100">
                    <i class="fas fa-hippo text-4xl"></i>
                </div>
            </div>
        </div>

        <!-- Male Count -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Male</p>
                    <p class="text-3xl font-bold">{{ $maleCount }}</p>
                </div>
                <div class="text-purple-100">
                    <i class="fas fa-mars text-4xl"></i>
                </div>
            </div>
        </div>

        <!-- Female Count -->
        <div class="bg-gradient-to-br from-pink-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-pink-100 text-sm font-medium">Female</p>
                    <p class="text-3xl font-bold">{{ $femaleCount }}</p>
                </div>
                <div class="text-pink-100">
                    <i class="fas fa-venus text-4xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Species Distribution -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Species Distribution</h3>
            <div class="space-y-3">
                @foreach ($speciesDistribution as $species => $count)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">{{ $species }}</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-32 bg-gray-200 rounded-full h-2">
                                <div class="bg-green-500 h-2 rounded-full" style="width: {{ ($count / $totalAnimals) * 100 }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Age Distribution -->
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-xl font-semibold text-gray-800 mb-4">Age Distribution</h3>
            <div class="space-y-3">
                @foreach ($ageDistribution as $age => $count)
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-gray-700">{{ $age }}</span>
                        <div class="flex items-center space-x-2">
                            <div class="w-32 bg-gray-200 rounded-full h-2">
                                <div class="bg-blue-500 h-2 rounded-full" style="width: {{ ($count / $totalAnimals) * 100 }}%"></div>
                            </div>
                            <span class="text-sm font-medium text-gray-900">{{ $count }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- Recent Animals -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-semibold text-gray-800 mb-4">Recent Animals</h3>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Field ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Species</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Sex</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Age</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach ($animals->take(10) as $animal)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <a href="/animals/{{ $animal->code }}" class="text-green-600 hover:text-green-800 font-medium">
                                    {{ $animal->code }}
                                </a>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $animal->field_label }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-orange-100 text-orange-800">
                                    {{ $animal->animal_species->name_common }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $animal->sex }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                {{ $animal->age }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div> 