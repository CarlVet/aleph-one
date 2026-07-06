<div class="bg-white rounded-xl shadow-xl border border-gray-100 overflow-hidden">
    <div class="flex flex-col h-full">
        <!-- Table Header Section -->
        <div class="bg-gradient-to-r from-cyan-50 to-blue-50 px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-800 flex items-center">
                <svg class="w-5 h-5 mr-2 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                    </path>
                </svg>
                Pools Selection
            </h3>
            <p class="text-sm text-gray-600 mt-1">Select sample pools</p>
        </div>

        <div class="bg-gradient-to-r from-gray-50 to-cyan-50 px-6 py-6 border-t border-gray-200">
            <div class="flex flex-col sm:flex-row justify-between items-center space-y-4 sm:space-y-0">
                <div class="text-sm text-gray-600">
                    <span id="selected_count" class="font-semibold text-cyan-600">0</span> pools selected
                </div>
                <div class="flex space-x-3">
                    <button type="button" id="pools_cancel_btn"
                        class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition-all duration-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        Cancel
                    </button>
                    <button id="confirm_pools_selection"
                        class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-cyan-500 transition-all duration-200 transform hover:scale-105">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        Confirm Selection
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table Container -->
    <div class="overflow-x-auto">
        <table id="pools_selection_table" class="w-full">
            <thead class="bg-gray-50 border-b border-gray-200">
                <tr>
                    <th scope="col" class="sticky left-0 z-10 bg-gray-50 px-6 py-4 text-left">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" id="select_all_pools"
                                class="h-5 w-5 text-cyan-600 focus:ring-cyan-500 border-gray-300 rounded transition-all duration-200">
                            <label for="select_all_pools"
                                class="text-sm font-semibold text-gray-700 cursor-pointer hover:text-cyan-600 transition-colors">
                                Select All
                            </label>
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">
                        <div class="flex items-center">
                            <span>Pool Code</span>
                            <svg class="w-4 h-4 ml-1 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                        </div>
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Number of
                        Samples</th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content
                        Type</th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Content ID
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Date Pooled
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Pooled at
                    </th>
                    <th scope="col"
                        class="px-6 py-4 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Created By
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @foreach ($pools as $pool)
                    <tr class="hover:bg-cyan-50 transition-all duration-200 ease-in-out group">
                        <td
                            class="sticky left-0 z-10 bg-white group-hover:bg-cyan-50 px-6 py-4 border-r border-gray-100">
                            <div class="flex items-center">
                                <input type="checkbox"
                                    class="select-pool h-5 w-5 text-cyan-600 focus:ring-cyan-500 border-gray-300 rounded transition-all duration-200 hover:scale-110"
                                    value="{{ $pool->id }}">
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <a href="/samples/pools/{{ $pool->code }}"
                                class="text-sm font-medium text-cyan-600 hover:text-cyan-800 hover:underline">
                                {{ $pool->code }}
                            </a>
                        </td>
                        <td class="px-6 py-4">
                            <span
                                class="text-sm font-medium text-gray-900">{{ $pool->nr_pooled ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if (optional($pool->pool_contents->first())->samples_type === 'App\Models\HumanSamples')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-pink-100 text-pink-800">Human
                                    Sample</span>
                            @elseif(optional($pool->pool_contents->first())->samples_type === 'App\Models\AnimalSamples')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Animal
                                    Sample</span>
                            @elseif(optional($pool->pool_contents->first())->samples_type === 'App\Models\EnvironmentSamples')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Environmental
                                    Sample</span>
                            @elseif (optional($pool->pool_contents->first())->samples_type === 'App\Models\ParasiteSamples')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">Parasite
                                    Sample</span>
                            @elseif(optional($pool->pool_contents->first())->samples_type === 'App\Models\NucleicAcids')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">Nucleic
                                    Acids</span>
                            @elseif(optional($pool->pool_contents->first())->samples_type === 'App\Models\Cultures')
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">Cultures</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <ul class="text-sm text-gray-900">
                                @foreach ($pool->pool_contents as $pool_content)
                                    @php($contentCode = data_get($pool_content, 'samples.code'))
                                    <li>
                                        @if (optional($pool->pool_contents->first())->samples_type === 'App\Models\HumanSamples')
                                            <a href="/samples/humans/{{ $contentCode }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-800 hover:underline">
                                                {{ $contentCode ?? 'N/A' }}
                                            </a>
                                        @elseif(optional($pool->pool_contents->first())->samples_type === 'App\Models\AnimalSamples')
                                            <a href="/samples/animals/{{ $contentCode }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-800 hover:underline">
                                                {{ $contentCode ?? 'N/A' }}
                                            </a>
                                        @elseif(optional($pool->pool_contents->first())->samples_type === 'App\Models\EnvironmentSamples')
                                            <a href="/samples/environment/{{ $contentCode }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-800 hover:underline">
                                                {{ $contentCode ?? 'N/A' }}
                                            </a>
                                        @elseif (optional($pool->pool_contents->first())->samples_type === 'App\Models\ParasiteSamples')
                                            <a href="/samples/parasites/{{ $contentCode }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-800 hover:underline">
                                                {{ $contentCode ?? 'N/A' }}
                                            </a>
                                        @elseif(optional($pool->pool_contents->first())->samples_type === 'App\Models\NucleicAcids')
                                            <a href="/samples/nucleic/{{ $contentCode }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-800 hover:underline">
                                                {{ $contentCode ?? 'N/A' }}
                                            </a>
                                        @elseif(optional($pool->pool_contents->first())->samples_type === 'App\Models\Cultures')
                                            <a href="/samples/cultures/{{ $contentCode }}" class="text-sm font-medium text-cyan-600 hover:text-cyan-800 hover:underline">
                                                {{ $contentCode ?? 'N/A' }}
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ $pool->date_pooled ? \Carbon\Carbon::parse($pool->date_pooled)->format('Y-m-d') : 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-gray-900">{{ data_get($pool, 'laboratories.name') ?? 'N/A' }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @php($pooledBy = trim((data_get($pool, 'people.title') ?? '').' '.(data_get($pool, 'people.first_name') ?? '').' '.(data_get($pool, 'people.last_name') ?? '')))
                            <span class="text-sm text-gray-900">{{ $pooledBy !== '' ? $pooledBy : 'N/A' }}</span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if (method_exists($pools, 'links'))
        <div class="px-6 py-4 border-t border-gray-200 bg-white">
            {{ $pools->withPath(route('samples.process.samples.pool'))->withQueryString()->onEachSide(1)->links() }}
        </div>
    @endif
</div>
