<div>
    <x-forms.form id="parasiteSpeciesForm" method="POST" action="/parasites/species" enctype="multipart/form-data">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Left Column: Form Fields -->
            <div class="lg:col-span-2">
                <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="text-center mb-6">
                        <h2 class="text-xl font-bold text-gray-800">Create New Parasite Species</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the taxonomic details below to create a new parasite species</p>
                    </div>

                    <div class="space-y-4">

                        <!-- Scientific name input -->
                        <x-forms.field label="Scientific Name:" name="name_scientific">
                            <div class="relative">
                                <x-forms.text-input id="name_scientific" name="name_scientific" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                    placeholder="e.g., Rhipicephalus sanguineus, Amblyomma hebraeum"></x-forms.text-input>
                                <div id="name_scientific_error" class="hidden mt-1 text-sm text-red-600"></div>
                                <div id="name_scientific_success" class="hidden mt-1 text-sm text-green-600">
                                    <i class="fas fa-check-circle mr-1"></i>Available
                                </div>
                            </div>
                        </x-forms.field>

                        <!-- Genus input -->
                        <x-forms.field label="Genus:" name="genus">
                            <x-forms.text-input id="genus" name="genus"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Ixodes, Tenia"></x-forms.text-input>
                        </x-forms.field>

                        <!-- Family input -->
                        <x-forms.field label="Family:" name="family">
                            <x-forms.text-input id="family" name="family"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Ixodidae, Cestoda"></x-forms.text-input>
                        </x-forms.field>

                        <!-- Order input -->
                        <x-forms.field label="Order:" name="order">
                            <x-forms.text-input id="order" name="order"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Rhabditida"></x-forms.text-input>
                        </x-forms.field>

                        <!-- Class input -->
                        <x-forms.field label="Class:" name="class">
                            <x-forms.text-input id="class" name="class"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Arachnida"></x-forms.text-input>
                        </x-forms.field>

                        <!-- Phylum input -->
                        <x-forms.field label="Phylum:" name="phylum">
                            <x-forms.text-input id="phylum" name="phylum"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="e.g., Nematoda, Arthropoda"></x-forms.text-input>
                        </x-forms.field>
                    </div>

                    <div class="flex justify-end space-x-3 pt-4">
                        <button type="submit" id="submitBtn"
                            class="group relative inline-flex items-center justify-center px-6 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                            <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                            Create Species
                        </button>
                    </div>
                </div>
            </div>

            <!-- Right Column: Instructions -->
            <div class="lg:col-span-1">
                <div class="bg-gradient-to-br from-blue-50 to-indigo-50 p-6 rounded-xl shadow-sm border border-blue-100 sticky top-4">
                    <div class="space-y-6">
                        <!-- Header -->
                        <div class="text-center">
                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center mx-auto mb-3">
                                <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-2">Form Instructions</h3>
                            <p class="text-sm text-gray-600">Follow these guidelines to ensure accurate species data</p>
                        </div>

                        <!-- IUCN Red List Section -->
                        <div class="bg-white p-4 rounded-lg border border-blue-200">
                            <div class="flex items-start space-x-3">
                                <div class="w-8 h-8 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-globe text-red-600 text-sm"></i>
                                </div>
                                <div>
                                    <h4 class="font-medium text-gray-800 mb-2">Verify Taxonomy</h4>
                                    <p class="text-sm text-gray-600 mb-3">Verify the correct taxonomic classification using the NCBI Taxonomy Browser!</p>
                                    <a href="https://www.ncbi.nlm.nih.gov/taxonomy" target="_blank" rel="noopener noreferrer"
                                        class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition-colors duration-200">
                                        <i class="fas fa-external-link-alt mr-2"></i>
                                        Visit NCBI Taxonomy Browser
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Field Guidelines -->
                        <div class="space-y-4">
                            <div class="bg-white p-4 rounded-lg border border-blue-200">
                                <h4 class="font-medium text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-list-ul text-blue-600 mr-2"></i>
                                    Required Fields
                                </h4>
                                <ul class="text-sm text-gray-600 space-y-2">
                                    <li class="flex items-start">
                                        <span class="w-2 h-2 bg-red-500 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                        <span><strong>Scientific Name:</strong> Use binomial nomenclature (Genus species)</span>
                                    </li>
                                </ul>
                            </div>

                            <div class="bg-white p-4 rounded-lg border border-blue-200">
                                <h4 class="font-medium text-gray-800 mb-3 flex items-center">
                                    <i class="fas fa-lightbulb text-yellow-600 mr-2"></i>
                                    Best Practices
                                </h4>
                                <ul class="text-sm text-gray-600 space-y-2">
                                    <li class="flex items-start">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                        <span>Check for existing species before creating new entries</span>
                                    </li>
                                    <li class="flex items-start">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                        <span>Use proper capitalization for scientific names</span>
                                    </li>
                                    <li class="flex items-start">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                        <span>Fill in as many taxonomic levels as possible</span>
                                    </li>
                                    <li class="flex items-start">
                                        <span class="w-2 h-2 bg-blue-500 rounded-full mt-2 mr-2 flex-shrink-0"></span>
                                        <span>Use standardized common names when available</span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Real-time Validation Info -->
                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                <h4 class="font-medium text-gray-800 mb-2 flex items-center">
                                    <i class="fas fa-shield-alt text-green-600 mr-2"></i>
                                    Real-time Validation
                                </h4>
                                <p class="text-sm text-gray-600">The form will automatically check for duplicate common and scientific names as you type, helping prevent duplicate entries.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </x-forms.form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const nameScientificInput = document.getElementById('name_scientific');
    const submitBtn = document.getElementById('submitBtn');

    let nameScientificTimeout;
    let nameScientificStatus = 'empty';

    function toTitleCaseWords(value) {
        const lowerWords = new Set([
            'and', 'or', 'nor', 'but', 'yet', 'so', 'for',
            'of', 'in', 'on', 'at', 'by', 'to', 'from', 'with', 'without', 'as', 'per', 'via',
            'a', 'an', 'the'
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

    function formatScientificName(value) {
        const normalized = (value || '')
            .toString()
            .toLowerCase()
            .replace(/\s+/g, ' ')
            .trim();

        if (!normalized) {
            return '';
        }

        const parts = normalized.split(' ');
        parts[0] = parts[0].charAt(0).toUpperCase() + parts[0].slice(1);

        return parts.join(' ');
    }

    function checkDuplicate(field, value, errorElement, successElement) {
        if (!value.trim()) {
            errorElement.classList.add('hidden');
            successElement.classList.add('hidden');
            nameScientificStatus = 'empty';
            updateSubmitButton();
            return;
        }

        fetch('/parasites/species/check-duplicate', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                field: field,
                value: value
            })
        })
        .then(response => response.json())
        .then(data => {
            const status = data.status || (data.exists ? 'exact' : 'new');
            const suggestions = Array.isArray(data.suggestions) ? data.suggestions : [];

            if (status === 'exact') {
                errorElement.className = 'mt-1 text-sm text-red-600';
                errorElement.innerHTML = '<i class="fa-solid fa-circle-xmark mr-1"></i>Name already exists. Go back and choose it from dropdown.';
                errorElement.classList.remove('hidden');
                successElement.classList.add('hidden');
                nameScientificStatus = 'exact';
            } else if (status === 'similar') {
                const similarTo = suggestions[0] || '';
                errorElement.className = 'mt-1 text-sm text-yellow-800';
                errorElement.innerHTML = `<i class="fa-solid fa-triangle-exclamation mr-1"></i>Input is similar to "${similarTo}" option.`;
                errorElement.classList.remove('hidden');
                successElement.classList.add('hidden');
                nameScientificStatus = 'similar';
            } else {
                errorElement.classList.add('hidden');
                successElement.className = 'mt-1 text-sm text-green-600';
                successElement.innerHTML = '<i class="fa-solid fa-plus mr-1"></i>Name is available.';
                successElement.classList.remove('hidden');
                nameScientificStatus = 'new';
            }
            updateSubmitButton();
        })
        .catch(error => {
            console.error('Error checking duplicate:', error);
        });
    }

    function updateSubmitButton() {
        if (nameScientificStatus === 'exact') {
            submitBtn.disabled = true;
            submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            submitBtn.classList.remove('hover:scale-105');
        } else {
            submitBtn.disabled = false;
            submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            submitBtn.classList.add('hover:scale-105');
        }
    }

    nameScientificInput.addEventListener('input', function() {
        clearTimeout(nameScientificTimeout);
        const value = formatScientificName(this.value).trim();
        const errorElement = document.getElementById('name_scientific_error');
        const successElement = document.getElementById('name_scientific_success');

        errorElement.classList.add('hidden');
        successElement.classList.add('hidden');

        if (value.length >= 2) {
            nameScientificTimeout = setTimeout(() => {
                checkDuplicate('name_scientific', value, errorElement, successElement);
            }, 500);
        }
    });

    nameScientificInput.addEventListener('blur', function() {
        this.value = formatScientificName(this.value);
    });

    document.getElementById('parasiteSpeciesForm').addEventListener('submit', function(e) {
        if (nameScientificStatus === 'exact') {
            e.preventDefault();
            alert('Please fix the validation errors before submitting the form.');
        }
    });
});
</script>
