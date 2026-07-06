<form method="POST" action="{{ route('projects.update', ['project' => $project->id, 'section' => 'team']) }}"
    class="space-y-6">
    @csrf
    @method('PATCH')
    <input type="hidden" name="section" value="team">

    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Team Members</h2>
            <p class="mt-2 text-sm text-gray-600">Update the project team members below</p>
        </div>

        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center space-x-2">
                <i class="fas fa-users text-blue-500 text-xl"></i>
                <h2 class="text-lg font-semibold text-gray-800">Team Members</h2>
            </div>

            @if ($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    <strong class="font-bold">Validation Errors:</strong>
                    <ul class="mt-2">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div id="team-members-container">
                @if (isset($project) && $project->people->count() > 0)
                    @foreach ($project->people as $index => $member)
                        @php
                            $role = (string) ($member->pivot?->role ?? '');
                            $dateJoined = $member->pivot?->date_joined
                                ? \Carbon\Carbon::parse($member->pivot->date_joined)->format('Y-m-d')
                                : now()->format('Y-m-d');
                            $memberModulesRaw = $member->pivot?->module_permissions;
                            $memberModules = is_array($memberModulesRaw)
                                ? $memberModulesRaw
                                : (json_decode((string) $memberModulesRaw, true) ?: []);
                        @endphp
                        <div
                            class="team-member-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200 {{ $index > 0 ? 'mt-4' : '' }}"
                            data-index="{{ $index }}">
                            <input type="hidden" name="team_members[{{ $index }}][person_id]"
                                value="{{ $member->id }}">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <x-forms.field label="Title:" name="team_members[{{ $index }}][title]">
                                    <x-forms.select-input id="team_members_{{ $index }}_title"
                                        name="team_members[{{ $index }}][title]" required class="team-title-select w-full">
                                        <option value="">Select title</option>
                                        <option value="Dr." {{ $member->title === 'Dr.' ? 'selected' : '' }}>Dr.</option>
                                        <option value="Prof." {{ $member->title === 'Prof.' ? 'selected' : '' }}>Prof.</option>
                                        <option value="Mr." {{ $member->title === 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                        <option value="Mrs." {{ $member->title === 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                                        <option value="Ms." {{ $member->title === 'Ms.' ? 'selected' : '' }}>Ms.</option>
                                        <option value="Miss" {{ $member->title === 'Miss' ? 'selected' : '' }}>Miss</option>
                                    </x-forms.select-input>
                                </x-forms.field>

                                <x-forms.field label="First Name:" name="team_members[{{ $index }}][first_name]">
                                    <x-forms.text-input id="team_members_{{ $index }}_first_name"
                                        name="team_members[{{ $index }}][first_name]" required
                                        value="{{ $member->first_name }}" class="w-full">
                                    </x-forms.text-input>
                                </x-forms.field>

                                <x-forms.field label="Last Name:" name="team_members[{{ $index }}][last_name]">
                                    <x-forms.text-input id="team_members_{{ $index }}_last_name"
                                        name="team_members[{{ $index }}][last_name]" required
                                        value="{{ $member->last_name }}" class="w-full">
                                    </x-forms.text-input>
                                </x-forms.field>

                                <x-forms.field label="Email:" name="team_members[{{ $index }}][email]">
                                    <x-forms.email-input id="team_members_{{ $index }}_email"
                                        name="team_members[{{ $index }}][email]" required value="{{ $member->email }}"
                                        class="w-full">
                                    </x-forms.email-input>
                                </x-forms.field>

                                <x-forms.field label="Role:" name="team_members[{{ $index }}][role]">
                                    <input list="role_options" id="team_members_{{ $index }}_role"
                                        name="team_members[{{ $index }}][role]" required value="{{ $role }}"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                        placeholder="Select or type role" />
                                </x-forms.field>

                                <x-forms.field label="Permission:" name="team_members[{{ $index }}][permission]">
                                    <x-forms.select-input id="team_members_{{ $index }}_permission"
                                        name="team_members[{{ $index }}][permission]" required class="team-permission-select w-full">
                                        <option value="">Select permission</option>
                                        <option value="viewer" {{ ($member->pivot?->permission ?? '') === 'viewer' ? 'selected' : '' }}>Viewer</option>
                                        <option value="editor" {{ ($member->pivot?->permission ?? '') === 'editor' ? 'selected' : '' }}>Editor</option>
                                        <option value="admin" {{ ($member->pivot?->permission ?? '') === 'admin' ? 'selected' : '' }}>Admin</option>
                                    </x-forms.select-input>
                                </x-forms.field>

                                <div class="team-modules-field">
                                    <x-forms.field label="Viewer Modules:"
                                        name="team_members[{{ $index }}][module_permissions][]">
                                        <div class="grid grid-cols-1 gap-1 text-xs border border-gray-200 rounded-lg p-2 max-h-36 overflow-y-auto bg-white team-modules-container">
                                            @foreach(($modulePermissionOptions ?? []) as $moduleKey => $moduleLabel)
                                                <label class="inline-flex items-center gap-2">
                                                    <input type="checkbox"
                                                        name="team_members[{{ $index }}][module_permissions][]"
                                                        value="{{ $moduleKey }}"
                                                        {{ in_array($moduleKey, $memberModules, true) ? 'checked' : '' }}
                                                        class="rounded border-gray-300">
                                                    <span>{{ $moduleLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </x-forms.field>
                                </div>

                                <x-forms.field label="Date Joined:" name="team_members[{{ $index }}][date_joined]">
                                    <x-forms.date-input id="team_members_{{ $index }}_date_joined"
                                        name="team_members[{{ $index }}][date_joined]" required
                                        value="{{ $dateJoined }}" class="w-full">
                                    </x-forms.date-input>
                                </x-forms.field>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="team-member-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200"
                        data-index="0">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-forms.field label="Title:" name="team_members[0][title]">
                                <x-forms.select-input id="team_members_0_title" name="team_members[0][title]"
                                    required class="team-title-select w-full">
                                    <option value="">Select title</option>
                                    <option value="Dr.">Dr.</option>
                                    <option value="Prof.">Prof.</option>
                                    <option value="Mr.">Mr.</option>
                                    <option value="Mrs.">Mrs.</option>
                                    <option value="Ms.">Ms.</option>
                                    <option value="Miss">Miss</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="First Name:" name="team_members[0][first_name]">
                                <x-forms.text-input id="team_members_0_first_name" name="team_members[0][first_name]"
                                    required class="w-full"></x-forms.text-input>
                            </x-forms.field>

                            <x-forms.field label="Last Name:" name="team_members[0][last_name]">
                                <x-forms.text-input id="team_members_0_last_name" name="team_members[0][last_name]"
                                    required class="w-full"></x-forms.text-input>
                            </x-forms.field>

                            <x-forms.field label="Email:" name="team_members[0][email]">
                                <x-forms.email-input id="team_members_0_email" name="team_members[0][email]"
                                    required class="w-full"></x-forms.email-input>
                            </x-forms.field>

                            <x-forms.field label="Role:" name="team_members[0][role]">
                                <input list="role_options" id="team_members_0_role" name="team_members[0][role]"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="Select or type role" />
                            </x-forms.field>

                            <x-forms.field label="Permission:" name="team_members[0][permission]">
                                <x-forms.select-input id="team_members_0_permission"
                                    name="team_members[0][permission]" required class="team-permission-select w-full">
                                    <option value="">Select permission</option>
                                    <option value="viewer">Viewer</option>
                                    <option value="editor">Editor</option>
                                    <option value="admin">Admin</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            <div class="team-modules-field">
                                <x-forms.field label="Viewer Modules:" name="team_members[0][module_permissions][]">
                                    <div class="grid grid-cols-1 gap-1 text-xs border border-gray-200 rounded-lg p-2 max-h-36 overflow-y-auto bg-white team-modules-container">
                                        @foreach(($modulePermissionOptions ?? []) as $moduleKey => $moduleLabel)
                                            <label class="inline-flex items-center gap-2">
                                                <input type="checkbox" name="team_members[0][module_permissions][]"
                                                    value="{{ $moduleKey }}" class="rounded border-gray-300">
                                                <span>{{ $moduleLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </x-forms.field>
                            </div>

                            <x-forms.field label="Date Joined:" name="team_members[0][date_joined]">
                                <x-forms.date-input id="team_members_0_date_joined" name="team_members[0][date_joined]"
                                    required value="{{ now()->format('Y-m-d') }}" class="w-full"></x-forms.date-input>
                            </x-forms.field>
                        </div>
                    </div>
                @endif
            </div>

            <datalist id="role_options">
                <option value="Principal Investigator">
                <option value="Supervisor">
                <option value="Co-supervisor">
                <option value="Collaborator">
                <option value="Postgraduate student">
                <option value="Undergraduate student">
                <option value="Responsible technologist">
                <option value="Data analyst">
                <option value="Bioinformatician">
                <option value="Laboratory technician">
                <option value="Project coordinator">
                <option value="Field researcher">
                <option value="Administrative support">
                <option value="Intern">
                <option value="Visiting scholar">
                <option value="Other">
            </datalist>

            <div class="mt-6">
                <button type="button" onclick="addTeamMember()"
                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                    <i class="fas fa-user-plus mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Add Team Member
                </button>
            </div>
        </div>

        <div
            class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-end rounded-b-xl border-t border-gray-200">
            <button type="submit"
                class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                Save Changes
            </button>
        </div>
    </div>
</form>

<style>
    .autofilled-disabled {
        background-color: #f3f4f6 !important;
        color: #6b7280 !important;
        cursor: not-allowed;
    }

    input[readonly].autofilled-disabled {
        background-color: #f3f4f6 !important;
        color: #6b7280 !important;
        cursor: not-allowed;
    }
</style>

<script>
    function initSelectizeForEntry(entryElement) {
        entryElement.querySelectorAll('.team-title-select, .team-permission-select').forEach((selectEl) => {
            if (selectEl.selectize) {
                return;
            }

            const isPermission = selectEl.classList.contains('team-permission-select');
            const selectize = $(selectEl).selectize({
                create: false,
                sortField: 'text',
                onChange(value) {
                    if (!isPermission) {
                        return;
                    }
                    toggleModulesVisibility(entryElement);
                },
            })[0].selectize;

            if (isPermission) {
                selectize.trigger('change');
            }
        });
        toggleModulesVisibility(entryElement);
    }

    function toggleModulesVisibility(entryElement) {
        const permissionSelect = entryElement.querySelector('.team-permission-select');
        const modulesField = entryElement.querySelector('.team-modules-field');
        if (!permissionSelect || !modulesField) {
            return;
        }

        const isViewer = permissionSelect.value === 'viewer';
        modulesField.style.display = isViewer ? '' : 'none';

        if (!isViewer) {
            modulesField.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
                checkbox.checked = false;
            });
        }
    }

    function setupEmailCheckingForEntry(entryElement) {
        const emailInput = entryElement.querySelector('input[name*="[email]"]');
        if (!emailInput || emailInput._handlerAttached) {
            return;
        }

        emailInput.addEventListener('blur', function() {
            const email = emailInput.value.trim();
            if (!email) {
                return;
            }

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector(
                'input[name="_token"]')?.value;
            if (!csrfToken) {
                return;
            }

            fetch('/team/check-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken
                    },
                    body: JSON.stringify({
                        email
                    })
                })
                .then((res) => res.json())
                .then((data) => {
                    const firstName = entryElement.querySelector('input[name*="[first_name]"]');
                    const lastName = entryElement.querySelector('input[name*="[last_name]"]');
                    const titleSelect = entryElement.querySelector('select[name*="[title]"]');
                    const titleSelectize = titleSelect?.selectize;
                    let personIdInput = entryElement.querySelector('input[name*="[person_id]"]');

                    if (data.found && data.person) {
                        if (firstName) {
                            firstName.value = data.person.first_name || '';
                            firstName.readOnly = true;
                            firstName.classList.add('autofilled-disabled');
                        }
                        if (lastName) {
                            lastName.value = data.person.last_name || '';
                            lastName.readOnly = true;
                            lastName.classList.add('autofilled-disabled');
                        }
                        if (titleSelectize) {
                            titleSelectize.setValue(data.person.title || '', true);
                            titleSelectize.lock();
                            titleSelectize.$control.addClass('autofilled-disabled');
                        }

                        if (!personIdInput) {
                            personIdInput = document.createElement('input');
                            personIdInput.type = 'hidden';
                            personIdInput.name = emailInput.name.replace('[email]', '[person_id]');
                            entryElement.appendChild(personIdInput);
                        }
                        personIdInput.value = data.person.id;
                    } else {
                        if (firstName) {
                            firstName.readOnly = false;
                            firstName.classList.remove('autofilled-disabled');
                        }
                        if (lastName) {
                            lastName.readOnly = false;
                            lastName.classList.remove('autofilled-disabled');
                        }
                        if (titleSelectize) {
                            titleSelectize.unlock();
                            titleSelectize.$control.removeClass('autofilled-disabled');
                        }
                        if (personIdInput) {
                            personIdInput.remove();
                        }
                    }
                })
                .catch(() => {});
        });

        emailInput._handlerAttached = true;
    }

    function addTeamMember() {
        const container = document.getElementById('team-members-container');
        const newIndex = container.querySelectorAll('.team-member-entry').length;

        const template = `
            <div class="team-member-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200 mt-4" data-index="${newIndex}">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title:</label>
                        <select id="team_members_${newIndex}_title" name="team_members[${newIndex}][title]" required class="team-title-select w-full px-4 py-2 text-sm border border-gray-200 rounded-lg">
                            <option value="">Select title</option>
                            <option value="Dr.">Dr.</option>
                            <option value="Prof.">Prof.</option>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Ms.">Ms.</option>
                            <option value="Miss">Miss</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">First Name:</label>
                        <input type="text" id="team_members_${newIndex}_first_name" name="team_members[${newIndex}][first_name]" required class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Last Name:</label>
                        <input type="text" id="team_members_${newIndex}_last_name" name="team_members[${newIndex}][last_name]" required class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Email:</label>
                        <input type="email" id="team_members_${newIndex}_email" name="team_members[${newIndex}][email]" required class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Role:</label>
                        <input list="role_options" id="team_members_${newIndex}_role" name="team_members[${newIndex}][role]" required class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg" placeholder="Select or type role" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Permission:</label>
                        <select id="team_members_${newIndex}_permission" name="team_members[${newIndex}][permission]" required class="team-permission-select w-full px-4 py-2 text-sm border border-gray-200 rounded-lg">
                            <option value="">Select permission</option>
                            <option value="viewer">Viewer</option>
                            <option value="editor">Editor</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="team-modules-field">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Viewer Modules:</label>
                        <div class="grid grid-cols-1 gap-1 text-xs border border-gray-200 rounded-lg p-2 max-h-36 overflow-y-auto bg-white team-modules-container">
                            @foreach(($modulePermissionOptions ?? []) as $moduleKey => $moduleLabel)
                                <label class="inline-flex items-center gap-2">
                                    <input type="checkbox" name="team_members[${newIndex}][module_permissions][]" value="{{ $moduleKey }}" class="rounded border-gray-300">
                                    <span>{{ $moduleLabel }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date Joined:</label>
                        <input type="date" id="team_members_${newIndex}_date_joined" name="team_members[${newIndex}][date_joined]" required value="{{ now()->format('Y-m-d') }}" class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg" />
                    </div>
                </div>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', template);
        const newEntry = container.lastElementChild;
        initSelectizeForEntry(newEntry);
        setupEmailCheckingForEntry(newEntry);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const entries = document.querySelectorAll('.team-member-entry');
        entries.forEach((entry) => {
            initSelectizeForEntry(entry);
            setupEmailCheckingForEntry(entry);
        });

        const form = document.querySelector('form');
        if (!form) {
            return;
        }

        form.addEventListener('submit', function() {
            const teamEntries = this.querySelectorAll('.team-member-entry');
            teamEntries.forEach((entry) => {
                const personIdInput = entry.querySelector('input[name*="[person_id]"]');
                const firstName = entry.querySelector('input[name*="[first_name]"]')?.value?.trim() ?? '';
                const lastName = entry.querySelector('input[name*="[last_name]"]')?.value?.trim() ?? '';
                const email = entry.querySelector('input[name*="[email]"]')?.value?.trim() ?? '';
                const title = entry.querySelector('select[name*="[title]"]')?.value ?? '';

                if (!personIdInput && !firstName && !lastName && !email && !title) {
                    entry.remove();
                }
            });
        });
    });
</script>
