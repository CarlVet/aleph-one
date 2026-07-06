<x-layout>
    <div class="max-w-6xl mx-auto">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Admin → Announcements</h1>
                <p class="mt-1 text-sm text-gray-600">Create and manage global announcements visible to everyone.</p>
            </div>

            <a href="{{ route('admin.announcements.create') }}"
                class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                <i class="fa-solid fa-plus"></i>
                Create
            </a>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Title</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Visibility</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Start</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">End</th>
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Status</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach ($announcements as $a)
                            @php
                                $now = now();
                                $active = (! $a->starts_at || $a->starts_at <= $now) && (! $a->ends_at || $a->ends_at >= $now);
                                $typeIcon = match ((string) $a->type) {
                                    'update' => 'fa-arrows-rotate',
                                    'meeting' => 'fa-calendar-days',
                                    'meeting_summary' => 'fa-calendar-days',
                                    'fix' => 'fa-screwdriver-wrench',
                                    'malfunction' => 'fa-triangle-exclamation',
                                    'info' => 'fa-circle-info',
                                    default => 'fa-bullhorn',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-900">
                                    <span class="inline-flex items-center gap-2">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-gray-100">
                                            <i class="fa-solid {{ $typeIcon }} text-gray-700"></i>
                                        </span>
                                        <span class="font-medium">{{ $a->type }}</span>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <div class="font-semibold">{{ $a->title }}</div>
                                    <div class="mt-1 line-clamp-2 text-xs text-gray-500">{{ $a->message }}</div>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    {{ $a->visibility }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    {{ $a->starts_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-700">
                                    {{ $a->ends_at?->format('Y-m-d H:i') ?? '—' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    @if ($active)
                                        <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-800">Active</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700">Inactive</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.announcements.edit', $a) }}"
                                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}"
                                            onsubmit="return confirm('Delete this announcement?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-sm font-semibold text-red-700 hover:bg-red-100">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 bg-white px-4 py-3">
                {{ $announcements->onEachSide(1)->links() }}
            </div>
        </div>
    </div>
</x-layout>

