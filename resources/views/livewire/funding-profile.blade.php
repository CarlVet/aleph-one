<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <!-- Header -->
        <div class="bg-gradient-to-r from-purple-900 to-purple-800 rounded-t-xl shadow-lg">
            <div class="px-6 py-8">
                <div class="flex justify-between items-start gap-4">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="bg-white/20 p-3 rounded-lg">
                                <i class="fas fa-hand-holding-usd text-white text-2xl"></i>
                            </div>
                            <div class="min-w-0">
                                <h1 class="text-3xl font-bold text-white truncate">Funding details</h1>
                                <p class="text-purple-100 text-lg truncate">{{ $funding->source ?? 'Funding source' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('profile.projects') }}"
                           class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to My Projects
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content -->
        <div class="bg-white shadow-lg rounded-b-xl">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 p-8">
                <div class="space-y-6">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-purple-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-info-circle text-purple-700"></i>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Funding information</h2>
                        </div>

                        <div class="space-y-4 text-sm">
                            <div>
                                <div class="text-xs font-medium text-gray-500 mb-1">Recipient</div>
                                @if($funding->recipient)
                                    <a href="{{ route('profile.show', $funding->recipient->id) }}"
                                       class="text-blue-600 hover:text-blue-800 font-medium">
                                        {{ trim(($funding->recipient->title ?? '').' '.($funding->recipient->first_name ?? '').' '.($funding->recipient->last_name ?? '')) ?: 'N/A' }}
                                    </a>
                                @else
                                    <div class="text-gray-900">N/A</div>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs font-medium text-gray-500 mb-1">Amount</div>
                                    <div class="text-gray-900 font-medium">{{ $funding->currency ?? '—' }} {{ $funding->amount ?? '—' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-medium text-gray-500 mb-1">Reference</div>
                                    <div class="text-gray-900">{{ $funding->reference ?? 'N/A' }}</div>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <div class="text-xs font-medium text-gray-500 mb-1">Start date</div>
                                    <div class="text-gray-900">{{ $funding->start_date?->format('Y-m-d') ?? 'N/A' }}</div>
                                </div>
                                <div>
                                    <div class="text-xs font-medium text-gray-500 mb-1">End date</div>
                                    <div class="text-gray-900">{{ $funding->end_date?->format('Y-m-d') ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                                <i class="fas fa-folder-open text-indigo-700"></i>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Linked projects</h2>
                        </div>

                        @if($funding->projects->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach($funding->projects as $project)
                                    <a href="{{ route('projects.profile', $project->code) }}"
                                       class="block bg-white border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0">
                                                <div class="text-sm font-semibold text-gray-900">{{ $project->code }}</div>
                                                <div class="text-xs text-gray-600 mt-1">{{ $project->title ?? 'N/A' }}</div>
                                            </div>
                                            <span class="shrink-0 text-xs font-semibold px-2 py-1 rounded-full bg-gray-100 text-gray-800">
                                                {{ $project->status ?? 'active' }}
                                            </span>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-10 bg-white rounded-lg border-2 border-dashed border-gray-300">
                                <div class="text-sm text-gray-600">No projects linked to this funding record.</div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

