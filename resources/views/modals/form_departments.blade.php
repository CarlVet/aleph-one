<div>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <!-- jQuery -->
        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <!-- Selectize -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.3/js/standalone/selectize.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/selectize.js/0.13.3/css/selectize.default.min.css">
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <style>
            .selectize-dropdown {
                z-index: 9999 !important;
            }

            .modal-content {
                overflow: visible !important;
            }

            .modal {
                overflow: visible !important;
            }
        </style>

        <x-forms.form method="POST" action="{{ route('departments.store') }}">
            @csrf
            @php
                $organizationGroups = ($organizationsByCountry ?? collect());
                if ($organizationGroups->isEmpty() && !empty($organizations)) {
                    $organizationGroups = collect(['Unassigned Country' => collect($organizations)]);
                }
            @endphp

            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Department Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new department</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        @php
                            $defaultDepartmentTypes = collect([
                                'Academic',
                                'Administrative',
                                'Bioinformatics',
                                'Clinical',
                                'Diagnostic',
                                'Epidemiology',
                                'Field Operations',
                                'Molecular Biology',
                                'Research',
                                'Veterinary',
                            ]);

                            $departmentTypeOptions = $defaultDepartmentTypes
                                ->merge($departmentTypes ?? collect())
                                ->map(fn ($value) => trim((string) $value))
                                ->filter()
                                ->unique(fn ($value) => mb_strtolower($value))
                                ->sortBy(fn ($value) => mb_strtolower($value))
                                ->values();
                        @endphp

                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-building text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Department Information</h2>
                            </div>

                            <x-forms.field label="Department Name:" name="department_name">
                                <x-forms.text-input
                                    id="department_name"
                                    name="department_name"
                                    type="text"
                                    required
                                    placeholder="e.g., Molecular Biology Department"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                                <div id="department_name_status" class="mt-1 text-sm hidden"></div>
                            </x-forms.field>

                            <x-forms.field label="Department Type:" name="department_type">
                                <x-forms.select-input
                                    id="department_type"
                                    name="department_type"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select or enter department type</option>
                                    @foreach ($departmentTypeOptions as $departmentType)
                                        <option value="{{ $departmentType }}">{{ $departmentType }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Building:" name="building">
                                <x-forms.text-input
                                    id="building"
                                    name="building"
                                    type="text"
                                    placeholder="e.g., Building A"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                            </x-forms.field>

                            <x-forms.field label="Organization:" name="organization_id">
                                <x-forms.select-input
                                    id="organization_id"
                                    name="organization_id"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select organization</option>
                                    @foreach($organizationGroups as $countryName => $countryOrganizations)
                                        <optgroup label="{{ $countryName }}">
                                            @foreach($countryOrganizations as $org)
                                                <option value="{{ $org->id }}">{{ $org->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Description:" name="description">
                                <x-forms.textarea
                                    id="description"
                                    name="description"
                                    placeholder="e.g., Responsible for molecular diagnostics and assay development"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                            </x-forms.field>
                        </div>

                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Instructions</h2>
                            </div>

                            <div class="space-y-4 text-sm text-gray-600">
                                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-400">
                                    <h3 class="font-semibold text-blue-800 mb-2">Required Fields</h3>
                                    <ul class="space-y-1 text-blue-700">
                                        <li>• <strong>Department Name:</strong> Enter a unique department name</li>
                                        <li>• <strong>Organization:</strong> Select the parent organization</li>
                                    </ul>
                                </div>

                                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-400">
                                    <h3 class="font-semibold text-green-800 mb-2">Optional Fields</h3>
                                    <ul class="space-y-1 text-green-700">
                                        <li>• <strong>Department Type:</strong> Select or create a type value</li>
                                        <li>• <strong>Building:</strong> Add building reference when available</li>
                                        <li>• <strong>Description:</strong> Add useful notes for context</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit
                        id="department_submit_btn"
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Department
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        @if (session('success'))
            <div id="departmentSuccessMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="departmentErrorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script src="/js/create-departments.js"></script>
    </div>
</div> 