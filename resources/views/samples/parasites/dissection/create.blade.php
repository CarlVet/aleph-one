<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">

        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/parasites/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/parasites/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>

        <x-forms.form method="POST" action="/samples/parasites/dissection" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Ticks Dissection Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Select parasites and register dissected parasite samples (with optional pooling)</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-bug text-purple-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Parasite Selection</h2>
                            </div>

                            @include('partials.tube-badge-display-toggle')

                            <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                <x-forms.table-button id="parasites_btn"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-purple-500 to-violet-600 text-white rounded-lg shadow-md hover:shadow-lg border border-purple-600">
                                    <i class="fas fa-spider mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                    Select Parasites
                                </x-forms.table-button>
                                <span id="parasites_id_count" class="text-sm text-gray-600">(0 selected)</span>
                            </div>

                            <x-forms.field label="Selected parasites:" name="parasites_id[]">
                                <x-forms.select-input id="parasites_id" name="parasites_id[]" multiple required data-tube-badge-toggle="1"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach (($selected_parasites ?? []) as $parasite)
                                        <option value="{{ $parasite->id }}" selected
                                            data-code="{{ $parasite->code }}"
                                            data-alias-code="{{ $parasite->parasite_alias_code ?? '' }}"
                                            data-species-name="{{ $parasite->species_name ?? '' }}">{{ $parasite->code }}@if(filled($parasite->parasite_alias_code)) ({{ $parasite->parasite_alias_code }})@endif</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <x-forms.field label="Dissected parasite sample type(s):" name="parasite_sample_types">
                                <x-forms.select-input id="parasite_sample_types" name="parasite_sample_types[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach (($parasite_sample_types ?? []) as $type)
                                        <option value="{{ $type->name }}">{{ $type->name }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <div class="pt-4 border-t border-gray-100">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-layer-group text-purple-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Storage Mode</h2>
                                </div>

                                <div class="mt-3 space-y-3">
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="radio" name="storage_mode" value="individual" class="form-radio text-purple-600 focus:ring-purple-500" checked>
                                        <span class="text-sm font-medium text-gray-700">Create one tube for each new parasite sample</span>
                                    </label>
                                    <label class="flex items-center space-x-3 cursor-pointer">
                                        <input type="radio" name="storage_mode" value="pool" class="form-radio text-purple-600 focus:ring-purple-500">
                                        <span class="text-sm font-medium text-gray-700">Pool all new parasite samples into one pool and one tube</span>
                                    </label>
                                </div>
                            </div>

                            <div id="selected_dissection_assignment" class="space-y-4" style="display: none;">
                                <div class="flex flex-wrap items-center justify-between gap-3">
                                    <h3 class="text-md font-semibold text-gray-700">Output tube alias assignment</h3>
                                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                        <input type="checkbox" id="auto_alias_from_parent" class="h-4 w-4 rounded border-gray-300 text-purple-600 focus:ring-purple-500">
                                        <span>Auto-populate output tube alias from parasite tube alias</span>
                                    </label>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 overflow-x-auto">
                                    <table class="min-w-full text-sm">
                                        <thead>
                                            <tr class="text-left text-xs font-semibold uppercase tracking-wide text-gray-500">
                                                <th class="pb-2 pr-4">Parasite</th>
                                                <th class="pb-2 pr-4">Tube alias</th>
                                                <th class="pb-2 pr-4">Sample type</th>
                                                <th class="pb-2">Output tube alias</th>
                                            </tr>
                                        </thead>
                                        <tbody id="dissection_assignment_list" class="divide-y divide-gray-200">
                                        </tbody>
                                    </table>
                                </div>
                                <div id="pool_tube_alias_section" class="hidden rounded-lg border border-gray-200 bg-gray-50 p-4">
                                    <label class="mb-1 block text-xs font-semibold text-gray-600" for="pool_tube_alias">Pool tube alias</label>
                                    <input type="text" id="pool_tube_alias" name="pool_tube_alias" value="{{ old('pool_tube_alias') }}"
                                        class="w-full max-w-md rounded-lg border border-gray-200 px-3 py-2 text-sm"
                                        placeholder="Optional alias for the pooled output tube">
                                </div>
                            </div>
                        </div>

                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-info-circle text-purple-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Ancillary Information</h2>
                            </div>

                            <x-forms.field label="Dissection date (date processed):" name="date_processed">
                                <x-forms.date-input id="date_processed" name="date_processed" value="{{ now()->toDateString() }}" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.date-input>
                            </x-forms.field>

                            <x-forms.field label="Dissected by:" name="people_id">
                                @if ($can_assign_registrar)
                                    <x-forms.select-input id="people_id" name="people_id" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach (($people ?? []) as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) $person->id === (int) ($locked_registrar_people_id ?? 0))>{{ $person->title }} {{ $person->first_name }} {{ $person->last_name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <x-forms.select-input id="people_id_locked" name="people_id_locked" disabled
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach (($people ?? []) as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) $person->id === (int) ($locked_registrar_people_id ?? 0))>{{ $person->title }} {{ $person->first_name }} {{ $person->last_name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                    <input type="hidden" name="people_id" value="{{ $locked_registrar_people_id }}">
                                @endif
                            </x-forms.field>

                            <x-forms.field label="Dissected at (laboratory):" name="laboratory">
                                <div class="flex items-start gap-3">
                                    <div class="flex-1">
                                        <x-forms.select-input id="laboratory" name="laboratory" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach (($laboratories_by_country ?? []) as $country => $labs)
                                                <optgroup label="{{ $country }}">
                                                    @foreach ($labs as $lab)
                                                        <option value="{{ $lab['name'] }}">{{ $lab['name'] }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endforeach
                                        </x-forms.select-input>
                                    </div>
                                    <x-lookup.button id="laboratories_lookup_btn" title="Browse laboratories table" />
                                </div>
                            </x-forms.field>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-purple-500 to-violet-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-purple-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Register Dissection
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        @include('partials.lookup.laboratories-modal')

        <x-table-modal id="parasites_modal" title="Parasites" closeButtonId="parasites_close_btn">
            <div data-modal-content class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        @push('scripts')
            <script>
                window.dissectionOldAliasCodes = @json(old('tube_alias_codes', []));
                window.laboratoryLookupRows = @json($laboratory_lookup_rows ?? []);
            </script>
            <script src="/js/lookup-table.js?v={{ filemtime(public_path('js/lookup-table.js')) }}"></script>
            <script src="/js/create-ticks-dissection.js?v={{ filemtime(public_path('js/create-ticks-dissection.js')) }}"></script>
        @endpush
    </div>
</x-layout>

