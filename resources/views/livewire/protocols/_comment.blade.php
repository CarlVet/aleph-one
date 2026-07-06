@php
    $person = $comment->user?->people;
    $title = trim((string) ($person?->title ?? ''));
    $name = trim((string) ($person?->name ?? ''));
    $label = trim($title.' '.$name) ?: ($comment->user?->email ?? 'Deleted user');

    $photoPath = trim((string) (data_get($person, 'pic_path')
        ?? data_get($person, 'photo_path')
        ?? data_get($person, 'photo')
        ?? data_get($person, 'profile_photo_path')
        ?? ''));

    $photoUrl = null;
    if ($photoPath !== '') {
        if (str_starts_with($photoPath, 'http://') || str_starts_with($photoPath, 'https://')) {
            $photoUrl = $photoPath;
        } elseif (str_starts_with($photoPath, '/')) {
            // Public path (e.g. "/images/carlo.jpeg")
            $photoUrl = asset($photoPath);
        } elseif (str_starts_with($photoPath, 'images/')) {
            // Public path without leading slash (e.g. "images/carlo.jpeg")
            $photoUrl = asset('/'.$photoPath);
        } else {
            // Stored on the public disk (e.g. "profile-photos/...")
            $photoUrl = \Illuminate\Support\Facades\Storage::url($photoPath);
        }
    }

    $initials = strtoupper(mb_substr((string) ($person?->first_name ?? ''), 0, 1).mb_substr((string) ($person?->last_name ?? ''), 0, 1));
    $initials = $initials ?: strtoupper(mb_substr($label ?: 'N', 0, 1));

    $hasReplies = isset($children[$comment->id]) && $children[$comment->id]->count();
    $isExpanded = $expandedThreads[$comment->id] ?? false;
    $showReplyForm = $replyForms[$comment->id] ?? false;
@endphp

<div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
    <div class="flex items-start gap-3">
        <div class="flex-shrink-0">
            @if($photoUrl)
                <img src="{{ $photoUrl }}" alt="{{ $label }}" class="h-9 w-9 rounded-full object-cover border border-gray-200" />
            @else
                <div class="h-9 w-9 rounded-full bg-gray-100 border border-gray-200 flex items-center justify-center text-xs font-semibold text-gray-700">
                    {{ $initials }}
                </div>
            @endif
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <div class="text-sm font-semibold text-gray-900">{{ $label }}</div>
                <div class="text-xs text-gray-500">{{ $comment->created_at?->diffForHumans() }}</div>
            </div>

            <div class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $comment->body }}</div>

            <div class="mt-4 flex flex-wrap items-center gap-2">
                @if($hasReplies)
                    <button
                        type="button"
                        wire:click="toggleThread({{ $comment->id }})"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-xs font-semibold text-gray-800 hover:bg-gray-200"
                    >
                        @if($isExpanded)
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                            </svg>
                            Hide replies
                        @else
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                            Show replies
                        @endif
                    </button>
                @endif

                @if($canComment)
                    <button
                        type="button"
                        wire:click="toggleReplyForm({{ $comment->id }})"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-50 px-3 py-2 text-xs font-semibold text-indigo-700 hover:bg-indigo-100"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 10h10a8 8 0 018 8v2M3 10l6-6m-6 6l6 6"></path>
                        </svg>
                        Reply
                    </button>
                @endif
            </div>

            @if($canComment && $showReplyForm)
                <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 p-4">
                    <div class="text-xs font-semibold text-gray-700">Write a reply</div>
                    <textarea
                        class="mt-2 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        rows="2"
                        wire:model.defer="replyBodies.{{ $comment->id }}"
                        placeholder="Write a reply..."
                    ></textarea>
                    <div class="mt-2 flex gap-2">
                        <button
                            type="button"
                            wire:click="addComment({{ $comment->id }})"
                            class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500"
                        >
                            Post reply
                        </button>
                    </div>
                </div>
            @endif

            @if($hasReplies && $isExpanded)
                <div class="mt-5 space-y-4 border-l border-gray-200 pl-6">
                    @foreach($children[$comment->id] as $reply)
                        @include('livewire.protocols._comment', [
                            'comment' => $reply,
                            'children' => $children,
                            'canComment' => $canComment,
                        ])
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
