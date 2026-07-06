<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <x-forms.form method="POST" action="/animals/species">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Create New Animal Species</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the taxonomic details below to create a new animal species</p>
                    </div>
                    
                    <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-dna text-green-500 text-xl"></i>
                            <h2 class="text-lg font-semibold text-gray-800">Taxonomic Information</h2>
                        </div>

                        <!-- Common name input -->
                        <x-forms.field label="Common Name:" name="name_common">
                            <x-forms.text-input id="name_common" name="name_common" required
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Lion, Elephant, Giraffe">
                        </x-forms.field>

                        <!-- Scientific name input -->
                        <x-forms.field label="Scientific Name:" name="name_scientific">
                            <x-forms.text-input id="name_scientific" name="name_scientific" required
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Panthera leo, Loxodonta africana">
                        </x-forms.field>

                        <!-- Genus input -->
                        <x-forms.field label="Genus:" name="genus">
                            <x-forms.text-input id="genus" name="genus"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Panthera, Loxodonta">
                        </x-forms.field>

                        <!-- Family input -->
                        <x-forms.field label="Family:" name="family">
                            <x-forms.text-input id="family" name="family"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Felidae, Elephantidae">
                        </x-forms.field>

                        <!-- Order input -->
                        <x-forms.field label="Order:" name="order">
                            <x-forms.text-input id="order" name="order"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Carnivora, Proboscidea">
                        </x-forms.field>

                        <!-- Class input -->
                        <x-forms.field label="Class:" name="class">
                            <x-forms.text-input id="class" name="class"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Mammalia">
                        </x-forms.field>

                        <!-- Phylum input -->
                        <x-forms.field label="Phylum:" name="phylum">
                            <x-forms.text-input id="phylum" name="phylum"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Chordata">
                        </x-forms.field>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Create Species
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

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
    </div>
</x-layout> 