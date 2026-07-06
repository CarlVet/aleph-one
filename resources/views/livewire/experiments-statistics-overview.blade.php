<div>
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h2 class="text-2xl font-semibold">Experiments Overview</h2>
            <a href="/experiments/statistics" class="text-blue-500 hover:text-blue-600">
                <i class="fas fa-arrow-left mr-2"></i>Back to Statistics
            </a>
        </div>

        <!-- Key Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Total Experiments</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $experiments->count() }}</p>
                    </div>
                    <div class="text-3xl text-blue-500">
                        <i class="fas fa-flask"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Animal Experiments</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $experiments_animals->count() }}</p>
                    </div>
                    <div class="text-3xl text-green-500">
                        <i class="fas fa-paw"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Parasite Experiments</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $experiments_parasites->count() }}</p>
                    </div>
                    <div class="text-3xl text-red-500">
                        <i class="fas fa-bug"></i>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-lg">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-600">Nucleic Acid Experiments</p>
                        <p class="text-2xl font-bold text-gray-800">{{ $experiments_nucleic->count() }}</p>
                    </div>
                    <div class="text-3xl text-purple-500">
                        <i class="fas fa-dna"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Distribution Chart -->
        <div class="bg-white p-6 rounded-xl shadow-lg mb-8">
            <h3 class="text-xl font-semibold mb-4">Experiment Type Distribution</h3>
            <div class="h-80">
                <canvas id="experimentDistributionChart"></canvas>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="bg-white p-6 rounded-xl shadow-lg">
            <h3 class="text-xl font-semibold mb-4">Recent Activity</h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Experiment</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th class="px-6 py-3 bg-gray-50 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($experiments->take(5) as $experiment)
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">{{ $experiment->name }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $experiment->type }}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $experiment->created_at->format('M d, Y') }}</td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Active
                                </span>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('livewire:load', function () {
            const ctx = document.getElementById('experimentDistributionChart').getContext('2d');
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: ['Animal', 'Parasite', 'Nucleic Acid'],
                    datasets: [{
                        data: [
                            {{ $experiments_animals->count() }},
                            {{ $experiments_parasites->count() }},
                            {{ $experiments_nucleic->count() }}
                        ],
                        backgroundColor: [
                            'rgba(34, 197, 94, 0.8)',
                            'rgba(239, 68, 68, 0.8)',
                            'rgba(168, 85, 247, 0.8)'
                        ],
                        borderColor: [
                            'rgb(34, 197, 94)',
                            'rgb(239, 68, 68)',
                            'rgb(168, 85, 247)'
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