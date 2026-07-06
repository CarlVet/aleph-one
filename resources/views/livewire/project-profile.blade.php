@php
    $sampleLabels = [
        'human_samples' => 'Human samples',
        'animal_samples' => 'Animal samples',
        'environment_samples' => 'Environment samples',
        'parasite_samples' => 'Parasite samples',
        'nucleic_acids' => 'Nucleic acids',
        'cultures' => 'Cultures',
        'pools' => 'Pools',
    ];
    $sampleColors = [
        'human_samples' => 'bg-emerald-100 text-emerald-800',
        'animal_samples' => 'bg-blue-100 text-blue-800',
        'environment_samples' => 'bg-amber-100 text-amber-800',
        'parasite_samples' => 'bg-purple-100 text-purple-800',
        'nucleic_acids' => 'bg-rose-100 text-rose-800',
        'cultures' => 'bg-indigo-100 text-indigo-800',
        'pools' => 'bg-pink-100 text-pink-800',
    ];
    $sampleLinks = [
        'human_samples' => '/samples/humans/list',
        'animal_samples' => '/samples/animals/list',
        'environment_samples' => '/samples/environment/list',
        'parasite_samples' => '/samples/parasites/list',
        'nucleic_acids' => '/samples/nucleic/list',
        'cultures' => '/samples/cultures/list',
        'pools' => '/samples/pools/list',
    ];
    $sourceColors = [
        'Human' => 'bg-emerald-100 text-emerald-800',
        'Animal' => 'bg-blue-100 text-blue-800',
        'Environment' => 'bg-amber-100 text-amber-800',
        'Parasite' => 'bg-purple-100 text-purple-800',
        'Culture' => 'bg-indigo-100 text-indigo-800',
        'Nucleic' => 'bg-rose-100 text-rose-800',
        'Pool' => 'bg-pink-100 text-pink-800',
    ];
    $summaryText = filled($project->description) ? $project->description : ($project->notes ?? '');
    $summaryPreview = \Illuminate\Support\Str::limit(trim($summaryText), 140);
    $hasLongSummary = strlen(trim($summaryText)) > 140;
    $fundingSourceList = $project->fundings->pluck('source')->filter()->unique()->values();
    $sortedSampleTypes = collect($metrics['samples']['by_type'])->filter(fn ($count) => $count > 0)->sortDesc();
    $projectTitle = $project->title ?: 'Untitled project';
    $hasLongTitle = strlen($projectTitle) > 72;
    $statusBadge = ($project->status ?? 'active') === 'completed'
        ? 'bg-slate-200 text-slate-800'
        : 'bg-emerald-100 text-emerald-800';
@endphp

<div class="relative overflow-hidden bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/40 min-h-screen"
    x-data="{ summaryExpanded: false, subProjectsExpanded: false, expandedSampleType: null, titleExpanded: false }">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-blue-100/40 via-transparent to-transparent"></div>
    <div class="pointer-events-none absolute -left-32 top-40 h-72 w-72 rounded-full bg-indigo-200/20 blur-3xl"></div>
    <div class="pointer-events-none absolute -right-24 bottom-20 h-64 w-64 rounded-full bg-sky-200/25 blur-3xl"></div>

    <div class="relative max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 py-8 space-y-6">
        {{-- Hero --}}
        <div class="overflow-hidden rounded-3xl bg-white shadow-xl ring-1 ring-slate-900/5">
            <div class="border-b border-slate-100 bg-gradient-to-r from-slate-900 via-blue-900 to-indigo-900 px-6 py-8 sm:px-8">
                <div class="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 flex-1">
                        <div class="flex items-start gap-4">
                            <span class="inline-flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-white/15 text-white shadow-lg backdrop-blur">
                                <i class="fas fa-folder-open text-2xl"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-medium text-blue-200">Project profile</p>
                                <h1 class="mt-1 text-2xl font-bold tracking-tight text-white sm:text-3xl"
                                    x-bind:class="titleExpanded ? 'leading-snug' : 'line-clamp-2 leading-snug'">
                                    {{ $projectTitle }}
                                </h1>
                                @if($hasLongTitle)
                                    <button type="button"
                                        x-on:click="titleExpanded = !titleExpanded"
                                        class="mt-1 text-xs font-semibold text-blue-200 hover:text-white"
                                        x-text="titleExpanded ? 'Show less' : 'Show full title'">
                                    </button>
                                @endif
                                <div class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                                    <span class="inline-flex items-center rounded-full bg-white/15 px-3 py-1 font-semibold text-white">
                                        {{ $project->code }}
                                    </span>
                                    <span class="inline-flex items-center rounded-full px-3 py-1 font-semibold {{ $statusBadge }}">
                                        {{ ucfirst($project->status ?? 'active') }}
                                    </span>
                                    @if($project->type)
                                        <span class="inline-flex items-center rounded-full bg-white/10 px-3 py-1 text-blue-100">
                                            {{ $project->type }}
                                        </span>
                                    @endif
                                </div>
                                @if($project->alias_code)
                                    <p class="mt-1 text-sm text-blue-100/90">Alias: <span class="font-semibold text-white">{{ $project->alias_code }}</span></p>
                                @endif
                            </div>
                        </div>
                        <div class="mt-5 space-y-2 text-sm text-blue-100/90">
                            <div class="flex flex-wrap gap-4">
                                <span><i class="fas fa-calendar-day mr-1.5 opacity-70"></i>Started {{ $project->date_started ?? '—' }}</span>
                                <span><i class="fas fa-flag-checkered mr-1.5 opacity-70"></i>End {{ $project->date_end ?? $project->date_end_intended ?? '—' }}</span>
                                @if($project->ethics_ref)
                                    <span><i class="fas fa-shield-alt mr-1.5 opacity-70"></i>{{ $project->ethics_ref }}</span>
                                @endif
                            </div>
                            @if($fundingSourceList->isNotEmpty())
                                <p>
                                    <i class="fas fa-hand-holding-usd mr-1.5 opacity-70"></i>
                                    <span class="font-medium text-blue-100">Funding sources:</span>
                                    {{ $fundingSourceList->join(', ') }}
                                </p>
                            @endif
                        </div>
                    </div>
                    <div class="flex flex-wrap gap-2 shrink-0">
                        <a href="{{ route('profile.projects') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/25">
                            <i class="fas fa-arrow-left"></i>
                            My projects
                        </a>
                        <a href="/documents"
                           class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/25">
                            <i class="fas fa-file-alt"></i>
                            Documents
                        </a>
                        @if($canEdit)
                            <a href="{{ route('projects.edit', ['project' => $project->id, 'section' => 'general']) }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-md transition hover:bg-blue-50">
                                <i class="fas fa-edit"></i>
                                Edit
                            </a>
                            @if(($project->status ?? '') !== 'completed')
                                <button type="button"
                                    onclick="markProjectComplete({{ $project->id }}, '{{ $project->code }}', '{{ $project->date_started ?? '' }}')"
                                    class="inline-flex items-center gap-2 rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-emerald-600">
                                    <i class="fas fa-check"></i>
                                    Mark complete
                                </button>
                            @endif
                        @endif
                        @if($canDeleteProject)
                            <button type="button"
                                onclick="deleteProject({{ $project->id }}, @js($project->code))"
                                class="inline-flex items-center gap-2 rounded-xl bg-red-600/90 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-red-700">
                                <i class="fas fa-trash"></i>
                                Delete
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Summary cards --}}
            <div class="grid grid-cols-2 gap-4 p-6 sm:grid-cols-3 lg:grid-cols-6 sm:p-8">
                @foreach ([
                    ['label' => 'Samples', 'value' => $metrics['samples']['total'], 'icon' => 'fa-flask', 'gradient' => 'from-blue-500 to-blue-600'],
                    ['label' => 'Experiments', 'value' => $metrics['experiments']['total'], 'icon' => 'fa-microscope', 'gradient' => 'from-emerald-500 to-emerald-600'],
                    ['label' => 'Sequences', 'value' => $metrics['sequences'], 'icon' => 'fa-dna', 'gradient' => 'from-violet-500 to-violet-600'],
                    ['label' => 'Documents', 'value' => $metrics['documents'], 'icon' => 'fa-file-alt', 'gradient' => 'from-sky-500 to-sky-600'],
                    ['label' => 'Team', 'value' => $metrics['team_size'], 'icon' => 'fa-users', 'gradient' => 'from-indigo-500 to-indigo-600'],
                    ['label' => 'Sub-projects', 'value' => $metrics['sub_projects'], 'icon' => 'fa-sitemap', 'gradient' => 'from-amber-500 to-amber-600'],
                ] as $card)
                    <div class="rounded-2xl border border-slate-100 bg-slate-50/80 p-4 text-center transition hover:-translate-y-0.5 hover:shadow-md">
                        <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-xl bg-gradient-to-br {{ $card['gradient'] }} text-white shadow-md">
                            <i class="fas {{ $card['icon'] }}"></i>
                        </div>
                        <div class="text-2xl font-bold text-slate-900">{{ number_format($card['value']) }}</div>
                        <div class="mt-1 text-xs font-medium text-slate-500">{{ $card['label'] }}</div>
                    </div>
                @endforeach
            </div>

            @if($project->people->isNotEmpty())
                <div class="flex flex-wrap items-center gap-4 border-t border-slate-100 px-6 py-5 sm:px-8">
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Team</span>
                    <div class="flex flex-wrap items-center gap-2">
                        @foreach($project->people->unique('id') as $member)
                            @php
                                $memberName = trim(($member->title ?? '').' '.($member->first_name ?? '').' '.($member->last_name ?? '')) ?: 'N/A';
                                $memberEmail = $member->users?->email ?? $member->email ?? null;
                            @endphp
                            <div class="relative" x-data="{ show: false }">
                                <a href="{{ route('profile.show', $member->id) }}"
                                   class="block rounded-full transition hover:z-20 hover:scale-110"
                                   x-on:mouseenter="show = true"
                                   x-on:mouseleave="show = false"
                                   x-on:focus="show = true"
                                   x-on:blur="show = false">
                                    <x-people-logo :person="$member" width="40" class="ring-2 ring-white shadow-sm" />
                                </a>
                                <div x-show="show"
                                     x-transition.opacity
                                     x-cloak
                                     class="pointer-events-none absolute bottom-full left-1/2 z-30 mb-2 w-56 -translate-x-1/2 rounded-xl border border-slate-200 bg-white p-3 shadow-lg ring-1 ring-slate-900/5">
                                    <p class="text-sm font-semibold text-slate-900">{{ $memberName }}</p>
                                    <p class="mt-1 text-xs font-medium text-indigo-700">{{ $member->pivot->role ?? 'Member' }}</p>
                                    @if(filled($memberEmail))
                                        <p class="mt-1 truncate text-xs text-slate-500">{{ $memberEmail }}</p>
                                    @endif
                                    <p class="mt-2 text-[11px] text-blue-600">View profile →</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- Row 1: summary, sub-projects (inline expand) --}}
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2 md:items-start">
            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm ring-1 ring-slate-900/5">
                <button type="button"
                    class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left transition hover:bg-slate-50"
                    x-on:click="summaryExpanded = !summaryExpanded">
                    <h2 class="text-base font-semibold text-slate-900">Project summary</h2>
                    <i class="fas text-slate-400" x-bind:class="summaryExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div class="border-t border-slate-100 px-5 py-4">
                    @if(filled(trim($summaryText)))
                        <div x-show="!summaryExpanded">
                            <p class="line-clamp-3 text-sm leading-relaxed text-slate-600">{{ $summaryPreview }}</p>
                        </div>
                        <div x-show="summaryExpanded" x-collapse>
                            <div class="text-sm leading-relaxed text-slate-700 whitespace-pre-wrap">{{ $summaryText }}</div>
                        </div>
                    @else
                        <p class="text-sm text-slate-500">No summary provided.</p>
                    @endif
                </div>
            </div>

            <div class="overflow-hidden rounded-2xl border border-slate-100 bg-white shadow-sm ring-1 ring-slate-900/5">
                <button type="button"
                    class="flex w-full items-center justify-between gap-3 px-5 py-4 text-left transition hover:bg-slate-50"
                    x-on:click="subProjectsExpanded = !subProjectsExpanded">
                    <h2 class="text-base font-semibold text-slate-900">Sub-projects ({{ $project->subProjects->count() }})</h2>
                    <i class="fas text-slate-400" x-bind:class="subProjectsExpanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                </button>
                <div class="border-t border-slate-100 px-5 py-4">
                    <div x-show="!subProjectsExpanded">
                        @if($project->subProjects->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach($project->subProjects->take(6) as $subProject)
                                    <span class="rounded-full bg-indigo-50 px-3 py-1 text-sm font-medium text-indigo-800 ring-1 ring-indigo-100">{{ $subProject->code }}</span>
                                @endforeach
                                @if($project->subProjects->count() > 6)
                                    <span class="text-sm text-slate-500">+{{ $project->subProjects->count() - 6 }} more</span>
                                @endif
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No sub-projects yet.</p>
                        @endif
                    </div>
                    <div x-show="subProjectsExpanded" x-collapse class="space-y-4">
                        @forelse($project->subProjects as $subProject)
                            <div class="rounded-xl border border-indigo-100 bg-gradient-to-r from-white to-indigo-50/40 p-4">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span class="font-bold text-slate-900">{{ $subProject->code }}</span>
                                            <span class="rounded-full px-2.5 py-0.5 text-xs font-semibold {{ ($subProject->status ?? 'active') === 'completed' ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-800' }}">
                                                {{ ucfirst($subProject->status ?? 'active') }}
                                            </span>
                                            <span class="rounded-full bg-indigo-100 px-2.5 py-0.5 text-xs font-semibold text-indigo-800">{{ $subProject->name }}</span>
                                        </div>
                                        <p class="mt-2 text-sm font-medium text-slate-800">{{ $subProject->title ?: 'Untitled sub-project' }}</p>
                                        @if(filled($subProject->description))
                                            <p class="mt-1 text-sm text-slate-600">{{ $subProject->description }}</p>
                                        @endif
                                    </div>
                                    @if($canManageSubProjects)
                                        <div class="flex shrink-0 items-center gap-3">
                                            @if(($subProject->status ?? 'active') !== 'completed')
                                                <button type="button"
                                                    onclick="markSubProjectComplete({{ $subProject->id }}, '{{ $subProject->code }}', '{{ $subProject->date_started ?? '' }}')"
                                                    class="text-sm font-semibold text-emerald-700 hover:text-emerald-900">Mark complete</button>
                                            @endif
                                            <form method="POST" action="{{ route('sub-projects.destroy', $subProject) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    onclick="deleteSubProject({{ $subProject->id }}, @js($subProject->code), this.closest('form'))"
                                                    class="text-sm font-semibold text-red-600 hover:text-red-800">Delete</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-slate-500">No sub-projects created yet.</p>
                        @endforelse
                        @if($canManageSubProjects)
                            <div x-data="subProjectCreateModal({ projectId: {{ (int) $project->id }}, endpoint: '{{ route('sub-projects.check-code') }}' })">
                                <button type="button" x-on:click="open = true"
                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:from-blue-700 hover:to-indigo-700">
                                    <i class="fas fa-plus"></i> Register new sub-project
                                </button>
                                <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" x-on:click.self="open = false">
                                    <div class="w-full max-w-3xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
                                        <div class="relative border-b border-slate-100 bg-gradient-to-r from-blue-50 via-indigo-50 to-slate-50 px-6 py-5">
                                            <button type="button" x-on:click="open = false" class="absolute right-4 top-4 text-slate-400 hover:text-slate-600">
                                                <i class="fas fa-times"></i>
                                            </button>
                                            <h4 class="text-lg font-bold text-slate-900">Register new sub-project</h4>
                                        </div>
                                        <form method="POST" action="{{ route('sub-projects.store') }}" class="space-y-3 p-6">
                                @csrf
                                <input type="hidden" name="project_id" value="{{ $project->id }}">
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                                    <div>
                                        <input type="text" name="code" placeholder="Code" x-model="code" x-on:input.debounce.350ms="checkCode()"
                                            class="w-full rounded-xl border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        <div x-show="codeError" class="mt-1 text-xs text-red-600" x-text="codeError"></div>
                                    </div>
                                    <div>
                                        <input type="text" name="name" list="sub-project-types" placeholder="Type"
                                            class="w-full rounded-xl border-slate-200 px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        <datalist id="sub-project-types">
                                            @foreach($subProjectTypeOptions as $typeOption)
                                                <option value="{{ $typeOption }}"></option>
                                            @endforeach
                                        </datalist>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-3 md:grid-cols-3">
                                    <input type="text" name="title" placeholder="Title" class="w-full rounded-xl border-slate-200 px-3 py-2 text-sm shadow-sm">
                                    <input type="date" name="date_started" class="w-full rounded-xl border-slate-200 px-3 py-2 text-sm shadow-sm">
                                    <input type="date" name="date_end_intended" class="w-full rounded-xl border-slate-200 px-3 py-2 text-sm shadow-sm">
                                </div>
                                <textarea name="description" rows="2" placeholder="Description (optional)"
                                    class="w-full rounded-xl border-slate-200 px-3 py-2 text-sm shadow-sm"></textarea>
                                <div>
                                    <div class="mb-2 text-xs font-medium text-slate-600">Members</div>
                                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                        @foreach($project->people as $member)
                                            <label class="inline-flex items-center gap-2 text-xs text-slate-700">
                                                <input type="checkbox" name="people_ids[]" value="{{ $member->id }}">
                                                {{ trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                            <div class="flex justify-end gap-2 border-t border-slate-100 pt-4">
                                                <button type="button" x-on:click="open = false" class="rounded-xl bg-slate-100 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-200">Cancel</button>
                                                <button type="submit" x-bind:disabled="codeError !== '' || checking"
                                                    class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
                                                    <i class="fas fa-plus"></i>Create sub-project
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Row 2: samples by type, pathogen domain, top pathogens --}}
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3 lg:items-start">
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                <h3 class="mb-5 flex items-center gap-3 text-base font-semibold text-slate-900">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-blue-100 text-blue-700"><i class="fas fa-flask"></i></span>
                    Samples by type
                </h3>
                <div class="space-y-2">
                    @forelse($sortedSampleTypes as $type => $count)
                        <div class="overflow-hidden rounded-xl border border-slate-200">
                            <button type="button"
                                class="flex w-full items-center justify-between bg-slate-50 px-4 py-3 text-left transition hover:bg-slate-100"
                                x-on:click="expandedSampleType = expandedSampleType === '{{ $type }}' ? null : '{{ $type }}'">
                                <span class="flex min-w-0 items-center gap-2.5">
                                    <i class="fas text-xs text-slate-400" x-bind:class="expandedSampleType === '{{ $type }}' ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                    <a href="{{ $sampleLinks[$type] }}" class="truncate text-sm font-semibold text-blue-700 hover:text-blue-900" x-on:click.stop>{{ $sampleLabels[$type] }}</a>
                                </span>
                                <span class="ml-3 shrink-0 rounded-full px-3 py-1 text-sm font-bold {{ $sampleColors[$type] }}">{{ number_format($count) }}</span>
                            </button>
                            <div class="border-t border-slate-100 bg-white px-4 py-3" x-show="expandedSampleType === '{{ $type }}'" x-collapse>
                                @foreach($metrics['samples']['details'][$type] ?? [] as $group)
                                    @if(count($group['data']) > 0)
                                        <div class="mb-4 last:mb-0">
                                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $group['label'] }}</p>
                                            <div class="space-y-2">
                                                @foreach($group['data'] as $label => $detailCount)
                                                    <div class="flex items-center justify-between rounded-lg bg-slate-50 px-3 py-2 text-sm">
                                                        <span class="truncate text-slate-700">{{ $label }}</span>
                                                        <span class="ml-3 shrink-0 font-semibold text-slate-900">{{ number_format($detailCount) }}</span>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No samples recorded yet.</p>
                    @endforelse
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                <h3 class="mb-5 flex items-center gap-3 text-base font-semibold text-slate-900">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-orange-100 text-orange-700"><i class="fas fa-virus"></i></span>
                    Experiments by pathogen domain
                </h3>
                @if(count($metrics['experiments']['by_pathogen']) > 0)
                    <div class="max-h-[32rem] space-y-2 overflow-y-auto pr-1" x-data="{ openDomains: {}, openFamilies: {} }">
                        @foreach ($metrics['experiments']['by_pathogen'] as $domain => $domainData)
                            @php $domainKey = md5($domain); @endphp
                            <div class="overflow-hidden rounded-xl border border-slate-200">
                                <button type="button"
                                    class="flex w-full items-center justify-between bg-slate-50 px-4 py-3 text-left transition hover:bg-slate-100"
                                    x-on:click="openDomains['{{ $domainKey }}'] = !openDomains['{{ $domainKey }}']">
                                    <span class="flex min-w-0 items-center gap-2.5">
                                        <i class="fas text-xs text-slate-400" x-bind:class="openDomains['{{ $domainKey }}'] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                        <span class="truncate font-semibold text-slate-800">{{ $domain }}</span>
                                    </span>
                                    <span class="ml-3 shrink-0 rounded-full bg-orange-100 px-3 py-1 text-sm font-bold text-orange-800">{{ number_format($domainData['count']) }}</span>
                                </button>
                                <div class="border-t border-slate-100" x-show="openDomains['{{ $domainKey }}']" x-collapse>
                                    @foreach ($domainData['families'] as $family => $familyData)
                                        @php $familyKey = md5($domain.$family); @endphp
                                        <button type="button"
                                            class="flex w-full items-center justify-between px-4 py-2.5 pl-8 text-left text-sm transition hover:bg-slate-50"
                                            x-on:click="openFamilies['{{ $familyKey }}'] = !openFamilies['{{ $familyKey }}']">
                                            <span class="flex min-w-0 items-center gap-2">
                                                <i class="fas text-xs text-slate-300" x-bind:class="openFamilies['{{ $familyKey }}'] ? 'fa-chevron-down' : 'fa-chevron-right'"></i>
                                                <span class="truncate text-slate-700">{{ $family }}</span>
                                            </span>
                                            <span class="ml-3 shrink-0 rounded-full bg-blue-100 px-2.5 py-0.5 text-sm font-semibold text-blue-800">{{ number_format($familyData['count']) }}</span>
                                        </button>
                                        <div x-show="openFamilies['{{ $familyKey }}']" x-collapse>
                                            @foreach ($familyData['species'] as $species => $speciesCount)
                                                <div class="flex items-center justify-between bg-slate-50/80 py-2 pl-12 pr-4 text-sm">
                                                    <span class="truncate text-slate-600">{{ $species }}</span>
                                                    <span class="ml-3 shrink-0 font-semibold text-purple-800">{{ number_format($speciesCount) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <p class="text-sm text-slate-500">No pathogen-targeted experiments yet.</p>
                @endif
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                <h3 class="mb-5 flex items-center gap-3 text-base font-semibold text-slate-900">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-700"><i class="fas fa-bullseye"></i></span>
                    Top pathogen species
                </h3>
                @if(count($metrics['experiments']['top_pathogens']) > 0)
                    <div class="space-y-2">
                        @foreach ($metrics['experiments']['top_pathogens'] as $species => $count)
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <span class="min-w-0 truncate text-sm font-medium text-slate-800">{{ $species }}</span>
                                <span class="ml-3 shrink-0 rounded-full bg-purple-100 px-3 py-1 text-sm font-bold text-purple-800">{{ number_format($count) }}</span>
                            </div>
                        @endforeach
                    </div>
                    <a href="/experiments/list" class="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-blue-700 hover:text-blue-900">
                        View experiments <i class="fas fa-arrow-right text-xs"></i>
                    </a>
                @else
                    <p class="text-sm text-slate-500">No pathogen targets recorded yet.</p>
                @endif
            </div>
        </div>

        {{-- Row 3: derived content --}}
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-4 xl:items-start">
            @php $hasParasiteSource = array_sum($metrics['content']['parasite_by_source']) > 0; @endphp
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                <h3 class="mb-5 flex items-center gap-3 text-base font-semibold text-slate-900">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-700"><i class="fas fa-bug"></i></span>
                    Parasite samples by source
                </h3>
                <div class="space-y-2">
                    @foreach ($metrics['content']['parasite_by_source'] as $source => $count)
                        @if($count > 0)
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <span class="text-sm font-medium text-slate-800">{{ $source }}</span>
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $sourceColors[$source] ?? 'bg-slate-100 text-slate-700' }}">{{ number_format($count) }}</span>
                            </div>
                        @endif
                    @endforeach
                    @if(! $hasParasiteSource)
                        <p class="text-sm text-slate-500">No parasite samples yet.</p>
                    @endif
                </div>
            </div>

            @php $hasNucleic = array_sum($metrics['content']['nucleic_by_source']) > 0; @endphp
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                <h3 class="mb-5 flex items-center gap-3 text-base font-semibold text-slate-900">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-rose-100 text-rose-700"><i class="fas fa-dna"></i></span>
                    Nucleic acids by source
                </h3>
                <div class="space-y-2">
                    @foreach ($metrics['content']['nucleic_by_source'] as $source => $count)
                        @if($count > 0)
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <span class="text-sm font-medium text-slate-800">{{ $source }}</span>
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $sourceColors[$source] ?? 'bg-slate-100 text-slate-700' }}">{{ number_format($count) }}</span>
                            </div>
                        @endif
                    @endforeach
                    @if(! $hasNucleic)
                        <p class="text-sm text-slate-500">No nucleic acids yet.</p>
                    @endif
                </div>
            </div>

            @php $hasCultures = array_sum($metrics['content']['cultures_by_source']) > 0; @endphp
            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                <h3 class="mb-5 flex items-center gap-3 text-base font-semibold text-slate-900">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700"><i class="fas fa-vial"></i></span>
                    Cultures by source
                </h3>
                <div class="space-y-2">
                    @foreach ($metrics['content']['cultures_by_source'] as $source => $count)
                        @if($count > 0)
                            <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                                <span class="text-sm font-medium text-slate-800">{{ $source }}</span>
                                <span class="rounded-full px-3 py-1 text-sm font-bold {{ $sourceColors[$source] ?? 'bg-slate-100 text-slate-700' }}">{{ number_format($count) }}</span>
                            </div>
                        @endif
                    @endforeach
                    @if(! $hasCultures)
                        <p class="text-sm text-slate-500">No cultures yet.</p>
                    @endif
                </div>
            </div>

            <div class="rounded-2xl border border-slate-100 bg-white p-6 shadow-sm ring-1 ring-slate-900/5">
                <h3 class="mb-5 flex items-center gap-3 text-base font-semibold text-slate-900">
                    <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700"><i class="fas fa-vials"></i></span>
                    Experiments by sample type
                </h3>
                <div class="space-y-2">
                    @forelse ($metrics['content']['experiments_by_content'] as $label => $count)
                        <div class="flex items-center justify-between rounded-xl border border-slate-100 bg-slate-50 px-4 py-3">
                            <span class="min-w-0 truncate text-sm font-medium text-slate-800">{{ $label }}</span>
                            <span class="ml-3 shrink-0 rounded-full bg-emerald-100 px-3 py-1 text-sm font-bold text-emerald-800">{{ number_format($count) }}</span>
                        </div>
                    @empty
                        <p class="text-sm text-slate-500">No experiments yet.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function subProjectCreateModal({ projectId, endpoint }) {
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
                    body: JSON.stringify({ project_id: projectId, code: value })
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

function markProjectComplete(projectId, projectCode, startDate) {
    const today = new Date().toISOString().slice(0, 10);

    Swal.fire({
        title: `Mark project ${projectCode} complete`,
        html: `
            <div class="text-left">
                <label class="block text-sm font-medium text-gray-700 mb-2">Official end date</label>
                <input id="official_end_date" type="date" class="swal2-input" value="${today}">
                <div class="text-xs text-gray-500 mt-2">This will set the official end date and mark the project as completed.</div>
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
            return { date_end: dateEnd };
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
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to mark project complete.' });
                return;
            }
            Swal.fire({ icon: 'success', title: 'Success', text: data.message || 'Project marked complete.', timer: 1500, showConfirmButton: false })
                .then(() => window.location.reload());
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to mark project complete.' }));
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
            return { date_end: dateEnd };
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
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to mark sub-project complete.' });
                return;
            }
            Swal.fire({ icon: 'success', title: 'Success', text: data.message || 'Sub-project marked complete.', timer: 1500, showConfirmButton: false })
                .then(() => window.location.reload());
        })
        .catch(() => Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to mark sub-project complete.' }));
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
