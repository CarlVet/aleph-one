<form action="{{ route('projects.store') }}" method="POST">
    @csrf
    <input type="hidden" name="step" value="3">

    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
        <div class="text-center mb-6">
            <h2 class="text-2xl font-bold text-gray-800">Funding Sources</h2>
            <p class="mt-2 text-sm text-gray-600">Add funding sources for your project below</p>
        </div>

        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
            <div class="flex items-center space-x-2">
                <i class="fas fa-money-bill-wave text-blue-500 text-xl"></i>
                <h2 class="text-lg font-semibold text-gray-800">Funding Sources</h2>
            </div>

            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                <div class="flex items-center space-x-2">
                    <i class="fas fa-info-circle text-blue-500"></i>
                    <div>
                        <p class="text-sm font-medium text-blue-800">Optional Step</p>
                        <p class="text-sm text-blue-600">Funding sources are optional. You can skip this step if no
                            funding information is available.</p>
                    </div>
                </div>
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

            <div id="funding-sources-container">
                <div class="funding-source-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-forms.field label="Funding Source:" name="funding_sources[0][source]">
                            <x-forms.text-input id="funding_sources_0_source" name="funding_sources[0][source]"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.text-input>
                        </x-forms.field>

                        <x-forms.field label="Recipient:" name="funding_sources[0][recipient_id]">
                            <x-forms.select-input id="funding_sources_0_recipient_id"
                                name="funding_sources[0][recipient_id]"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                <option value="">Select recipient</option>
                                @foreach ($teamMembers as $index => $member)
                                    <option value="{{ $member['person_id'] ?? 'new_' . $index }}">
                                        {{ $member['title'] }} {{ $member['first_name'] }} {{ $member['last_name'] }}
                                        @if (isset($member['person_id']) && $member['person_id'])
                                            (Existing)
                                        @else
                                            (New)
                                        @endif
                                    </option>
                                @endforeach
                            </x-forms.select-input>
                        </x-forms.field>

                        <x-forms.field label="Amount:" name="funding_sources[0][amount]">
                            <x-forms.text-input id="funding_sources_0_amount" name="funding_sources[0][amount]"
                                type="number" step="0.01"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.text-input>
                        </x-forms.field>

                        <x-forms.field label="Currency:" name="funding_sources[0][currency]">
                            <input list="currency_options" id="funding_sources_0_currency"
                                name="funding_sources[0][currency]"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                                placeholder="Select or type currency" />

                            <datalist id="currency_options">
                                <option value="USD"> <!-- US Dollar -->
                                <option value="EUR"> <!-- Euro -->
                                <option value="GBP"> <!-- British Pound -->
                                <option value="JPY"> <!-- Japanese Yen -->
                                <option value="AUD"> <!-- Australian Dollar -->
                                <option value="CAD"> <!-- Canadian Dollar -->
                                <option value="CHF"> <!-- Swiss Franc -->
                                <option value="CNY"> <!-- Chinese Yuan -->
                                <option value="INR"> <!-- Indian Rupee -->
                                <option value="BRL"> <!-- Brazilian Real -->
                                <option value="ZAR"> <!-- South African Rand -->
                                <option value="KES"> <!-- Kenyan Shilling -->
                                <option value="NGN"> <!-- Nigerian Naira -->
                                <option value="MXN"> <!-- Mexican Peso -->
                                <option value="SEK"> <!-- Swedish Krona -->
                                <option value="NOK"> <!-- Norwegian Krone -->
                                <option value="DKK"> <!-- Danish Krone -->
                                <option value="NZD"> <!-- New Zealand Dollar -->
                                <option value="SGD"> <!-- Singapore Dollar -->
                            </datalist>
                        </x-forms.field>


                        <x-forms.field label="Reference:" name="funding_sources[0][reference]">
                            <x-forms.text-input id="funding_sources_0_reference" name="funding_sources[0][reference]"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.text-input>
                        </x-forms.field>

                        <x-forms.field label="Start Date:" name="funding_sources[0][start_date]">
                            <x-forms.date-input id="funding_sources_0_start_date" name="funding_sources[0][start_date]"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.date-input>
                        </x-forms.field>

                        <x-forms.field label="End Date:" name="funding_sources[0][end_date]">
                            <x-forms.date-input id="funding_sources_0_end_date" name="funding_sources[0][end_date]"
                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                            </x-forms.date-input>
                        </x-forms.field>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <button type="button" onclick="addFundingSource()"
                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg shadow-md hover:shadow-lg border border-green-600">
                    <i class="fas fa-plus mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Add Funding Source
                </button>
            </div>
        </div>

        <!-- Action Buttons -->
        <div
            class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-between rounded-b-xl border-t border-gray-200">
            <a href="{{ route('projects.create', ['step' => 2]) }}"
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

<script>
    function removeFundingSource(button) {
        const container = document.getElementById('funding-sources-container');
        const entry = button.closest('.funding-source-entry');
        if (!container || !entry) {
            return;
        }

        if (container.querySelectorAll('.funding-source-entry').length <= 1) {
            return;
        }

        entry.remove();
    }

    function addFundingSource() {
        const container = document.getElementById('funding-sources-container');
        const entries = container.getElementsByClassName('funding-source-entry');
        const newIndex = entries.length;

        const template = `
        <div class="funding-source-entry space-y-4 p-4 bg-gray-50 rounded-lg border border-gray-200 mt-4">
            <div class="flex items-center justify-between">
                <h4 class="text-sm font-semibold text-gray-700">Additional funding source</h4>
                <button type="button" onclick="removeFundingSource(this)"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-red-500 transition hover:bg-red-50 hover:text-red-700"
                    title="Remove funding source" aria-label="Remove funding source">
                    <i class="fas fa-trash-alt text-sm"></i>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <x-forms.field label="Funding Source:" name="funding_sources[${newIndex}][source]">
                    <x-forms.text-input id="funding_sources_${newIndex}_source" name="funding_sources[${newIndex}][source]"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.text-input>
                </x-forms.field>

                <x-forms.field label="Recipient:" name="funding_sources[${newIndex}][recipient_id]">
                    <x-forms.select-input id="funding_sources_${newIndex}_recipient_id" name="funding_sources[${newIndex}][recipient_id]"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                        <option value="">Select recipient</option>
                        @foreach ($teamMembers as $index => $member)
                            <option value="{{ $member['person_id'] ?? 'new_' . $index }}">
                                {{ $member['title'] }} {{ $member['first_name'] }} {{ $member['last_name'] }}
                                @if (isset($member['person_id']) && $member['person_id'])
                                    (Existing)
                                @else
                                    (New)
                                @endif
                            </option>
                        @endforeach
                    </x-forms.select-input>
                </x-forms.field>

                <x-forms.field label="Amount:" name="funding_sources[${newIndex}][amount]">
                    <x-forms.text-input id="funding_sources_${newIndex}_amount" name="funding_sources[${newIndex}][amount]" type="number" step="0.01"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.text-input>
                </x-forms.field>

                <x-forms.field label="Currency:" name="funding_sources[${newIndex}][currency]">
                    <input list="currency_options_${newIndex}" id="funding_sources_${newIndex}_currency"
                        name="funding_sources[${newIndex}][currency]"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"
                        placeholder="Select or type currency" />

                    <datalist id="currency_options_${newIndex}">
                        <option value="USD"> <!-- US Dollar -->
                        <option value="EUR"> <!-- Euro -->
                        <option value="GBP"> <!-- British Pound -->
                        <option value="JPY"> <!-- Japanese Yen -->
                        <option value="AUD"> <!-- Australian Dollar -->
                        <option value="CAD"> <!-- Canadian Dollar -->
                        <option value="CHF"> <!-- Swiss Franc -->
                        <option value="CNY"> <!-- Chinese Yuan -->
                        <option value="INR"> <!-- Indian Rupee -->
                        <option value="BRL"> <!-- Brazilian Real -->
                        <option value="ZAR"> <!-- South African Rand -->
                        <option value="KES"> <!-- Kenyan Shilling -->
                        <option value="NGN"> <!-- Nigerian Naira -->
                        <option value="MXN"> <!-- Mexican Peso -->
                        <option value="SEK"> <!-- Swedish Krona -->
                        <option value="NOK"> <!-- Norwegian Krone -->
                        <option value="DKK"> <!-- Danish Krone -->
                        <option value="NZD"> <!-- New Zealand Dollar -->
                        <option value="SGD"> <!-- Singapore Dollar -->
                    </datalist>
                </x-forms.field>

                <x-forms.field label="Reference:" name="funding_sources[${newIndex}][reference]">
                    <x-forms.text-input id="funding_sources_${newIndex}_reference" name="funding_sources[${newIndex}][reference]"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.text-input>
                </x-forms.field>

                <x-forms.field label="Start Date:" name="funding_sources[${newIndex}][start_date]">
                    <x-forms.date-input id="funding_sources_${newIndex}_start_date" name="funding_sources[${newIndex}][start_date]"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.date-input>
                </x-forms.field>

                <x-forms.field label="End Date:" name="funding_sources[${newIndex}][end_date]">
                    <x-forms.date-input id="funding_sources_${newIndex}_end_date" name="funding_sources[${newIndex}][end_date]"
                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                    </x-forms.date-input>
                </x-forms.field>
            </div>
        </div>
    `;

        container.insertAdjacentHTML('beforeend', template);
    }
</script>
