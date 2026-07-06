<x-layout>
    <div class="mt-2 md:col-span-2 md:mt-0">

        <!-- Buttons for Create, Edit, and Delete -->
        <div class="text-center flex justify-center space-x-4 mt-2 mb-4">
            <a href="/bank/boxes/list"
                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-gray-500 to-gray-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-gray-600">
                <i class="fas fa-list mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                List
            </a>
        </div>
        <x-forms.form method="POST" action="/bank/boxes/positions" enctype="multipart/form-data">
            @csrf
            <div class="shadow-xl sm:overflow-hidden sm:rounded-xl bg-gradient-to-br from-white to-gray-50">
                <div class="space-y-6 bg-white px-8 py-6 rounded-xl">
                    <x-forms.sub-project-form-header :options="$sub_project_options ?? []" />
                    <div class="text-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-800">Box Positions Registration Form</h2>
                        <p class="mt-2 text-sm text-gray-600">Fill in the details below to register new box positions</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Left Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-box text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Boxes Information</h2>
                            </div>

                            <!-- Box selection -->
                            <x-forms.field label="Select box:" name="box[]">
                                <x-forms.select-input id="box" name="box[]" multiple required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="">Select a box</option>
                                    @foreach ($boxes as $box)
                                        <option value="{{ $box->id }}" 
                                            data-rows="{{ $box->n_rows }}"
                                            data-columns="{{ $box->n_columns }}">
                                            {{ $box->code }}{{ $box->name ? ' - ' . $box->name : '' }} ({{ $box->n_rows }}x{{ $box->n_columns }})
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <button id="boxes_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Box
                            </button>

                            <!-- Location selection -->
                            <x-forms.field label="Select location:" name="location">
                                <x-forms.select-input id="location" name="location" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    @foreach ($locations as $location)
                                        <option value="{{ $location->id }}">
                                            {{ $location->name }}{{ $location->laboratories?->name ? ' (' . $location->laboratories->name . ')' : '' }}
                                        </option>
                                    @endforeach
                                </x-forms.select-input>
                            </x-forms.field>

                            <button id="box_location_form_btn" type="button"
                                class="group relative inline-flex items-center justify-center px-4 py-2 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-amber-400 to-amber-500 text-white rounded-lg shadow-md hover:shadow-lg border border-amber-500">
                                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Location
                            </button>

                            <!-- Sub-location selection -->
                            <x-forms.field label="Enter sub-location (e.g. shelf 1, drawer 2, etc.):" name="sub_location">
                                <x-forms.textarea id="sub_location" name="sub_location"
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                </x-forms.textarea>
                            </x-forms.field>
                        </div>

                        <!-- Right Column -->
                        <div class="space-y-6 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-info-circle text-blue-500 text-xl"></i>
                                <h2 class="text-lg font-semibold text-gray-800">Ancillary Information</h2>
                            </div>

                            <!-- Movement date input -->
                            <x-forms.field label="Movement date:" name="date">
                                <x-forms.date-input id="date" name="date" value="{{ now()->toDateString() }}"
                                    required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200"></x-forms.date-input>
                            </x-forms.field>

                            <!-- Person moving tube input -->
                            <x-forms.field label="Moved by:" name="mover">
                                @if ($can_assign_registrar)
                                    <x-forms.select-input id="mover" name="mover" required
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}">
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                @else
                                    <x-forms.select-input id="mover" name="mover_locked" disabled
                                        class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                        @foreach ($people as $person)
                                            <option value="{{ $person->id }}"
                                                @selected((int) $person->id === (int) ($locked_registrar_people_id ?? 0))>
                                                {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                            </option>
                                        @endforeach
                                    </x-forms.select-input>
                                    <input type="hidden" name="mover" value="{{ $locked_registrar_people_id }}">
                                @endif
                            </x-forms.field>

                            <!-- Reason input -->
                            <x-forms.field label="Reason of movement:" name="reason">
                                <x-forms.select-input id="reason" name="reason" required
                                    class="w-full px-4 py-2 text-sm border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent shadow-sm transition-all duration-200">
                                    <option value="Sample reorganization">Sample reorganization</option>
                                    <option value="Temperature condition change">Temperature condition change</option>
                                    <option value="Damaged box">Damaged box</option>
                                    <option value="Accidental fall of box">Accidental fall of box</option>
                                    <option value="Storage optimization">Storage optimization</option>
                                    <option value="Correct misplacement">Correct misplacement</option>
                                </x-forms.select-input>
                            </x-forms.field>

                        </div>
                    </div>
                </div>

                <div class="bg-gradient-to-r from-gray-50 to-gray-100 px-8 py-6 flex items-center justify-center rounded-b-xl border-t border-gray-200">
                    <x-forms.submit
                        class="group relative inline-flex items-center justify-center px-8 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-blue-600">
                        <i class="fas fa-save mr-2 text-lg group-hover:rotate-12 transition-transform duration-300"></i>
                        Save Box Positions
                    </x-forms.submit>
                </div>
            </div>
        </x-forms.form>

        <x-table-modal id="boxes_modal" title="Box Registration Form"
            closeButtonId="boxes_close_btn">
            @include('modals.form_box')
        </x-table-modal>

        <x-table-modal id="box_location_form_modal" title="Storage Location Registration Form"
            closeButtonId="box_location_form_close_btn">
            @include('modals.form_locations')
        </x-table-modal>

        @if (session('success'))
            <div id="successMessage" class="hidden">{{ session('success') }}</div>
        @endif

        @if (session('error'))
            <div id="errorMessage" class="hidden">{{ session('error') }}</div>
        @endif

        <script>
            
        </script>

        <!-- Selectize scripts -->
        @push('scripts')
            <script src="/js/create-box-positions.js"></script>
        @endpush
    </div>
</x-layout>
