@php
    $accents = [
        'green' => [
            'head' => 'bg-green-50',
            'hover' => 'hover:bg-green-50',
            'title' => 'hover:text-green-600',
            'badge' => 'bg-green-100 text-green-800',
            'edit' => 'text-green-600 hover:text-green-800',
            'link' => 'hover:text-green-600',
            'ring' => 'focus:ring-green-100 focus:border-green-300',
        ],
        'amber' => [
            'head' => 'bg-amber-50',
            'hover' => 'hover:bg-amber-50',
            'title' => 'hover:text-amber-700',
            'badge' => 'bg-amber-100 text-amber-800',
            'edit' => 'text-amber-700 hover:text-amber-900',
            'link' => 'hover:text-amber-700',
            'ring' => 'focus:ring-amber-100 focus:border-amber-300',
        ],
        'slate' => [
            'head' => 'bg-slate-50',
            'hover' => 'hover:bg-slate-50',
            'title' => 'hover:text-slate-600',
            'badge' => 'bg-slate-100 text-slate-700',
            'edit' => 'text-slate-600 hover:text-slate-800',
            'link' => 'hover:text-slate-600',
            'ring' => 'focus:ring-slate-100 focus:border-slate-300',
        ],
        'indigo' => [
            'head' => 'bg-indigo-50',
            'hover' => 'hover:bg-indigo-50',
            'title' => 'hover:text-indigo-600',
            'badge' => 'bg-indigo-100 text-indigo-800',
            'edit' => 'text-indigo-600 hover:text-indigo-800',
            'link' => 'hover:text-indigo-600',
            'ring' => 'focus:ring-indigo-100 focus:border-indigo-300',
        ],
    ];
    $a = $accents[$accent] ?? $accents['slate'];
    $showActions = $showActions ?? false;
    $dimRows = $dimRows ?? false;
    $colspan = $showActions ? 9 : 8;
    $filterEndFrom = $filterEndFrom ?? 'officialEndFrom';
    $filterEndTo = $filterEndTo ?? 'officialEndTo';
    $radioColor = match ($accent) {
        'amber' => 'text-amber-600 focus:ring-amber-500',
        'green' => 'text-green-600 focus:ring-green-500',
        'indigo' => 'text-indigo-600 focus:ring-indigo-500',
        default => 'text-slate-600 focus:ring-slate-500',
    };
    $filterInputClass = 'w-full px-2 py-1.5 text-xs border border-gray-300 rounded-md focus:outline-none focus:ring-2 '.$a['ring'].' bg-white';
@endphp

<div class="border border-gray-200 rounded-xl shadow-sm bg-white overflow-hidden">
    <div class="overflow-x-auto">
        <table class="index-data-table min-w-full divide-y divide-gray-200">
            <thead class="{{ $a['head'] }}">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><x-sort-button field="code" :active="$sortField" :direction="$sortDirection">Code</x-sort-button></th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><x-sort-button field="type" :active="$sortField" :direction="$sortDirection">Type</x-sort-button></th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><x-sort-button field="title" :active="$sortField" :direction="$sortDirection">Title</x-sort-button></th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><x-sort-button field="date_started" :active="$sortField" :direction="$sortDirection">Start Date</x-sort-button></th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><x-sort-button :field="$endSortKey" :active="$sortField" :direction="$sortDirection">{{ $endLabel }}</x-sort-button></th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Funding</th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"><x-sort-button field="role" :active="$sortField" :direction="$sortDirection">Your Role</x-sort-button></th>
                    <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Collaborators</th>
                    @if ($showActions)
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    @endif
                </tr>
                <tr class="{{ $a['head'] }}">
                    <th class="px-6 pb-3 align-top">
                        <input type="text" wire:model.live.debounce.300ms="code" placeholder="Filter code" class="{{ $filterInputClass }}">
                    </th>
                    <th class="px-6 pb-3 align-top">
                        <input type="text" wire:model.live.debounce.300ms="type" placeholder="Filter type" class="{{ $filterInputClass }}">
                    </th>
                    <th class="px-6 pb-3 align-top">
                        <input type="text" wire:model.live.debounce.300ms="title" placeholder="Filter title" class="{{ $filterInputClass }}">
                    </th>
                    <th class="px-6 pb-3 align-top">
                        <div class="flex flex-col gap-1">
                            <input type="date" wire:model.live="startDateFrom" class="{{ $filterInputClass }}">
                            <input type="date" wire:model.live="startDateTo" class="{{ $filterInputClass }}">
                        </div>
                    </th>
                    <th class="px-6 pb-3 align-top">
                        <div class="flex flex-col gap-1">
                            <input type="date" wire:model.live="{{ $filterEndFrom }}" class="{{ $filterInputClass }}">
                            <input type="date" wire:model.live="{{ $filterEndTo }}" class="{{ $filterInputClass }}">
                        </div>
                    </th>
                    <th class="px-6 pb-3 align-top">
                        <input type="text" wire:model.live.debounce.300ms="funding" placeholder="Filter funding" class="{{ $filterInputClass }}">
                    </th>
                    <th class="px-6 pb-3 align-top">
                        <input type="text" wire:model.live.debounce.300ms="role" placeholder="Filter role" class="{{ $filterInputClass }}">
                    </th>
                    <th class="px-6 pb-3 align-top">
                        <input type="text" wire:model.live.debounce.300ms="collaborator" placeholder="Filter collaborator" class="{{ $filterInputClass }}">
                    </th>
                    @if ($showActions)
                        <th class="px-6 pb-3"></th>
                    @endif
                </tr>
            </thead>
            @foreach ($projects as $project)
                @php
                    $canUpdate = auth()->user()?->can('update', $project);
                    $canDeleteProject = ($project->pivot->permission ?? null) === 'admin';
                    $fundings = $project->fundings ?? collect();
                    $fundingsCount = $fundings->count();
                    $collaborators = $project->people->filter(fn ($c) => $c->id !== $person->id);
                    $collaboratorsCount = $collaborators->count();
                    $isSelected = session('selected_project_id') == $project->id;
                @endphp
                <tbody class="divide-y divide-gray-200 {{ $isSelected ? 'bg-green-50/60' : 'bg-white' }}" x-data="{ openSubProjects: false }">
                    <tr class="{{ $isSelected ? 'bg-green-50/60' : $a['hover'] }} transition-colors duration-200 {{ $dimRows && ! $isSelected ? 'opacity-80' : '' }}">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 align-top {{ $isSelected ? 'border-l-4 border-green-500' : 'border-l-4 border-transparent' }}">
                            <div class="flex items-center">
                                <input type="radio" name="{{ $radioPrefix }}selected_project" value="{{ $project->id }}"
                                    id="{{ $radioPrefix }}{{ $project->id }}"
                                    class="h-4 w-4 {{ $radioColor }} border-gray-300"
                                    {{ $isSelected ? 'checked' : '' }}
                                    onchange="submitProjectSelection(this)">
                                <a href="{{ route('projects.profile', $project->code) }}"
                                    class="ml-3 font-semibold text-blue-600 hover:text-blue-800">
                                    {{ $project->code }}
                                </a>
                                @if ($isSelected)
                                    <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-green-600 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-white">
                                        <i class="fas fa-check text-[9px]"></i> Current
                                    </span>
                                @endif
                            </div>
                            @if ($project->alias_code)
                                <p class="mt-0.5 ml-7 text-xs text-gray-500">Alias: {{ $project->alias_code }}</p>
                            @endif
                            <button type="button"
                                class="mt-2 ml-7 text-xs font-medium text-indigo-600 hover:text-indigo-800"
                                x-on:click="openSubProjects = !openSubProjects">
                                <i class="fas mr-1" x-bind:class="openSubProjects ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                Sub-projects ({{ $project->subProjects->count() }})
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $project->type ?: '—' }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            <button type="button"
                                onclick="showFullTitle('{{ $project->code }}', '{{ addslashes($project->title) }}')"
                                class="max-w-[180px] truncate {{ $a['title'] }} focus:outline-none transition-colors duration-200">
                                {{ $project->title }}
                            </button>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $project->date_started ?: 'N/A' }}</td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $project->{$endField} ?: $endFallback }}</td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            @if ($fundingsCount > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($fundings->take(2) as $f)
                                        <a href="{{ route('fundings.profile', $f) }}"
                                            class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors">
                                            {{ $f->source ?? 'Funding' }}
                                        </a>
                                    @endforeach
                                    @if ($fundingsCount > 2)
                                        <button type="button" wire:click="openFundingModal({{ $project->id }})"
                                            class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-50 text-purple-800 hover:bg-purple-100 border border-purple-200 transition-colors">
                                            +{{ $fundingsCount - 2 }}
                                        </button>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center space-x-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $a['badge'] }}">
                                    {{ $project->pivot->role }}
                                </span>
                                @if ($canUpdate)
                                    <a href="{{ route('projects.edit', ['project' => $project->id, 'section' => 'general']) }}"
                                        class="{{ $a['edit'] }} transition-colors duration-200">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-500">
                            @if ($collaboratorsCount > 0)
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($collaborators->take(3) as $collaborator)
                                        <div class="flex items-center text-xs">
                                            <x-people-logo :person="$collaborator" width="30" />
                                            <a href="{{ route('profile.show', $collaborator->id) }}"
                                                class="ml-2 {{ $a['link'] }} transition-colors duration-200">
                                                {{ $collaborator->first_name }} {{ $collaborator->last_name }}
                                            </a>
                                            <span class="ml-2 whitespace-nowrap px-2 py-1 text-xs rounded-full {{ $collaborator->pivot->role === $project->pivot->role ? $a['badge'] : 'bg-blue-100 text-blue-800' }}">
                                                {{ $collaborator->pivot->role }}
                                            </span>
                                        </div>
                                    @endforeach
                                    @if ($collaboratorsCount > 3)
                                        <button type="button" wire:click="openCollaboratorModal({{ $project->id }})"
                                            class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-50 text-yellow-800 hover:bg-yellow-100 border border-yellow-200 transition-colors">
                                            +{{ $collaboratorsCount - 3 }}
                                        </button>
                                    @endif
                                </div>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        @if ($showActions)
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <div class="flex flex-wrap items-center gap-2">
                                    @if ($canUpdate)
                                        <button type="button"
                                            onclick="markProjectComplete({{ $project->id }}, '{{ $project->code }}', '{{ $project->date_started ?? '' }}')"
                                            class="inline-flex items-center gap-2 px-3 py-2 bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold rounded-lg transition-colors duration-200">
                                            <i class="fas fa-check"></i>
                                            Mark complete
                                        </button>
                                    @endif
                                    @if ($canDeleteProject)
                                        <button type="button"
                                            onclick="deleteProject({{ $project->id }}, @js($project->code))"
                                            class="inline-flex items-center gap-2 px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition-colors duration-200">
                                            <i class="fas fa-trash"></i>
                                            Delete
                                        </button>
                                    @endif
                                    @if (! $canUpdate && ! $canDeleteProject)
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </div>
                            </td>
                        @endif
                    </tr>
                    <tr x-show="openSubProjects" x-collapse class="bg-indigo-50/60">
                        <td colspan="{{ $colspan }}" class="px-6 py-4">
                            @include('livewire.partials.sub-projects-table', ['project' => $project, 'canManage' => ($project->pivot->permission ?? null) === 'admin'])
                        </td>
                    </tr>
                </tbody>
            @endforeach
            @if ($projects->isEmpty())
                <tbody>
                    <tr>
                        <td colspan="{{ $colspan }}" class="px-6 py-12 text-center">
                            <div class="mx-auto mb-3 flex h-11 w-11 items-center justify-center rounded-full bg-gray-100">
                                <i class="fas fa-folder-open text-lg text-gray-400"></i>
                            </div>
                            <p class="text-sm font-medium text-gray-700">{{ $emptyMessage ?? 'No projects match your filters.' }}</p>
                            <p class="mt-1 text-sm text-gray-500">Try adjusting the filters above.</p>
                        </td>
                    </tr>
                </tbody>
            @endif
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $projects->links() }}
</div>
