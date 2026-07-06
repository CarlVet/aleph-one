<div data-profile-tables class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8" x-data="{
    editing: {
        first_name: false,
        last_name: false,
        sex: false,
        date_of_birth: false,
        age: false,
        marital_status: false,
        email: false,
        alternate_email: false,
        phone: false,
        alternate_phone: false,
        preferred_contact_method: false
    }
}"
x-on:start-edit.window="editing[$event.detail.field] = true"
x-on:save-edit.window="editing[$event.detail.field] = false"
x-on:cancel-edit.window="editing[$event.detail.field] = false"
x-on:show-success.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'success', title: 'Success!', text: $event.detail.message, timer: 2000, showConfirmButton: false }); }"
x-on:show-error.window="if(typeof Swal !== 'undefined') { Swal.fire({ icon: 'error', title: 'Error!', text: $event.detail.message, confirmButtonColor: '#d33' }); }"
wire:ignore.self>
    <div class="px-4 py-6 sm:px-0">
        @if(!$canView)
            <!-- Unauthorized Access Message -->
            <div class="bg-gradient-to-br from-red-50 to-red-100 border border-red-200 rounded-2xl p-8 shadow-lg">
                <div class="flex items-center justify-center">
                    <div class="text-center max-w-md">
                        <div class="bg-red-100 p-4 rounded-full w-20 h-20 flex items-center justify-center mx-auto mb-6 shadow-inner">
                            <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                            </svg>
                        </div>
                        <h2 class="text-2xl font-bold text-red-900 mb-3">Access Denied</h2>
                        <p class="text-red-700 text-lg mb-6 leading-relaxed">{{ $unauthorizedMessage }}</p>
                        <a href="/samples/humans/list" class="inline-flex items-center px-6 py-3 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-all duration-200 shadow-md hover:shadow-lg transform hover:-translate-y-0.5">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                            </svg>
                            Back to Human Samples List
                        </a>
                    </div>
                </div>
            </div>
        @else
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-pink-600 to-pink-500 rounded-t-xl shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="bg-white/20 p-3 rounded-lg">
                                    <i class="fas fa-person text-white text-[40px] group-hover:rotate-12 transition-transform duration-300"></i>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-white">Human Profile</h1>
                                    <p class="text-pink-100 text-lg">Code: {{ $human->code }}</p>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="flex items-center space-x-4">
                                <span class="text-pink-100 text-sm">
                                    {{ $human->first_name }} {{ $human->last_name }}
                                </span>
                                @if(!$canEdit)
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                        <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                        View Only
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <a href="/humans/list"
                                class="inline-flex items-center px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Back to List
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        <!-- Main Content -->
        <div class="bg-white shadow-lg rounded-b-xl">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 p-8">

                <!-- Left Column - Main Details -->
                <div class="lg:col-span-2 space-y-8">

                    <!-- Patient Information Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-pink-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Patient Information</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">First Name</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="first_name_{{ $human->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              wire:click="startEdit('first_name')" 
                                              x-show="!editing.first_name">
                                            {{ $human->first_name }}
                                        </span>
                                        <div x-show="editing.first_name" class="inline-flex items-center space-x-2">
                                            <input type="text" 
                                                   wire:model="editingValues.first_name" 
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $human->first_name }}"
                                                   x-ref="first_name_input"
                                                   x-init="$nextTick(() => $refs.first_name_input.focus())">
                                            <button wire:click="saveEdit('first_name')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit('first_name')" 
                                                    class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Last Name</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="last_name_{{ $human->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              wire:click="startEdit('last_name')" 
                                              x-show="!editing.last_name">
                                            {{ $human->last_name }}
                                        </span>
                                        <div x-show="editing.last_name" class="inline-flex items-center space-x-2">
                                            <input type="text" 
                                                   wire:model="editingValues.last_name" 
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $human->last_name }}"
                                                   x-ref="last_name_input"
                                                   x-init="$nextTick(() => $refs.last_name_input.focus())">
                                            <button wire:click="saveEdit('last_name')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit('last_name')" 
                                                    class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Sex</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="sex_{{ $human->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              wire:click="startEdit('sex')" 
                                              x-show="!editing.sex">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $human->sex === 'male' ? 'bg-blue-100 text-blue-800' : 
                                                   ($human->sex === 'female' ? 'bg-pink-100 text-pink-800' : 
                                                   'bg-gray-100 text-gray-800') }}">
                                                {{ ucfirst($human->sex) ?? 'N/A' }}
                                            </span>
                                        </span>
                                        <div x-show="editing.sex" class="inline-flex items-center space-x-2">
                                            <select wire:model="editingValues.sex" 
                                                    class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                    x-ref="sex_input"
                                                    x-init="$nextTick(() => $refs.sex_input.focus())">
                                                <option value="">Select sex</option>
                                                <option value="male">Male</option>
                                                <option value="female">Female</option>
                                                <option value="other">Other</option>
                                            </select>
                                            <button wire:click="saveEdit('sex')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit('sex')" 
                                                    class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Date of Birth</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="date_of_birth_{{ $human->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              wire:click="startEdit('date_of_birth')" 
                                              x-show="!editing.date_of_birth">
                                            {{ $human->date_of_birth ?? 'N/A' }}
                                        </span>
                                        <div x-show="editing.date_of_birth" class="inline-flex items-center space-x-2">
                                            <input type="date" 
                                                   wire:model="editingValues.date_of_birth" 
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   x-ref="date_of_birth_input"
                                                   x-init="$nextTick(() => $refs.date_of_birth_input.focus())">
                                            <button wire:click="saveEdit('date_of_birth')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit('date_of_birth')" 
                                                    class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </dd>
                            </div>


                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Marital Status</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="marital_status_{{ $human->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              wire:click="startEdit('marital_status')" 
                                              x-show="!editing.marital_status">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                {{ ucfirst(str_replace('_', ' ', $human->marital_status)) ?? 'N/A' }}
                                            </span>
                                        </span>
                                        <div x-show="editing.marital_status" class="inline-flex items-center space-x-2">
                                            <select wire:model="editingValues.marital_status" 
                                                    class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                    x-ref="marital_status_input"
                                                    x-init="$nextTick(() => $refs.marital_status_input.focus())">
                                                <option value="">Select marital status</option>
                                                <option value="single">Single</option>
                                                <option value="married">Married</option>
                                                <option value="divorced">Divorced</option>
                                                <option value="widowed">Widowed</option>
                                                <option value="separated">Separated</option>
                                            </select>
                                            <button wire:click="saveEdit('marital_status')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit('marital_status')" 
                                                    class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Email</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="email_{{ $human->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              wire:click="startEdit('email')" 
                                              x-show="!editing.email">
                                            @if($human->email)
                                                <a href="mailto:{{ $human->email }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $human->email }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </span>
                                        <div x-show="editing.email" class="inline-flex items-center space-x-2">
                                            <input type="email" 
                                                   wire:model="editingValues.email" 
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $human->email ?? 'Enter email' }}"
                                                   x-ref="email_input"
                                                   x-init="$nextTick(() => $refs.email_input.focus())">
                                            <button wire:click="saveEdit('email')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit('email')" 
                                                    class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Alternate Email</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="alternate_email_{{ $human->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              wire:click="startEdit('alternate_email')" 
                                              x-show="!editing.alternate_email">
                                            @if($human->alternate_email)
                                                <a href="mailto:{{ $human->alternate_email }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $human->alternate_email }}
                                                </a>
                                            @else
                                                N/A
                                            @endif
                                        </span>
                                        <div x-show="editing.alternate_email" class="inline-flex items-center space-x-2">
                                            <input type="email" 
                                                   wire:model="editingValues.alternate_email" 
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $human->alternate_email ?? 'Enter alternate email' }}"
                                                   x-ref="alternate_email_input"
                                                   x-init="$nextTick(() => $refs.alternate_email_input.focus())">
                                            <button wire:click="saveEdit('alternate_email')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit('alternate_email')" 
                                                    class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Phone Number</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="phone_{{ $human->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              wire:click="startEdit('phone')" 
                                              x-show="!editing.phone">
                                            {{ $human->phone ?? 'N/A' }}
                                        </span>
                                        <div x-show="editing.phone" class="inline-flex items-center space-x-2">
                                            <input type="tel" 
                                                   wire:model="editingValues.phone" 
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $human->phone ?? 'Enter phone number' }}"
                                                   x-ref="phone_input"
                                                   x-init="$nextTick(() => $refs.phone_input.focus())">
                                            <button wire:click="saveEdit('phone')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit('phone')" 
                                                    class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Alternate Phone</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="alternate_phone_{{ $human->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              wire:click="startEdit('alternate_phone')" 
                                              x-show="!editing.alternate_phone">
                                            {{ $human->alternate_phone ?? 'N/A' }}
                                        </span>
                                        <div x-show="editing.alternate_phone" class="inline-flex items-center space-x-2">
                                            <input type="tel" 
                                                   wire:model="editingValues.alternate_phone" 
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $human->alternate_phone ?? 'Enter alternate phone' }}"
                                                   x-ref="alternate_phone_input"
                                                   x-init="$nextTick(() => $refs.alternate_phone_input.focus())">
                                            <button wire:click="saveEdit('alternate_phone')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit('alternate_phone')" 
                                                    class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Preferred Contact Method</dt>
                                <dd class="text-sm text-gray-900">
                                    <div class="inline-edit" wire:key="preferred_contact_method_{{ $human->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              wire:click="startEdit('preferred_contact_method')" 
                                              x-show="!editing.preferred_contact_method">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ ucfirst(str_replace('_', ' ', $human->preferred_contact_method)) ?? 'N/A' }}
                                            </span>
                                        </span>
                                        <div x-show="editing.preferred_contact_method" class="inline-flex items-center space-x-2">
                                            <select wire:model="editingValues.preferred_contact_method" 
                                                    class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                    x-ref="preferred_contact_method_input"
                                                    x-init="$nextTick(() => $refs.preferred_contact_method_input.focus())">
                                                <option value="">Select preferred contact method</option>
                                                <option value="email">Email</option>
                                                <option value="phone">Phone</option>
                                                <option value="sms">SMS</option>
                                                <option value="mail">Mail</option>
                                            </select>
                                            <button wire:click="saveEdit('preferred_contact_method')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button wire:click="cancelEdit('preferred_contact_method')" 
                                                    class="w-6 h-6 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>
                                </dd>
                            </div>
                        </div>
                    </div>

                    <!-- Related Samples Section -->
                    @if($human->human_samples->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Related Samples</h2>
                        </div>

                        <div class="space-y-4">
                            @foreach($human->human_samples as $sample)
                                <div class="bg-white rounded-lg p-4 shadow-sm">
                                    <div class="flex justify-between items-start">
                                        <div>
                                            <a href="/samples/humans/{{ $sample->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                {{ $sample->code }}
                                            </a>
                                        </div>
                                        <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full shadow-sm
                                            {{ $sample->sample_purpose === 'diagnostic' ? 'bg-gradient-to-r from-red-100 to-red-200 text-red-800' : 
                                               ($sample->sample_purpose === 'research' ? 'bg-gradient-to-r from-blue-100 to-blue-200 text-blue-800' : 
                                               'bg-gradient-to-r from-green-100 to-green-200 text-green-800') }}">
                                            {{ ucfirst($sample->sample_purpose) }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    <!-- Experiments Results Section -->
                    @if($sampleExperiments->count() > 0)
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{ open: false }">
                        <button @click="open = !open" class="flex items-center justify-between w-full mb-6">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <i class="fa-solid fa-flask text-2xl text-blue-600"></i>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Experiments results ({{ $sampleExperiments->count() }})</h2>
                            </div>
                            <svg class="w-5 h-5 text-gray-500 transform transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition>
                            <p class="text-xs text-gray-500 mb-3">All experiments conducted on this patient's samples and on samples derived from them.</p>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Protocol</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pathogen</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Purpose</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date Tested</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Outcome</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200">
                                        @foreach($sampleExperiments as $experiment)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <a href="/experiments/{{ $experiment->code }}" class="text-blue-600 hover:text-blue-800 font-medium hover:underline">
                                                    {{ $experiment->code }}
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $experiment->protocols->name ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $experiment->pathogens->species ?? 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $experiment->purpose ? ucfirst($experiment->purpose) : 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                {{ $experiment->date_tested ? \Carbon\Carbon::parse($experiment->date_tested)->format('M d, Y') : 'N/A' }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                    {{ $experiment->outcome_discrete === 'Strong positive'
                                                        ? 'bg-red-700 text-white'
                                                        : ($experiment->outcome_discrete === 'Positive'
                                                            ? 'bg-orange-100 text-orange-800'
                                                            : ($experiment->outcome_discrete === 'Suspect'
                                                                ? 'bg-yellow-100 text-yellow-800'
                                                                : ($experiment->outcome_discrete === 'Negative'
                                                                    ? 'bg-green-100 text-green-800'
                                                                    : 'bg-gray-100 text-gray-800'))) }}">
                                                    {{ $experiment->outcome_discrete ?? 'N/A' }}
                                                </span>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Right Column - Sidebar -->
                <div class="space-y-6">


                    <!-- Photo Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center gap-3">
                            <div class="bg-pink-100 p-2 rounded-lg">
                                    <svg class="w-6 h-6 text-pink-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                        </path>
                                    </svg>
                            </div>
                        <div class="min-w-0">
                            <h2 class="text-lg font-semibold text-gray-900 leading-tight">Photo</h2>
                            <p class="mt-0.5 text-xs text-gray-500 leading-snug">
                                Max file size: 50MB <br> Formats: JPG, PNG, WEBP, TIFF, PDF
                            </p>
                        </div>
                    </div>

                        @if($canEdit)
                            <div class="mt-2">
                                @if(!$photo)
                                    <label for="photo-upload"
                                           class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200 cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                        </svg>
                                        Select photo
                                    </label>
                                    <input
                                        type="file"
                                        id="photo-upload"
                                        class="hidden"
                                        accept=".jpg,.jpeg,.png,.webp,.tif,.tiff,.pdf"
                                        wire:model="photo"
                                        wire:loading.attr="disabled"
                                        x-data
                                        x-on:photo-uploaded.window="$el.value = ''"
                                        x-on:photo-cancelled.window="$el.value = ''"
                                    >
                                @else
                                    <div class="flex flex-wrap gap-2">
                                        <button wire:click="uploadPhoto"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Upload
                                        </button>
                                        <button wire:click="cancelPhotoSelection"
                                                class="inline-flex items-center gap-2 px-4 py-2 bg-gray-700 hover:bg-gray-800 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                            Cancel
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($uploadError)
                            <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                <div class="flex items-center">
                                    <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-red-800 break-words">{{ $uploadError }}</span>
                                </div>
                            </div>
                        @endif

                        <x-upload-progress wireModel="photo" class="mt-3" />

                        <!-- Photo Display -->
                        @php
                            $photoPath = $human->photo_path;
                            $photoUrl = $photoPath ? Storage::url($photoPath) : null;
                            $photoExt = $photoPath ? strtolower(pathinfo($photoPath, PATHINFO_EXTENSION)) : null;
                            $photoIsPreviewable = in_array($photoExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                        @endphp
                        @if ($photoUrl)
                            <div class="relative group">
                                <a href="{{ $photoUrl }}" target="_blank" class="block">
                                    @if($photoIsPreviewable)
                                        <img src="{{ $photoUrl }}" alt="Patient photo"
                                            class="w-full h-auto rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-200">
                                    @else
                                        <div class="w-full rounded-lg border border-gray-200 bg-white p-6 text-center text-sm text-gray-700 shadow-sm hover:shadow transition-shadow duration-200">
                                            File uploaded ({{ strtoupper((string) $photoExt) }}) — click to open
                                        </div>
                                    @endif
                                </a>
                                
                                @if($canEdit)
                                    <!-- Delete Button (appears on hover) -->
                                    <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <button wire:click="deletePhoto" 
                                            wire:confirm="Are you sure you want to delete this photo?"
                                            class="bg-red-600 hover:bg-red-700 text-white p-2 rounded-full shadow-lg transition-colors duration-200">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        @else
                            <!-- No Photo Placeholder -->
                            <div class="text-center py-12 bg-white rounded-lg border-2 border-dashed border-gray-300">
                                <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                    <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                </svg>
                                <div class="mt-4">
                                    <p class="text-sm text-gray-600">No photo uploaded yet</p>
                                    <p class="text-xs text-gray-500 mt-1">Click "Select Photo" to add an image</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('SweetAlert available:', typeof Swal !== 'undefined');
    
    // Listen for Livewire events
    window.addEventListener('show-success', function(event) {
        console.log('show-success event received:', event.detail);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: event.detail.message,
                timer: 2000,
                showConfirmButton: false
            });
        }
    });
    
    window.addEventListener('show-error', function(event) {
        console.log('show-error event received:', event.detail);
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: event.detail.message,
                confirmButtonColor: '#d33'
            });
        }
    });
});
</script> 