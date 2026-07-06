@if($project->subProjects->count() > 0)
    <div class="overflow-x-auto rounded-lg border border-indigo-100 bg-white shadow-sm">
        <table class="min-w-full text-xs text-gray-700">
            <thead class="bg-indigo-50 text-gray-600">
                <tr>
                    <th class="px-3 py-2 text-left font-semibold">Code</th>
                    <th class="px-3 py-2 text-left font-semibold">Type</th>
                    <th class="px-3 py-2 text-left font-semibold">Title</th>
                    <th class="px-3 py-2 text-left font-semibold">Start</th>
                    <th class="px-3 py-2 text-left font-semibold">Intended End</th>
                    <th class="px-3 py-2 text-left font-semibold">End</th>
                    <th class="px-3 py-2 text-left font-semibold">Status</th>
                    <th class="px-3 py-2 text-left font-semibold">Collaborators</th>
                    <th class="px-3 py-2 text-left font-semibold">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-indigo-100">
                @foreach($project->subProjects as $subProject)
                    <tr>
                        <td class="px-3 py-2 font-semibold text-gray-900">{{ $subProject->code }}</td>
                        <td class="px-3 py-2">{{ $subProject->name }}</td>
                        <td class="px-3 py-2">{{ $subProject->title ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $subProject->date_started ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $subProject->date_end_intended ?: '—' }}</td>
                        <td class="px-3 py-2">{{ $subProject->date_end ?: '—' }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-[11px] font-semibold {{ ($subProject->status ?? 'active') === 'completed' ? 'bg-gray-100 text-gray-700' : 'bg-emerald-100 text-emerald-800' }}">
                                {{ ucfirst($subProject->status ?? 'active') }}
                            </span>
                        </td>
                        <td class="px-3 py-2">
                            @if($subProject->people->count() > 0)
                                {{ $subProject->people->map(fn ($p) => trim(($p->first_name ?? '') . ' ' . ($p->last_name ?? '')))->implode(', ') }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if($canManage)
                                <div class="flex items-center gap-3">
                                    @if(($subProject->status ?? 'active') !== 'completed')
                                        <button type="button"
                                            onclick="markSubProjectComplete({{ $subProject->id }}, '{{ $subProject->code }}', '{{ $subProject->date_started ?? '' }}')"
                                            class="text-xs font-semibold text-emerald-700 hover:text-emerald-900">
                                            Mark complete
                                        </button>
                                    @endif
                                    <button type="button"
                                        x-on:click="$dispatch('open-sub-project-edit', @js([
                                            'id' => $subProject->id,
                                            'code' => $subProject->code,
                                            'name' => $subProject->name,
                                            'title' => $subProject->title,
                                            'description' => $subProject->description,
                                            'date_started' => $subProject->date_started,
                                            'date_end_intended' => $subProject->date_end_intended,
                                            'date_end' => $subProject->date_end,
                                            'people_ids' => $subProject->people->pluck('id')->all(),
                                        ]))"
                                        class="text-xs font-semibold text-indigo-700 hover:text-indigo-900">
                                        Edit
                                    </button>
                                    <button type="button"
                                        onclick="deleteSubProject({{ $subProject->id }}, @js($subProject->code))"
                                        class="text-xs font-semibold text-red-600 hover:text-red-800">
                                        Delete
                                    </button>
                                </div>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@else
    <div class="text-sm text-gray-500">No sub-projects yet.</div>
@endif

@if($canManage)
    <div class="mt-4" x-data="subProjectEditModal()" x-on:open-sub-project-edit.window="openEdit($event.detail)">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-on:click.self="open = false">
            <div class="w-full max-w-3xl rounded-xl bg-white shadow-2xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-900">Edit sub-project</h4>
                    <button type="button" x-on:click="open = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" x-bind:action="`/sub-projects/${form.id}`" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    @method('PATCH')
                    <div>
                        <input type="text" name="code" placeholder="Sub-project code" x-model="form.code"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" required>
                    </div>
                    <div>
                        <input type="text" name="name" list="sub-project-type-options-edit-{{ $project->id }}" placeholder="Sub-project type"
                            x-model="form.name" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" required>
                    </div>
                    <input type="text" name="title" placeholder="Sub-project title" x-model="form.title"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                    <input type="date" name="date_started" x-model="form.date_started"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                    <input type="date" name="date_end_intended" x-model="form.date_end_intended"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                    <input type="date" name="date_end" x-model="form.date_end"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                    <textarea name="description" rows="2" placeholder="Description (optional)" x-model="form.description"
                        class="md:col-span-3 w-full px-3 py-2 text-sm border border-gray-200 rounded-lg"></textarea>
                    <div class="md:col-span-3">
                        <div class="text-xs text-gray-600 mb-2">Collaborators</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($project->people as $member)
                                <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                    <input type="checkbox" name="people_ids[]" value="{{ $member->id }}"
                                        x-bind:checked="form.people_ids.includes({{ $member->id }})"
                                        x-on:change="togglePerson({{ $member->id }}, $event.target.checked)">
                                    {{ trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-3 flex justify-end gap-2">
                        <button type="button" x-on:click="open = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg">
                            <i class="fas fa-save mr-2"></i>Save changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif

@if($canManage)
    <div class="mt-4" x-data="subProjectCreateModal({ projectId: {{ (int) $project->id }}, endpoint: '{{ route('sub-projects.check-code') }}' })">
        <button type="button"
            x-on:click="open = true"
            class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm rounded-lg">
            <i class="fas fa-plus mr-2"></i>Register new sub-project
        </button>

        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" x-on:click.self="open = false">
            <div class="w-full max-w-3xl rounded-xl bg-white shadow-2xl border border-gray-200 p-5">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="text-lg font-semibold text-gray-900">Register new sub-project</h4>
                    <button type="button" x-on:click="open = false" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <form method="POST" action="{{ route('sub-projects.store') }}" class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    @csrf
                    <input type="hidden" name="project_id" value="{{ $project->id }}">
                    <div>
                        <input type="text" name="code" placeholder="Sub-project code" x-model="code" x-on:input.debounce.350ms="checkCode()"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" required>
                        <div x-show="codeError" class="text-xs text-red-600 mt-1" x-text="codeError"></div>
                    </div>
                    <div>
                        <input type="text" name="name" list="sub-project-type-options-{{ $project->id }}" placeholder="Sub-project type"
                            class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg" required>
                        <datalist id="sub-project-type-options-{{ $project->id }}">
                            @foreach($project->subProjects->pluck('name')->filter()->unique()->values() as $typeOption)
                                <option value="{{ $typeOption }}"></option>
                            @endforeach
                        </datalist>
                    </div>
                    <input type="text" name="title" placeholder="Sub-project title"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                    <input type="date" name="date_started"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                    <input type="date" name="date_end_intended"
                        class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg">
                    <textarea name="description" rows="2" placeholder="Description (optional)"
                        class="md:col-span-3 w-full px-3 py-2 text-sm border border-gray-200 rounded-lg"></textarea>
                    <div class="md:col-span-3">
                        <div class="text-xs text-gray-600 mb-2">Collaborators</div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                            @foreach($project->people as $member)
                                <label class="inline-flex items-center gap-2 text-xs text-gray-700">
                                    <input type="checkbox" name="people_ids[]" value="{{ $member->id }}">
                                    {{ trim(($member->first_name ?? '') . ' ' . ($member->last_name ?? '')) }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="md:col-span-3 flex justify-end gap-2">
                        <button type="button" x-on:click="open = false" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm font-medium rounded-lg">
                            Cancel
                        </button>
                        <button type="submit"
                            x-bind:disabled="codeError !== '' || checking"
                            class="inline-flex items-center px-3 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 disabled:cursor-not-allowed text-white text-sm rounded-lg">
                            <i class="fas fa-plus mr-2"></i>Create sub-project
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
