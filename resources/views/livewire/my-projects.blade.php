@push('styles')
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endpush

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <!-- Header -->
        <div class="bg-gradient-to-r from-indigo-900 via-indigo-800 to-violet-800 rounded-t-2xl shadow-lg">
            <div class="px-6 py-7 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
                <div class="flex items-start gap-4">
                    <div class="bg-white/15 ring-1 ring-white/20 p-3 rounded-xl shrink-0">
                        <svg class="w-7 h-7 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-bold text-white">My Projects</h1>
                        <p class="mt-1 max-w-2xl text-sm leading-relaxed text-indigo-100/90">
                            Pick a workspace to explore the platform. Use
                            <span class="font-semibold text-white">Guest mode</span> to browse public datasets, or
                            select one of your projects to view and manage its private data.
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="hidden sm:inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1.5 text-sm text-indigo-50 ring-1 ring-white/15">
                        <i class="fas fa-folder-open"></i>
                        {{ $projectCount }} {{ \Illuminate\Support\Str::plural('project', $projectCount) }}
                    </span>
                    <a href="{{ route('projects.create') }}"
                        class="group inline-flex items-center justify-center gap-2 px-5 py-2.5 text-sm font-semibold bg-white text-indigo-800 rounded-xl shadow-sm hover:shadow-md hover:bg-indigo-50 transition-all duration-200">
                        <i class="fas fa-plus-circle text-base group-hover:rotate-90 transition-transform duration-300"></i>
                        New Project
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white shadow-lg rounded-b-2xl">
            <div class="p-6 sm:p-8">
                <form id="projectSelectionForm" action="{{ route('project.select') }}" method="POST">
                    @csrf

                    <!-- Step 1: Active workspace -->
                    <section class="mb-10">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-white text-sm font-bold">1</span>
                            <h2 class="text-base font-bold uppercase tracking-wide text-gray-700">Active workspace</h2>
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <!-- Guest mode (selectable) -->
                            <label for="project_guest"
                                class="relative flex cursor-pointer items-start gap-3 rounded-xl border-2 p-5 transition-all duration-200 {{ !$selectedProjectCode ? 'border-purple-400 bg-purple-50 ring-2 ring-purple-100' : 'border-gray-200 bg-white hover:border-purple-300 hover:bg-purple-50/40' }}">
                                <input type="radio" name="selected_project" value="guest" id="project_guest"
                                    class="mt-1 h-5 w-5 text-purple-600 focus:ring-purple-500 border-gray-300"
                                    {{ !$selectedProjectCode ? 'checked' : '' }}
                                    onchange="submitProjectSelection(this)">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="bg-purple-100 p-2 rounded-lg"><i class="fas fa-eye text-lg text-purple-600"></i></span>
                                        <span class="text-lg font-semibold text-purple-900">Guest mode</span>
                                        @if (!$selectedProjectCode)
                                            <span class="ml-auto inline-flex items-center gap-1 rounded-full bg-purple-600 px-2.5 py-0.5 text-xs font-semibold text-white">
                                                <i class="fas fa-check text-[10px]"></i> Active
                                            </span>
                                        @endif
                                    </div>
                                    <p class="mt-2 text-sm text-gray-600">
                                        Explore <span class="font-semibold">public datasets</span> shared across every
                                        project. Read-only — nothing is saved to a project.
                                    </p>
                                </div>
                            </label>

                            <!-- Project workspace (mirrors current selection) -->
                            <div class="relative flex items-start gap-3 rounded-xl border-2 p-5 transition-all duration-200 {{ $selectedProjectCode ? 'border-green-400 bg-green-50 ring-2 ring-green-100' : 'border-dashed border-gray-200 bg-gray-50/60' }}">
                                <span class="p-2 rounded-lg {{ $selectedProjectCode ? 'bg-green-100' : 'bg-indigo-100' }}">
                                    <i class="fas fa-folder-open text-lg {{ $selectedProjectCode ? 'text-green-600' : 'text-indigo-600' }}"></i>
                                </span>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg font-semibold text-gray-900">Project workspace</span>
                                        @if ($selectedProjectCode)
                                            <span class="ml-auto inline-flex items-center gap-1 rounded-full bg-green-600 px-2.5 py-0.5 text-xs font-semibold text-white">
                                                <i class="fas fa-check text-[10px]"></i> Active
                                            </span>
                                        @endif
                                    </div>
                                    @if ($selectedProjectCode)
                                        <p class="mt-2 text-sm text-gray-600">
                                            Working in
                                            <span class="inline-flex items-center rounded-md bg-green-100 px-2 py-0.5 text-sm font-bold text-green-800">{{ $selectedProjectCode }}</span>
                                            — viewing and saving its private data.
                                        </p>
                                    @else
                                        <p class="mt-2 text-sm text-gray-600">
                                            Select a project from the list below to load its
                                            <span class="font-semibold">private data</span> and save new records to it.
                                        </p>
                                        <span class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700">
                                            <i class="fas fa-arrow-down animate-bounce"></i> Choose one below
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </section>

                    <!-- Step 2: Your projects -->
                    @php
                        $activeTotal = $activeProjects->total();
                        $mixedTotal = $completedWithActiveSubProjects->total();
                        $completedTotal = $completedProjects->total();
                        $allTotal = $allProjects->total();
                        $hasAnyProject = ($membershipProjectCount ?? 0) > 0;
                        $activeFilters = collect([
                            $code, $type, $title, $role, $funding, $collaborator,
                            $startDateFrom, $startDateTo, $intendedEndFrom, $intendedEndTo,
                            $officialEndFrom, $officialEndTo,
                        ])->filter(fn ($v) => trim((string) $v) !== '')->count();
                    @endphp

                    <section x-data="{ tab: 'all' }">
                        <div class="flex items-center gap-3 mb-4">
                            <span class="flex h-7 w-7 items-center justify-center rounded-full bg-indigo-600 text-white text-sm font-bold">2</span>
                            <h2 class="text-base font-bold uppercase tracking-wide text-gray-700">Your projects</h2>
                            <span class="text-sm font-normal normal-case text-gray-400">— select a project's radio to start working in it</span>
                        </div>

                        @if (!$hasAnyProject)
                            <div class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50 px-6 py-14 text-center">
                                <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                    <i class="fas fa-folder-open text-xl text-gray-400"></i>
                                </div>
                                <p class="text-sm font-medium text-gray-700">No projects to show.</p>
                                <p class="mt-1 text-sm text-gray-500">Adjust your filters, or create a new project to get started.</p>
                                <a href="{{ route('projects.create') }}"
                                    class="mt-4 inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-colors">
                                    <i class="fas fa-plus-circle"></i> Create project
                                </a>
                            </div>
                        @else
                            <div class="rounded-2xl border border-gray-200 bg-white shadow-sm overflow-hidden">
                                <!-- Toolbar: tabs -->
                                <div class="border-b border-gray-200 bg-gray-50/70">
                                    <div class="flex flex-col gap-3 px-4 py-4 lg:flex-row lg:items-center lg:justify-between">
                                        <div class="inline-flex flex-wrap items-center gap-1 rounded-xl bg-gray-100 p-1" role="tablist">
                                            @foreach ([['id' => 'all', 'label' => 'All projects', 'dot' => 'bg-indigo-500', 'total' => $allTotal], ['id' => 'active', 'label' => 'Active', 'dot' => 'bg-green-500', 'total' => $activeTotal], ['id' => 'mixed', 'label' => 'Completed, active sub-projects', 'dot' => 'bg-amber-500', 'total' => $mixedTotal], ['id' => 'completed', 'label' => 'Completed', 'dot' => 'bg-slate-400', 'total' => $completedTotal]] as $t)
                                                <button type="button" x-on:click="tab = '{{ $t['id'] }}'"
                                                    :class="tab === '{{ $t['id'] }}' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-800'"
                                                    class="inline-flex items-center gap-2 rounded-lg px-3 py-1.5 text-sm font-semibold transition-all duration-150">
                                                    <span class="h-2 w-2 rounded-full {{ $t['dot'] }}"></span>
                                                    {{ $t['label'] }}
                                                    <span :class="tab === '{{ $t['id'] }}' ? 'bg-gray-900 text-white' : 'bg-gray-200 text-gray-600'"
                                                        class="rounded-full px-1.5 py-0.5 text-[11px] font-bold leading-none transition-colors">{{ $t['total'] }}</span>
                                                </button>
                                            @endforeach
                                        </div>

                                        <div class="flex flex-wrap items-center gap-2">
                                            @if ($activeFilters > 0)
                                                <button type="button" wire:click="clearFilters"
                                                    class="inline-flex items-center gap-2 px-3 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                                    <i class="fas fa-times"></i> Clear filters ({{ $activeFilters }})
                                                </button>
                                            @endif
                                            <div class="inline-flex items-center gap-2 pl-1">
                                                <label for="per-page" class="text-sm font-medium text-gray-500">Rows</label>
                                                <select id="per-page" wire:model.live="perPage"
                                                    class="px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-100 focus:border-indigo-300 bg-white">
                                                    <option value="5">5</option>
                                                    <option value="10">10</option>
                                                    <option value="20">20</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tab panels -->
                                <div class="p-4 sm:p-5">
                                    <div x-show="tab === 'all'" x-cloak>
                                        @include('livewire.partials.project-table', [
                                            'projects' => $allProjects,
                                            'person' => $person,
                                            'accent' => 'indigo',
                                            'endField' => 'date_end',
                                            'endLabel' => 'End Date',
                                            'endSortKey' => 'date_end',
                                            'endFallback' => 'Ongoing',
                                            'showActions' => false,
                                            'dimRows' => false,
                                            'radioPrefix' => 'project_all_',
                                            'filterEndFrom' => 'officialEndFrom',
                                            'filterEndTo' => 'officialEndTo',
                                            'emptyMessage' => 'No projects match your filters.',
                                        ])
                                    </div>

                                    <div x-show="tab === 'active'" x-cloak>
                                        @include('livewire.partials.project-table', [
                                            'projects' => $activeProjects,
                                            'person' => $person,
                                            'accent' => 'green',
                                            'endField' => 'date_end_intended',
                                            'endLabel' => 'Intended End Date',
                                            'endSortKey' => 'date_end_intended',
                                            'endFallback' => 'Ongoing',
                                            'showActions' => true,
                                            'dimRows' => false,
                                            'radioPrefix' => 'project_',
                                            'filterEndFrom' => 'intendedEndFrom',
                                            'filterEndTo' => 'intendedEndTo',
                                            'emptyMessage' => 'No active projects match your filters.',
                                        ])
                                    </div>

                                    <div x-show="tab === 'mixed'" x-cloak>
                                        @include('livewire.partials.project-table', [
                                            'projects' => $completedWithActiveSubProjects,
                                            'person' => $person,
                                            'accent' => 'amber',
                                            'endField' => 'date_end',
                                            'endLabel' => 'Official End Date',
                                            'endSortKey' => 'date_end',
                                            'endFallback' => 'N/A',
                                            'showActions' => false,
                                            'dimRows' => false,
                                            'radioPrefix' => 'project_mixed_',
                                            'filterEndFrom' => 'officialEndFrom',
                                            'filterEndTo' => 'officialEndTo',
                                            'emptyMessage' => 'No completed projects with active sub-projects match your filters.',
                                        ])
                                    </div>

                                    <div x-show="tab === 'completed'" x-cloak>
                                        @include('livewire.partials.project-table', [
                                            'projects' => $completedProjects,
                                            'person' => $person,
                                            'accent' => 'slate',
                                            'endField' => 'date_end',
                                            'endLabel' => 'Official End Date',
                                            'endSortKey' => 'date_end',
                                            'endFallback' => 'N/A',
                                            'showActions' => false,
                                            'dimRows' => true,
                                            'radioPrefix' => 'project_completed_',
                                            'filterEndFrom' => 'officialEndFrom',
                                            'filterEndTo' => 'officialEndTo',
                                            'emptyMessage' => 'No completed projects match your filters.',
                                        ])
                                    </div>
                                </div>
                            </div>
                        @endif
                    </section>
                </form>
            </div>
        </div>
    </div>

    <div id="titleModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden" onclick="closeModal()">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 max-w-2xl w-full" onclick="event.stopPropagation()">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-medium text-gray-900" id="modalTitle"></h3>
                    <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                        <span class="sr-only">Close</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <p class="text-gray-500 whitespace-pre-wrap" id="modalContent"></p>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div id="myProjectsSuccessMessage" class="hidden">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div id="myProjectsErrorMessage" class="hidden">{{ session('error') }}</div>
    @endif
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const successMessage = document.getElementById('myProjectsSuccessMessage');
            const errorMessage = document.getElementById('myProjectsErrorMessage');

            if (successMessage && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: successMessage.textContent,
                    confirmButtonText: 'OK',
                });
            }

            if (errorMessage && typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage.textContent,
                    confirmButtonText: 'OK',
                });
            }
        });
    </script>
    <script>
        function subProjectCreateModal({
            projectId,
            endpoint
        }) {
            return {
                open: false,
                code: '',
                codeError: '',
                checking: false,
                async checkCode() {
                    const value = String(this.code ?? '').trim();
                    if (value === '') {
                        this.codeError = '';
                        return;
                    }

                    this.checking = true;
                    try {
                        const response = await fetch(endpoint, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            },
                            body: JSON.stringify({
                                project_id: projectId,
                                code: value
                            })
                        });
                        const payload = await response.json();
                        this.codeError = payload?.available ? '' : (payload?.message || 'This sub-project code is already used.');
                    } catch (error) {
                        this.codeError = '';
                    } finally {
                        this.checking = false;
                    }
                }
            };
        }

        function subProjectEditModal() {
            return {
                open: false,
                form: {
                    id: null,
                    code: '',
                    name: '',
                    title: '',
                    description: '',
                    date_started: '',
                    date_end_intended: '',
                    date_end: '',
                    people_ids: [],
                },
                openEdit(detail) {
                    this.form = {
                        id: detail.id,
                        code: detail.code ?? '',
                        name: detail.name ?? '',
                        title: detail.title ?? '',
                        description: detail.description ?? '',
                        date_started: detail.date_started ?? '',
                        date_end_intended: detail.date_end_intended ?? '',
                        date_end: detail.date_end ?? '',
                        people_ids: Array.isArray(detail.people_ids) ? [...detail.people_ids] : [],
                    };
                    this.open = true;
                },
                togglePerson(personId, checked) {
                    const id = Number(personId);
                    if (checked) {
                        if (!this.form.people_ids.includes(id)) {
                            this.form.people_ids.push(id);
                        }
                    } else {
                        this.form.people_ids = this.form.people_ids.filter((value) => value !== id);
                    }
                },
            };
        }

        function escapeHtml(value) {
            const div = document.createElement('div');
            div.textContent = String(value ?? '');
            return div.innerHTML;
        }

        function showFullTitle(code, title) {
            document.getElementById('modalTitle').textContent = `Project ${code} - Full Title`;
            document.getElementById('modalContent').textContent = title;
            document.getElementById('titleModal').classList.remove('hidden');
        }

        function showFundingSources(fundings) {
            const list = Array.isArray(fundings) ? fundings : [];

            const html = list.length ?
                `
                    <div class="text-left">
                        <div class="text-sm text-gray-600 mb-3">Funding sources</div>
                        <div class="flex flex-col gap-2">
                            ${list.map(item => {
                                const label = escapeHtml(item?.label ?? 'Funding');
                                const url = String(item?.url ?? '#');
                                return `<a href="${url}" class="text-sm font-semibold text-purple-700 hover:text-purple-900 underline">${label}</a>`;
                            }).join('')}
                        </div>
                    </div>
                ` :
                `<div class="text-sm text-gray-600">No funding sources.</div>`;

            Swal.fire({
                title: 'Funding',
                html,
                icon: 'info',
                showConfirmButton: true,
                confirmButtonText: 'Close',
            });
        }

        function showCollaborators(collaborators) {
    const html = collaborators.length
        ? `
    <div class="text-left">
        <div class="text-sm text-gray-600 mb-3">Collaborators</div>
        <div class="flex flex-col gap-2">
            ${collaborators.map(c => `
                <div class="flex items-center gap-2">
                    ${c.logo 
                        ? `<img src="${c.logo}" class="w-8 h-8 rounded-full object-cover" alt="${c.label}">` 
                        : `<div class="w-8 h-8 rounded-full bg-gray-400 flex items-center justify-center text-white font-medium">${c.label[0] ?? '?'}</div>`}
                    <a href="${c.url}" class="text-sm font-semibold text-indigo-700 hover:text-indigo-900 underline">
                        ${c.label}
                    </a>
                </div>
            `).join('')}
        </div>
    </div>
    `
        : `<div class="text-sm text-gray-600">No collaborators.</div>`;

    Swal.fire({
        title: 'Collaborators',
        html,
        icon: 'info',
        showConfirmButton: true,
        confirmButtonText: 'Close',
        width: '400px',
    });
}



        document.addEventListener('livewire:init', () => {
            if (typeof Livewire === 'undefined') return;

            Livewire.on('fundings-modal', (payload) => {
                const fundings = payload?.fundings ?? [];
                showFundingSources(fundings);
            });

            Livewire.on('collaborators-modal', (payload) => {
                const collaborators = payload?.collaborators ?? [];
                showCollaborators(collaborators);
            });
        });

        function closeModal() {
            document.getElementById('titleModal').classList.add('hidden');
        }

        function submitProjectSelection(radio) {
            const form = document.getElementById('projectSelectionForm');
            // Each tab uses its own radio group name, so build the payload from the
            // clicked radio explicitly instead of relying on the whole form's data.
            const formData = new FormData();
            formData.append('selected_project', radio.value);

            fetch(form.action, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: data.message,
                            position: 'center',
                            showConfirmButton: false,
                            timer: 1500,
                            timerProgressBar: true,
                            width: 'auto',
                            padding: '2em'
                        }).then(() => window.location.reload());
                    }
                });
        }

        function markProjectComplete(projectId, projectCode, startDate) {
            const today = new Date().toISOString().slice(0, 10);

            Swal.fire({
                title: `Mark project ${projectCode} complete`,
                html: `
                <div class="text-left">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Official end date</label>
                    <input id="official_end_date" type="date" class="swal2-input" value="${today}">
                    <div class="text-xs text-gray-500 mt-2">This will move the project to the “Completed Projects” section.</div>
                </div>
            `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Mark complete',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const dateEnd = document.getElementById('official_end_date')?.value;
                    if (!dateEnd) {
                        Swal.showValidationMessage('Please choose an official end date.');
                        return false;
                    }
                    if (startDate && dateEnd < startDate) {
                        Swal.showValidationMessage('Official end date cannot be before the start date.');
                        return false;
                    }
                    return {
                        date_end: dateEnd
                    };
                }
            }).then((result) => {
                if (!result.isConfirmed) return;

                fetch(`/projects/${projectId}/complete`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(result.value)
                    })
                    .then(r => r.json().then(data => ({
                        ok: r.ok,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        if (!ok || !data.success) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to mark project complete.'
                            });
                            return;
                        }
                        Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message || 'Project marked complete.',
                                timer: 1500,
                                showConfirmButton: false
                            })
                            .then(() => window.location.reload());
                    })
                    .catch(() => Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to mark project complete.'
                    }));
            });
        }

        function markSubProjectComplete(subProjectId, subProjectCode, startDate) {
            const today = new Date().toISOString().slice(0, 10);

            Swal.fire({
                title: `Mark ${subProjectCode} complete`,
                html: `
                <div class="text-left">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sub-project end date</label>
                    <input id="sub_project_end_date" type="date" class="swal2-input" value="${today}">
                </div>
            `,
                focusConfirm: false,
                showCancelButton: true,
                confirmButtonText: 'Mark complete',
                cancelButtonText: 'Cancel',
                preConfirm: () => {
                    const dateEnd = document.getElementById('sub_project_end_date')?.value;
                    if (!dateEnd) {
                        Swal.showValidationMessage('Please choose an end date.');
                        return false;
                    }
                    if (startDate && dateEnd < startDate) {
                        Swal.showValidationMessage('End date cannot be before the start date.');
                        return false;
                    }
                    return {
                        date_end: dateEnd
                    };
                }
            }).then((result) => {
                if (!result.isConfirmed) return;

                fetch(`/sub-projects/${subProjectId}/complete`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify(result.value)
                    })
                    .then(r => r.json().then(data => ({
                        ok: r.ok,
                        data
                    })))
                    .then(({
                        ok,
                        data
                    }) => {
                        if (!ok || !data.success) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to mark sub-project complete.'
                            });
                            return;
                        }
                        Swal.fire({
                                icon: 'success',
                                title: 'Success',
                                text: data.message || 'Sub-project marked complete.',
                                timer: 1500,
                                showConfirmButton: false
                            })
                            .then(() => window.location.reload());
                    })
                    .catch(() => Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to mark sub-project complete.'
                    }));
            });
        }

        function deleteProject(projectId, projectCode) {
            Swal.fire({
                title: `Delete project ${projectCode}?`,
                html: '<p class="text-sm text-slate-600">This permanently removes the project and all associated data. This cannot be undone.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Delete project',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                const form = document.createElement('form');
                form.method = 'POST';
                form.action = `/projects/${projectId}`;
                form.innerHTML = `
                    <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                    <input type="hidden" name="_method" value="DELETE">
                `;
                document.body.appendChild(form);
                form.submit();
            });
        }

        function deleteSubProject(subProjectId, subProjectCode, form = null) {
            Swal.fire({
                title: `Delete sub-project ${subProjectCode}?`,
                html: '<p class="text-sm text-slate-600">Records linked to this sub-project will be kept, but their sub-project assignment will be removed.</p>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                confirmButtonText: 'Delete sub-project',
                cancelButtonText: 'Cancel',
            }).then((result) => {
                if (!result.isConfirmed) {
                    return;
                }

                if (form) {
                    form.submit();
                    return;
                }

                const deleteForm = document.createElement('form');
                deleteForm.method = 'POST';
                deleteForm.action = `/sub-projects/${subProjectId}`;
                deleteForm.innerHTML = `
                    <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]').content}">
                    <input type="hidden" name="_method" value="DELETE">
                `;
                document.body.appendChild(deleteForm);
                deleteForm.submit();
            });
        }
    </script>
@endpush
