<div class="min-h-screen bg-gray-50 py-8">
    <!-- Filters Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Pathogen Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Pathogen</label>
                    <select wire:model.live="selectedPathogen" 
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Pathogens</option>
                        @foreach($pathogens as $pathogen)
                            <option value="{{ $pathogen->id }}">{{ $pathogen->species }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Time Range Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Time Range</label>
                    <select wire:model.live="selectedTimeRange"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="all">All Time</option>
                        <option value="last_month">Last Month</option>
                        <option value="last_3_months">Last 3 Months</option>
                        <option value="last_6_months">Last 6 Months</option>
                        <option value="last_year">Last Year</option>
                    </select>
                </div>

                <!-- Group Filter -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sample Group</label>
                    <select wire:model.live="selectedGroup"
                            class="w-full rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="all">All Groups</option>
                        <option value="animal">Animal Samples</option>
                        <option value="parasite">Parasite Samples</option>
                        <option value="nucleic">Nucleic Acids</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Total Samples Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Samples</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $prevalenceData['total_samples'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-full">
                        <i class="fas fa-flask text-2xl text-blue-600"></i>
                    </div>
                </div>
            </div>

            <!-- Positive Samples Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Positive Samples</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $prevalenceData['positive_samples'] }}</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-full">
                        <i class="fas fa-check-circle text-2xl text-green-600"></i>
                    </div>
                </div>
            </div>

            <!-- Prevalence Card -->
            <div class="bg-white rounded-xl shadow-lg p-6 transform transition-all duration-300 hover:scale-105">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-500">Prevalence</p>
                        <p class="text-3xl font-bold text-gray-900">{{ $prevalenceData['prevalence'] }}%</p>
                        <p class="text-sm text-gray-500">
                            95% CI: {{ $prevalenceData['confidence_interval']['lower'] }}% - {{ $prevalenceData['confidence_interval']['upper'] }}%
                        </p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-full">
                        <i class="fas fa-chart-pie text-2xl text-purple-600"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Risk Factors Analysis -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
        <div class="bg-white rounded-xl shadow-lg p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Risk Factors Analysis</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocol</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Samples</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Positive Samples</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prevalence</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">95% CI</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($riskFactors as $factor)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $factor['factor'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $factor['protocol'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $factor['total'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $factor['positives'] }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $factor['prevalence'] }}%</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $factor['confidence_interval']['lower'] }}% - {{ $factor['confidence_interval']['upper'] }}%
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Prevalence Trend Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Prevalence Trend</h3>
                <div class="h-80">
                    <canvas id="prevalenceTrendChart"></canvas>
                </div>
            </div>

            <!-- Risk Factors Comparison Chart -->
            <div class="bg-white rounded-xl shadow-lg p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Risk Factors Comparison</h3>
                <div class="h-80">
                    <canvas id="riskFactorsChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div>
        <div class="text-center mt-2">
            <h2 class="text-2xl font-semibold mb-4">Experiments Statistics</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 text-center">
                <a href="/experiments/statistics/overview" class="group">
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <div class="text-4xl mb-4 text-blue-500 group-hover:text-blue-600">
                            <i class="fas fa-chart-pie"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Overview Statistics</h3>
                        <p class="text-gray-600">Get a high-level overview of your experiments data with key metrics and trends.</p>
                    </div>
                </a>

                <a href="/experiments/statistics/detailed" class="group">
                    <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100 hover:shadow-xl transition-all duration-300 transform hover:scale-105">
                        <div class="text-4xl mb-4 text-green-500 group-hover:text-green-600">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <h3 class="text-xl font-semibold text-gray-800 mb-2">Detailed Analysis</h3>
                        <p class="text-gray-600">Dive deep into your experiments data with comprehensive statistical analysis.</p>
                    </div>
                </a>

                <div class="bg-white p-6 rounded-xl shadow-lg border border-gray-100">
                    <div class="text-4xl mb-4 text-purple-500">
                        <i class="fas fa-database"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800 mb-2">Data Summary</h3>
                    <div class="grid grid-cols-2 gap-4 text-left">
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">Total Experiments</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $experiments->count() }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">Animal Experiments</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $experiments_animals->count() }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">Parasite Experiments</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $experiments_parasites->count() }}</p>
                        </div>
                        <div class="p-3 bg-gray-50 rounded-lg">
                            <p class="text-sm text-gray-600">Nucleic Acid Experiments</p>
                            <p class="text-2xl font-bold text-gray-800">{{ $experiments_nucleic->count() }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:initialized', function () {
        // Initialize charts
        const prevalenceTrendCtx = document.getElementById('prevalenceTrendChart').getContext('2d');
        const riskFactorsCtx = document.getElementById('riskFactorsChart').getContext('2d');

        // Prevalence Trend Chart
        new Chart(prevalenceTrendCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Prevalence',
                    data: [12, 19, 15, 17, 22, 20],
                    borderColor: 'rgb(59, 130, 246)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });

        // Risk Factors Chart
        new Chart(riskFactorsCtx, {
            type: 'bar',
            data: {
                labels: ['Location A', 'Location B', 'Location C'],
                datasets: [{
                    label: 'Prevalence',
                    data: [15, 25, 18],
                    backgroundColor: 'rgba(59, 130, 246, 0.5)',
                    borderColor: 'rgb(59, 130, 246)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    });
</script>
@endpush 