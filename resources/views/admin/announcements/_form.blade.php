@php
    $types = [
        'update' => ['label' => 'Update', 'icon' => 'fa-arrows-rotate'],
        'meeting' => ['label' => 'Meeting', 'icon' => 'fa-calendar-days'],
        'meeting_summary' => ['label' => 'Meeting summary', 'icon' => 'fa-calendar-days'],
        'fix' => ['label' => 'Fix', 'icon' => 'fa-screwdriver-wrench'],
        'malfunction' => ['label' => 'Malfunction', 'icon' => 'fa-triangle-exclamation'],
        'info' => ['label' => 'Info', 'icon' => 'fa-circle-info'],
    ];

    $visibilities = [
        'all' => 'Everyone (guest + authenticated)',
        'authenticated' => 'Authenticated users only',
        'guest' => 'Guest mode only',
    ];
@endphp

<div class="grid grid-cols-1 gap-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-semibold text-gray-700">Type</label>
            <select name="type"
                class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($types as $value => $meta)
                    <option value="{{ $value }}" @selected(old('type', $announcement->type ?? 'update') === $value)>
                        {{ $meta['label'] }}
                    </option>
                @endforeach
            </select>
            @error('type')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-semibold text-gray-700">Visibility rules</label>
            <select name="visibility"
                class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                @foreach ($visibilities as $value => $label)
                    <option value="{{ $value }}" @selected(old('visibility', $announcement->visibility ?? 'all') === $value)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('visibility')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700">Title</label>
        <input type="text" name="title" value="{{ old('title', $announcement->title ?? '') }}"
            class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
        @error('title')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700">Content</label>
        <textarea name="message" rows="6"
            class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('message', $announcement->message ?? '') }}</textarea>
        @error('message')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-semibold text-gray-700">Start date</label>
            <input type="datetime-local" name="starts_at"
                value="{{ old('starts_at', isset($announcement) && $announcement->starts_at ? $announcement->starts_at->format('Y-m-d\\TH:i') : '') }}"
                class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('starts_at')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700">End date</label>
            <input type="datetime-local" name="ends_at"
                value="{{ old('ends_at', isset($announcement) && $announcement->ends_at ? $announcement->ends_at->format('Y-m-d\\TH:i') : '') }}"
                class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('ends_at')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
            <label class="block text-sm font-semibold text-gray-700">Git commit hash (optional)</label>
            <input type="text" name="git_commit_hash" value="{{ old('git_commit_hash', $announcement->git_commit_hash ?? '') }}"
                class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('git_commit_hash')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
        <div>
            <label class="block text-sm font-semibold text-gray-700">Git commit message (optional)</label>
            <input type="text" name="git_commit_message"
                value="{{ old('git_commit_message', $announcement->git_commit_message ?? '') }}"
                class="mt-1 w-full rounded-xl border-gray-200 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" />
            @error('git_commit_message')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-gray-700">Advanced visibility rules (JSON, optional)</label>
        <textarea name="visibility_rules" rows="3"
            class="mt-1 w-full rounded-xl border-gray-200 font-mono text-xs shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            placeholder='{"include_emails":["a@b.com"],"exclude_emails":["x@y.com"]}'>{{ old('visibility_rules', isset($announcement) && is_array($announcement->visibility_rules) ? json_encode($announcement->visibility_rules) : '') }}</textarea>
        <p class="mt-1 text-xs text-gray-500">If invalid JSON, it will be ignored.</p>
        @error('visibility_rules')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>
</div>

