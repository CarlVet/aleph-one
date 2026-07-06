<x-layout>
    <div class="mx-auto max-w-3xl">
        <div class="flex items-start justify-between gap-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">Edit {{ strtolower($definition['title']) }} entry</h1>
                <p class="mt-1 text-sm text-gray-600">Changes here affect all linked records that reference this value.</p>
            </div>

            <a href="{{ route('admin.lookups.show', $lookup) }}"
                class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="fa-solid fa-arrow-left"></i>
                Back
            </a>
        </div>

        @if ($linkedTotal > 0)
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <div class="font-semibold">Linked data warning</div>
                <div class="mt-1">
                    This value is currently linked to {{ number_format($linkedTotal) }} records:
                    {{ collect($linkedUsage)->map(fn ($usage) => $usage['count'].' '.$usage['label'])->implode(', ') }}.
                </div>
                <div class="mt-3 space-y-3">
                    @foreach ($linkedUsage as $usage)
                        <div class="rounded-lg border border-amber-200 bg-white/70 p-3">
                            <div class="text-sm font-semibold text-amber-950">
                                {{ $usage['count'] }} {{ $usage['label'] }}
                            </div>
                            <ul class="mt-2 space-y-1 text-xs text-gray-700">
                                @foreach ($usage['examples'] as $example)
                                    <li>{{ $example }}</li>
                                @endforeach
                                @if ($usage['remaining_count'] > 0)
                                    <li class="font-medium text-amber-900">+{{ $usage['remaining_count'] }} more linked records</li>
                                @endif
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="mt-6 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                {{ session('error') }}
            </div>
        @endif

        <form method="POST" action="{{ route('admin.lookups.update', [$lookup, $record->id]) }}"
            class="mt-6 rounded-2xl border border-gray-200 bg-white p-6 shadow"
            @if ($editConfirmMessage) onsubmit="return confirm(@js($editConfirmMessage));" @endif>
            @csrf
            @method('PATCH')

            @include('admin.lookups._form')

            <div class="mt-8 flex items-center justify-end gap-3">
                <a href="{{ route('admin.lookups.show', $lookup) }}"
                    class="rounded-xl border border-gray-200 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancel</a>
                <button type="submit"
                    class="rounded-xl bg-indigo-600 px-5 py-2 text-sm font-semibold text-white shadow hover:bg-indigo-700">
                    Save changes
                </button>
            </div>
        </form>
    </div>
</x-layout>
