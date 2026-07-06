<form action="{{ route('projects.store') }}" method="POST">
    @csrf
    <input type="hidden" name="step" value="2">

    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Team Members</h2>
            <p class="mt-2 text-sm text-gray-600">Add team members to your project below</p>
        </div>

        <!-- Instructions -->
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-6">
            <div class="flex items-start space-x-3">
                <i class="fas fa-info-circle text-blue-500 mt-1"></i>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-blue-800 mb-2">How to add team members:</h3>
                    <ul class="text-sm text-blue-700 space-y-1">
                        <li class="flex items-start space-x-2">
                            <span class="text-blue-500">•</span>
                            <span><strong>You are already included</strong> as the first team member with your information pre-filled</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <span class="text-blue-500">•</span>
                            <span><strong>Auto-fill feature:</strong> When you enter an email address, if that person already exists in the system, their information (name, title) will be automatically filled in and those fields will be disabled</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <span class="text-blue-500">•</span>
                            <span><strong>Add more members:</strong> Click the "Add Team Member" button below to add additional team members</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <span class="text-blue-500">•</span>
                            <span><strong>Required fields:</strong> All fields marked with an asterisk (*) are required</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <span class="text-blue-500">•</span>
                            <span><strong>Role field:</strong> You can select from the dropdown or type a custom role</span>
                        </li>
                    </ul>
                </div>
            </div>
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
                <div class="team-member-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <input type="hidden" name="team_members[0][person_id]" value="{{ auth()->user()->people->id }}">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-forms.field label="Title:" name="team_members[0][title]">
                            <x-forms.select-input id="team_members_0_title" name="team_members[0][title]" required
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                <option value="">Select title</option>
                                <option value="Dr." {{ auth()->user()->people->title == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                                <option value="Prof." {{ auth()->user()->people->title == 'Prof.' ? 'selected' : '' }}>Prof.</option>
                                <option value="Mr." {{ auth()->user()->people->title == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                <option value="Mrs." {{ auth()->user()->people->title == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                                <option value="Ms." {{ auth()->user()->people->title == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                                <option value="Miss" {{ auth()->user()->people->title == 'Miss' ? 'selected' : '' }}>Miss</option>
                            </x-forms.select-input>
                        </x-forms.field>

                        <x-forms.field label="First Name:" name="team_members[0][first_name]">
                            <x-forms.text-input id="team_members_0_first_name" name="team_members[0][first_name]" required
                                value="{{ auth()->user()->people->first_name }}"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.text-input>
                        </x-forms.field>

                        <x-forms.field label="Last Name:" name="team_members[0][last_name]">
                            <x-forms.text-input id="team_members_0_last_name" name="team_members[0][last_name]" required
                                value="{{ auth()->user()->people->last_name }}"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.text-input>
                        </x-forms.field>

                        <x-forms.field label="Email:" name="team_members[0][email]">
                            <x-forms.email-input id="team_members_0_email" name="team_members[0][email]" required
                                value="{{ auth()->user()->email }}"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.email-input>
                        </x-forms.field>

                        
                        <x-forms.field label="Role:" name="team_members[0][role]">
                            <input list="role_options" id="team_members_0_role" name="team_members[0][role]" required
                                value="Principal Investigator"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Select or type role" />
                        
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
                        </x-forms.field>
                        

                        <x-forms.field label="Permission:" name="team_members[0][permission]">
                            <x-forms.select-input id="team_members_0_permission" name="team_members[0][permission]" required
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                <option value="">Select permission</option>
                                <option value="viewer">Viewer</option>
                                <option value="editor">Editor</option>
                                <option value="admin" selected>Admin</option>
                            </x-forms.select-input>
                        </x-forms.field>
                        <div class="team-modules-field">
                            <x-forms.field label="Viewer Modules:" name="team_members[0][module_permissions][]">
                                <div class="grid grid-cols-1 gap-1 text-xs border border-gray-200 rounded-lg p-2 max-h-36 overflow-y-auto bg-white team-modules-container">
                                    @foreach(($modulePermissionOptions ?? []) as $moduleKey => $moduleLabel)
                                        <label class="inline-flex items-center gap-2">
                                            <input type="checkbox" name="team_members[0][module_permissions][]" value="{{ $moduleKey }}" class="rounded border-gray-300">
                                            <span>{{ $moduleLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </x-forms.field>
                        </div>

                        <x-forms.field label="Date Joined:" name="team_members[0][date_joined]">
                            <x-forms.date-input id="team_members_0_date_joined" name="team_members[0][date_joined]" required
                                value="{{ now()->format('Y-m-d') }}"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.date-input>
                        </x-forms.field>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="button" onclick="addTeamMember()"
                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                    <i
                        class="fas fa-user-plus mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Add Team Member
                </button>
            </div>
        </div>

        <!-- Action Buttons -->
        <div
            class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-between rounded-b-xl border-t border-gray-200">
            <a href="{{ route('projects.create', ['step' => 1]) }}"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i
                    class="fas fa-arrow-left mr-2 text-lg group-hover:-translate-x-1 transition-transform duration-300"></i>
                Back
            </a>

            <button type="submit"
                class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i
                    class="fas fa-arrow-right mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                Next Step
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
    
    select.autofilled-disabled {
        background-color: #f3f4f6 !important;
        color: #6b7280 !important;
        cursor: not-allowed;
        pointer-events: none;
    }
</style>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    function toggleModulesVisibility(entryElement) {
        const permissionSelect = entryElement.querySelector('select[name*="[permission]"]');
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

    function setupPermissionToggleForEntry(entryElement) {
        const permissionSelect = entryElement.querySelector('select[name*="[permission]"]');
        if (!permissionSelect || permissionSelect._permissionHandlerAttached) {
            return;
        }

        permissionSelect.addEventListener('change', function() {
            toggleModulesVisibility(entryElement);
        });
        permissionSelect._permissionHandlerAttached = true;
        toggleModulesVisibility(entryElement);
    }

    // Email check for existing person/user - similar to team.js
    // Function to setup email checking for a specific team member entry
    function setupEmailCheckingForEntry(entryElement) {
        const emailInput = entryElement.querySelector('input[name*="email"]');
        if (!emailInput || emailInput._handlerAttached) return;
        
        emailInput.addEventListener('blur', function() {
            console.log("Email blur triggered", emailInput.value);
            const email = emailInput.value.trim();
            if (!email) return;
            
            // Try to get CSRF token from meta or hidden input
            let csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
            if (!csrfToken) {
                csrfToken = document.querySelector('input[name="_token"]')?.value;
            }
            if (!csrfToken) {
                console.error('CSRF token not found');
                return;
            }
            
            fetch('{{ route('projects.check-team-email') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ email })
            })
            .then(res => {
                if (!res.ok) {
                    throw new Error('Email lookup failed with status ' + res.status);
                }
                return res.json();
            })
            .then(data => {
                console.log('Email check response:', data);
                if (data.found && data.person) {
                    // Helper to autofill and disable
                    function autofillAndDisable(selector, value) {
                        let el = entryElement.querySelector(selector);
                        if (!el) return;
                        el.value = value || '';
                        if (el.tagName === 'SELECT') {
                            // Don't disable select - just make it visually appear disabled
                            el.classList.add('autofilled-disabled');
                        } else {
                            el.readOnly = true;
                            el.classList.add('autofilled-disabled');
                        }
                    }
                    autofillAndDisable('input[name*="first_name"]', data.person.first_name);
                    autofillAndDisable('input[name*="last_name"]', data.person.last_name);
                    autofillAndDisable('select[name*="title"]', data.person.title);
                    
                    // Add hidden input for person_id
                    let hidden = entryElement.querySelector('input[name*="person_id"]');
                    if (!hidden) {
                        hidden = document.createElement('input');
                        hidden.type = 'hidden';
                        hidden.name = emailInput.name.replace('email', 'person_id');
                        entryElement.appendChild(hidden);
                    }
                    hidden.value = data.person.id;
                    
                    // Show notification
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'info',
                            title: 'Existing Person',
                            text: 'This email is already registered. The form has been auto-filled.'
                        });
                    } else {
                        alert('This email is already registered. The form has been auto-filled.');
                    }
                } else {
                    // Helper to enable and remove class
                    function enableAndRemoveClass(selector) {
                        let el = entryElement.querySelector(selector);
                        if (!el) return;
                        if (el.tagName === 'SELECT') {
                            // Just remove the visual class - select is not disabled
                            el.classList.remove('autofilled-disabled');
                        } else {
                            el.readOnly = false;
                            el.classList.remove('autofilled-disabled');
                        }
                    }
                    enableAndRemoveClass('input[name*="first_name"]');
                    enableAndRemoveClass('input[name*="last_name"]');
                    enableAndRemoveClass('select[name*="title"]');
                    
                    // Remove hidden input
                    let hidden = entryElement.querySelector('input[name*="person_id"]');
                    if (hidden) hidden.remove();
                }
            })
            .catch(err => {
                console.error('Email check AJAX error:', err);
            });
        });
        emailInput._handlerAttached = true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Setup email checking for all existing team member entries
        const existingEntries = document.querySelectorAll('.team-member-entry');
        existingEntries.forEach((entry, index) => {
            // Skip email checking for the first entry (current user)
            if (index === 0) {
                // Mark the first entry as auto-filled for the current user
                const emailInput = entry.querySelector('input[name*="email"]');
                const firstNameInput = entry.querySelector('input[name*="first_name"]');
                const lastNameInput = entry.querySelector('input[name*="last_name"]');
                const titleSelect = entry.querySelector('select[name*="title"]');
                
                if (emailInput && firstNameInput && lastNameInput && titleSelect) {
                    // Make fields read-only instead of disabled to ensure they get submitted
                    firstNameInput.readOnly = true;
                    lastNameInput.readOnly = true;
                    emailInput.readOnly = true;
                    // Don't disable the select - just make it visually appear disabled
                    titleSelect.classList.add('autofilled-disabled');
                    
                    firstNameInput.classList.add('autofilled-disabled');
                    lastNameInput.classList.add('autofilled-disabled');
                    emailInput.classList.add('autofilled-disabled');
                }
            } else {
                setupEmailCheckingForEntry(entry);
            }
            setupPermissionToggleForEntry(entry);
        });

        // Handle form submission to enable read-only fields and remove empty entries
        document.querySelector('form').addEventListener('submit', function() {
            console.log('Form submission handler called');
            
            // Enable read-only fields for all entries (including auto-filled ones)
            const readOnlyFields = this.querySelectorAll('input[readonly]');
            console.log('Found read-only fields:', readOnlyFields.length);
            readOnlyFields.forEach(field => {
                field.readOnly = false;
                console.log('Enabled read-only field:', field.name, field.value);
            });
            
            // Note: Select fields are not disabled anymore, just visually styled
            
            // Remove empty team member entries (except the first one which is the current user)
            const teamEntries = this.querySelectorAll('.team-member-entry');
            teamEntries.forEach((entry, index) => {
                if (index === 0) return; // Skip the first entry (current user)
                
                const titleField = entry.querySelector('select[name*="title"]');
                const firstNameField = entry.querySelector('input[name*="first_name"]');
                const lastNameField = entry.querySelector('input[name*="last_name"]');
                const emailField = entry.querySelector('input[name*="email"]');
                
                // Check if all required fields are empty OR if they have values but are read-only (auto-filled)
                const isTitleEmpty = !titleField || titleField.value === '';
                const isFirstNameEmpty = !firstNameField || (firstNameField.value.trim() === '' && !firstNameField.readOnly);
                const isLastNameEmpty = !lastNameField || (lastNameField.value.trim() === '' && !lastNameField.readOnly);
                const isEmailEmpty = !emailField || (emailField.value.trim() === '' && !emailField.readOnly);
                
                // If all fields are empty (and not auto-filled), remove this entry
                if (isTitleEmpty && isFirstNameEmpty && isLastNameEmpty && isEmailEmpty) {
                    console.log('Removing empty team member entry:', index);
                    entry.remove();
                }
            });
        });
    });

    function removeTeamMember(button) {
        const container = document.getElementById('team-members-container');
        const entry = button.closest('.team-member-entry');
        if (!container || !entry) {
            return;
        }

        const entries = container.querySelectorAll('.team-member-entry');
        if (entries.length <= 1 || entry === entries[0]) {
            return;
        }

        entry.remove();
    }

    function addTeamMember() {
        const container = document.getElementById('team-members-container');
        const entries = container.getElementsByClassName('team-member-entry');
        const newIndex = entries.length;

        const template = `
        <div class="team-member-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200 mt-4">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-700">Additional team member</h4>
                <button type="button" onclick="removeTeamMember(this)"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-500 transition hover:bg-red-50 hover:text-red-700"
                    title="Remove team member" aria-label="Remove team member">
                    <i class="fas fa-trash-alt text-sm"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Title:</label>
                    <select id="team_members_${newIndex}_title" name="team_members[${newIndex}][title]" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
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
                    <input type="text" id="team_members_${newIndex}_first_name" name="team_members[${newIndex}][first_name]" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Last Name:</label>
                    <input type="text" id="team_members_${newIndex}_last_name" name="team_members[${newIndex}][last_name]" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email:</label>
                    <input type="email" id="team_members_${newIndex}_email" name="team_members[${newIndex}][email]" required
                        title="Enter the email exactly as the member uses it — capitalization matters."
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    <span class="mt-1 block text-xs text-gray-500">Enter the email exactly as the member uses it — capitalization matters.</span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Role:</label>
                    <input list="role_options" id="team_members_${newIndex}_role" name="team_members[${newIndex}][role]" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                        placeholder="Select or type role" />
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Permission:</label>
                    <select id="team_members_${newIndex}_permission" name="team_members[${newIndex}][permission]" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
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
                    <input type="date" id="team_members_${newIndex}_date_joined" name="team_members[${newIndex}][date_joined]" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                </div>
            </div>
        </div>
    `;

        container.insertAdjacentHTML('beforeend', template);
        
        // Setup email checking for the newly added team member entry
        const newEntry = container.lastElementChild;
        if (newEntry) {
            setupEmailCheckingForEntry(newEntry);
            setupPermissionToggleForEntry(newEntry);
        }
    }
</script>
