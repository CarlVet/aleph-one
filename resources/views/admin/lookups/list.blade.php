<x-layout>
    <div class="mx-auto max-w-6xl">
        <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <a href="{{ route('admin.lookups.index') }}" class="hover:text-gray-700">Admin</a>
                    <span>/</span>
                    <span>{{ $definition['title'] }}</span>
                </div>
                <h1 class="mt-1 text-2xl font-bold text-gray-900">{{ $definition['title'] }}</h1>
                <p class="mt-1 text-sm text-gray-600">Global values shared across projects. Use caution when editing linked data.</p>
            </div>

            <div class="flex items-center gap-3">
                <a href="{{ route('admin.lookups.index') }}"
                    class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-left"></i>
                    All tables
                </a>
                <a href="{{ route('admin.lookups.create', $lookup) }}"
                    class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                    <i class="fa-solid fa-plus"></i>
                    Create
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="mt-6 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <form method="GET" action="{{ route('admin.lookups.show', $lookup) }}"
            class="mt-6 rounded-2xl border border-gray-200 bg-white p-4 shadow">
            <div class="flex flex-col gap-3 md:flex-row">
                <input type="text" name="q" value="{{ $search }}" placeholder="Search {{ strtolower($definition['title']) }}..."
                    class="w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                        Search
                    </button>
                    @if ($search !== '')
                        <a href="{{ route('admin.lookups.show', $lookup) }}"
                            class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>

        <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            @foreach ($definition['list_columns'] as $column)
                                <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">
                                    {{ $definition['fields'][$column]['label'] ?? str($column)->replace('_', ' ')->title() }}
                                </th>
                            @endforeach
                            <th class="px-4 py-3 text-left text-xs font-bold uppercase tracking-wider text-gray-600">Linked data</th>
                            <th class="px-4 py-3 text-right text-xs font-bold uppercase tracking-wider text-gray-600">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse ($records as $record)
                            @php
                                $linkedUsage = $usageById[$record->id] ?? [];
                                $linkedTotal = collect($linkedUsage)->sum('count');
                                $deleteMessage = $linkedTotal > 0
                                    ? 'This value is linked to '.$linkedTotal.' records. Deletion is blocked until dependent data is fixed.'
                                    : 'Delete this entry?';
                            @endphp
                            <tr class="hover:bg-gray-50">
                                @foreach ($definition['list_columns'] as $column)
                                    <td class="px-4 py-3 text-sm text-gray-800">
                                        {{ \App\Support\AdminLookupRegistry::displayValue($record, $lookup, $column) }}
                                    </td>
                                @endforeach
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    @if ($linkedTotal > 0)
                                        <details class="group">
                                            <summary
                                                class="flex cursor-pointer list-none items-center gap-2 text-left">
                                                <span
                                                    class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                                                    {{ number_format($linkedTotal) }} linked
                                                </span>
                                                <span class="text-xs text-gray-500">View details</span>
                                            </summary>
                                            <div class="mt-2 space-y-2">
                                                @foreach ($linkedUsage as $usage)
                                                    <div class="rounded-lg border border-amber-100 bg-amber-50/60 p-2">
                                                        <div class="text-xs font-semibold text-amber-900">
                                                            {{ $usage['count'] }} {{ $usage['label'] }}
                                                        </div>
                                                        <div class="mt-1 text-xs text-gray-600">
                                                            {{ implode('; ', $usage['examples']) }}
                                                            @if ($usage['remaining_count'] > 0)
                                                                ; +{{ $usage['remaining_count'] }} more
                                                            @endif
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <span class="text-gray-400">None</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <div class="inline-flex items-center gap-2">
                                        <a href="{{ route('admin.lookups.edit', [$lookup, $record->id]) }}"
                                            class="rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                                            Edit
                                        </a>
                                        <form method="POST" action="{{ route('admin.lookups.destroy', [$lookup, $record->id]) }}"
                                            onsubmit="return confirm(@js($deleteMessage));">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="rounded-lg px-3 py-1.5 text-sm font-semibold {{ $linkedTotal > 0 ? 'border border-amber-200 bg-amber-50 text-amber-800 hover:bg-amber-100' : 'border border-red-200 bg-red-50 text-red-700 hover:bg-red-100' }}">
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($definition['list_columns']) + 2 }}"
                                    class="px-4 py-8 text-center text-sm text-gray-500">
                                    No entries found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="border-t border-gray-200 bg-white px-4 py-3">
                {{ $records->onEachSide(1)->links() }}
            </div>
        </div>
    </div>
</x-layout>
