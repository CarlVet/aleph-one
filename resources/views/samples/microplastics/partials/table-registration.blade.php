<div x-show="registerMode === 'table'" x-cloak class="mt-6">
    <x-forms.form method="POST" action="/samples/microplastics">
        @csrf
        <input type="hidden" name="register_mode" value="table">
        <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
            <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Microplastics Table Registration</h2>
                        <p class="mt-2 text-sm text-gray-600">Add one row per identification and register the whole batch in a single transaction.</p>
                    </div>
                    <button type="button" id="microplastics-table-add-row"
                        class="inline-flex items-center justify-center rounded-xl border border-blue-600 bg-gradient-to-r from-blue-500 to-blue-600 px-4 py-2 text-sm font-medium text-white shadow-md transition-all duration-300 hover:scale-105 hover:shadow-lg">
                        <i class="fas fa-plus mr-2"></i>
                        Add row
                    </button>
                </div>

                <div class="overflow-x-auto rounded-2xl border border-gray-200">
                    <table class="min-w-full divide-y divide-gray-200 text-sm" id="microplastics-table-registration">
                        <thead class="bg-gray-50 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Tube</th>
                                <th class="px-4 py-3">Protocol</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Weight (g)</th>
                                <th class="px-4 py-3">r</th>
                                <th class="px-4 py-3">Feret</th>
                                <th class="px-4 py-3">Identification date</th>
                                <th class="px-4 py-3">Laboratory</th>
                                <th class="px-4 py-3">Identified by</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="microplastics-table-rows" class="divide-y divide-gray-100 bg-white"></tbody>
                    </table>
                </div>
            </div>

            <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                <x-forms.submit class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                    <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                    Save Table Registration
                </x-forms.submit>
            </div>
        </div>
    </x-forms.form>

    <script>
        window.microplasticsTableState = {
            tubes: @json($table_tube_options ?? []),
            protocols: @json(($microplastic_protocols ?? collect())->pluck('name')->values()),
            mpsTypes: @json(($mps_types ?? collect())->pluck('name')->values()),
            laboratories: @json(collect($laboratories_by_country ?? [])->flatten(1)->pluck('name')->values()),
            people: @json(($people ?? collect())->map(fn ($person) => ['id' => $person->id, 'label' => trim(($person->title ?? '').' '.$person->first_name.' '.$person->last_name)])->values()),
        };
    </script>
</div>
