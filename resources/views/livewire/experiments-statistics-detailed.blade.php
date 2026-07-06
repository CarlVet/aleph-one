<div>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-semibold">Detailed Experiments Analysis</h2>
            <a href="/experiments/statistics" class="text-blue-500 hover:text-blue-600">
                <i class="fas fa-arrow-left mr-2"></i>Back to Statistics
            </a>
        </div>

        <!-- Time Series Chart -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <h3 class="text-xl font-semibold mb-4">Experiments Over Time</h3>
            <div class="h-96">
                <canvas id="experimentsTimeSeriesChart"></canvas>
            </div>
        </div>

        <!-- Detailed Metrics -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Animal Experiments -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h3 class="text-xl font-semibold mb-4">Animal Experiments Analysis</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Experiments</span>
                        <span class="font-semibold">{{ $experiments_animals->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Active Experiments</span>
                        <span class="font-semibold">{{ $experiments_animals->where('status', 'active')->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Completed Experiments</span>
                        <span class="font-semibold">{{ $experiments_animals->where('status', 'completed')->count() }}</span>
                    </div>
                </div>
            </div>

            <!-- Parasite Experiments -->
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <h3 class="text-xl font-semibold mb-4">Parasite Experiments Analysis</h3>
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Total Experiments</span>
                        <span class="font-semibold">{{ $experiments_parasites->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Active Experiments</span>
                        <span class="font-semibold">{{ $experiments_parasites->where('status', 'active')->count() }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">Completed Experiments</span>
                        <span class="font-semibold">{{ $experiments_parasites->where('status', 'completed')->count() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Nucleic Acid Experiments -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <h3 class="text-xl font-semibold mb-4">Nucleic Acid Experiments Analysis</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Total Experiments</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $experiments_nucleic->count() }}</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Active Experiments</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $experiments_nucleic->where('status', 'active')->count() }}</p>
                </div>
                <div class="p-4 bg-gray-50 rounded-lg">
                    <p class="text-sm text-gray-600">Completed Experiments</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $experiments_nucleic->where('status', 'completed')->count() }}</p>
                </div>
            </div>
        </div>

        <!-- Status Distribution -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h3 class="text-xl font-semibold mb-4">Experiment Status Distribution</h3>
            <div class="h-80">
                <canvas id="statusDistributionChart"></canvas>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:load', function () {
            // Time Series Chart
            const timeSeriesCtx = document.getElementById('experimentsTimeSeriesChart').getContext('2d');
            new Chart(timeSeriesCtx, {
                type: 'line',
                data: {
                    labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                    datasets: [{
                        label: 'Animal Experiments',
                        data: [12, 19, 15, 17, 22, 25],
                        borderColor: 'rgb(34, 197, 94)',
                        tension: 0.1
                    }, {
                        label: 'Parasite Experiments',
                        data: [8, 12, 9, 14, 16, 18],
                        borderColor: 'rgb(239, 68, 68)',
                        tension: 0.1
                    }, {
                        label: 'Nucleic Acid Experiments',
                        data: [5, 8, 6, 9, 11, 13],
                        borderColor: 'rgb(168, 85, 247)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // Status Distribution Chart
            const statusCtx = document.getElementById('statusDistributionChart').getContext('2d');
            new Chart(statusCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Active', 'Completed', 'Pending'],
                    datasets: [{
                        data: [
                            {{ $experiments->where('status', 'active')->count() }},
                            {{ $experiments->where('status', 'completed')->count() }},
                            {{ $experiments->where('status', 'pending')->count() }}
                        ],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(59, 130, 246, 0.8)',
                            'rgba(245, 158, 11, 0.8)'
                        ],
                        borderColor: [
                            'rgb(34, 197, 94)',
                            'rgb(59, 130, 246)',
                            'rgb(245, 158, 11)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });
        });
    </script>
    @endpush
</div> 