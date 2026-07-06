<x-layout>
    <x-slot:heading>
        Project Documents
    </x-slot:heading>

    <div class="relative overflow-hidden bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/40">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-blue-100/40 via-transparent to-transparent"></div>
        <div class="pointer-events-none absolute -left-32 top-40 h-72 w-72 rounded-full bg-indigo-200/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-24 bottom-20 h-64 w-64 rounded-full bg-sky-200/25 blur-3xl"></div>
    <!-- Documents Section -->
    <div class="relative py-10">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
            @php
                $user = Auth::user();
                $project = null;
                $isProjectAdmin = false;

                if ($user && $user->people) {
                    $project = $user->people
                        ->projects()
                        ->where('projects.id', session('selected_project_id'))
                        ->withPivot('role', 'date_joined', 'permission')
                        ->first();

                    $isProjectAdmin = $project
                        && $project->pivot
                        && strtolower(trim((string) $project->pivot->permission)) === 'admin';
                }
            @endphp
            @if ($project && $project->pivot && $project->pivot->permission != 'viewer')
                <!-- Modal -->
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
                    id="document_modal" style="display:none;">
                    <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-blue-50 via-indigo-50 to-slate-50 px-6 py-5">
                            <button id="close_document_modal_btn" type="button"
                                class="absolute right-4 top-4 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/80 hover:text-slate-600">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="flex items-center gap-3 pr-10">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/30">
                                    <i class="fas fa-file-circle-plus"></i>
                                </span>
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">Add new document</h2>
                                    <p class="text-xs text-slate-500">Upload to the project archive</p>
                                </div>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="p-6">
                            @csrf
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Title*</label>
                                    <input type="text" name="title" required
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Type*</label>
                                    <select name="type" id="document_type_select" required
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select document type</option>
                                        <option value="Amendment">Amendment</option>
                                        <option value="Budget Plan">Budget Plan</option>
                                        <option value="Collaboration Agreement / MoU">Collaboration Agreement / MoU</option>
                                        <option value="Conference Abstract">Conference Abstract</option>
                                        <option value="Ethics Approval">Ethics Approval</option>
                                        <option value="Final Report">Final Report</option>
                                        <option value="Grant Application">Grant Application</option>
                                        <option value="Poster">Poster</option>
                                        <option value="Presentation">Presentation</option>
                                        <option value="Progress Report">Progress Report</option>
                                        <option value="Project Charter">Project Charter</option>
                                        <option value="Project Proposal">Project Proposal</option>
                                        <option value="Publication">Publication</option>
                                        <option value="Research Permits">Research Permits</option>
                                        <option value="Thesis / Dissertation">Thesis / Dissertation</option>
                                    </select>
                                </div>
                                <div id="parent_document_field" style="display:none;">
                                    <label class="block text-sm font-medium text-slate-700">Document to Amend*</label>
                                    <select name="parent_id"
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select document</option>
                                        @foreach ($project->documents->where('type', '!=', 'Amendment') as $doc)
                                            <option value="{{ $doc->id }}">{{ $doc->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">File*</label>
                                    <input type="file" name="file" required
                                        accept=".pdf,.doc,.docx,.ppt,.pptx,.txt"
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700" />
                                    <p class="mt-1 text-xs text-slate-500">Maximum file size: 55 MB</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Description</label>
                                    <textarea name="description" class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Date*</label>
                                    <input type="date" name="document_date"
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                            </div>
                            <div class="mt-6 flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                                <p class="text-xs text-red-500">* Required fields</p>
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-500/25 transition hover:from-blue-700 hover:to-indigo-700">
                                    <i class="fas fa-plus"></i>
                                    Add document
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            @if ($isProjectAdmin)
                <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
                    id="edit_document_modal" style="display:none;">
                    <div class="relative w-full max-w-lg overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
                        <div class="border-b border-slate-100 bg-gradient-to-r from-slate-50 via-blue-50 to-indigo-50 px-6 py-5">
                            <button id="close_edit_document_modal_btn" type="button"
                                class="absolute right-4 top-4 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/80 hover:text-slate-600">
                                <i class="fas fa-times"></i>
                            </button>
                            <div class="flex items-center gap-3 pr-10">
                                <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 text-white shadow-md">
                                    <i class="fas fa-pen"></i>
                                </span>
                                <div>
                                    <h2 class="text-lg font-bold text-slate-900">Edit document</h2>
                                    <p class="text-xs text-slate-500">Update metadata or replace the file</p>
                                </div>
                            </div>
                        </div>
                        <form id="edit_document_form" method="POST" enctype="multipart/form-data" class="p-6">
                            @csrf
                            @method('PATCH')
                            <div class="grid grid-cols-1 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Title*</label>
                                    <input type="text" name="title" id="edit_document_title" required
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Type*</label>
                                    <select name="type" id="edit_document_type_select" required
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select document type</option>
                                        <option value="Amendment">Amendment</option>
                                        <option value="Budget Plan">Budget Plan</option>
                                        <option value="Collaboration Agreement / MoU">Collaboration Agreement / MoU</option>
                                        <option value="Conference Abstract">Conference Abstract</option>
                                        <option value="Ethics Approval">Ethics Approval</option>
                                        <option value="Final Report">Final Report</option>
                                        <option value="Grant Application">Grant Application</option>
                                        <option value="Poster">Poster</option>
                                        <option value="Presentation">Presentation</option>
                                        <option value="Progress Report">Progress Report</option>
                                        <option value="Project Charter">Project Charter</option>
                                        <option value="Project Proposal">Project Proposal</option>
                                        <option value="Publication">Publication</option>
                                        <option value="Research Permits">Research Permits</option>
                                        <option value="Thesis / Dissertation">Thesis / Dissertation</option>
                                    </select>
                                </div>
                                <div id="edit_parent_document_field" style="display:none;">
                                    <label class="block text-sm font-medium text-slate-700">Document to Amend*</label>
                                    <select name="parent_id" id="edit_document_parent_id"
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                        <option value="">Select document</option>
                                        @if ($project)
                                            @foreach ($project->documents->where('type', '!=', 'Amendment') as $doc)
                                                <option value="{{ $doc->id }}">{{ $doc->title }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Replace file</label>
                                    <input type="file" name="file"
                                        accept=".pdf,.doc,.docx,.ppt,.pptx,.txt"
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-blue-700" />
                                    <p class="mt-1 text-xs text-slate-500">Leave empty to keep the current file. Maximum: 55 MB</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Description</label>
                                    <textarea name="description" id="edit_document_description"
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Date</label>
                                    <input type="date" name="document_date" id="edit_document_date"
                                        class="mt-1 block w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </div>
                            </div>
                            <div class="mt-6 flex items-center justify-between gap-4 border-t border-slate-100 pt-4">
                                <p class="text-xs text-red-500">* Required fields</p>
                                <button type="submit"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-slate-800 to-slate-900 px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-slate-900 hover:to-black">
                                    <i class="fas fa-check"></i>
                                    Save changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
            <!-- End New Document Card and Modal -->
            @if ($documentsByType->isEmpty())
                @if ($project && $project->pivot && $project->pivot->permission != 'viewer')
                    <div class="mb-8">
                        <button type="button" id="open_document_modal_btn"
                            class="group relative w-full overflow-hidden rounded-3xl border border-blue-100 bg-gradient-to-r from-sky-50 via-blue-50 to-indigo-50 p-6 text-left shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl">
                            <div class="absolute inset-y-0 right-0 w-40 bg-gradient-to-l from-blue-100/60 to-transparent"></div>
                            <div class="relative flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                                <div class="flex items-start gap-4">
                                    <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-200/70 transition-transform duration-300 group-hover:scale-105">
                                        <i class="fas fa-plus-circle text-xl"></i>
                                    </div>
                                    <div>
                                        <div class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-slate-500 ring-1 ring-blue-100">
                                            <i class="fas fa-arrow-up-from-bracket text-blue-500"></i>
                                            Add to project archive
                                        </div>
                                        <h3 class="mt-3 text-xl font-bold text-slate-900">Upload a new document</h3>
                                        <p class="mt-2 max-w-2xl text-sm text-slate-600">
                                            Open the document form to add proposals, approvals, reports, publications, presentations, or amendments.
                                        </p>
                                    </div>
                                </div>
                                <span
                                    class="inline-flex items-center justify-center gap-2 rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition-colors duration-200 group-hover:bg-slate-800">
                                    Open document form
                                    <i class="fas fa-arrow-right transition-transform duration-200 group-hover:translate-x-1"></i>
                                </span>
                            </div>
                        </button>
                    </div>
                @endif
                <!-- Empty State -->
                <div class="rounded-2xl border border-dashed border-slate-200 bg-white/60 py-14 text-center shadow-sm">
                    <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-2xl bg-gradient-to-br from-slate-100 to-slate-200 shadow-inner">
                        <i class="fas fa-folder-open text-3xl text-slate-400"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">No documents yet</h3>
                    <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
                        Upload proposals, ethics approvals, reports, and more to build your project archive.
                    </p>
                </div>
            @else
                @php
                    $typeStyles = [
                        'Project Proposal' => [
                            'icon' => 'fas fa-file-contract',
                            'badge' => 'from-sky-500 to-blue-600',
                            'soft' => 'from-sky-50 via-blue-50 to-indigo-50',
                            'ring' => 'border-sky-100',
                            'icon_wrap' => 'bg-sky-100 text-sky-600',
                        ],
                        'Project Charter' => [
                            'icon' => 'fas fa-project-diagram',
                            'badge' => 'from-cyan-500 to-blue-600',
                            'soft' => 'from-cyan-50 via-sky-50 to-blue-50',
                            'ring' => 'border-cyan-100',
                            'icon_wrap' => 'bg-cyan-100 text-cyan-600',
                        ],
                        'Ethics Approval' => [
                            'icon' => 'fas fa-scale-balanced',
                            'badge' => 'from-emerald-500 to-teal-600',
                            'soft' => 'from-emerald-50 via-teal-50 to-cyan-50',
                            'ring' => 'border-emerald-100',
                            'icon_wrap' => 'bg-emerald-100 text-emerald-600',
                        ],
                        'Grant Application' => [
                            'icon' => 'fas fa-hand-holding-usd',
                            'badge' => 'from-lime-500 to-emerald-600',
                            'soft' => 'from-lime-50 via-emerald-50 to-green-50',
                            'ring' => 'border-lime-100',
                            'icon_wrap' => 'bg-lime-100 text-lime-700',
                        ],
                        'Budget Plan' => [
                            'icon' => 'fas fa-coins',
                            'badge' => 'from-yellow-500 to-amber-600',
                            'soft' => 'from-yellow-50 via-amber-50 to-orange-50',
                            'ring' => 'border-yellow-100',
                            'icon_wrap' => 'bg-yellow-100 text-yellow-700',
                        ],
                        'Collaboration Agreement / MoU' => [
                            'icon' => 'fas fa-handshake',
                            'badge' => 'from-teal-500 to-cyan-600',
                            'soft' => 'from-teal-50 via-cyan-50 to-sky-50',
                            'ring' => 'border-teal-100',
                            'icon_wrap' => 'bg-teal-100 text-teal-700',
                        ],
                        'Progress Report' => [
                            'icon' => 'fas fa-chart-line',
                            'badge' => 'from-violet-500 to-purple-600',
                            'soft' => 'from-violet-50 via-purple-50 to-fuchsia-50',
                            'ring' => 'border-violet-100',
                            'icon_wrap' => 'bg-violet-100 text-violet-600',
                        ],
                        'Final Report' => [
                            'icon' => 'fas fa-flag-checkered',
                            'badge' => 'from-amber-500 to-orange-600',
                            'soft' => 'from-amber-50 via-orange-50 to-yellow-50',
                            'ring' => 'border-amber-100',
                            'icon_wrap' => 'bg-amber-100 text-amber-600',
                        ],
                        'Publication' => [
                            'icon' => 'fas fa-book-open',
                            'badge' => 'from-pink-500 to-rose-600',
                            'soft' => 'from-pink-50 via-rose-50 to-red-50',
                            'ring' => 'border-pink-100',
                            'icon_wrap' => 'bg-pink-100 text-pink-600',
                        ],
                        'Research Permits' => [
                            'icon' => 'fab fa-researchgate',
                            'badge' => 'from-green-500 to-emerald-600',
                            'soft' => 'from-green-50 via-emerald-50 to-teal-50',
                            'ring' => 'border-green-100',
                            'icon_wrap' => 'bg-green-100 text-green-600',
                        ],
                        'Conference Abstract' => [
                            'icon' => 'fas fa-microphone',
                            'badge' => 'from-fuchsia-500 to-pink-600',
                            'soft' => 'from-fuchsia-50 via-pink-50 to-rose-50',
                            'ring' => 'border-fuchsia-100',
                            'icon_wrap' => 'bg-fuchsia-100 text-fuchsia-600',
                        ],
                        'Poster' => [
                            'icon' => 'fas fa-image',
                            'badge' => 'from-orange-500 to-amber-600',
                            'soft' => 'from-orange-50 via-amber-50 to-yellow-50',
                            'ring' => 'border-orange-100',
                            'icon_wrap' => 'bg-orange-100 text-orange-600',
                        ],
                        'Presentation' => [
                            'icon' => 'fas fa-person-chalkboard',
                            'badge' => 'from-cyan-500 to-sky-600',
                            'soft' => 'from-cyan-50 via-sky-50 to-blue-50',
                            'ring' => 'border-cyan-100',
                            'icon_wrap' => 'bg-cyan-100 text-cyan-600',
                        ],
                        'Thesis / Dissertation' => [
                            'icon' => 'fas fa-graduation-cap',
                            'badge' => 'from-indigo-500 to-violet-600',
                            'soft' => 'from-indigo-50 via-violet-50 to-purple-50',
                            'ring' => 'border-indigo-100',
                            'icon_wrap' => 'bg-indigo-100 text-indigo-600',
                        ],
                        'Amendment' => [
                            'icon' => 'fas fa-pen-to-square',
                            'badge' => 'from-slate-500 to-gray-700',
                            'soft' => 'from-slate-50 via-gray-50 to-zinc-50',
                            'ring' => 'border-slate-200',
                            'icon_wrap' => 'bg-slate-100 text-slate-600',
                        ],
                    ];

                    $sortedDocumentsByType = $documentsByType
                        ->reject(fn ($documents, $type) => $type === 'Amendment')
                        ->sortKeys();
                    $typeTabs = $sortedDocumentsByType
                        ->map(function ($documents, $type) use ($typeStyles) {
                            $style = $typeStyles[$type] ?? [
                                'icon' => 'fas fa-file-alt',
                                'badge' => 'from-gray-500 to-slate-600',
                                'soft' => 'from-gray-50 via-slate-50 to-zinc-50',
                                'ring' => 'border-gray-200',
                                'icon_wrap' => 'bg-gray-100 text-gray-600',
                            ];

                            return [
                                'type' => $type,
                                'count' => $documents->count(),
                                'style' => $style,
                            ];
                        })
                        ->values();
                @endphp
                <div x-data="{
                    activeType: 'all',
                    titleSearch: '',
                    matchesTitle(title) {
                        const query = this.titleSearch.trim().toLowerCase();
                        if (query === '') {
                            return true;
                        }

                        return String(title ?? '').toLowerCase().includes(query);
                    },
                    sectionTitles(type, titles) {
                        if (this.activeType !== 'all' && this.activeType !== type) {
                            return false;
                        }

                        if (this.titleSearch.trim() === '') {
                            return true;
                        }

                        return titles.some((title) => this.matchesTitle(title));
                    },
                }" class="space-y-7">
                    <div class="relative overflow-hidden rounded-3xl border border-white/60 bg-white/75 p-5 shadow-xl shadow-slate-200/40 backdrop-blur-md sm:p-6">
                        <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/80 via-blue-50/30 to-indigo-50/20"></div>
                        <div class="pointer-events-none absolute -right-20 -top-20 h-48 w-48 rounded-full bg-blue-200/30 blur-3xl"></div>
                        <div class="pointer-events-none absolute -bottom-24 -left-16 h-40 w-40 rounded-full bg-violet-200/25 blur-3xl"></div>
                        <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-xl">
                                <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-700">
                                    <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                    Project archive
                                </div>
                                <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Document library</h2>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                    Filter by document type, preview details inline, or open files in a new tab.
                                </p>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                                <div class="flex items-center gap-2 rounded-2xl border border-slate-200/80 bg-white/90 px-4 py-2.5 shadow-sm">
                                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                        <i class="fas fa-layer-group text-sm"></i>
                                    </span>
                                    <div>
                                        <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Collection</div>
                                        <div class="text-sm font-bold text-slate-800">{{ $typeTabs->count() }} types · {{ $project->documents->count() }} files</div>
                                    </div>
                                </div>
                                @if ($project && $project->pivot && $project->pivot->permission != 'viewer')
                                    <button type="button" id="open_document_modal_btn"
                                        class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 via-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:scale-[1.02] hover:from-blue-700 hover:to-indigo-700 hover:shadow-xl hover:shadow-blue-500/35"
                                        title="Register a new document" aria-label="Register a new document">
                                        <i class="fas fa-cloud-arrow-up"></i>
                                        <span>New document</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        <div class="relative mt-5 -mx-1 overflow-x-auto pb-1">
                            <div class="flex min-w-max gap-2 px-1">
                            <button type="button" @click="activeType = 'all'"
                                :class="activeType === 'all'
                                    ? 'border-transparent bg-slate-900 text-white shadow-lg shadow-slate-900/20 ring-2 ring-slate-900/10'
                                    : 'border-slate-200/90 bg-white/90 text-slate-600 hover:border-slate-300 hover:bg-white hover:text-slate-900 hover:shadow-md'"
                                class="inline-flex items-center gap-2.5 rounded-2xl border px-3.5 py-2.5 text-left text-xs font-semibold transition-all duration-200">
                                <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl"
                                    :class="activeType === 'all' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-600'">
                                    <i class="fas fa-folder-open"></i>
                                </span>
                                <span class="flex flex-col">
                                    <span>All documents</span>
                                    <span class="text-[10px] font-normal opacity-75">{{ $project->documents->count() }} total</span>
                                </span>
                            </button>
                            @foreach ($typeTabs as $tab)
                                <button type="button" @click="activeType = @js($tab['type'])"
                                    :class="activeType === @js($tab['type'])
                                        ? 'border-transparent bg-gradient-to-r {{ $tab['style']['badge'] }} text-white shadow-lg ring-2 ring-white/60'
                                        : 'border-slate-200/90 bg-white/90 text-slate-600 hover:border-slate-300 hover:bg-white hover:text-slate-900 hover:shadow-md'"
                                    class="inline-flex max-w-[220px] items-center gap-2.5 rounded-2xl border px-3.5 py-2.5 text-left text-xs font-semibold transition-all duration-200">
                                    <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $tab['style']['icon_wrap'] }}"
                                        :class="activeType === @js($tab['type']) ? '!bg-white/20 !text-white shadow-sm' : ''">
                                        <i class="{{ $tab['style']['icon'] }} text-xs"></i>
                                    </span>
                                    <span class="flex min-w-0 flex-col">
                                        <span class="truncate">{{ $tab['type'] }}</span>
                                        <span class="text-[10px] font-normal opacity-75">{{ $tab['count'] }} file{{ $tab['count'] === 1 ? '' : 's' }}</span>
                                    </span>
                                </button>
                            @endforeach
                            </div>
                        </div>

                        <div class="relative mt-4 rounded-2xl border border-slate-200/80 bg-white/90 p-3 shadow-sm">
                            <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                <i class="fas fa-magnifying-glass text-[10px]"></i>
                                SEARCH DOCUMENTS
                            </div>
                            <label class="block">
                                <span class="mb-1 block text-xs font-medium text-slate-600">TITLE</span>
                                <input type="search"
                                    x-model.debounce.200ms="titleSearch"
                                    placeholder="Filter by document title…"
                                    class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            </label>
                            <p x-show="titleSearch.trim() !== ''" x-cloak class="mt-2 text-xs text-slate-500">
                                Showing documents whose title matches your search.
                            </p>
                        </div>
                    </div>

                    @foreach ($sortedDocumentsByType as $type => $documents)
                        @php
                            $style = $typeStyles[$type] ?? [
                                'icon' => 'fas fa-file-alt',
                                'badge' => 'from-gray-500 to-slate-600',
                                'soft' => 'from-gray-50 via-slate-50 to-zinc-50',
                                'ring' => 'border-gray-200',
                                'icon_wrap' => 'bg-gray-100 text-gray-600',
                            ];
                            $parentDocuments = $documents
                                ->whereNull('parent_id')
                                ->sortByDesc(fn ($document) => optional($document->document_date)->timestamp ?? optional($document->created_at)->timestamp ?? 0);
                            $amendmentsCount = $parentDocuments->sum(fn ($document) => $document->amendments->count());
                            $sectionTitleList = $parentDocuments->pluck('title')->values()->all();
                        @endphp
                        <section x-show="sectionTitles(@js($type), @js($sectionTitleList))" x-cloak
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 translate-y-2"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="space-y-5">
                            <div class="relative overflow-hidden rounded-2xl border {{ $style['ring'] }} bg-gradient-to-r {{ $style['soft'] }} px-4 py-4 shadow-sm">
                                <div class="pointer-events-none absolute inset-y-0 right-0 w-1/3 bg-gradient-to-l from-white/50 to-transparent"></div>
                                <div class="relative flex items-center gap-4">
                                    <div class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-white/95 shadow-md ring-1 ring-white {{ $style['icon_wrap'] }}">
                                        <i class="{{ $style['icon'] }} text-lg"></i>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h2 class="text-lg font-bold tracking-tight text-slate-900">{{ $type }}</h2>
                                        <p class="text-xs text-slate-600">
                                            {{ $parentDocuments->count() }} primary document{{ $parentDocuments->count() === 1 ? '' : 's' }}
                                            @if ($amendmentsCount > 0)
                                                <span class="text-slate-400">·</span> {{ $amendmentsCount }} amendment{{ $amendmentsCount === 1 ? '' : 's' }}
                                            @endif
                                        </p>
                                    </div>
                                    <span class="hidden shrink-0 rounded-full bg-white/90 px-3 py-1.5 text-[11px] font-bold uppercase tracking-wide text-slate-500 shadow-sm ring-1 ring-white sm:inline-flex">
                                        {{ $documents->count() }} files
                                    </span>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                @foreach ($parentDocuments as $document)
                                    @php
                                        $isPdf = Str::endsWith(strtolower($document->file_name), '.pdf');
                                        $isWord = Str::endsWith(strtolower($document->file_name), ['.doc', '.docx']);
                                        $isPowerPoint = Str::endsWith(strtolower($document->file_name), ['.ppt', '.pptx']);
                                        $fileExtension = strtoupper(pathinfo($document->file_name, PATHINFO_EXTENSION) ?: 'FILE');
                                    @endphp
                                    <article
                                        x-data="{ expanded: false }"
                                        x-show="matchesTitle(@js($document->title))"
                                        :class="expanded ? 'sm:col-span-2 xl:col-span-2 ring-1 ring-slate-300 shadow-md' : 'hover:shadow-md'"
                                        class="group flex flex-col overflow-hidden rounded-xl border border-slate-200/80 bg-white shadow-sm transition-all duration-200">
                                        <div class="flex flex-1 flex-col p-4">
                                            <div class="flex items-start gap-3">
                                                <div class="flex shrink-0 flex-col items-center gap-1">
                                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg border border-slate-100 bg-slate-50
                                                        @if ($isPdf) text-red-500
                                                        @elseif($isWord) text-blue-500
                                                        @elseif($isPowerPoint) text-orange-500
                                                        @else text-slate-500 @endif">
                                                        @if ($isPdf)
                                                            <i class="fas fa-file-pdf text-lg"></i>
                                                        @elseif($isWord)
                                                            <i class="fas fa-file-word text-lg"></i>
                                                        @elseif($isPowerPoint)
                                                            <i class="fas fa-file-powerpoint text-lg"></i>
                                                        @else
                                                            <i class="fas fa-file text-lg"></i>
                                                        @endif
                                                    </div>
                                                    <span class="text-[9px] font-medium uppercase tracking-widest text-slate-400">{{ $fileExtension }}</span>
                                                </div>

                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <h3 class="text-[15px] font-semibold leading-snug text-slate-900"
                                                            :class="expanded ? '' : 'line-clamp-2'">
                                                            {{ $document->title }}
                                                        </h3>
                                                        @if ($isProjectAdmin)
                                                            <div class="flex shrink-0 items-center gap-0.5 opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100">
                                                                <button type="button"
                                                                    class="edit-document-btn inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                                    title="Edit document"
                                                                    aria-label="Edit document"
                                                                    data-update-url="{{ route('documents.update', $document) }}"
                                                                    data-title="{{ e($document->title) }}"
                                                                    data-type="{{ e($document->type) }}"
                                                                    data-description="{{ e($document->description ?? '') }}"
                                                                    data-document-date="{{ $document->document_date?->format('Y-m-d') }}"
                                                                    data-parent-id="{{ $document->parent_id }}">
                                                                    <i class="fas fa-pen text-[11px]"></i>
                                                                </button>
                                                                <form method="POST" action="{{ route('documents.destroy', $document) }}"
                                                                    class="delete-document-form">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit"
                                                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                                        title="Delete document"
                                                                        aria-label="Delete document">
                                                                        <i class="fas fa-trash text-[11px]"></i>
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    <p class="mt-1 truncate text-xs text-slate-400" title="{{ $document->file_name }}">
                                                        {{ $document->file_name }}
                                                    </p>

                                                    <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-slate-500">
                                                        @if ($document->document_date)
                                                            <span class="inline-flex items-center gap-1.5">
                                                                <i class="far fa-calendar text-[10px] text-slate-300"></i>
                                                                {{ \Carbon\Carbon::parse($document->document_date)->format('M j, Y') }}
                                                            </span>
                                                        @endif
                                                        @if ($document->amendments->count())
                                                            <span class="inline-flex items-center gap-1.5">
                                                                <i class="fas fa-code-branch text-[10px] text-slate-300"></i>
                                                                {{ $document->amendments->count() }} amendment{{ $document->amendments->count() === 1 ? '' : 's' }}
                                                            </span>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>

                                            @if ($document->description)
                                                <p x-show="! expanded" x-cloak
                                                    class="mt-3 text-xs leading-relaxed text-slate-500 line-clamp-2">
                                                    {{ $document->description }}
                                                </p>
                                            @endif

                                            <div x-show="expanded" x-collapse class="mt-4">
                                                <div class="rounded-lg border border-slate-100 bg-slate-50/60 p-4">
                                                    <dl class="grid grid-cols-2 gap-x-5 gap-y-3 text-xs">
                                                        <div>
                                                            <dt class="text-slate-400">Category</dt>
                                                            <dd class="mt-0.5 font-medium text-slate-800">{{ $document->type }}</dd>
                                                        </div>
                                                        <div>
                                                            <dt class="text-slate-400">Format</dt>
                                                            <dd class="mt-0.5 font-medium text-slate-800">{{ $fileExtension }}</dd>
                                                        </div>
                                                        @if ($document->document_date)
                                                            <div>
                                                                <dt class="text-slate-400">Document date</dt>
                                                                <dd class="mt-0.5 font-medium text-slate-800">{{ \Carbon\Carbon::parse($document->document_date)->format('F j, Y') }}</dd>
                                                            </div>
                                                        @endif
                                                        @if ($document->created_at)
                                                            <div>
                                                                <dt class="text-slate-400">Uploaded</dt>
                                                                <dd class="mt-0.5 font-medium text-slate-800">{{ $document->created_at->format('M j, Y') }}</dd>
                                                            </div>
                                                        @endif
                                                        <div class="col-span-2">
                                                            <dt class="text-slate-400">File name</dt>
                                                            <dd class="mt-0.5 break-all font-medium text-slate-800">{{ $document->file_name }}</dd>
                                                        </div>
                                                    </dl>
                                                    @if ($document->description)
                                                        <div class="mt-4 border-t border-slate-200/80 pt-4">
                                                            <dt class="text-xs text-slate-400">Description</dt>
                                                            <dd class="mt-1.5 text-sm leading-relaxed text-slate-700">{{ $document->description }}</dd>
                                                        </div>
                                                    @endif
                                                </div>

                                                @if ($document->amendments->count())
                                                    <div class="mt-3 space-y-2">
                                                        <p class="text-xs font-medium text-slate-500">Amendments</p>
                                                        @foreach ($document->amendments as $amendment)
                                                            <div class="flex items-start justify-between gap-3 rounded-lg border border-slate-100 bg-white px-3 py-2.5">
                                                                <div class="min-w-0">
                                                                    <p class="text-sm font-medium text-slate-800">{{ $amendment->title }}</p>
                                                                    @if ($amendment->description)
                                                                        <p class="mt-1 text-xs leading-relaxed text-slate-500">{{ $amendment->description }}</p>
                                                                    @endif
                                                                    @if ($amendment->document_date)
                                                                        <p class="mt-1.5 text-[11px] text-slate-400">
                                                                            {{ \Carbon\Carbon::parse($amendment->document_date)->format('M j, Y') }}
                                                                        </p>
                                                                    @endif
                                                                </div>
                                                                <div class="flex shrink-0 items-center gap-0.5">
                                                                    <a href="{{ asset('storage/' . $amendment->file_path) }}" target="_blank"
                                                                        class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                                        title="Open amendment">
                                                                        <i class="fas fa-external-link-alt text-[10px]"></i>
                                                                    </a>
                                                                    @if ($isProjectAdmin)
                                                                        <button type="button"
                                                                            class="edit-document-btn inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
                                                                            title="Edit amendment"
                                                                            aria-label="Edit amendment"
                                                                            data-update-url="{{ route('documents.update', $amendment) }}"
                                                                            data-title="{{ e($amendment->title) }}"
                                                                            data-type="{{ e($amendment->type) }}"
                                                                            data-description="{{ e($amendment->description ?? '') }}"
                                                                            data-document-date="{{ $amendment->document_date?->format('Y-m-d') }}"
                                                                            data-parent-id="{{ $amendment->parent_id }}">
                                                                            <i class="fas fa-pen text-[10px]"></i>
                                                                        </button>
                                                                        <form method="POST" action="{{ route('documents.destroy', $amendment) }}"
                                                                            class="delete-document-form">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit"
                                                                                class="inline-flex h-7 w-7 items-center justify-center rounded-md text-slate-400 transition hover:bg-red-50 hover:text-red-600"
                                                                                title="Delete amendment"
                                                                                aria-label="Delete amendment">
                                                                                <i class="fas fa-trash text-[10px]"></i>
                                                                            </button>
                                                                        </form>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="mt-4 flex gap-2 border-t border-slate-100 pt-3">
                                                <button type="button" @click="expanded = ! expanded"
                                                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-slate-900">
                                                    <i class="fas fa-chevron-down text-[10px] transition-transform duration-200"
                                                        :class="expanded ? 'rotate-180' : ''"></i>
                                                    <span x-text="expanded ? 'Less' : 'More'"></span>
                                                </button>
                                                <a href="{{ asset('storage/' . $document->file_path) }}" target="_blank"
                                                    class="inline-flex flex-1 items-center justify-center gap-1.5 rounded-lg bg-slate-900 px-3 py-2 text-xs font-medium text-white transition hover:bg-slate-800">
                                                    Open
                                                    <i class="fas fa-arrow-up-right-from-square text-[10px] opacity-70"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                                <div x-show="titleSearch.trim() !== '' && !@js($sectionTitleList).some((title) => matchesTitle(title))" x-cloak
                                    class="col-span-full rounded-xl border border-dashed border-slate-200 bg-slate-50 px-6 py-10 text-center">
                                    <p class="text-sm font-medium text-slate-700">No documents in this category match your search.</p>
                                    <p class="mt-1 text-sm text-slate-500">Try a different title or clear the search field.</p>
                                </div>
                            </div>
                        </section>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    </div>
    @if (session('success'))
        <div id="documentSuccessMessage" class="hidden">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div id="documentErrorMessage" class="hidden">{{ session('error') }}</div>
    @endif

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/documents.js"></script>
    <script>
        $(document).ready(function() {
            function updateParentDocumentField() {
                if ($('#document_type_select').val().trim().toLowerCase() === 'amendment') {
                    $('#parent_document_field').show();
                } else {
                    $('#parent_document_field').hide();
                }
            }

            $('#document_type_select').on('input change', updateParentDocumentField);
            updateParentDocumentField();
        });
    </script>
</x-layout>
