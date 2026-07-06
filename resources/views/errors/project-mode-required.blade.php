<x-layout>
    <div class="mx-auto max-w-3xl px-4 py-16 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-3xl border border-amber-200 bg-white shadow-xl">
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 px-8 py-6 border-b border-amber-200">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                        <i class="fa-solid fa-lock text-2xl"></i>
                    </div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Select a project first</h1>
                        <p class="mt-1 text-sm text-gray-600">This page is available only in project mode.</p>
                    </div>
                </div>
            </div>

            <div class="px-8 py-8">
                <div class="rounded-2xl border border-gray-200 bg-gray-50 p-5">
                    <p class="text-sm text-gray-700">
                        You are currently browsing in guest mode. Select one of your projects first, then come back to this page.
                    </p>
                    <p class="mt-3 text-sm text-gray-700">
                        <span class="font-semibold text-gray-900">Requested page:</span>
                        <code class="ml-2 rounded bg-white px-2 py-1 text-xs text-gray-700">{{ $requestedPath }}</code>
                    </p>
                </div>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ $redirectUrl }}"
                        class="inline-flex items-center gap-2 rounded-xl bg-indigo-600 px-5 py-3 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                        <i class="fa-solid fa-folder-open"></i>
                        Go to My Projects
                    </a>
                    <a href="/"
                        class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-5 py-3 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                        <i class="fa-solid fa-house"></i>
                        Back to Home
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layout>
