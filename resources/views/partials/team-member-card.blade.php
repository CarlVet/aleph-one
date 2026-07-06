@props([
    'member',
    'project',
    'canEditTeam' => false,
    'modulePermissionOptions' => [],
    'subProjects' => collect(),
    'permissionStyles' => [],
])

@php
    $permission = strtolower((string) ($member->pivot->permission ?? 'viewer'));
    $permissionStyle = $permissionStyles[$permission] ?? [
        'label' => ucfirst($permission),
        'badge' => 'from-slate-500 to-gray-600',
        'soft' => 'from-slate-50 via-gray-50 to-zinc-50',
        'ring' => 'border-slate-200',
        'icon_wrap' => 'bg-slate-100 text-slate-600',
        'icon' => 'fas fa-user',
    ];
    $memberModuleMatrix = \App\Support\ProjectPermission::matrixForMembership(
        $member->pivot->permission ?? 'viewer',
        $member->pivot->module_permissions ?? null
    );
    $memberSubProjectIds = $subProjects
        ->filter(fn ($subProject) => $subProject->people->contains('id', $member->id))
        ->pluck('id')
        ->all();
    $enabledViews = collect($memberModuleMatrix)->filter(fn ($access) => ($access['view'] ?? false))->count();
    $enabledEdits = collect($memberModuleMatrix)->filter(fn ($access) => ($access['edit'] ?? false))->count();
    $canEditMember = $canEditTeam && $member->id !== (Auth::user()->people->id ?? null);
@endphp

<div
    class="group relative flex flex-col items-center rounded-[2rem] border {{ $permissionStyle['ring'] }} bg-gradient-to-b {{ $permissionStyle['soft'] }} p-6 text-center shadow-sm transition-all duration-300 hover:-translate-y-1 hover:shadow-xl"
    x-data="{ editing: false }"
    x-show="typeof matchesMember === 'function' ? matchesMember(@js($member->first_name), @js($member->last_name)) : true"
    x-transition.opacity>
    @if ($canEditMember)
        <div class="absolute right-3 top-3 flex gap-1.5 opacity-0 transition-opacity group-hover:opacity-100">
            <button type="button" x-show="!editing" @click="editing = true"
                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/80 bg-white/90 text-slate-600 shadow-sm transition hover:bg-white hover:text-blue-600"
                title="Edit member">
                <i class="fas fa-pen text-xs"></i>
            </button>
            <button type="button" x-show="editing" @click="editing = false"
                class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-white/80 bg-white/90 text-slate-500 shadow-sm transition hover:bg-white"
                title="Close editor">
                <i class="fas fa-times text-xs"></i>
            </button>
        </div>
    @endif

    <div class="relative">
        <div class="rounded-full bg-gradient-to-br {{ $permissionStyle['badge'] }} p-1 shadow-lg shadow-slate-300/40">
            <div class="rounded-full bg-white p-1">
                <x-people-logo :person="$member" width="96" class="!rounded-full" />
            </div>
        </div>
        <span class="absolute -bottom-1 left-1/2 -translate-x-1/2 whitespace-nowrap rounded-full bg-gradient-to-r {{ $permissionStyle['badge'] }} px-3 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white shadow-sm">
            {{ $permission }}
        </span>
    </div>

    <h3 class="mt-5 text-base font-bold text-slate-900">
        {{ trim(($member->title ? $member->title.' ' : '').$member->first_name.' '.$member->last_name) }}
    </h3>

    <p class="mt-1 text-sm font-medium text-slate-600">
        <template x-if="!editing">
            <span>{{ $member->pivot->role ?? 'Team member' }}</span>
        </template>
        @if ($canEditMember)
            <template x-if="editing">
                <form method="POST" action="{{ route('team.updateRole', $member->id) }}" class="inline">
                    @csrf
                    <input list="role_options_{{ $member->id }}" name="edit_role"
                        class="mx-auto w-full max-w-[220px] rounded-xl border-slate-200 text-xs"
                        value="{{ $member->pivot->role }}" onchange="this.form.submit()" />
                    <datalist id="role_options_{{ $member->id }}">
                        @foreach($project->people->pluck('pivot.role')->unique()->filter()->all() as $role)
                            <option value="{{ $role }}">{{ $role }}</option>
                        @endforeach
                    </datalist>
                </form>
            </template>
        @endif
    </p>

    <div class="mt-3 flex flex-wrap items-center justify-center gap-2 text-xs text-slate-500">
        @if ($member->pivot && $member->pivot->date_joined)
            <span class="inline-flex items-center gap-1 rounded-full bg-white/80 px-2.5 py-1 ring-1 ring-slate-200/80">
                <i class="fas fa-calendar-day text-[10px] text-slate-400"></i>
                <template x-if="!editing">
                    <span>Joined {{ \Carbon\Carbon::parse($member->pivot->date_joined)->format('M Y') }}</span>
                </template>
                @if ($canEditMember)
                    <template x-if="editing">
                        <form method="POST" action="{{ route('team.updateDateJoined', $member->id) }}" class="inline">
                            @csrf
                            <input type="date" name="date_joined"
                                value="{{ \Carbon\Carbon::parse($member->pivot->date_joined)->format('Y-m-d') }}"
                                class="rounded border-slate-200 text-[10px]" onchange="this.form.submit()" />
                        </form>
                    </template>
                @endif
            </span>
        @endif
        @if ($member->users)
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 font-semibold text-emerald-700 ring-1 ring-emerald-100">
                <i class="fas fa-circle text-[6px]"></i>
                Active
            </span>
        @endif
    </div>

    <div class="mt-4 w-full space-y-2 rounded-2xl border border-white/70 bg-white/70 p-3 text-left text-xs text-slate-600 backdrop-blur-sm">
        @if ($member->email)
            <div class="flex items-center gap-2 truncate">
                <i class="fas fa-envelope w-4 text-center text-slate-400"></i>
                <span class="truncate">{{ $member->email }}</span>
            </div>
        @endif
        @if ($member->organizations)
            <div class="flex items-center gap-2 truncate">
                <i class="fas fa-building w-4 text-center text-slate-400"></i>
                <span class="truncate">{{ $member->organizations->name ?? 'Not specified' }}</span>
            </div>
        @endif
        @if (in_array($permission, ['viewer', 'editor'], true))
            <div class="flex items-center gap-2">
                <i class="fas fa-shield-halved w-4 text-center text-slate-400"></i>
                <span>View {{ $enabledViews }}/{{ count($modulePermissionOptions) }} · Edit {{ $enabledEdits }}/{{ count($modulePermissionOptions) }}</span>
            </div>
        @endif
        @if ($subProjects->count() > 0)
            @php
                $memberSubProjectLabels = $subProjects
                    ->filter(fn ($subProject) => in_array($subProject->id, $memberSubProjectIds, true))
                    ->map(fn ($subProject) => $subProject->code)
                    ->values();
            @endphp
            <div class="flex items-start gap-2">
                <i class="fas fa-flag w-4 text-center text-slate-400 mt-0.5"></i>
                <span>{{ $memberSubProjectLabels->isNotEmpty() ? $memberSubProjectLabels->join(', ') : 'All sub-projects' }}</span>
            </div>
        @endif
    </div>

    @if ($canEditMember)
        <div x-show="editing" x-collapse class="mt-4 w-full space-y-3 rounded-2xl border border-blue-100 bg-white p-4 text-left shadow-inner">
            <form method="POST" action="{{ route('team.updatePermission', $member->id) }}">
                @csrf
                <label class="mb-1 block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Permission level</label>
                <select name="permission" class="w-full rounded-xl border-slate-200 text-sm" onchange="this.form.submit()">
                    <option value="viewer" {{ $permission === 'viewer' ? 'selected' : '' }}>Viewer</option>
                    <option value="editor" {{ $permission === 'editor' ? 'selected' : '' }}>Editor</option>
                    <option value="admin" {{ $permission === 'admin' ? 'selected' : '' }}>Admin</option>
                </select>
            </form>

            @if (in_array($permission, ['viewer', 'editor'], true))
                <form method="POST" action="{{ route('team.updateModulePermissions', $member->id) }}" class="space-y-3">
                    @csrf
                    <x-team.module-permissions-matrix
                        :module-permission-options="$modulePermissionOptions"
                        :module-matrix="$memberModuleMatrix"
                        name-prefix="module_permissions"
                        :show-save-button="true" />
                </form>
            @endif

            @if ($subProjects->count() > 0)
                <form method="POST" action="{{ route('team.updateSubProjects', $member->id) }}" class="space-y-2">
                    @csrf
                    <label class="block text-[11px] font-semibold uppercase tracking-wide text-slate-500">Sub-project assignments</label>
                    <div class="grid grid-cols-1 gap-1.5">
                        @foreach($subProjects as $subProject)
                            <label class="inline-flex items-center gap-2 rounded-lg border border-slate-100 bg-slate-50 px-2 py-1.5">
                                <input type="checkbox" name="sub_project_ids[]" value="{{ $subProject->id }}"
                                    {{ in_array($subProject->id, $memberSubProjectIds, true) ? 'checked' : '' }}
                                    class="rounded border-slate-300">
                                <span>{{ $subProject->code }} — {{ $subProject->name }}</span>
                            </label>
                        @endforeach
                    </div>
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 transition hover:bg-indigo-100">
                        <i class="fas fa-save text-[10px]"></i>
                        Save sub-projects
                    </button>
                </form>
            @endif

            @if ($member->pivot->permission !== 'admin')
                <form method="POST" action="{{ route('team.detach', $member->id) }}"
                    onsubmit="return confirm('Remove this member from the project?');">
                    @csrf
                    <button type="submit"
                        class="w-full rounded-xl border border-rose-200 bg-rose-50 px-3 py-2 text-xs font-semibold text-rose-700 transition hover:bg-rose-100">
                        <i class="fas fa-user-minus mr-1"></i>
                        Remove from project
                    </button>
                </form>
            @endif
        </div>
    @endif

    <a href="{{ route('profile.show', $member->id) }}"
        class="mt-4 inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:bg-slate-800">
        <i class="fas fa-user text-xs"></i>
        View profile
    </a>
</div>
