<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <!-- Header Section -->
        <div class="bg-gradient-to-r from-indigo-900 to-indigo-800 rounded-t-xl shadow-lg">
            <div class="px-6 py-8">
                <div class="flex items-start justify-between gap-6">
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="bg-white/20 p-3 rounded-lg">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-white">Protocol Details</h1>
                            <p class="text-indigo-100 text-lg">Code: {{ $protocol->code }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap justify-end gap-3">
                            <a href="/experiments/list"
                            class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to List
                            </a>
                            <button wire:click="exportProtocol"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                Export
                            </button>
                            @if ($canManageProtocol)
                                <button wire:click="openEditProtocolModal"
                                    class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                    <i class="fas fa-edit mr-2"></i>
                                    Edit Protocol
                                </button>
                            @endif
                            @if ($canManageProtocol)
                                <button wire:click="deleteProtocol"
                                    wire:confirm="Are you sure you want to delete this protocol? This action cannot be undone."
                                    class="inline-flex items-center px-4 py-2 bg-red-600 hover:bg-red-500 text-white font-medium rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                        </path>
                                    </svg>
                                    Delete
                                </button>
                            @endif
                    </div>
                </div>

                <div class="mt-2 rounded-xl bg-white/10 p-4 backdrop-blur-sm">
                    <div class="grid grid-cols-1 gap-4 text-sm text-indigo-50 lg:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)_minmax(0,1fr)]">
                        <div class="min-w-0">
                            <div class="text-xs uppercase tracking-wide text-indigo-200">Title</div>
                            <div class="mt-1 font-semibold text-white break-words">{{ $protocol->name }}</div>
                        </div>
                        <div class="min-w-0 lg:text-center">
                            <div class="text-xs uppercase tracking-wide text-indigo-200">Technique Name</div>
                            <div class="mt-1 font-semibold text-white break-words">{{ $protocol->techniques->name ?? 'N/A' }}</div>
                        </div>
                        <div class="min-w-0 lg:text-right">
                            <div class="text-xs uppercase tracking-wide text-indigo-200">Technique Type</div>
                            <div class="mt-1 font-semibold text-white break-words">{{ $protocol->techniques->type ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white shadow-lg rounded-b-xl">
            <div class="space-y-8 p-8">
                @if (session()->has('message'))
                    <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                        {{ session('message') }}
                    </div>
                @endif
                @if (session()->has('error'))
                    <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                        {{ session('error') }}
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                    <div class="bg-gray-50 rounded-xl p-6 lg:col-span-2">
                        <div class="flex items-start justify-between gap-4 mb-4">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="bg-red-100 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z">
                                        </path>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <h2 class="text-xl font-semibold text-gray-900 leading-tight">Protocol document
                                    </h2>
                                    <div class="mt-1 text-xs text-gray-500 leading-snug">
                                        Max file size: 50MB • Formats: PDF, DOC, DOCX
                                    </div>
                                </div>
                            </div>

                            <!-- Upload Button -->
                            @if (!$document && $canManageProtocol)
                                <label for="document-upload"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200 cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12">
                                        </path>
                                    </svg>
                                    Select document
                                    <input type="file" id="document-upload" class="hidden"
                                        accept=".pdf,.doc,.docx" wire:model="document" wire:loading.attr="disabled"
                                        x-data x-on:document-uploaded.window="$el.value = ''"
                                        x-on:document-cancelled.window="$el.value = ''">
                                </label>
                            @elseif($document && $canManageProtocol)
                                <!-- Action Buttons when document is selected -->
                                <div class="flex flex-wrap gap-2">
                                    <button wire:click="uploadDocument"
                                        class="inline-flex items-center gap-2 px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Upload
                                    </button>
                                    <button wire:click="cancelDocumentSelection"
                                        class="inline-flex items-center gap-2 px-3 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                        Cancel
                                    </button>
                                </div>
                            @endif

                            @if (!$canManageProtocol)
                                <div class="text-xs text-indigo-400">
                                    You can view and download this protocol, but you can’t upload or delete documents
                                    unless you created it.
                                </div>
                            @endif
                        </div>

                        @if ($uploadError)
                            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                            clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-red-800 break-words">{{ $uploadError }}</span>
                                </div>
                            </div>
                        @endif

                        <x-upload-progress wireModel="document" class="mt-3" />

                        @if ($protocol->pdf_path)
                            <div class="relative group">
                                @php
                                    $fileExtension = pathinfo($protocol->pdf_path, PATHINFO_EXTENSION);
                                    $isPdf = strtolower($fileExtension) === 'pdf';
                                    $isWord = in_array(strtolower($fileExtension), ['doc', 'docx']);
                                @endphp

                                <div class="bg-white rounded-lg shadow overflow-hidden">
                                    @if ($isPdf)
                                        <iframe src="{{ Storage::url($protocol->pdf_path) }}" class="w-full h-96"
                                            frameborder="0">
                                        </iframe>
                                    @elseif($isWord)
                                        <div class="flex items-center justify-center p-8 bg-white rounded-lg shadow">
                                            <div class="text-center">
                                                <i class="fas fa-file-word text-6xl text-blue-600 mb-4"></i>
                                                <h3 class="text-lg font-medium text-gray-900 mb-2">Word Document</h3>
                                                <p class="text-sm text-gray-600 mb-4">This is a Word document that can
                                                    be downloaded and viewed</p>
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <!-- Delete Button (appears on hover) -->
                                @if ($canManageProtocol)
                                    <div
                                        class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <button wire:click="deleteDocument"
                                            wire:confirm="Are you sure you want to delete this document?"
                                            class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-full shadow-lg transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                @endif

                                <div class="mt-4">
                                    <a href="{{ Storage::url($protocol->pdf_path) }}" download
                                        class="w-full flex items-center justify-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                            </path>
                                        </svg>
                                        Download {{ $isPdf ? 'PDF' : 'Document' }}
                                    </a>
                                </div>
                            </div>
                        @else
                            <!-- No Document Placeholder -->
                            <div class="text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none"
                                    viewBox="0 0 48 48">
                                    <path
                                        d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02"
                                        stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600">No protocol document uploaded yet</p>
                                    <p class="text-xs text-gray-500 mt-1">Click "Select Document" to add a PDF or Word
                                        file</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-5">
                        <div class="space-y-5">
                            <div class="text-sm text-indigo-900">
                                <div class="font-semibold">Created by</div>
                                @if ($protocol->user->people)
                                    <a href="/profile/{{ $protocol->user->people->id }}"
                                        class="mt-2 inline-flex items-center gap-2 text-indigo-700 hover:underline">
                                        <x-people-logo :person="$protocol->user->people" width="28" />
                                        <span>{{ $protocol->user->people->title . ' ' . $protocol->user->people->first_name . ' ' . $protocol->user->people->last_name }}</span>
                                    </a>
                                @else
                                    <div class="mt-2 text-indigo-700">N/A</div>
                                @endif
                            </div>
                            <div>
                                <div class="mb-1 text-sm font-semibold text-indigo-900">Associated Pathogens</div>
                                @if ($protocol->pathogens->count() > 0)
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($protocol->pathogens as $pathogen)
                                            <span
                                                class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-800">
                                                {{ $pathogen->species }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-indigo-700">No linked pathogens.</p>
                                @endif
                            </div>
                            @if ($canManageProtocol)
                                <button id="pathogen_protocol_btn" type="button"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                    <i class="fas fa-link mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                    Create New Protocol-Pathogen Association
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-6">
                    <div class="flex items-center justify-between mb-6">
                        <div class="flex items-center">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.746 0 3.332.477 4.5 1.253v13C19.832 18.477 18.246 18 16.5 18c-1.746 0-3.332.477-4.5 1.253">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Associated Studies</h2>
                        </div>
                        @if ($canManageProtocol)
                            <button wire:click="openAssociateStudiesModal"
                                class="inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
                                <i class="fas fa-link mr-2"></i>
                                Link studies
                            </button>
                        @endif
                    </div>

                    @if ($protocol->studies->count() > 0)
                        <div class="flex flex-wrap gap-2">
                            @foreach ($protocol->studies as $study)
                                <a href="/studies/{{ $study->id }}"
                                    class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 hover:bg-blue-200 transition-colors duration-200">
                                    {{ $study->ref_key }}
                                </a>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-gray-500">No linked studies yet.</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="mt-8 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-lg">
            <div class="flex items-center justify-between gap-4 border-b border-gray-200 px-8 py-5">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="bg-indigo-100 p-2 rounded-lg">
                        <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 10h.01M12 10h.01M16 10h.01M9 16h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2z">
                            </path>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            <h2 class="text-xl font-semibold text-gray-900">Comments</h2>
                            @if (!is_null($commentsCount))
                                <span
                                    class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700">
                                    {{ $commentsCount }}
                                </span>
                            @endif
                        </div>
                        <div class="mt-0.5 text-xs text-gray-500">
                            @if ($canComment)
                                All registered users can comment (including viewers and guest mode).
                            @else
                                Sign in to comment.
                            @endif
                        </div>
                    </div>
                </div>

                <button type="button" wire:click="toggleComments"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-100 px-3 py-2 text-sm font-semibold text-gray-800 hover:bg-gray-200">
                    @if ($showComments)
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7">
                            </path>
                        </svg>
                        Collapse
                    @else
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                        Expand
                    @endif
                </button>
            </div>

            @if ($showComments)
                <div class="p-8">
                    @if ($canComment)
                        <div class="rounded-xl border border-gray-200 bg-gray-50 p-5">
                            <div class="text-sm font-semibold text-gray-900">Add a comment</div>
                            <textarea
                                class="mt-2 w-full rounded-lg border-gray-300 bg-white text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                rows="3" wire:model.defer="newComment" placeholder="Write a comment..."></textarea>
                            <div class="mt-3 flex gap-2">
                                <button type="button" wire:click="addComment"
                                    class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                                    Post comment
                                </button>
                            </div>
                        </div>
                    @endif

                    <div class="mt-6 space-y-4">
                        @if ($topLevelComments && $topLevelComments->count())
                            @foreach ($topLevelComments as $comment)
                                @include('livewire.protocols._comment', [
                                    'comment' => $comment,
                                    'children' => $commentChildren,
                                    'canComment' => $canComment,
                                ])
                            @endforeach

                            <div class="pt-2">
                                {{ $topLevelComments->links() }}
                            </div>
                        @else
                            <div
                                class="rounded-xl border border-dashed border-gray-300 bg-gray-50 p-6 text-sm text-gray-600">
                                No comments yet.
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($showEditProtocolModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="w-full max-w-2xl rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Edit Protocol Information</h3>
                    <button type="button" wire:click="closeEditProtocolModal"
                        class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Protocol Name</label>
                        <input type="text" wire:model.defer="editProtocolName"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                        @error('editProtocolName')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium text-gray-700">Technique</label>
                        <select wire:model.defer="editTechniqueId"
                            class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="">Select technique</option>
                            @foreach ($availableTechniques as $technique)
                                <option value="{{ $technique->id }}">{{ $technique->name }} ({{ $technique->type }})</option>
                            @endforeach
                        </select>
                        @error('editTechniqueId')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="flex justify-end gap-3 border-t px-6 py-4">
                    <button type="button" wire:click="closeEditProtocolModal"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveProtocolInfo"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Save
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($showAssociateStudiesModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div class="flex w-full max-w-6xl max-h-[92vh] flex-col rounded-xl bg-white shadow-2xl">
                <div class="flex items-center justify-between border-b px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900">Link Studies</h3>
                    <button type="button" wire:click="closeAssociateStudiesModal"
                        class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="flex-1 overflow-y-auto px-6 py-5">
                    <label class="mb-3 block text-sm font-semibold text-gray-700">Studies</label>
                    <div class="overflow-auto rounded-lg border border-gray-300 max-h-[60vh]">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        Select
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        <button type="button" wire:click="sortStudies('ref_key')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                            Ref key
                                            @if ($studySortBy === 'ref_key')
                                                <i class="fas fa-sort-{{ $studySortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort text-gray-400"></i>
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        <button type="button" wire:click="sortStudies('title')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                            Title
                                            @if ($studySortBy === 'title')
                                                <i class="fas fa-sort-{{ $studySortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort text-gray-400"></i>
                                            @endif
                                        </button>
                                    </th>
                                    <th class="px-3 py-2 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                                        <button type="button" wire:click="sortStudies('publication_year')" class="inline-flex items-center gap-1 hover:text-gray-900">
                                            Year
                                            @if ($studySortBy === 'publication_year')
                                                <i class="fas fa-sort-{{ $studySortDirection === 'asc' ? 'up' : 'down' }}"></i>
                                            @else
                                                <i class="fas fa-sort text-gray-400"></i>
                                            @endif
                                        </button>
                                    </th>
                                </tr>
                                <tr class="bg-white">
                                    <th class="px-3 py-2"></th>
                                    <th class="px-3 py-2">
                                        <input type="text" wire:model.live.debounce.300ms="studyRefKeyFilter"
                                            class="w-full rounded-md border border-gray-300 px-2 py-1 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Filter ref key">
                                    </th>
                                    <th class="px-3 py-2">
                                        <input type="text" wire:model.live.debounce.300ms="studyTitleFilter"
                                            class="w-full rounded-md border border-gray-300 px-2 py-1 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Filter title">
                                    </th>
                                    <th class="px-3 py-2">
                                        <input type="text" wire:model.live.debounce.300ms="studyYearFilter"
                                            class="w-full rounded-md border border-gray-300 px-2 py-1 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            placeholder="Filter year">
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 bg-white">
                                @forelse ($availableStudies as $study)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-3 py-2 align-top">
                                            <input type="checkbox" wire:model="selectedStudyIds" value="{{ $study->id }}"
                                                class="mt-0.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-800">
                                            <a href="/studies/{{ $study->id }}" class="font-medium text-blue-700 hover:underline">
                                                {{ $study->ref_key }}
                                            </a>
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-700">
                                            {{ $study->title ?? 'N/A' }}
                                        </td>
                                        <td class="px-3 py-2 text-sm text-gray-700">
                                            {{ $study->publication_year ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-3 py-4 text-sm text-gray-500">
                                            No studies found.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $availableStudies->links(data: ['scrollTo' => false]) }}
                    </div>
                    <p class="mt-2 text-xs text-gray-500">
                        Checked studies will be linked to this protocol.
                    </p>
                </div>
                <div class="flex justify-end gap-3 border-t px-6 py-4">
                    <button type="button" wire:click="closeAssociateStudiesModal"
                        class="rounded-lg border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100">
                        Cancel
                    </button>
                    <button type="button" wire:click="saveAssociatedStudies"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                        Link selected studies
                    </button>
                </div>
            </div>
        </div>
    @endif

    @if ($canManageProtocol)
        <x-table-modal id="pathogen_protocol_modal" title="Pathogen-Protocol Association Form"
            closeButtonId="pathogen_protocol_close_btn">
            @include('modals.form_pathogen_protocol', [
                'exp_protocols' => $exp_protocols,
                'pathogens' => $pathogens,
                'protocol_pathogen_map' => $protocol_pathogen_map,
                'default_protocol_ass' => $protocol->name,
            ])
        </x-table-modal>
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const openBtn = document.getElementById('pathogen_protocol_btn');
            const closeBtn = document.getElementById('pathogen_protocol_close_btn');
            const modal = document.getElementById('pathogen_protocol_modal');

            if (openBtn && modal) {
                openBtn.addEventListener('click', function() {
                    modal.classList.remove('hidden');
                });
            }

            if (closeBtn && modal) {
                closeBtn.addEventListener('click', function() {
                    modal.classList.add('hidden');
                });
            }
        });
    </script>
</div>
