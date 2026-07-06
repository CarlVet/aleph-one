<div>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <x-forms.form method="POST" action="/microplastics_protocols" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Microplastics Identification Protocol - Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new microplastics identification protocol</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-flask text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">General Protocol Information</h2>
                            </div>

                            <x-forms.field label="Enter protocol name in full:" name="protocol_name">
                                <x-forms.textarea id="protocol_name" name="protocol_name" rows="1"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.textarea>
                            </x-forms.field>

                            <x-forms.field label="Select technique type:" name="protocol_new">
                                <x-forms.select-input id="protocol_new" name="protocol_new" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach (($microplastic_techniques ?? collect()) as $technique)
                                        <option value="{{ $technique->name }}">{{ $technique->name }}</option>
                                    @endforeach
                                    @if (($microplastic_techniques ?? collect())->isEmpty())
                                        <option value="Microplastics identification">Microplastics identification</option>
                                    @endif
                                </x-forms.select-input>
                            </x-forms.field>
                        </div>

                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-file-alt text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Protocol Document</h2>
                            </div>

                            <div class="mt-4">
                                <label for="protocol_pdf" class="block text-sm font-medium text-gray-700 mb-2">Upload Protocol Document</label>
                                <input type="file" id="protocol_pdf" name="protocol_pdf" accept=".pdf,.doc,.docx"
                                    class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Protocol
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>
    </div>
</div>
