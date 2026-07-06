<form method="POST" action="{{ route('projects.update', ['project' => $project->id, 'section' => 'funding']) }}"
    class="space-y-6">
    @csrf
    @method('PATCH')
    <input type="hidden" name="section" value="funding">

    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Edit Funding Sources</h2>
            <p class="mt-2 text-sm text-gray-600">Update the project funding sources below</p>
        </div>

        <!-- Save Reminder Message -->
        <div id="save-reminder" class="hidden bg-yellow-100 text-yellow-800 p-4 rounded-lg mb-4 text-sm">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span id="save-reminder-text"></span>
        </div>

        <div class="space-y-6">
            <div id="funding-sources">
                @foreach ($project->fundings as $index => $funding)
                    <div class="funding-source bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
                        <input type="hidden" name="funding[{{ $index }}][id]" value="{{ $funding->id }}">
                        <div class="flex justify-between items-center mb-4">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-money-bill-wave text-blue-500 text-xl"></i>
                                <h3 class="text-lg font-semibold text-gray-800">Funding Source {{ $index + 1 }}</h3>
                            </div>
                            <button type="button" onclick="markFundingForRemoval(this, {{ $funding->id }})"
                                class="text-red-500 hover:text-red-700">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Funding Source -->
                            <div>
                                <label for="funding[{{ $index }}][source]"
                                    class="block text-sm font-medium text-gray-700">Funding Source</label>
                                <input type="text" name="funding[{{ $index }}][source]"
                                    id="funding[{{ $index }}][source]" value="{{ $funding->source }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <!-- Funding Recipient -->
                            <div>
                                <label for="funding[{{ $index }}][recipient]"
                                    class="block text-sm font-medium text-gray-700">Funding Recipient</label>
                                <select name="funding[{{ $index }}][recipient]"
                                    id="funding[{{ $index }}][recipient]"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach ($people as $person)
                                        <option value="{{ $person->id }}"
                                            {{ $funding->recipient_id === $person->id ? 'selected' : '' }}>
                                            {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Funding Amount -->
                            <div>
                                <label for="funding[{{ $index }}][amount]"
                                    class="block text-sm font-medium text-gray-700">Funding Amount</label>
                                <div class="mt-1 relative rounded-md shadow-sm">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 sm:text-sm">R</span>
                                    </div>
                                    <input type="number" name="funding[{{ $index }}][amount]"
                                        id="funding[{{ $index }}][amount]" value="{{ $funding->amount }}"
                                        class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        step="0.01" min="0">
                                </div>
                            </div>

                            <!-- Currency -->
                            <div>
                                <label for="funding[{{ $index }}][currency]"
                                    class="block text-sm font-medium text-gray-700">Currency</label>
                                <select name="funding[{{ $index }}][currency]"
                                    id="funding[{{ $index }}][currency]"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="ZAR" {{ $funding->currency === 'ZAR' ? 'selected' : '' }}>ZAR
                                    </option>
                                    <option value="USD" {{ $funding->currency === 'USD' ? 'selected' : '' }}>USD
                                    </option>
                                    <option value="EUR" {{ $funding->currency === 'EUR' ? 'selected' : '' }}>EUR
                                    </option>
                                    <option value="GBP" {{ $funding->currency === 'GBP' ? 'selected' : '' }}>GBP
                                    </option>
                                </select>
                            </div>

                            <!-- Funding Reference -->
                            <div>
                                <label for="funding[{{ $index }}][reference]"
                                    class="block text-sm font-medium text-gray-700">Funding Reference</label>
                                <input type="text" name="funding[{{ $index }}][reference]"
                                    id="funding[{{ $index }}][reference]" value="{{ $funding->reference }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>

                        <!-- Funding Period -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <div>
                                <label for="funding[{{ $index }}][start_date]"
                                    class="block text-sm font-medium text-gray-700">Funding Start Date</label>
                                <input type="date" name="funding[{{ $index }}][start_date]"
                                    id="funding[{{ $index }}][start_date]"
                                    value="{{ $funding->start_date ? $funding->start_date->format('Y-m-d') : '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div>
                                <label for="funding[{{ $index }}][end_date]"
                                    class="block text-sm font-medium text-gray-700">Funding End Date</label>
                                <input type="date" name="funding[{{ $index }}][end_date]"
                                    id="funding[{{ $index }}][end_date]"
                                    value="{{ $funding->end_date ? $funding->end_date->format('Y-m-d') : '' }}"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>

            <button type="button" onclick="addFundingSource()"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-green-600">
                <i class="fas fa-plus mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                Add Funding Source
            </button>
        </div>

        <!-- Navigation Buttons -->
        <div
            class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-between rounded-b-xl border-t border-gray-200">
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
    function markFundingForRemoval(button, fundingId) {
        const fundingSource = button.closest('.funding-source');
        fundingSource.style.display = 'none';

        // Add hidden input for funding removal
        const hiddenInput = document.createElement('input');
        hiddenInput.type = 'hidden';
        hiddenInput.name = 'removed_fundings[]';
        hiddenInput.value = fundingId;
        fundingSource.appendChild(hiddenInput);

        // Show informational message
        showSaveReminder('Funding source marked for removal. Remember to save changes.');
    }

    function showSaveReminder(message) {
        const reminder = document.getElementById('save-reminder');
        const reminderText = document.getElementById('save-reminder-text');
        if (reminder && reminderText) {
            reminderText.textContent = message;
            reminder.classList.remove('hidden');
        }
    }

    function addFundingSource() {
        const fundingSources = document.getElementById('funding-sources');
        const index = fundingSources.children.length;

        const template = `
            <div class="funding-source bg-white p-6 rounded-xl shadow-sm border border-gray-100 mb-6">
                <div class="flex justify-between items-center mb-4">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-money-bill-wave text-blue-500 text-xl"></i>
                        <h3 class="text-lg font-semibold text-gray-800">Funding Source ${index + 1}</h3>
                    </div>
                    <button type="button" onclick="removeFundingSource(this)" class="text-red-500 hover:text-red-700">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Funding Source -->
                    <div>
                        <label for="funding[${index}][source]" class="block text-sm font-medium text-gray-700">Funding Source</label>
                        <input type="text" name="funding[${index}][source]" id="funding[${index}][source]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <!-- Funding Recipient -->
                    <div>
                        <label for="funding[${index}][recipient]" class="block text-sm font-medium text-gray-700">Funding Recipient</label>
                        <select name="funding[${index}][recipient]" id="funding[${index}][recipient]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @foreach ($people as $person)
                                <option value="{{ $person->id }}">{{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Funding Amount -->
                    <div>
                        <label for="funding[${index}][amount]" class="block text-sm font-medium text-gray-700">Funding Amount</label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <span class="text-gray-500 sm:text-sm">R</span>
                            </div>
                            <input type="number" name="funding[${index}][amount]" id="funding[${index}][amount]"
                                class="pl-7 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                step="0.01" min="0">
                        </div>
                    </div>

                    <!-- Currency -->
                    <div>
                        <label for="funding[${index}][currency]" class="block text-sm font-medium text-gray-700">Currency</label>
                        <select name="funding[${index}][currency]" id="funding[${index}][currency]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="ZAR">ZAR</option>
                            <option value="USD">USD</option>
                            <option value="EUR">EUR</option>
                            <option value="GBP">GBP</option>
                        </select>
                    </div>

                    <!-- Funding Reference -->
                    <div>
                        <label for="funding[${index}][reference]" class="block text-sm font-medium text-gray-700">Funding Reference</label>
                        <input type="text" name="funding[${index}][reference]" id="funding[${index}][reference]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>

                <!-- Funding Period -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label for="funding[${index}][start_date]" class="block text-sm font-medium text-gray-700">Funding Start Date</label>
                        <input type="date" name="funding[${index}][start_date]" id="funding[${index}][start_date]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="funding[${index}][end_date]" class="block text-sm font-medium text-gray-700">Funding End Date</label>
                        <input type="date" name="funding[${index}][end_date]" id="funding[${index}][end_date]"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>
                </div>

            </div>
        `;

        fundingSources.insertAdjacentHTML('beforeend', template);
    }

    function removeFundingSource(button) {
        if (confirm('Are you sure you want to remove this funding source?')) {
            button.closest('.funding-source').remove();
            // Update the numbering of remaining funding sources
            document.querySelectorAll('.funding-source').forEach((source, index) => {
                source.querySelector('h3').textContent = `Funding Source ${index + 1}`;
            });
        }
    }
</script>
