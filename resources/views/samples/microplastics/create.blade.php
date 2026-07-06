<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0" x-data="{ registerMode: '{{ old('register_mode', 'form') }}' }">
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/samples/microplastics/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
            <a href="/samples/microplastics/dashboard"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                <i class="fas fa-chart-bar mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
                Dashboard
            </a>
        </div>

        <div class="mx-auto mb-6 w-full max-w-2xl rounded-2xl border border-gray-200 bg-white px-6 py-4 shadow-sm">
            <div class="flex flex-col items-center justify-between gap-4 sm:flex-row">
                <div class="text-sm font-semibold text-gray-800">Register microplastics by:</div>
                <div class="flex flex-wrap items-center justify-center gap-4 text-sm">
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="microplastics_register_mode" value="form" x-model="registerMode"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                        <span class="text-gray-700">Form</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="microplastics_register_mode" value="import" x-model="registerMode"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                        <span class="text-gray-700">Import CSV</span>
                    </label>
                    <label class="inline-flex items-center gap-2">
                        <input type="radio" name="microplastics_register_mode" value="table" x-model="registerMode"
                            class="h-4 w-4 text-blue-600 focus:ring-blue-500">
                        <span class="text-gray-700">Table</span>
                    </label>
                </div>
            </div>
        </div>

        <div x-show="registerMode === 'import'" x-cloak class="mt-6">
            <livewire:imports.microplastics-import />
        </div>

        @include('samples.microplastics.partials.table-registration')

        <div x-show="registerMode === 'form'" x-cloak>
            <x-forms.form method="POST" action="/samples/microplastics" enctype="multipart/form-data">
                @csrf
                <input type="hidden" name="register_mode" value="form">
                <input type="hidden" name="project_id_snapshot" value="{{ (int) ($selected_project_id ?? session('selected_project_id')) }}">
                <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                    <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                            <h2 class="text-2xl font-bold text-gray-800">Microplastics Identification Form</h2>
                            <p class="mt-2 text-sm text-gray-600">Fill in the details below to register a new microplastics identification</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-flask text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Source Tube Information</h2>
                                </div>

                                <x-forms.field label="Select sample origin:" name="model">
                                    <x-forms.select-input id="model" name="model" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        <option value="Human samples">Human samples</option>
                                        <option value="Animal samples">Animal samples</option>
                                        <option value="Environmental samples">Environmental samples</option>
                                        <option value="Parasite samples">Parasite samples</option>
                                        <option value="Pools">Pools</option>
                                    </x-forms.select-input>
                                </x-forms.field>

                                @include('partials.tube-badge-display-toggle')

                                @foreach ([
                                    'human' => ['Human Tubes', 'person', 'pink', $selected_human_tubes ?? []],
                                    'animal' => ['Animal Tubes', 'paw', 'yellow', $selected_animal_tubes ?? []],
                                    'environment' => ['Environmental Tubes', 'leaf', 'green', $selected_environment_tubes ?? []],
                                    'parasite' => ['Parasite Tubes', 'spider', 'purple', $selected_parasite_tubes ?? []],
                                    'pool' => ['Pool Tubes', 'layer-group', 'cyan', $selected_pool_tubes ?? []],
                                ] as $key => [$label, $icon, $color, $selectedTubes])
                                    @php
                                        $sectionId = $key === 'environment' ? 'environment_model' : $key.'_model';
                                        $buttonId = $key.'_tubes_btn';
                                        $countId = $key.'_tube_id_count';
                                        $selectId = $key.'_tube_id';
                                    @endphp
                                    <div id="{{ $sectionId }}" style="display: none;" class="space-y-4">
                                        <div class="flex items-center space-x-4" data-tube-display-anchor="1">
                                            <x-forms.table-button id="{{ $buttonId }}"
                                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-{{ $color }}-500 to-{{ $color }}-600 text-white rounded-lg shadow-md hover:shadow-lg border border-{{ $color }}-600">
                                                <i class="fas fa-{{ $icon }} mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                                                Select {{ $label }}
                                            </x-forms.table-button>
                                            <span id="{{ $countId }}" class="text-sm text-gray-600">(0 selected)</span>
                                        </div>
                                        <x-forms.field name="{{ $selectId }}[]">
                                            <x-forms.select-input id="{{ $selectId }}" name="{{ $selectId }}[]" multiple data-tube-badge-toggle="1"
                                                class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-{{ $color }}-500 focus:border-transparent shadow-sm transition-all duration-200">
                                                @foreach ($selectedTubes as $tube)
                                                    <option value="{{ $tube->id }}" data-code="{{ $tube->code }}" data-alias-code="{{ $tube->alias_code ?? '' }}" selected>{{ $tube->code }}</option>
                                                @endforeach
                                            </x-forms.select-input>
                                        </x-forms.field>
                                    </div>
                                @endforeach
                            </div>

                            <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-recycle text-blue-500 text-xl"></i>
                                    <h2 class="text-lg font-semibold text-gray-800">Identification Information</h2>
                                </div>

                                <x-forms.field label="Sample weight (g):" name="sample_weight">
                                    <x-forms.numeric-input id="sample_weight" name="sample_weight" min="0" step="any"
                                        value="{{ old('sample_weight') }}"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                                </x-forms.field>

                                <x-forms.field label="Pearson correlation coefficient (r):" name="r_coeff">
                                    <x-forms.numeric-input id="r_coeff" name="r_coeff" min="-1" max="1" step="any"
                                        value="{{ old('r_coeff') }}"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                                </x-forms.field>

                                <x-forms.field label="Microplastics type(s):" name="mps_type">
                                    <x-forms.select-input id="mps_type" name="mps_type[]" required multiple
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @php($oldMpsTypes = collect(old('mps_type', []))->map(fn ($value) => (string) $value)->all())
                                        @foreach (($mps_types ?? collect()) as $mpsType)
                                            <option value="{{ $mpsType->name }}" @selected(in_array($mpsType->name, $oldMpsTypes, true))>{{ $mpsType->name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                    <p class="mt-2 text-xs text-gray-500">
                                        Select one or more particle types detected for the selected measurement(s).
                                    </p>
                                </x-forms.field>

                                <x-forms.field label="Tubes from same sample source:" name="source_measurement_mode">
                                    <div class="space-y-3 rounded-xl border border-gray-200 bg-gray-50 px-4 py-3">
                                        <label class="flex items-start gap-3 text-sm text-gray-700">
                                            <input type="radio" name="source_measurement_mode" value="pooled"
                                                class="mt-1 text-blue-600 focus:ring-blue-500"
                                                @checked(old('source_measurement_mode') === 'pooled')>
                                            <span>
                                                <span class="font-semibold text-gray-900">Were pooled</span>
                                                One microplastics record per selected source sample and per selected MPS type.
                                            </span>
                                        </label>
                                        <label class="flex items-start gap-3 text-sm text-gray-700">
                                            <input type="radio" name="source_measurement_mode" value="separate_measurements"
                                                class="mt-1 text-blue-600 focus:ring-blue-500"
                                                @checked(old('source_measurement_mode', 'separate_measurements') === 'separate_measurements')>
                                            <span>
                                                <span class="font-semibold text-gray-900">Were separate measurements</span>
                                                Each selected tube represents its own measurement, even when tubes share the same source sample.
                                            </span>
                                        </label>
                                    </div>
                                </x-forms.field>

                                <x-forms.field label="Feret diameter (um):" name="m_feret">
                                    <x-forms.numeric-input id="m_feret" name="m_feret" min="0" step="any"
                                        value="{{ old('m_feret') }}"
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200" />
                                </x-forms.field>

                                <x-forms.field label="Identification date:" name="identification_date">
                                    <x-forms.date-input id="identification_date" name="identification_date"
                                        value="{{ old('identification_date', now()->toDateString()) }}" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.date-input>
                                </x-forms.field>

                                <x-forms.field label="Identification protocol:" name="protocol">
                                    <x-forms.select-input id="protocol" name="protocol" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($microplastic_protocols as $protocol)
                                            <option value="{{ $protocol->name }}" @selected(old('protocol') === $protocol->name)>{{ $protocol->name }}</option>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>

                                <button id="microplastics_protocol_form_btn" type="button"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                    <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                    Create New Protocol
                                </button>

                                <x-forms.field label="Identified at:" name="microplastics_lab">
                                    <x-forms.select-input id="lab" name="microplastics_lab" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($laboratories_by_country as $country => $labs)
                                            <optgroup label="{{ $country }}">
                                                @foreach ($labs as $lab)
                                                    <option value="{{ $lab['name'] }}" @selected(old('microplastics_lab') === $lab['name'])>{{ $lab['name'] }}</option>
                                                @endforeach
                                            </optgroup>
                                        @endforeach
                                    </x-forms.select-input>
                                </x-forms.field>

                                <button id="microplastics_lab_form_btn" type="button"
                                    class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                    <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                    Create New Laboratory
                                </button>

                                <x-forms.field label="Identified by:" name="identifier">
                                    @if (!($can_assign_registrar ?? false) && !empty($locked_registrar_people_id))
                                        <input type="hidden" name="identifier" value="{{ (int) $locked_registrar_people_id }}">
                                    @endif
                                    @if ($can_assign_registrar ?? false)
                                        <x-forms.select-input id="scientist" name="identifier" required
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($people as $person)
                                                <option value="{{ $person->id }}" @selected((int) old('identifier') === (int) $person->id)>
                                                    {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                                </option>
                                            @endforeach
                                        </x-forms.select-input>
                                    @else
                                        <x-forms.select-input id="scientist" name="identifier" required disabled
                                            class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                            @foreach ($people as $person)
                                                <option value="{{ $person->id }}" @selected((int) old('identifier', $locked_registrar_people_id ?? null) === (int) $person->id)>
                                                    {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                                </option>
                                            @endforeach
                                        </x-forms.select-input>
                                    @endif
                                </x-forms.field>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                        <x-forms.submit class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                            <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                            Save Microplastics Identification
                        </x-forms.submit>
                    </div>
                </div>
            </x-forms.form>
        </div>

        @foreach ([
            'human' => 'Human Tubes',
            'animal' => 'Animal Tubes',
            'environment' => 'Environmental Tubes',
            'parasite' => 'Parasite Tubes',
            'nucleic' => 'Nucleic Tubes',
            'culture' => 'Culture Tubes',
            'pool' => 'Pool Tubes',
        ] as $key => $title)
            <x-table-modal id="{{ $key }}_tubes_modal" title="{{ $title }}" closeButtonId="{{ $key }}_tubes_close_btn">
                <div class="text-sm text-gray-500">Loading…</div>
            </x-table-modal>
        @endforeach

        <x-table-modal id="microplastics_lab_form_modal" title="Laboratory Registration Form" closeButtonId="microplastics_lab_form_close_btn">
            @include('modals.form_laboratories')
        </x-table-modal>

        <x-table-modal id="microplastics_protocol_form_modal" title="Microplastics Identification Protocol - Registration Form" closeButtonId="microplastics_protocol_form_close_btn">
            @include('modals.form_microplastics_protocol')
        </x-table-modal>

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        @push('scripts')
            <script src="{{ asset('js/create-microplastics.js') }}?v={{ filemtime(public_path('js/create-microplastics.js')) }}"></script>
            <script src="{{ asset('js/create-microplastics-table.js') }}?v={{ filemtime(public_path('js/create-microplastics-table.js')) }}"></script>
        @endpush
    </div>
</x-layout>
