<x-layout-plain>
    <x-slot:heading>
        Register
    </x-slot:heading>
    <main>
        <div class="flex min-h-full items-center justify-center px-4 py-1 sm:px-6 lg:px-8">
            <div class="w-full max-w-sm">
                @include('partials.auth-brand', ['subtitle' => 'Create your account'])

                <div class="mt-8 rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-200 sm:p-8">
                <form class="space-y-6" action="/register" method="POST" id="registerForm">
                    @csrf
                    @php
                        $registerInput = 'block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm';
                    @endphp
                    <div class="space-y-5">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="space-y-1.5">
                                <label for="title" class="block text-sm font-medium text-gray-700">Title</label>
                                <select id="title" name="title" required class="{{ $registerInput }}">
                                    <option value="Mr." {{ old('title') == 'Mr.' ? 'selected' : '' }}>Mr.</option>
                                    <option value="Mrs." {{ old('title') == 'Mrs.' ? 'selected' : '' }}>Mrs.</option>
                                    <option value="Ms." {{ old('title') == 'Ms.' ? 'selected' : '' }}>Ms.</option>
                                    <option value="Dr." {{ old('title') == 'Dr.' ? 'selected' : '' }}>Dr.</option>
                                    <option value="Prof." {{ old('title') == 'Prof.' ? 'selected' : '' }}>Prof.</option>
                                </select>
                            </div>
                            <div class="col-span-2 space-y-1.5">
                                <label for="first_name" class="block text-sm font-medium text-gray-700">First names</label>
                                <input id="first_name" name="first_name" type="text" required class="{{ $registerInput }}" value="{{ old('first_name') }}">
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label for="last_name" class="block text-sm font-medium text-gray-700">Last name</label>
                            <input id="last_name" name="last_name" type="text" required class="{{ $registerInput }}" value="{{ old('last_name') }}">
                        </div>

                        <div class="space-y-1.5">
                            <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                            <input id="email" name="email" type="email" required class="{{ $registerInput }}" value="{{ old('email') }}">
                        </div>

                        <div class="space-y-1.5">
                            <label for="job" class="block text-sm font-medium text-gray-700">Occupation</label>
                            <input id="job" name="job" type="text" required class="{{ $registerInput }}" placeholder="Select or enter occupation" value="{{ old('job') }}" list="jobs">
                            <datalist id="jobs">
                                @foreach($jobs as $job)
                                    <option value="{{ $job }}">
                                @endforeach
                            </datalist>
                        </div>

                        <div class="space-y-1.5">
                            <label for="organization_name" class="block text-sm font-medium text-gray-700">Organization <span class="font-normal text-gray-400">(optional)</span></label>
                            <div class="flex gap-2">
                                <input id="organization_name" name="organization_name" type="text" class="{{ $registerInput }} flex-1"
                                    placeholder="Select or add your organization"
                                    value="{{ $oldOrganizationName ?? old('organization_name') }}" list="organizations">
                                <button type="button" id="register_organization_form_btn" class="inline-flex items-center justify-center rounded-lg bg-[#008E9A]/10 px-3 text-[#008E9A] hover:bg-[#008E9A]/20" title="Add organization">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                            <datalist id="organizations">
                                @foreach($organizations as $org)
                                    <option value="{{ $org['full_affiliation'] }}" data-id="{{ $org['id'] }}">
                                @endforeach
                            </datalist>
                            <input type="hidden" id="organization_id" name="organization_id" value="{{ old('organization_id') }}">
                        </div>

                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label for="orcid" class="block text-sm font-medium text-gray-700">ORCID <span class="font-normal text-gray-400">(optional)</span></label>
                                <input id="orcid" name="orcid" type="text" class="{{ $registerInput }}" value="{{ old('orcid') }}">
                            </div>
                            <div class="space-y-1.5">
                                <label for="date_birth" class="block text-sm font-medium text-gray-700">Birth date <span class="font-normal text-gray-400">(optional)</span></label>
                                <input id="date_birth" name="date_birth" type="date" class="{{ $registerInput }}" value="" max="{{ now()->subYears(10)->format('Y-m-d') }}">
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input id="password" name="password" type="password" required autocomplete="new-password" class="{{ $registerInput }}" placeholder="At least 6 characters">
                        </div>

                        <div class="space-y-1.5">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700">Confirm password</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="{{ $registerInput }}">
                        </div>
                    </div>

                    @php
                        $termsUrl = config('legal.terms_url');
                        $privacyUrl = config('legal.privacy_url');
                    @endphp
                    @if ($termsUrl || $privacyUrl)
                        <div class="mt-5">
                            <label for="accept_legal" class="flex items-start gap-2 text-sm text-gray-600">
                                <input id="accept_legal" name="accept_legal" type="checkbox" value="1" required
                                    {{ old('accept_legal') ? 'checked' : '' }}
                                    class="mt-0.5 h-4 w-4 rounded border-gray-300 text-[#008E9A] focus:ring-[#008E9A]">
                                <span>
                                    I have read and agree to the
                                    @if ($termsUrl)
                                        <a href="{{ $termsUrl }}" target="_blank" rel="noopener" class="font-medium text-[#008E9A] hover:text-[#00727d]">Terms of Service</a>
                                    @endif
                                    @if ($termsUrl && $privacyUrl) and @endif
                                    @if ($privacyUrl)
                                        <a href="{{ $privacyUrl }}" target="_blank" rel="noopener" class="font-medium text-[#008E9A] hover:text-[#00727d]">Privacy Policy</a>
                                    @endif
                                </span>
                            </label>
                            @if ($errors->has('accept_legal'))
                                <p class="mt-1 text-xs text-red-500">{{ $errors->first('accept_legal') }}</p>
                            @endif
                        </div>
                    @endif

                    <div>
                        <button type="submit" id="registerSubmitButton"
                            class="flex w-full justify-center rounded-lg bg-[#008E9A] px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#00727d] focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#008E9A]">Create account</button>
                    </div>

                    <ul>
                        <li class="text-xs mt-2">
                            <a href="/login" class="font-medium text-[#008E9A] hover:text-[#00727d]">Have an account? Sign in</a>
                        </li>
                        @if ($errors->has('email'))
                            <li class="text-red-500 text-xs mt-2">{{ $errors->first('email') }}</li>
                        @endif

                        @if ($errors->has('password'))
                            <li class="text-red-500 text-xs mt-2">{{ $errors->first('password') }}</li>
                        @endif

                        @if ($errors->has('organization_name'))
                            <li class="text-red-500 text-xs mt-2">{{ $errors->first('organization_name') }}</li>
                        @endif

                    </ul>
                </form>
                </div>
            </div>
        </div>

        <!-- Organization Modal -->
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40" id="register_organization_form_modal" style="display:none;">
            <div class="bg-white rounded-lg shadow-lg w-full max-w-md max-h-[90vh] flex flex-col">
                <div class="p-6 border-b border-gray-200">
                    <button id="register_organization_form_close_btn" type="button" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times"></i>
                    </button>
                    <h2 class="text-lg font-bold mb-4 text-[#008E9A]">Add New Organization</h2>
                </div>
                <div class="flex-1 overflow-y-auto p-6">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Organization Name*</label>
                            <input type="text" id="new_organization_name" 
                                class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm"
                                required />
                            <div id="register_new_organization_name_status" class="mt-1 text-sm hidden"></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Organization Type*</label>
                            <input type="text" id="new_organization_type" 
                                class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm"
                                placeholder="Select or enter organization type"
                                list="organization_types" required/>
                            <datalist id="organization_types">
                                <option value="Government Agency">Government Agency</option>
                                <option value="Research Institute">Research Institute</option>
                                <option value="University">University</option>
                                <option value="Non-Profit Organization">Non-Profit Organization</option>
                                <option value="Private Company">Private Company</option>
                                <option value="Zoo">Zoo</option>
                                <option value="Wildlife Sanctuary">Wildlife Sanctuary</option>
                                <option value="Veterinary Clinic">Veterinary Clinic</option>
                                <option value="Laboratory">Laboratory</option>
                                <option value="Conservation Organization">Conservation Organization</option>
                                <option value="National Park">National Park</option>
                                <option value="Game Reserve">Game Reserve</option>
                                <option value="Museum">Museum</option>
                                <option value="Hospital">Hospital</option>
                                <option value="Pharmaceutical Company">Pharmaceutical Company</option>
                                <option value="Biotechnology Company">Biotechnology Company</option>
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Country*</label>
                            <input type="text" id="new_organization_country" 
                                class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm"
                                placeholder="Select or enter country"
                                list="countries" required/>
                            <datalist id="countries">
                                @foreach($countries as $country)
                                    <option value="{{ $country->name }}">{{ $country->name }}</option>
                                @endforeach
                            </datalist>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">City</label>
                            <input type="text" id="new_organization_city" class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Region/Province</label>
                            <input type="text" id="new_organization_region" class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Address</label>
                            <textarea id="new_organization_address" rows="2" class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Website</label>
                            <input type="url" id="new_organization_website" class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Description</label>
                            <textarea id="new_organization_description" rows="3" class="mt-1 block w-full rounded-lg border-0 py-2 px-3 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-[#008E9A] sm:text-sm"></textarea>
                        </div>
                        <p class="text-sm text-gray-500">* Required fields</p>
                    </div>
                </div>
                <div class="p-6 border-t border-gray-200 bg-gray-50">
                    <div class="flex justify-end space-x-3">
                        <button type="button" id="register_organization_cancel_btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                            Cancel
                        </button>
                        <button type="button" id="register_organization_save_btn" class="px-4 py-2 text-sm font-medium text-white bg-[#008E9A] border border-transparent rounded-md hover:bg-[#00727d] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#008E9A]">
                            Add Organization
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <script>
        // Store organization data for validation
        const organizations = @json($organizations);
        
        const organizationInput = document.getElementById('organization_name');
        const organizationIdInput = document.getElementById('organization_id');
        const form = document.getElementById('registerForm');
        const submitButton = document.getElementById('registerSubmitButton');

        // Store new organization data for form submission
        let newOrganizationData = null;

        // Organization modal functionality
        const organizationModal = document.getElementById('register_organization_form_modal');
        const organizationBtn = document.getElementById('register_organization_form_btn');
        const organizationCloseBtn = document.getElementById('register_organization_form_close_btn');
        const organizationCancelBtn = document.getElementById('register_organization_cancel_btn');
        const organizationSaveBtn = document.getElementById('register_organization_save_btn');
        const newOrganizationNameInput = document.getElementById('new_organization_name');
        const newOrganizationNameStatus = document.getElementById('register_new_organization_name_status');

        let registerOrganizationNameStatus = 'empty';
        let registerOrganizationNameCheckTimer = null;

        function titleCaseWords(value) {
            const lowerWords = new Set([
                'and', 'or', 'nor', 'but', 'yet', 'so', 'for',
                'of', 'in', 'on', 'at', 'by', 'to', 'from', 'with', 'without', 'as', 'per', 'via',
                'a', 'an', 'the',
            ]);

            return (value || '')
                .toString()
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim()
                .split(' ')
                .filter(Boolean)
                .map((word, index) => {
                    if (index > 0 && lowerWords.has(word)) {
                        return word;
                    }

                    return word.charAt(0).toUpperCase() + word.slice(1);
                })
                .join(' ');
        }

        function setRegisterOrganizationSaveBlocked(blocked) {
            organizationSaveBtn.disabled = blocked;
            organizationSaveBtn.classList.toggle('opacity-50', blocked);
            organizationSaveBtn.classList.toggle('cursor-not-allowed', blocked);
        }

        function renderRegisterOrganizationNameStatus(payload) {
            const status = payload && payload.status ? payload.status : 'empty';
            const suggestions = Array.isArray(payload?.suggestions) ? payload.suggestions : [];

            registerOrganizationNameStatus = status;

            if (status === 'empty') {
                newOrganizationNameStatus.classList.add('hidden');
                newOrganizationNameStatus.textContent = '';
                setRegisterOrganizationSaveBlocked(false);
                return;
            }

            newOrganizationNameStatus.classList.remove('hidden');

            if (status === 'exact') {
                newOrganizationNameStatus.className = 'mt-1 text-sm text-red-700';
                newOrganizationNameStatus.innerHTML = '<i class="fa-solid fa-circle-xmark mr-1"></i>Name already exists. Go back and choose it from dropdown.';
                setRegisterOrganizationSaveBlocked(true);
                return;
            }

            if (status === 'similar') {
                const similarTo = suggestions[0] || payload?.match || '';
                newOrganizationNameStatus.className = 'mt-1 text-sm text-yellow-800';
                newOrganizationNameStatus.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1"></i>Input is similar to "${similarTo}" option.`;
                setRegisterOrganizationSaveBlocked(false);
                return;
            }

            newOrganizationNameStatus.className = 'mt-1 text-sm text-green-700';
            newOrganizationNameStatus.innerHTML = '<i class="fa-solid fa-plus mr-1"></i>Name is available.';
            setRegisterOrganizationSaveBlocked(false);
        }

        function runRegisterOrganizationNameCheck(value) {
            fetch('/register/validation/organization-name', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ value }),
            })
                .then((response) => response.json())
                .then((data) => renderRegisterOrganizationNameStatus(data))
                .catch(() => renderRegisterOrganizationNameStatus({ status: 'empty' }));
        }

        function resetRegisterOrganizationModalValidation() {
            registerOrganizationNameStatus = 'empty';
            newOrganizationNameStatus.classList.add('hidden');
            newOrganizationNameStatus.textContent = '';
            setRegisterOrganizationSaveBlocked(false);
        }

        function showOrganizationModal() {
            resetRegisterOrganizationModalValidation();
            organizationModal.style.display = 'flex';
        }

        function hideOrganizationModal() {
            organizationModal.style.display = 'none';
            // Clear form fields
            document.getElementById('new_organization_name').value = '';
            document.getElementById('new_organization_type').value = '';
            document.getElementById('new_organization_country').value = '';
            document.getElementById('new_organization_city').value = '';
            document.getElementById('new_organization_region').value = '';
            document.getElementById('new_organization_address').value = '';
            document.getElementById('new_organization_website').value = '';
            document.getElementById('new_organization_description').value = '';
            resetRegisterOrganizationModalValidation();
        }

        function saveNewOrganization() {
            const name = titleCaseWords(document.getElementById('new_organization_name').value.trim());
            const type = document.getElementById('new_organization_type').value;
            const country = document.getElementById('new_organization_country').value;
            const city = document.getElementById('new_organization_city').value;
            const region = document.getElementById('new_organization_region').value;
            const address = document.getElementById('new_organization_address').value;
            const website = document.getElementById('new_organization_website').value;
            const description = document.getElementById('new_organization_description').value;

            if (!name) {
                alert('Organization name is required');
                return;
            }

            if (registerOrganizationNameStatus === 'exact') {
                alert('An organization with this name already exists. Select it from the list on the registration form instead.');
                return;
            }

            document.getElementById('new_organization_name').value = name;

            // Store the new organization data
            newOrganizationData = {
                name: name,
                type: type,
                country: country,
                city: city,
                region: region,
                address: address,
                website: website,
                description: description
            };

            // Update the organization input with the new name
            const fullAffiliation = country ? `${name}, ${country}` : name;
            organizationInput.value = fullAffiliation;
            organizationIdInput.value = ''; // Clear existing ID since this is a new organization

            hideOrganizationModal();
        }

        // Event listeners for organization modal
        organizationBtn.addEventListener('click', showOrganizationModal);
        organizationCloseBtn.addEventListener('click', hideOrganizationModal);
        organizationCancelBtn.addEventListener('click', hideOrganizationModal);
        organizationSaveBtn.addEventListener('click', saveNewOrganization);

        newOrganizationNameInput.addEventListener('input', function () {
            const formattedForCheck = titleCaseWords(this.value);
            clearTimeout(registerOrganizationNameCheckTimer);
            registerOrganizationNameCheckTimer = setTimeout(() => {
                runRegisterOrganizationNameCheck(formattedForCheck);
            }, 350);
        });

        newOrganizationNameInput.addEventListener('blur', function () {
            const formatted = titleCaseWords(this.value);
            this.value = formatted;
            runRegisterOrganizationNameCheck(formatted);
        });

        // Validate organization input
        function validateOrganization() {
            const inputValue = organizationInput.value.trim();
            if (inputValue === '') {
                organizationIdInput.value = '';
                return true;
            }
            
            // Check if this is a newly created organization
            if (newOrganizationData && newOrganizationData.name) {
                const fullAffiliation = newOrganizationData.country ? `${newOrganizationData.name}, ${newOrganizationData.country}` : newOrganizationData.name;
                if (inputValue === fullAffiliation) {
                    organizationIdInput.value = ''; // Clear existing ID since this is a new organization
                    return true;
                }
            }
            
            const matchingOrg = organizations.find(org => org.full_affiliation === inputValue);
            if (matchingOrg) {
                organizationIdInput.value = matchingOrg.id;
                return true;
            } else {
                organizationIdInput.value = '';
                return false;
            }
        }

        // Add event listeners
        organizationInput.addEventListener('input', validateOrganization);
        organizationInput.addEventListener('change', function() {
            if (!validateOrganization()) {
                // Check if we have new organization data
                if (!newOrganizationData || !newOrganizationData.name) {
                    organizationInput.setCustomValidity('Please select a valid organization from the list or create a new one');
                } else {
                    organizationInput.setCustomValidity('');
                }
            } else {
                organizationInput.setCustomValidity('');
            }
        });

        // Form submission validation and handling
        form.addEventListener('submit', function(e) {
            let isValid = true;
            
            // Validate organization
            if (organizationInput.value.trim() !== '') {
                if (!validateOrganization()) {
                    // Check if we have new organization data
                    if (!newOrganizationData || !newOrganizationData.name) {
                        organizationInput.setCustomValidity('Please select a valid organization from the list or create a new one');
                        isValid = false;
                    } else {
                        organizationInput.setCustomValidity('');
                    }
                } else {
                    organizationInput.setCustomValidity('');
                }
            }
            
            if (!isValid) {
                e.preventDefault();
                return;
            }

            // Add hidden inputs for new organization data
            if (newOrganizationData) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'new_organization_data';
                hiddenInput.value = JSON.stringify(newOrganizationData);
                form.appendChild(hiddenInput);
            }

            submitButton.disabled = true;
            submitButton.classList.add('cursor-not-allowed', 'opacity-75');
            submitButton.textContent = 'Registering...';
        });

        // Initialize with old values if they exist
        if (organizationInput.value) {
            validateOrganization();
        }

        // Birth date validation
        const birthDateInput = document.getElementById('date_birth');
        const maxDate = new Date();
        maxDate.setFullYear(maxDate.getFullYear() - 10);
        birthDateInput.max = maxDate.toISOString().split('T')[0];

        birthDateInput.addEventListener('change', function() {
            if (!this.value) {
                this.setCustomValidity('');
                return;
            }

            const selectedDate = new Date(this.value);
            const minDate = new Date();
            minDate.setFullYear(minDate.getFullYear() - 10);
            
            if (selectedDate > minDate) {
                this.setCustomValidity('You must be at least 10 years old to register.');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</x-layout-plain>
