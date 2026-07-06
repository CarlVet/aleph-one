<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">
        <!-- Create, Edit, Dashboard (Centered) -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/nucleic/sequences/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/nucleic/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>

        <x-forms.form method="POST" action="/samples/nucleic/sequences" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Sequences Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new sequence</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-dna text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Nucleic Acid Information</h2>
                            </div>

                            <!-- Nucleic acids -->
                            <div class="flex items-center space-x-4">
                                <x-forms.table-button id="nucleic_tubes_btn"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-indigo-500 to-indigo-600 text-white rounded-lg shadow-md hover:shadow-lg border border-cyan-600">
                                    <i class="fas fa-dna mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                    Select Nucleic Tubes
                                </x-forms.table-button>
                                <span id="nucleic_tube_id_count" class="text-sm text-gray-600">(0 selected)</span>
                            </div>
                            <x-forms.field name="nucleic_tube_id[]">
                                <x-forms.select-input id="nucleic_tube_id" name="nucleic_tube_id[]" multiple
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($selected_nucleic_tubes ?? [] as $tube)
                                        <option value="{{ $tube->id }}" selected>{{ $tube->code }}</option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Fasta File Upload -->
                            <x-forms.field label="Fasta File:" name="fasta_file">
                                <x-forms.file-input id="fasta_file" name="fasta_file" accept=".txt,.fasta,.fa"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.file-input>
                                <p class="mt-1 text-sm text-gray-500">Accepted formats: .txt, .fasta, .fa (Max size: 10MB)</p>
                            </x-forms.field>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-microscope text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Sequencing Information</h2>
                            </div>

                            <!-- Sequence Length -->
                            <x-forms.field label="Sequence Length:" name="length">
                                <x-forms.numeric-input id="length" name="length" min="1" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.numeric-input>
                            </x-forms.field>

                            <!-- Sequencing Method -->
                            <x-forms.field label="Sequencing Method:" name="method">
                                <x-forms.select-input id="method" name="method" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Sanger sequencing">Sanger sequencing</option>
                                    <option value="Next generation sequencing">Next generation sequencing</option>
                                    <option value="Whole genome sequencing">Whole genome sequencing</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Sequencing Instrument -->
                            <x-forms.field label="Sequencing Instrument:" name="instrument">
                                <x-forms.select-input id="instrument" name="instrument" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Illumina">Illumina</option>
                                    <option value="PacBio">PacBio</option>
                                    <option value="Oxford Nanopore">Oxford Nanopore</option>
                                    <option value="ABI 3730">ABI 3730</option>
                                    <option value="Ion Torrent">Ion Torrent</option>
                                    <option value="Roche 454">Roche 454</option>
                                    <option value="ABI 3130">ABI 3130</option>
                                    <option value="ABI 3100">ABI 3100</option>
                                    <option value="ABI 3500">ABI 3500</option>
                                    <option value="Illumina MiSeq">Illumina MiSeq</option>
                                    <option value="Illumina HiSeq">Illumina HiSeq</option>
                                    <option value="Illumina NovaSeq">Illumina NovaSeq</option>
                                </x-forms.select-input>
                            </x-forms.field>

                            <!-- Sequencing Date -->
                            <x-forms.field label="Sequencing Date:" name="date_sequenced">
                                <x-forms.date-input id="date_sequenced" name="date_sequenced" value="{{ now()->toDateString() }}" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.date-input>
                            </x-forms.field>

                            <!-- Sequenced By -->
                            <x-forms.field label="Sequenced By:" name="people_id">
                                @if ($can_assign_registrar)
                                    <x-forms.select-input id="people_id" name="people_id" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}">
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <x-forms.select-input id="people_id_locked" name="people_id_locked" disabled
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) $person->id === (int) ($locked_registrar_people_id ?? 0))>
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                    <input type="hidden" name="people_id" value="{{ $locked_registrar_people_id }}">
                                @endif
                            </x-forms.field>

                            <!-- Sequencing Location -->
                            <x-forms.field label="Sequencing Location:" name="laboratories_id">
                                <x-forms.select-input id="laboratories_id" name="laboratories_id" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @php
                                        $laboratoryGroups = ($laboratories_by_country ?? collect());
                                        if ($laboratoryGroups->isEmpty() && !empty($laboratories)) {
                                            $laboratoryGroups = collect(['Unassigned Country' => collect($laboratories)]);
                                        }
                                    @endphp
                                    @foreach ($laboratoryGroups as $countryName => $countryLaboratories)
                                        <optgroup label="{{ $countryName }}">
                                            @foreach ($countryLaboratories as $lab)
                                                <option value="{{ $lab->id }}">{{ $lab->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>
                            <button id="sequence_lab_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Laboratory
                            </button>
                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Sequence
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <!-- Tube Selection Modals -->
        <x-table-modal id="nucleic_tubes_modal" title="Nucleic Tubes" closeButtonId="nucleic_tubes_close_btn">
            <div class="text-sm text-gray-500">Loading…</div>
        </x-table-modal>

        <x-table-modal id="sequence_lab_form_modal" title="Laboratory Registration Form"
            closeButtonId="sequence_lab_form_close_btn">
            @include('modals.form_laboratories', [
                'organizations' => $organizations ?? collect(),
                'organizations_by_country' => $organizations_by_country ?? collect(),
                'countries' => $countries ?? collect(),
                'organization_types' => $organization_types ?? collect(),
                'lab_types' => $lab_types ?? collect(),
            ])
        </x-table-modal>


        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="/js/create-sequences.js?v={{ filemtime(public_path('js/create-sequences.js')) }}"></script>
        @endpush
    </div>
</x-layout>
