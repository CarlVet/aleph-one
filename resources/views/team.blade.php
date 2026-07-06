<x-layout>
    <x-slot:heading>
        Project Team
    </x-slot:heading>

    <div class="relative overflow-hidden bg-gradient-to-br from-slate-50 via-blue-50/30 to-indigo-50/40">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(ellipse_at_top_right,_var(--tw-gradient-stops))] from-blue-100/40 via-transparent to-transparent"></div>
        <div class="pointer-events-none absolute -left-32 top-40 h-72 w-72 rounded-full bg-indigo-200/20 blur-3xl"></div>
        <div class="pointer-events-none absolute -right-24 bottom-20 h-64 w-64 rounded-full bg-sky-200/25 blur-3xl"></div>

        <div class="relative py-10">
            <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
                @if ($project->people->isEmpty())
                    <div class="rounded-2xl border border-dashed border-slate-200 bg-white/60 py-16 text-center shadow-sm">
                        <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-full bg-gradient-to-br from-slate-100 to-slate-200 shadow-inner">
                            <i class="fas fa-users text-3xl text-slate-400"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-900">No team members yet</h3>
                        <p class="mx-auto mt-2 max-w-sm text-sm text-slate-500">
                            Add collaborators to manage permissions, module access, and sub-project assignments.
                        </p>
                        @if ($canEditTeam)
                            <button type="button" id="open_team_collaborator_modal_btn"
                                class="mt-6 inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:scale-[1.02]">
                                <i class="fas fa-user-plus"></i>
                                Add first member
                            </button>
                        @endif
                    </div>
                @else
                    <div x-data="{
                        groupMode: 'permission',
                        activeGroup: 'all',
                        firstNameFilter: '',
                        surnameFilter: '',
                        matchesMember(firstName, lastName) {
                            const first = this.firstNameFilter.trim().toLowerCase();
                            const surname = this.surnameFilter.trim().toLowerCase();
                            const firstMatch = first === '' || String(firstName ?? '').toLowerCase().includes(first);
                            const surnameMatch = surname === '' || String(lastName ?? '').toLowerCase().includes(surname);
                            return firstMatch && surnameMatch;
                        },
                        get hasNameFilter() {
                            return this.firstNameFilter.trim() !== '' || this.surnameFilter.trim() !== '';
                        },
                    }" class="space-y-7">
                        <div class="relative overflow-hidden rounded-3xl border border-white/60 bg-white/75 p-5 shadow-xl shadow-slate-200/40 backdrop-blur-md sm:p-6">
                            <div class="pointer-events-none absolute inset-0 bg-gradient-to-br from-white/80 via-blue-50/30 to-indigo-50/20"></div>
                            <div class="relative flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
                                <div class="max-w-xl">
                                    <div class="inline-flex items-center gap-2 rounded-full border border-blue-100 bg-blue-50/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.18em] text-blue-700">
                                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                                        Project team
                                    </div>
                                    <h2 class="mt-3 text-2xl font-bold tracking-tight text-slate-900 sm:text-3xl">Team directory</h2>
                                    <p class="mt-2 text-sm leading-relaxed text-slate-600">
                                        Browse members by permission or job role, manage module access, and assign sub-projects.
                                    </p>
                                </div>
                                <div class="flex flex-wrap items-center gap-3 lg:justify-end">
                                    <div class="flex items-center gap-2 rounded-2xl border border-slate-200/80 bg-white/90 px-4 py-2.5 shadow-sm">
                                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                                            <i class="fas fa-users text-sm"></i>
                                        </span>
                                        <div>
                                            <div class="text-[11px] font-medium uppercase tracking-wide text-slate-400">Roster</div>
                                            <div class="text-sm font-bold text-slate-800">{{ $project->people->count() }} members</div>
                                        </div>
                                    </div>
                                    @if ($canEditTeam)
                                        <button type="button" id="open_team_collaborator_modal_btn"
                                            class="inline-flex items-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 via-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:scale-[1.02] hover:from-blue-700 hover:to-indigo-700">
                                            <i class="fas fa-user-plus"></i>
                                            <span>New member</span>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <div class="relative mt-5 flex flex-col gap-3">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="text-[11px] font-semibold uppercase tracking-wide text-slate-400">Group by</span>
                                    <button type="button" @click="groupMode = 'permission'; activeGroup = 'all'"
                                        :class="groupMode === 'permission' ? 'bg-slate-900 text-white shadow-md' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                                        class="rounded-xl px-3.5 py-2 text-xs font-semibold transition">
                                        Permission
                                    </button>
                                    <button type="button" @click="groupMode = 'role'; activeGroup = 'all'"
                                        :class="groupMode === 'role' ? 'bg-slate-900 text-white shadow-md' : 'bg-white text-slate-600 ring-1 ring-slate-200 hover:bg-slate-50'"
                                        class="rounded-xl px-3.5 py-2 text-xs font-semibold transition">
                                        Role
                                    </button>
                                </div>
                            </div>

                            <div class="relative mt-4 -mx-1 overflow-x-auto pb-1">
                                <div class="flex min-w-max gap-2 px-1" x-show="groupMode === 'permission'">
                                    <button type="button" @click="activeGroup = 'all'"
                                        :class="activeGroup === 'all' ? 'border-transparent bg-slate-900 text-white shadow-lg shadow-slate-900/20' : 'border-slate-200/90 bg-white/90 text-slate-600 hover:bg-white hover:shadow-md'"
                                        class="inline-flex items-center gap-2.5 rounded-2xl border px-3.5 py-2.5 text-xs font-semibold transition">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl" :class="activeGroup === 'all' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-600'">
                                            <i class="fas fa-users"></i>
                                        </span>
                                        <span class="flex flex-col text-left"><span>All members</span><span class="text-[10px] font-normal opacity-75">{{ $project->people->count() }} total</span></span>
                                    </button>
                                    @foreach ($permissionFolders as $folder)
                                        <button type="button" @click="activeGroup = @js($folder['key'])"
                                            :class="activeGroup === @js($folder['key']) ? 'border-transparent bg-gradient-to-r {{ $folder['style']['badge'] }} text-white shadow-lg' : 'border-slate-200/90 bg-white/90 text-slate-600 hover:bg-white hover:shadow-md'"
                                            class="inline-flex items-center gap-2.5 rounded-2xl border px-3.5 py-2.5 text-xs font-semibold transition">
                                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl {{ $folder['style']['icon_wrap'] }}" :class="activeGroup === @js($folder['key']) ? '!bg-white/20 !text-white' : ''">
                                                <i class="{{ $folder['style']['icon'] }} text-xs"></i>
                                            </span>
                                            <span class="flex flex-col text-left"><span>{{ $folder['label'] }}</span><span class="text-[10px] font-normal opacity-75">{{ $folder['count'] }} members</span></span>
                                        </button>
                                    @endforeach
                                </div>
                                <div class="flex min-w-max gap-2 px-1" x-show="groupMode === 'role'">
                                    <button type="button" @click="activeGroup = 'all'"
                                        :class="activeGroup === 'all' ? 'border-transparent bg-slate-900 text-white shadow-lg shadow-slate-900/20' : 'border-slate-200/90 bg-white/90 text-slate-600 hover:bg-white hover:shadow-md'"
                                        class="inline-flex items-center gap-2.5 rounded-2xl border px-3.5 py-2.5 text-xs font-semibold transition">
                                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl" :class="activeGroup === 'all' ? 'bg-white/15 text-white' : 'bg-slate-100 text-slate-600'">
                                            <i class="fas fa-users"></i>
                                        </span>
                                        <span class="flex flex-col text-left"><span>All members</span><span class="text-[10px] font-normal opacity-75">{{ $project->people->count() }} total</span></span>
                                    </button>
                                    @foreach ($roleFolders as $folder)
                                        <button type="button" @click="activeGroup = @js($folder['key'])"
                                            :class="activeGroup === @js($folder['key']) ? 'border-transparent bg-gradient-to-r {{ $folder['style']['badge'] }} text-white shadow-lg' : 'border-slate-200/90 bg-white/90 text-slate-600 hover:bg-white hover:shadow-md'"
                                            class="inline-flex max-w-[240px] items-center gap-2.5 rounded-2xl border px-3.5 py-2.5 text-xs font-semibold transition">
                                            <span class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl {{ $folder['style']['icon_wrap'] }}" :class="activeGroup === @js($folder['key']) ? '!bg-white/20 !text-white' : ''">
                                                <i class="{{ $folder['style']['icon'] }} text-xs"></i>
                                            </span>
                                            <span class="flex min-w-0 flex-col text-left"><span class="truncate">{{ $folder['label'] }}</span><span class="text-[10px] font-normal opacity-75">{{ $folder['count'] }} members</span></span>
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            <div class="relative mt-4 rounded-2xl border border-slate-200/80 bg-white/90 p-3 shadow-sm">
                                <div class="mb-2 flex items-center gap-2 text-[11px] font-semibold uppercase tracking-wide text-slate-400">
                                    <i class="fas fa-magnifying-glass text-[10px]"></i>
                                    Search members
                                </div>
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-medium text-slate-600">First name</span>
                                        <input type="search" x-model.debounce.200ms="firstNameFilter"
                                            placeholder="Filter by first name…"
                                            class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </label>
                                    <label class="block">
                                        <span class="mb-1 block text-xs font-medium text-slate-600">Surname</span>
                                        <input type="search" x-model.debounce.200ms="surnameFilter"
                                            placeholder="Filter by surname…"
                                            class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                    </label>
                                </div>
                                <p x-show="hasNameFilter" x-cloak class="mt-2 text-xs text-slate-500">
                                    Showing members matching both filters (leave a field empty to ignore it).
                                </p>
                            </div>
                        </div>

                        <div x-show="activeGroup === 'all'" x-transition.opacity class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                            @foreach ($allMembers as $member)
                                @include('partials.team-member-card', [
                                    'member' => $member,
                                    'project' => $project,
                                    'canEditTeam' => $canEditTeam,
                                    'modulePermissionOptions' => $modulePermissionOptions,
                                    'subProjects' => $subProjects ?? collect(),
                                    'permissionStyles' => $permissionStyles,
                                ])
                            @endforeach
                        </div>

                        @foreach ($permissionFolders as $folder)
                            <div x-show="groupMode === 'permission' && activeGroup === @js($folder['key'])" x-transition.opacity>
                                <div class="mb-5 overflow-hidden rounded-3xl border {{ $folder['style']['ring'] }} bg-gradient-to-r {{ $folder['style']['soft'] }} p-5 shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl {{ $folder['style']['icon_wrap'] }} shadow-sm">
                                            <i class="{{ $folder['style']['icon'] }}"></i>
                                        </span>
                                        <div>
                                            <h3 class="text-xl font-bold text-slate-900">{{ $folder['label'] }}</h3>
                                            <p class="text-sm text-slate-600">{{ $folder['count'] }} member{{ $folder['count'] === 1 ? '' : 's' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                                    @foreach ($folder['members'] as $member)
                                        @include('partials.team-member-card', [
                                            'member' => $member,
                                            'project' => $project,
                                            'canEditTeam' => $canEditTeam,
                                            'modulePermissionOptions' => $modulePermissionOptions,
                                            'subProjects' => $subProjects ?? collect(),
                                            'permissionStyles' => $permissionStyles,
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach

                        @foreach ($roleFolders as $folder)
                            <div x-show="groupMode === 'role' && activeGroup === @js($folder['key'])" x-transition.opacity>
                                <div class="mb-5 overflow-hidden rounded-3xl border {{ $folder['style']['ring'] }} bg-gradient-to-r {{ $folder['style']['soft'] }} p-5 shadow-sm">
                                    <div class="flex items-center gap-3">
                                        <span class="inline-flex h-12 w-12 items-center justify-center rounded-2xl {{ $folder['style']['icon_wrap'] }} shadow-sm">
                                            <i class="{{ $folder['style']['icon'] }}"></i>
                                        </span>
                                        <div>
                                            <h3 class="text-xl font-bold text-slate-900">{{ $folder['label'] }}</h3>
                                            <p class="text-sm text-slate-600">{{ $folder['count'] }} member{{ $folder['count'] === 1 ? '' : 's' }}</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                                    @foreach ($folder['members'] as $member)
                                        @include('partials.team-member-card', [
                                            'member' => $member,
                                            'project' => $project,
                                            'canEditTeam' => $canEditTeam,
                                            'modulePermissionOptions' => $modulePermissionOptions,
                                            'subProjects' => $subProjects ?? collect(),
                                            'permissionStyles' => $permissionStyles,
                                        ])
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    @if ($canEditTeam)
        <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm" id="team_collaborator_modal">
            <div class="relative max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
                <div class="sticky top-0 z-10 border-b border-slate-100 bg-gradient-to-r from-blue-50 via-indigo-50 to-slate-50 px-6 py-5">
                    <button id="close_team_collaborator_modal_btn" type="button"
                        class="absolute right-4 top-4 inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 transition hover:bg-white/80 hover:text-slate-600">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="flex items-center gap-3 pr-10">
                        <span class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-blue-600 to-indigo-600 text-white shadow-md shadow-blue-500/30">
                            <i class="fas fa-user-plus"></i>
                        </span>
                        <div>
                            <h2 class="text-lg font-bold text-slate-900">Add team member</h2>
                            <p class="text-xs text-slate-500">Set permission, module access, and sub-projects</p>
                        </div>
                    </div>
                </div>
                <form method="POST" action="{{ route('team.store') }}" class="p-6">
                    @csrf
                    <div class="space-y-6">
                        <section class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4">
                            <h3 class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Personal details</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-forms.field label="Title*" name="title">
                                    <x-forms.select-input id="title" name="title" class="w-full rounded-xl border-slate-200 text-sm shadow-sm">
                                        <option value="">Select</option>
                                        <option value="Mr.">Mr.</option>
                                        <option value="Mrs.">Mrs.</option>
                                        <option value="Ms.">Ms.</option>
                                        <option value="Dr.">Dr.</option>
                                        <option value="Prof.">Prof.</option>
                                    </x-forms.select-input>
                                </x-forms.field>
                                <x-forms.field label="First name*" name="first_name">
                                    <input type="text" name="first_name" required
                                        class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </x-forms.field>
                                <x-forms.field label="Last name*" name="last_name">
                                    <input type="text" name="last_name" required
                                        class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </x-forms.field>
                                <x-forms.field label="Date of birth" name="date_birth">
                                    <input type="date" name="date_birth"
                                        class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </x-forms.field>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4">
                            <h3 class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Affiliation</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div class="flex items-end gap-2">
                                    <x-forms.field label="Department" name="departments_id" class="mb-0 flex-1">
                                        <select id="departments_id" name="departments_id"
                                            class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Select</option>
                                            @foreach(App\Models\Departments::all() as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </x-forms.field>
                                    <button type="button" id="team_department_form_btn"
                                        class="mb-2 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100"
                                        title="Add department">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                                <div class="flex items-end gap-2">
                                    <x-forms.field label="Organization*" name="organizations_id" class="mb-0 flex-1">
                                        <select id="organizations_id" name="organizations_id" required
                                            class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                            <option value="">Select</option>
                                            @foreach(App\Models\Organizations::all() as $org)
                                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                                            @endforeach
                                        </select>
                                    </x-forms.field>
                                    <button type="button" id="team_organization_form_btn"
                                        class="mb-2 inline-flex h-10 w-10 items-center justify-center rounded-xl border border-blue-200 bg-blue-50 text-blue-700 transition hover:bg-blue-100"
                                        title="Add organization">
                                        <i class="fas fa-plus text-xs"></i>
                                    </button>
                                </div>
                                <x-forms.field label="Email*" name="email" class="md:col-span-2">
                                    <input type="email" name="email"
                                        class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                        title="Enter the email exactly as the member uses it — capitalization matters." />
                                    <p class="mt-1 text-xs text-slate-500">Enter the email exactly as the member uses it — capitalization matters.</p>
                                </x-forms.field>
                            </div>
                        </section>

                        <section class="rounded-2xl border border-slate-200/80 bg-slate-50/60 p-4">
                            <h3 class="mb-3 text-[11px] font-semibold uppercase tracking-wide text-slate-500">Project access</h3>
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <x-forms.field label="Role*" name="role">
                                    <x-forms.select-input id="role" name="role" required class="w-full rounded-xl border-slate-200 text-sm shadow-sm">
                                        <option value="">Select</option>
                                        @foreach($project->people->pluck('pivot.role')->unique()->filter()->all() as $role)
                                            <option value="{{ $role }}">{{ $role }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>
                                <x-forms.field label="Permission*" name="permission">
                                    <x-forms.select-input id="permission" name="permission" required class="w-full rounded-xl border-slate-200 text-sm shadow-sm">
                                        <option value="">Select</option>
                                        <option value="viewer">Viewer</option>
                                        <option value="editor">Editor</option>
                                        <option value="admin">Admin</option>
                                    </x-forms.select-input>
                                </x-forms.field>
                                <x-forms.field label="Date joined*" name="date_joined">
                                    <input type="date" name="date_joined" required value="{{ now()->format('Y-m-d') }}"
                                        class="w-full rounded-xl border-slate-200 text-sm shadow-sm focus:border-blue-500 focus:ring-blue-500" />
                                </x-forms.field>
                            </div>
                            <div class="mt-4 rounded-2xl border border-white/80 bg-white p-4 shadow-sm" id="add-member-module-access">
                                <x-team.module-permissions-matrix
                                    :module-permission-options="$modulePermissionOptions"
                                    :module-matrix="\App\Support\ProjectPermission::defaultModuleMatrix('editor')"
                                    name-prefix="module_permissions" />
                            </div>
                            @if(($subProjects ?? collect())->count() > 0)
                                <div class="mt-4 rounded-2xl border border-indigo-100 bg-indigo-50/50 p-4">
                                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-wide text-indigo-700">Sub-project assignments (optional)</label>
                                    <div class="grid grid-cols-1 gap-2 md:grid-cols-2">
                                        @foreach($subProjects as $subProject)
                                            <label class="inline-flex items-center gap-2 rounded-xl border border-white/80 bg-white px-3 py-2 text-sm text-slate-700 shadow-sm">
                                                <input type="checkbox" name="sub_project_ids[]" value="{{ $subProject->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                <span>{{ $subProject->code }} — {{ $subProject->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                    <p class="mt-2 text-xs text-slate-500">Members with assigned sub-projects can only register data within those sub-projects.</p>
                                </div>
                            @endif
                        </section>
                    </div>
                    <div class="mt-6 flex items-center justify-between border-t border-slate-100 pt-4">
                        <p class="text-xs text-rose-500">* Required fields</p>
                        <button type="submit"
                            class="inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-md shadow-blue-500/25 transition hover:from-blue-700 hover:to-indigo-700">
                            <i class="fas fa-user-plus text-xs"></i>
                            Add member
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <x-table-modal id="team_organization_form_modal" title="Organization Registration Form" closeButtonId="team_organization_form_close_btn">
            @include('modals.form_organizations')
        </x-table-modal>
        <x-table-modal id="team_department_form_modal" title="Department Registration Form" closeButtonId="team_department_form_close_btn">
            @include('modals.form_departments')
        </x-table-modal>
    @endif
    @if (session('success'))
        <div id="teamSuccessMessage" class="hidden">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div id="teamErrorMessage" class="hidden">{{ session('error') }}</div>
    @endif

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="/js/team.js"></script>
</x-layout>
