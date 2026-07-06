@php
    use App\Models\ParasiteSampleObservation;
    use App\Models\ParasiteSamples;
    use App\Support\MediaPreview;

    $owner = $owner ?? ($parasite ?? null);
    $observations = ($observations ?? $parasiteObservations ?? null) ?? ($owner?->observations ?? collect());
    $legacyPhotoPath = $legacyPhotoPath ?? $owner?->photo_path;
    $readOnlyObservationIds = $readOnlyObservationIds ?? [];
    $sourceLabels = $sourceLabels ?? [];

    $photoTotal = $observations->count() ?: ($legacyPhotoPath ? 1 : 0);
    $activeObservation = $observations[$activePhotoIndex] ?? $observations->first();
    $activePhoto = $activeObservation?->photo;
    $activePhotoPath = $activePhoto?->photo_path ?: $legacyPhotoPath;
    $activePhotoUrl = MediaPreview::url($activePhotoPath);
    $activeMetadataReadOnly = ($activeObservation instanceof ParasiteSampleObservation
        && ! $owner instanceof ParasiteSamples)
        || ($activeObservation && in_array($activeObservation->id, $readOnlyObservationIds, true));
    $activeSourceLabel = $activeObservation?->getAttribute('source_label')
        ?? ($activeObservation ? ($sourceLabels[$activeObservation->id] ?? null) : null);
@endphp

<div class="bg-gradient-to-br from-gray-50 via-white to-pink-50 rounded-2xl p-8 shadow-inner border border-gray-100">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between mb-6">
        <div class="flex items-center gap-3">
            <div class="bg-pink-100 p-3 rounded-xl shadow-sm">
                <svg class="w-7 h-7 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Observation Photos</h2>
                <p class="text-sm text-gray-500">Browse parasite images and documents with observation dates, notes, and comments.</p>
            </div>
        </div>
        <div class="flex flex-col items-start gap-2 lg:items-end">
            @if($photoTotal > 0)
                <span class="inline-flex items-center rounded-full bg-pink-100 px-4 py-1.5 text-sm font-semibold text-pink-800">
                    {{ $photoTotal }} {{ Str::plural('file', $photoTotal) }}
                </span>
            @endif
            @if($canEdit)
                @if(!$photo)
                    <div class="text-left lg:text-right">
                        <label for="photo-upload"
                            class="inline-flex cursor-pointer items-center gap-2 rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-blue-700">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Add observation photo
                        </label>
                        <input type="file" id="photo-upload" class="hidden"
                            accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf"
                            wire:model="photo" wire:loading.attr="disabled"
                            x-on:photo-uploaded.window="$el.value = ''"
                            x-on:photo-cancelled.window="$el.value = ''">
                        <p class="mt-1 text-xs text-gray-500">Max 50MB · JPG, PNG, WEBP, TIFF, PDF</p>
                    </div>
                @else
                    <div class="w-full min-w-[280px] rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div class="grid grid-cols-1 gap-3">
                            @if($canEditPhotoDates)
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Observed by</label>
                                    <select wire:model="photoObserverPeopleId"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                        <option value="">Current user (default)</option>
                                        @foreach($observerPeople as $person)
                                            <option value="{{ $person->id }}">
                                                {{ trim(($person->title ?? '').' '.($person->first_name ?? '').' '.($person->last_name ?? '')) }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-semibold text-gray-600">Date of observation</label>
                                    <input type="date" wire:model="photoObservedAt"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm">
                                </div>
                            @endif
                            <div>
                                <label class="mb-1 block text-xs font-semibold text-gray-600">Notes</label>
                                <textarea wire:model="photoNotes" rows="2"
                                    class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                    placeholder="Observation notes (optional)"></textarea>
                            </div>
                        </div>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <button wire:click="uploadPhoto"
                                class="inline-flex items-center gap-2 rounded-xl bg-green-600 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-green-700">
                                Upload photo
                            </button>
                            <button wire:click="cancelPhotoSelection"
                                class="inline-flex items-center gap-2 rounded-xl bg-gray-700 px-4 py-2 text-sm font-semibold text-white shadow-md hover:bg-gray-800">
                                Cancel
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>

    @if($activePhotoUrl)
        <div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-slate-900 via-slate-800 to-pink-950 shadow-2xl ring-1 ring-black/10">
            <div class="relative aspect-[16/9] min-h-[320px] sm:min-h-[420px] lg:min-h-[520px]">
                <div wire:key="parasite-gallery-{{ $activePhotoIndex }}-{{ $activeObservation?->id ?? 'legacy' }}"
                    class="absolute inset-0">
                    <x-media-preview
                        :path="$activePhotoPath"
                        alt="Parasite observation photo"
                        class="absolute inset-0 h-full w-full" />
                </div>

                <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 via-black/40 to-transparent p-6 pt-16">
                    <div class="flex flex-col gap-3 text-white sm:flex-row sm:items-end sm:justify-between">
                        <div class="pointer-events-auto">
                            @if($canEditPhotoDates && $activeObservation && ! $activeMetadataReadOnly)
                                <label class="text-xs font-semibold uppercase tracking-wider text-pink-200">Observed</label>
                                <input type="date"
                                    wire:model.blur="editingPhotoObservedAt"
                                    class="mt-1 block rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-sm text-white backdrop-blur-sm focus:border-pink-300 focus:outline-none focus:ring-2 focus:ring-pink-400">
                                <label class="mt-3 block text-xs font-semibold uppercase tracking-wider text-pink-200">Observed by</label>
                                <select wire:model.live="editingObserverPeopleId"
                                    class="mt-1 block w-full max-w-md rounded-lg border border-white/20 bg-white/10 px-3 py-1.5 text-sm text-white backdrop-blur-sm focus:border-pink-300 focus:outline-none focus:ring-2 focus:ring-pink-400">
                                    <option value="">Unknown</option>
                                    @foreach($observerPeople as $person)
                                        <option value="{{ $person->id }}" class="text-gray-900">
                                            {{ trim(($person->title ?? '').' '.($person->first_name ?? '').' '.($person->last_name ?? '')) }}
                                        </option>
                                    @endforeach
                                </select>
                            @elseif($activeObservation?->observed_at || $activeSourceLabel)
                                @if($activeSourceLabel)
                                    <p class="text-xs font-semibold uppercase tracking-wider text-amber-200">From sample</p>
                                    <p class="text-sm font-medium text-white/90">{{ $activeSourceLabel }}</p>
                                @endif
                                <p class="text-xs font-semibold uppercase tracking-wider text-pink-200">Observed</p>
                                <p class="text-lg font-semibold">{{ $activeObservation->observed_at->format('M d, Y') }}</p>
                                @if($activeObservation->people)
                                    <p class="mt-1 text-sm text-white/80">
                                        by {{ trim(($activeObservation->people->title ?? '').' '.($activeObservation->people->first_name ?? '').' '.($activeObservation->people->last_name ?? '')) }}
                                    </p>
                                @endif
                            @endif
                            @if($canEditPhotoDates && $activeObservation && ! $activeMetadataReadOnly)
                                <label class="mt-3 block text-xs font-semibold uppercase tracking-wider text-pink-200">Notes</label>
                                <textarea
                                    rows="2"
                                    placeholder="Observation notes (optional)"
                                    wire:model.blur="editingPhotoNotes"
                                    class="mt-1 block w-full max-w-2xl rounded-lg border border-white/20 bg-white/10 px-3 py-2 text-sm text-white placeholder-white/50 backdrop-blur-sm focus:border-pink-300 focus:outline-none focus:ring-2 focus:ring-pink-400"></textarea>
                            @elseif($activeObservation?->notes)
                                <p class="mt-2 max-w-2xl text-sm text-white/90">{{ $activeObservation->notes }}</p>
                            @endif
                        </div>
                        @if($observations->count() > 1)
                            <p class="text-sm font-medium text-white/80">
                                {{ $activePhotoIndex + 1 }} / {{ $observations->count() }}
                            </p>
                        @endif
                    </div>
                </div>

                @if($observations->count() > 1)
                    <button type="button" wire:click="previousPhoto"
                        class="absolute left-4 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-3 text-white backdrop-blur-sm transition hover:bg-black/70 hover:scale-105">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                    </button>
                    <button type="button" wire:click="nextPhoto"
                        class="absolute right-4 top-1/2 -translate-y-1/2 rounded-full bg-black/50 p-3 text-white backdrop-blur-sm transition hover:bg-black/70 hover:scale-105">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>
                @endif

                @if($canEdit && $activeObservation && ! $activeMetadataReadOnly)
                    <button type="button" wire:click="deleteObservation({{ $activeObservation->id }})"
                        wire:confirm="Are you sure you want to delete this observation?"
                        class="absolute right-4 top-4 rounded-full bg-red-600/90 p-2.5 text-white shadow-lg transition hover:bg-red-700">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                        </svg>
                    </button>
                @endif
            </div>
        </div>

        @if($observations->count() > 1)
            <div class="mt-5 flex gap-3 overflow-x-auto pb-2">
                @foreach($observations as $index => $thumbObservation)
                    @php
                        $thumbPhoto = $thumbObservation->photo;
                        $thumbPath = $thumbPhoto?->photo_path;
                        $thumbUrl = MediaPreview::url($thumbPath);
                        $thumbIsImage = MediaPreview::isImage($thumbPath);
                        $thumbIsPdf = MediaPreview::isPdf($thumbPath);
                    @endphp
                    @if($thumbUrl)
                        <button type="button" wire:click="showPhotoAt({{ $index }})"
                            class="group relative flex-shrink-0 overflow-hidden rounded-xl transition-all duration-200 {{ $activePhotoIndex === $index ? 'ring-2 ring-pink-500 ring-offset-2 scale-105' : 'opacity-70 hover:opacity-100 hover:scale-105' }}">
                            @if($thumbIsImage)
                                <img src="{{ $thumbUrl }}" alt="Thumbnail {{ $index + 1 }}"
                                    class="h-20 w-28 object-cover sm:h-24 sm:w-32">
                            @elseif($thumbIsPdf)
                                <div class="flex h-20 w-28 items-center justify-center bg-red-50 sm:h-24 sm:w-32">
                                    <i class="fas fa-file-pdf text-2xl text-red-600"></i>
                                </div>
                            @else
                                <div class="flex h-20 w-28 items-center justify-center bg-gray-100 sm:h-24 sm:w-32">
                                    <i class="fas fa-file-alt text-xl text-gray-500"></i>
                                </div>
                            @endif
                            @if($thumbObservation->observed_at)
                                <span class="absolute inset-x-0 bottom-0 bg-black/60 px-1 py-0.5 text-[10px] text-white">
                                    {{ $thumbObservation->observed_at->format('Y-m-d') }}
                                </span>
                            @endif
                        </button>
                    @endif
                @endforeach
            </div>
        @endif

        @if($activeObservation && $activeObservation->id)
            @php
                $photoCommentCount = $activeObservation->comments->sum(fn ($comment) => 1 + $comment->replies->count());
            @endphp
            <div class="mt-6 rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h3 class="text-sm font-semibold text-gray-900">Observation comments</h3>
                    <span class="text-xs text-gray-500">{{ $photoCommentCount }} comment{{ $photoCommentCount === 1 ? '' : 's' }}</span>
                </div>

                <div class="space-y-3 max-h-80 overflow-y-auto">
                    @forelse($activeObservation->comments as $comment)
                        @php
                            $commentPerson = $comment->user?->people;
                            $commentName = $commentPerson
                                ? trim(($commentPerson->title ?? '').' '.($commentPerson->first_name ?? '').' '.($commentPerson->last_name ?? ''))
                                : ($comment->user?->email ?? 'User');
                        @endphp
                        <div wire:key="photo-comment-{{ $comment->id }}" class="rounded-lg border border-gray-100 bg-gray-50 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-gray-900">{{ $commentName }}</span>
                                <span class="text-xs text-gray-500">{{ $comment->created_at?->diffForHumans() }}</span>
                            </div>
                            <p class="mt-2 whitespace-pre-wrap text-sm text-gray-700">{{ $comment->body }}</p>
                            @if($canCommentOnPhotos)
                                <button type="button" wire:click="toggleReplyForm({{ $comment->id }})"
                                    class="mt-2 text-xs font-semibold text-blue-600 hover:text-blue-800">
                                    Reply
                                </button>
                            @endif

                            @if($comment->replies->isNotEmpty())
                                <div class="mt-3 space-y-2 border-l-2 border-gray-200 pl-3">
                                    @foreach($comment->replies as $reply)
                                        @php
                                            $replyPerson = $reply->user?->people;
                                            $replyName = $replyPerson
                                                ? trim(($replyPerson->title ?? '').' '.($replyPerson->first_name ?? '').' '.($replyPerson->last_name ?? ''))
                                                : ($reply->user?->email ?? 'User');
                                        @endphp
                                        <div wire:key="photo-reply-{{ $reply->id }}" class="rounded-md bg-white p-2">
                                            <div class="flex items-center justify-between gap-2">
                                                <span class="text-xs font-medium text-gray-900">{{ $replyName }}</span>
                                                <span class="text-[11px] text-gray-500">{{ $reply->created_at?->diffForHumans() }}</span>
                                            </div>
                                            <p class="mt-1 whitespace-pre-wrap text-sm text-gray-700">{{ $reply->body }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            @if($canCommentOnPhotos && ($showReplyForm[$comment->id] ?? false))
                                <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50/50 p-3">
                                    <textarea
                                        wire:model.defer="replyPhotoComments.{{ $comment->id }}"
                                        rows="2"
                                        class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        placeholder="Write a reply..."></textarea>
                                    <div class="mt-2 flex gap-2">
                                        <button type="button" wire:click="addObservationComment({{ $activeObservation->id }}, {{ $comment->id }})"
                                            class="inline-flex items-center rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-blue-700">
                                            Post reply
                                        </button>
                                        <button type="button" wire:click="toggleReplyForm({{ $comment->id }})"
                                            class="inline-flex items-center rounded-lg bg-gray-200 px-3 py-1.5 text-xs font-semibold text-gray-700 transition hover:bg-gray-300">
                                            Cancel
                                        </button>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No comments yet for this photo.</p>
                    @endforelse
                </div>

                @if($canCommentOnPhotos)
                    <div class="mt-4 border-t border-gray-100 pt-4">
                        <label class="mb-1 block text-xs font-semibold text-gray-600">Add a comment</label>
                        <textarea
                            wire:model.defer="newPhotoComments.{{ $activeObservation->id }}"
                            rows="3"
                            class="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm"
                            placeholder="Write a comment about this observation photo..."></textarea>
                        <button type="button" wire:click="addObservationComment({{ $activeObservation->id }})"
                            class="mt-2 inline-flex items-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-blue-700">
                            Post comment
                        </button>
                    </div>
                @endif
            </div>
        @endif
    @else
        <div class="flex aspect-[16/9] min-h-[280px] items-center justify-center rounded-2xl border-2 border-dashed border-gray-300 bg-white/80">
            <div class="text-center">
                <svg class="mx-auto h-14 w-14 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                <p class="mt-3 text-sm font-medium text-gray-600">No photos uploaded yet</p>
            </div>
        </div>
    @endif

    @if($uploadError)
        <div class="mt-4 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $uploadError }}</div>
    @endif

    <x-upload-progress wireModel="photo" class="mt-4" />
</div>
