<form method="POST" action="{{ route('projects.update', ['project' => $project->id, 'section' => 'general']) }}"
    class="space-y-6">
    @csrf
    @method('PATCH')
    <input type="hidden" name="section" value="general">

    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit General Information</h2>
            <p class="mt-2 text-sm text-gray-600">Update the project general information below</p>
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

                    <x-forms.field label="Project Code:" name="code">
                        <x-forms.text-input id="code" name="code" value="{{ $project->code ?? old('code') }}"
                            required disabled
                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200 bg-gray-100">
                        </x-forms.text-input>
                        <p class="text-xs text-gray-500 mt-1">Project codes are auto-generated and cannot be changed</p>
                    </x-forms.field>

                    <x-forms.field label="Project code alias (optional):" name="alias_code">
                        <x-forms.text-input id="alias_code" name="alias_code" maxlength="255"
                            value="{{ old('alias_code', $project->alias_code ?? '') }}"
                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                            placeholder="Optional custom/legacy code for this project">
                        </x-forms.text-input>
                    </x-forms.field>

                    <x-forms.field label="Project Type:" name="type">
                        <x-forms.select-input id="type" name="type" required
                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            <option value="PhD project"
                                {{ (isset($project) && $project->type === 'PhD project') || old('type') === 'PhD project' ? 'selected' : '' }}>
                                PhD project</option>
                            <option value="MSc project"
                                {{ (isset($project) && $project->type === 'MSc project') || old('type') === 'MSc project' ? 'selected' : '' }}>
                                MSc project</option>
                            <option value="Research assignment"
                                {{ (isset($project) && $project->type === 'Research assignment') || old('type') === 'Research assignment' ? 'selected' : '' }}>
                                Research assignment</option>
                            <option value="Publication-related project"
                                {{ (isset($project) && $project->type === 'Publication-related project') || old('type') === 'Publication-related project' ? 'selected' : '' }}>
                                Publication-related project</option>
                        </x-forms.select-input>
                    </x-forms.field>

                    <x-forms.field label="Start Date:" name="date_started">
                        <x-forms.date-input id="date_started" name="date_started"
                            value="{{ $project->date_started ?? old('date_started') }}" required
                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        </x-forms.date-input>
                    </x-forms.field>

                    <x-forms.field label="Intended End Date:" name="date_end_intended">
                        <x-forms.date-input id="date_end_intended" name="date_end_intended"
                            value="{{ $project->date_end_intended ?? old('date_end_intended') }}"
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
                    <x-forms.textarea id="title" name="title" :value="old('title', $project->title ?? '')" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.textarea>
                </x-forms.field>

                <x-forms.field label="Brief description:" name="description">
                    <x-forms.textarea id="description" name="description" :value="old('description', $project->description ?? ($project->notes ?? ''))" rows="4" required
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.textarea>
                </x-forms.field>

                <x-forms.field label="Ethics Reference:" name="ethics_ref">
                    <x-forms.text-input id="ethics_ref" name="ethics_ref"
                        value="{{ $project->ethics_ref ?? old('ethics_ref') }}"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.text-input>
                </x-forms.field>
            </div>
        </div>

        <!-- Navigation Buttons -->
        <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-between rounded-b-xl border-t border-gray-200">
            <div class="flex space-x-4">
            </div>

            <button type="submit"
                class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                Save Changes
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