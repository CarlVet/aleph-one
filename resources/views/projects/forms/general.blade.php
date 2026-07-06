<form action="{{ route('projects.store') }}" method="POST">
    @csrf
    <input type="hidden" name="step" value="1">

    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">General Information</h2>
            <p class="mt-2 text-sm text-gray-600">Enter the project general information below</p>
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

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Left Column -->
            <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <!-- General Information -->
                <div class="space-y-6">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                        <h2 class="text-lg font-semibold text-gray-800">Basic Information</h2>
                    </div>

                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-info-circle text-blue-500"></i>
                            <div>
                                <p class="text-sm font-medium text-blue-800">Project Code</p>
                                <p class="text-sm text-blue-600">This project will be assigned code: <span class="font-bold">{{ $nextCode ?? 'A1A1' }}</span></p>
                            </div>
                        </div>
                    </div>

                    <x-forms.field label="Project code alias (optional):" name="alias_code">
                        <x-forms.text-input id="alias_code" name="alias_code" type="text" maxlength="255"
                            value="{{ old('alias_code', session('project.general.alias_code')) }}"
                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Optional custom/legacy code for this project" />
                    </x-forms.field>

                    <x-forms.field label="Project Type:" name="project_type">
                        <input list="project_type_options" id="project_type" name="project_type" required
                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Select or type project type" />
                    
                        <datalist id="project_type_options">
                            <option value="PhD project">
                                <option value="MSc project">
                                <option value="BSc project">
                                <option value="Internship">
                                <option value="Research assignment">
                                <option value="Research collaboration">
                                <option value="Publication-related project">
                                <option value="Diagnostic development">
                                <option value="Field survey">
                                <option value="Surveillance project">
                                <option value="Capacity building">
                                <option value="Pilot study">
                                <option value="Training program">
                                <option value="Outreach activity">
                                <option value="Grant-funded project">
                                <option value="Personal interest">
                        </datalist>
                    </x-forms.field>
                    

                    <x-forms.field label="Start Date:" name="start_date">
                        <x-forms.date-input id="start_date" name="start_date" required
                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        </x-forms.date-input>
                    </x-forms.field>

                    <x-forms.field label="Intended End Date:" name="intended_end_date">
                        <x-forms.date-input id="intended_end_date" name="intended_end_date" required
                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        </x-forms.date-input>
                    </x-forms.field>
                </div>
            </div>

            <!-- Right Column -->
            <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-file-alt text-blue-500 text-xl"></i>
                    <h2 class="text-lg font-semibold text-gray-800">Project Details</h2>
                </div>

                <x-forms.field label="Project Title:" name="title">
                    <x-forms.textarea id="title" name="title" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.textarea>
                </x-forms.field>

                <x-forms.field label="Brief description:" name="description">
                    <x-forms.textarea id="description" name="description" rows="4" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.textarea>
                </x-forms.field>

                <x-forms.field label="Ethics Reference:" name="ethics_reference">
                    <x-forms.text-input id="ethics_reference" name="ethics_reference"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.text-input>
                </x-forms.field>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-between rounded-b-xl border-t border-gray-200">
            <div class="flex space-x-4">
            </div>

            <button type="submit"
                class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-arrow-right mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                Next Step
            </button>
        </div>
    </div>
</form>

<script>
    // Get the success and error message elements from the DOM
    const successMessageElement = document.getElementById('successMessage');
    const errorMessageElement = document.getElementById('errorMessage');

    // Show success message if it exists
    if (successMessageElement) {
        Swal.fire({
            icon: 'success',
            title: 'Success',
            text: successMessageElement.textContent,
        });
    }

    // Show error message if it exists
    if (errorMessageElement) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: errorMessageElement.textContent,
        });
    }
</script>
