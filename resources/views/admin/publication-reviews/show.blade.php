<x-layout>
    <div class="mx-auto max-w-6xl">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <a href="{{ route('admin.publication-reviews.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-700">
                    <i class="fa-solid fa-arrow-left mr-1"></i>
                    Back to review queue
                </a>
                <h1 class="mt-3 text-2xl font-bold text-gray-900">Publication review request</h1>
                <p class="mt-1 text-sm text-gray-600">Inspect the submitted data and decide whether it can be published.</p>
            </div>

            <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $statusBadges[$reviewRequest->status] ?? 'bg-gray-100 text-gray-700' }}">
                {{ $statusOptions[$reviewRequest->status] ?? ucfirst(str_replace('_', ' ', $reviewRequest->status)) }}
            </span>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Request details</h2>

                    <dl class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Project</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $reviewRequest->project?->code ?? 'N/A' }} - {{ $reviewRequest->project?->title ?? 'Unknown project' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Data type</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ \App\Support\PublicationReviewRegistry::label($reviewRequest->data_type, $reviewRequest->literature_type) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Requester</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $reviewRequest->requester?->people?->name ?: ($reviewRequest->requester?->email ?? 'Unknown user') }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Submitted</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ optional($reviewRequest->submitted_at)->format('Y-m-d H:i') ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Items</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ number_format($reviewRequest->items->count()) }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-semibold uppercase tracking-wide text-gray-500">Reviewed by</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $reviewRequest->reviewer?->people?->name ?: ($reviewRequest->reviewer?->email ?? '—') }}</dd>
                        </div>
                    </dl>

                    @if ($reviewRequest->requester_message)
                        <div class="mt-6 rounded-xl border border-gray-200 bg-gray-50 p-4">
                            <h3 class="text-sm font-semibold text-gray-800">Submission note</h3>
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ $reviewRequest->requester_message }}</p>
                        </div>
                    @endif

                    @if ($reviewRequest->reviewer_message)
                        <div class="mt-4 rounded-xl border border-indigo-200 bg-indigo-50 p-4">
                            <h3 class="text-sm font-semibold text-indigo-900">Latest admin message</h3>
                            <p class="mt-2 whitespace-pre-line text-sm text-indigo-800">{{ $reviewRequest->reviewer_message }}</p>
                        </div>
                    @endif
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Submitted items</h2>
                    <div class="mt-4 overflow-hidden rounded-xl border border-gray-200">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Code / reference</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Summary</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 bg-white">
                                    @foreach ($reviewRequest->items as $item)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $item->code ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ class_basename($item->reviewable_type) }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-700">{{ $item->summary ?? '—' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
                    <h2 class="text-lg font-semibold text-gray-900">Review action</h2>

                    @if ($reviewRequest->status !== 'pending')
                        <p class="mt-3 text-sm text-gray-600">This request has already been reviewed. Submit a new publication request if further changes are needed.</p>
                    @else
                        <form method="POST" action="{{ route('admin.publication-reviews.decide', $reviewRequest) }}" class="mt-4 space-y-4">
                            @csrf

                            <div>
                                <label for="reviewer_message" class="mb-2 block text-sm font-medium text-gray-700">Message to user</label>
                                <textarea id="reviewer_message" name="reviewer_message" rows="6"
                                    class="w-full rounded-xl border border-gray-300 px-3 py-2 text-sm text-gray-700 shadow-sm focus:border-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                    placeholder="Explain what was approved, what needs to change, or why this was rejected.">{{ old('reviewer_message') }}</textarea>
                                @error('reviewer_message')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="grid grid-cols-1 gap-3">
                                <button type="submit" name="decision" value="approved"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-green-700">
                                    <i class="fa-solid fa-check"></i>
                                    Approve
                                </button>
                                <button type="submit" name="decision" value="changes_requested"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-blue-700">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                    Request modifications
                                </button>
                                <button type="submit" name="decision" value="rejected"
                                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-red-700">
                                    <i class="fa-solid fa-ban"></i>
                                    Reject
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-layout>
