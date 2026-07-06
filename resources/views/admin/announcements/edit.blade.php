<x-layout>
    <div class="max-w-3xl mx-auto">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit announcement</h1>
                <p class="mt-1 text-sm text-gray-600">Update title/content/dates/visibility.</p>
            </div>

            <a href="{{ route('admin.announcements.index') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="fa-solid fa-arrow-left"></i>
                Back
            </a>
        </div>

        <form method="POST" action="{{ route('admin.announcements.update', $announcement) }}"
            class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow">
            @csrf
            @method('PATCH')

            @include('admin.announcements._form', ['announcement' => $announcement])

            <div class="mt-8 flex items-center justify-end gap-3">
                <a href="{{ route('admin.announcements.index') }}"
                    class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit"
                    class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</x-layout>

