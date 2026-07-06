<x-layout>
    <div class="mx-auto max-w-7xl">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Admin → Check data for publication</h1>
                <p class="mt-1 text-sm text-gray-600">Review publication submissions and decide whether they can be made public.</p>
            </div>

            <div class="flex flex-wrap items-center gap-3">
                <a href="{{ route('admin.lookups.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-table-cells-large"></i>
                    Lookup tables
                </a>
                <a href="{{ route('admin.announcements.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-bullhorn"></i>
                    Announcements
                </a>
            </div>
        </div>

        <form method="GET" class="mt-6 rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700">Status</label>
                    <select name="status"
                        class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        @foreach ($statusOptions as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($canSeeAllProjects)
                    <div>
                        <label class="mb-2 block text-sm font-medium text-gray-700">Project</label>
                        <select name="project"
                            class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="0">All projects</option>
                            @foreach ($projects as $project)
                                <option value="{{ $project->id }}" @selected($projectFilter === $project->id)>{{ $project->code }} - {{ $project->title }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="flex items-end">
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                        <i class="fa-solid fa-filter"></i>
                        Apply filters
                    </button>
                </div>
            </div>
        </form>

        <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Project</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Requester</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Data type</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Items</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Submitted</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($reviewRequests as $reviewRequest)
                            @php
                                $badgeClass = match ($reviewRequest->status) {
                                    'approved' => 'bg-green-100 text-green-800',
                                    'changes_requested' => 'bg-blue-100 text-blue-800',
                                    'rejected' => 'bg-red-100 text-red-800',
                                    default => 'bg-amber-100 text-amber-800',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                    <div class="font-semibold">{{ $reviewRequest->project?->code ?? 'N/A' }}</div>
                                    <div class="text-xs text-gray-500">{{ $reviewRequest->project?->title ?? 'Unknown project' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div class="font-medium text-gray-900">{{ $reviewRequest->requester?->people?->name ?: ($reviewRequest->requester?->email ?? 'Unknown user') }}</div>
                                    <div class="text-xs text-gray-500">{{ $reviewRequest->requester?->email ?? 'No email' }}</div>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    {{ \App\Support\PublicationReviewRegistry::label($reviewRequest->data_type, $reviewRequest->literature_type) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    {{ number_format((int) $reviewRequest->items_count) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    {{ optional($reviewRequest->submitted_at)->format('Y-m-d H:i') ?? 'N/A' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                        {{ $statusOptions[$reviewRequest->status] ?? ucfirst(str_replace('_', ' ', $reviewRequest->status)) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <a href="{{ route('admin.publication-reviews.show', $reviewRequest) }}"
                                        class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-600">
                                    No publication review requests match the current filters.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 bg-white px-4 py-3">
                {{ $reviewRequests->onEachSide(1)->links() }}
            </div>
        </div>
    </div>
</x-layout>
