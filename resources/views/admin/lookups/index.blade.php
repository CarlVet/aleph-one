<x-layout>
    <div class="mx-auto max-w-6xl">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Admin → Global lookup tables</h1>
                <p class="mt-1 text-sm text-gray-600">Manage shared reference tables used across all projects.</p>
            </div>

            <a href="{{ route('admin.announcements.index') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="fa-solid fa-bullhorn"></i>
                Announcements
            </a>
        </div>

        <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($lookups as $entry)
                <a href="{{ route('admin.lookups.show', $entry['lookup']) }}"
                    class="rounded-2xl border border-gray-200 bg-white p-5 shadow transition hover:-translate-y-0.5 hover:border-indigo-300 hover:shadow-md">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900">{{ $entry['title'] }}</h2>
                            <p class="mt-1 text-sm text-gray-500">{{ number_format($entry['count']) }} entries</p>
                        </div>

                        <span
                            class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-indigo-50 text-indigo-700">
                            <i class="fa-solid fa-table-cells-large"></i>
                        </span>
                    </div>
                </a>
            @endforeach
        </div>
    </div>
</x-layout>
