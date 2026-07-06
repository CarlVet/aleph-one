<x-layout>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-indigo-900 to-indigo-800 rounded-t-xl shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                                                    <div class="flex items-center space-x-3 mb-4">
                            <div class="bg-white/20 p-3 rounded-lg relative group">
                                @if($person->pic_path)
                                    <x-people-logo :person="$person" width="48" />
                                    <!-- Upload overlay for existing photo -->
                                    @if($person->id === auth()->user()->people->id)
                                    <div class="absolute inset-0 bg-black bg-opacity-50 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <div class="text-white text-center">
                                            <i class="fa-solid fa-camera text-xl mb-1"></i>
                                            <div class="text-xs">Click to update</div>
                                        </div>
                                    </div>
                                    @endif
                                @else
                                    <div class="w-12 h-12 rounded-full bg-white/30 flex items-center justify-center">
                                        <i class="fa-solid fa-user text-white text-xl"></i>
                                    </div>
                                    @if($person->id === auth()->user()->people->id)
                                    <!-- Upload overlay for no photo -->
                                    <div class="absolute inset-0 bg-black bg-opacity-50 rounded-lg flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                                        <div class="text-white text-center">
                                            <i class="fa-solid fa-camera text-xl mb-1"></i>
                                            <div class="text-xs">Click to upload</div>
                                        </div>
                                    </div>
                                    @endif
                                @endif
                                @if($person->id === auth()->user()->people->id)
                                <label for="profile-photo-upload" class="absolute inset-0 cursor-pointer">
                                    <input type="file" id="profile-photo-upload" class="hidden" accept="image/*" onchange="confirmPhotoUpdate(this)">
                                </label>
                                @endif
                            </div>
                            <div>
                                <h1 class="text-3xl font-bold text-white">
                                    {{ $person->id === auth()->user()->people->id ? 'My Profile' : 'Collaborator Profile' }}
                                </h1>
                                <p class="text-indigo-100 text-lg">
                                    {{ $person->title . ' ' . $person->first_name . ' ' . $person->last_name }}
                                </p>
                            </div>
                        </div>

                            <!-- Status Badge -->
                            <div class="flex items-center space-x-4">
                                <span class="text-indigo-100 text-sm">
                                    {{ $person->job ?? 'N/A' }} • {{ $person->organizations->name ?? 'N/A' }}
                                </span>
                            </div>
                        </div>

                        @if(isset($user) && $user && $person->id !== auth()->user()->people->id)
                            <div class="flex items-center gap-2">
                                <button
                                    type="button"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-white/20 hover:bg-white/30 text-white font-semibold rounded-lg transition-colors duration-200"
                                    onclick="window.openChatWithUser?.({{ (int) $user->id }}, @js(($person->first_name ?? '').' '.($person->last_name ?? '')), @js($person->pic_path ?? ''))"
                                    title="Message"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 10h.01M12 10h.01M16 10h.01M21 12c0 4.418-4.03 8-9 8a10.5 10.5 0 01-4-.77L3 20l1.3-3.9A7.7 7.7 0 013 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                        </path>
                                    </svg>
                                    Message
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white shadow-lg rounded-b-xl">
                <div class="p-8 space-y-8">

                    <!-- Personal Information Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Personal Information</h2>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6" x-data="{
                            editing: {
                                title: false,
                                first_name: false,
                                last_name: false,
                                job: false,
                                orcid: false
                            }
                        }" @if($person->id !== auth()->user()->people->id) style="pointer-events: none;" @endif>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Title</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="title_{{ $person->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              onclick="startEdit('title', '{{ $person->title ?? '' }}')" 
                                              x-show="!editing.title">
                                            {{ $person->title ?? 'N/A' }}
                                        </span>
                                        <div x-show="editing.title" class="inline-flex items-center space-x-2">
                                            <input type="text" 
                                                   id="title_input"
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $person->title ?? 'N/A' }}"
                                                   x-ref="title_input"
                                                   x-init="$nextTick(() => $refs.title_input.focus())">
                                            <button onclick="saveEdit('title')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button onclick="cancelEdit('title')" 
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">First Names</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="first_name_{{ $person->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              onclick="startEdit('first_name', '{{ $person->first_name }}')" 
                                              x-show="!editing.first_name">
                                            {{ $person->first_name }}
                                        </span>
                                        <div x-show="editing.first_name" class="inline-flex items-center space-x-2">
                                            <input type="text" 
                                                   id="first_name_input"
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $person->first_name }}"
                                                   x-ref="first_name_input"
                                                   x-init="$nextTick(() => $refs.first_name_input.focus())">
                                            <button onclick="saveEdit('first_name')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button onclick="cancelEdit('first_name')" 
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
                                    <div class="inline-edit" wire:key="last_name_{{ $person->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              onclick="startEdit('last_name', '{{ $person->last_name }}')" 
                                              x-show="!editing.last_name">
                                            {{ $person->last_name }}
                                        </span>
                                        <div x-show="editing.last_name" class="inline-flex items-center space-x-2">
                                            <input type="text" 
                                                   id="last_name_input"
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $person->last_name }}"
                                                   x-ref="last_name_input"
                                                   x-init="$nextTick(() => $refs.last_name_input.focus())">
                                            <button onclick="saveEdit('last_name')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button onclick="cancelEdit('last_name')" 
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Email Address</dt>
                                <dd class="text-sm text-gray-900">
                                    <a href="mailto:{{ $contactEmail ?? '' }}" class="text-blue-600 hover:text-blue-800 hover:underline">
                                        {{ $contactEmail ?? 'N/A' }}
                                    </a>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Job Title</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="job_{{ $person->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              onclick="startEdit('job', '{{ $person->job ?? '' }}')" 
                                              x-show="!editing.job">
                                            {{ $person->job ?? 'N/A' }}
                                        </span>
                                        <div x-show="editing.job" class="inline-flex items-center space-x-2">
                                            <input type="text" 
                                                   id="job_input"
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $person->job ?? 'N/A' }}"
                                                   x-ref="job_input"
                                                   x-init="$nextTick(() => $refs.job_input.focus())">
                                            <button onclick="saveEdit('job')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button onclick="cancelEdit('job')" 
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">ORCID</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="orcid_{{ $person->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              onclick="startEdit('orcid', '{{ $person->orcid ?? '' }}')" 
                                              x-show="!editing.orcid">
                                            {{ $person->orcid ?? 'N/A' }}
                                        </span>
                                        <div x-show="editing.orcid" class="inline-flex items-center space-x-2">
                                            <input type="text" 
                                                   id="orcid_input"
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $person->orcid ?? 'N/A' }}"
                                                   x-ref="orcid_input"
                                                   x-init="$nextTick(() => $refs.orcid_input.focus())">
                                            <button onclick="saveEdit('orcid')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button onclick="cancelEdit('orcid')" 
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">Permission Level</dt>
                                <dd class="text-sm text-gray-900">
                                    @if($user)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            {{ $user->permission === 'Admin' ? 'bg-red-100 text-red-800' : 
                                               ($user->permission === 'Manager' ? 'bg-yellow-100 text-yellow-800' : 
                                               'bg-green-100 text-green-800') }}">
                                            {{ $user->permission ?? 'Guest' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            Not registered
                                        </span>
                                    @endif
                                </dd>
                            </div>
                        </div>
                    </div>

                    <!-- Work Information Section -->
                    <div class="bg-gray-50 rounded-xl p-6" x-data="{
                        editing: {
                            organization: false
                        },
                        showOrganizationModal: false
                    }" x-cloak @if($person->id !== auth()->user()->people->id) style="pointer-events: none;" @endif>
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center">
                                <div class="bg-blue-100 p-2 rounded-lg mr-3">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                        </path>
                                    </svg>
                                </div>
                                <h2 class="text-xl font-semibold text-gray-900">Work Information</h2>
                            </div>
                            @if($person->id === auth()->user()->people->id)
                            <button id="profile_organization_form_btn" 
                                    class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Add Organization
                            </button>
                            @endif
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Workplace</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    <div class="inline-edit" wire:key="organization_{{ $person->id }}">
                                        <span class="editable-text cursor-pointer hover:bg-gray-100 px-2 py-1 rounded transition-colors" 
                                              onclick="startEdit('organization', '{{ $person->organizations->name ?? '' }}')" 
                                              x-show="!editing.organization">
                                            {{ $person->organizations->name ?? 'N/A' }}
                                        </span>
                                        <div x-show="editing.organization" class="inline-flex items-center space-x-2">
                                            <input type="text" 
                                                   id="organization_input"
                                                   class="px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                                   placeholder="{{ $person->organizations->name ?? 'N/A' }}"
                                                   list="organizations_list"
                                                   x-ref="organization_input"
                                                   x-init="$nextTick(() => $refs.organization_input.focus())"
                                                   onchange="validateOrganization(this)">
                                            <datalist id="organizations_list">
                                                @foreach($organizations ?? [] as $org)
                                                    <option value="{{ $org->name }}">
                                                @endforeach
                                            </datalist>
                                            <button onclick="saveEdit('organization')" 
                                                    class="w-6 h-6 bg-green-500 hover:bg-green-600 text-white rounded-full flex items-center justify-center transition-colors duration-200">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            </button>
                                            <button onclick="cancelEdit('organization')" 
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
                                <dt class="text-sm font-medium text-gray-500 mb-1">City</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $person->organizations->city ?? 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Region</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $person->organizations->region ?? 'N/A' }}
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500 mb-1">Country</dt>
                                <dd class="text-sm text-gray-900 font-medium">
                                    {{ $person->organizations->countries->name ?? 'N/A' }}
                                </dd>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Stats Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-green-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Quick Stats</h2>
                        </div>

                        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-4">
                            <div class="bg-white p-4 rounded-lg border text-center">
                                <div class="text-2xl font-bold text-blue-600">{{ $stats['total_projects'] }}</div>
                                <div class="text-sm text-gray-500">Projects</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border text-center">
                                <div class="text-2xl font-bold text-green-600">{{ $stats['total_samples'] }}</div>
                                <div class="text-sm text-gray-500">Samples</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border text-center">
                                <div class="text-2xl font-bold text-purple-600">{{ $stats['total_experiments'] }}</div>
                                <div class="text-sm text-gray-500">Experiments</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border text-center">
                                <div class="text-2xl font-bold text-indigo-600">{{ $stats['total_nucleic_acids'] }}</div>
                                <div class="text-sm text-gray-500">Nucleic Acids</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border text-center">
                                <div class="text-2xl font-bold text-orange-600">{{ $stats['total_cultures'] }}</div>
                                <div class="text-sm text-gray-500">Cultures</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border text-center">
                                <div class="text-2xl font-bold text-cyan-600">{{ $stats['total_pools'] }}</div>
                                <div class="text-sm text-gray-500">Pools</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border text-center">
                                <div class="text-2xl font-bold text-red-600">{{ $stats['total_sequences'] }}</div>
                                <div class="text-sm text-gray-500">Sequences</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border text-center">
                                <div class="text-2xl font-bold text-yellow-600">{{ $stats['total_parasites'] }}</div>
                                <div class="text-sm text-gray-500">Parasites</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border text-center">
                                <div class="text-2xl font-bold text-teal-600">{{ $stats['total_meta_studies'] }}</div>
                                <div class="text-sm text-gray-500">Meta Studies</div>
                            </div>
                            <div class="bg-white p-4 rounded-lg border text-center">
                                <div class="text-2xl font-bold text-emerald-600">{{ $stats['total_fundings'] }}</div>
                                <div class="text-sm text-gray-500">Fundings</div>
                            </div>
                        </div>
                    </div>

                    <!-- Projects Section -->
                    <div class="bg-gray-50 rounded-xl p-6">
                        <div class="flex items-center mb-6">
                            <div class="bg-indigo-100 p-2 rounded-lg mr-3">
                                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                    </path>
                                </svg>
                            </div>
                            <h2 class="text-xl font-semibold text-gray-900">Associated Projects</h2>
                        </div>

                        @if($person->projects->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach($person->projects as $project)
                                    <a href="{{ route('projects.profile', $project->code) }}"
                                       class="group block bg-white p-4 rounded-lg border hover:shadow-md transition-shadow relative">
                                        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-5 rounded-lg transition-colors duration-200"></div>
                                        <div class="flex items-start justify-between mb-2">
                                            <h3 class="text-lg font-semibold text-gray-900">{{ $project->code }}</h3>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                                {{ $project->pivot->role === 'Principal Investigator' ? 'bg-red-100 text-red-800' : 
                                                   ($project->pivot->role === 'Supervisor' ? 'bg-yellow-100 text-yellow-800' : 
                                                   'bg-blue-100 text-blue-800') }}">
                                                {{ $project->pivot->role }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-2">{{ Str::limit($project->title, 80) }}</p>
                                        <div class="text-xs text-gray-500">
                                            <div>Started: {{ $project->date_started ? \Carbon\Carbon::parse($project->date_started)->format('M Y') : 'N/A' }}</div>
                                            <div>Type: {{ $project->type }}</div>
                                            @if($project->pivot->date_joined)
                                                <div>Joined: {{ \Carbon\Carbon::parse($project->pivot->date_joined)->format('M Y') }}</div>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                </svg>
                                <p class="mt-2">No projects associated yet</p>
                            </div>
                        @endif
                    </div>

                    <!-- Collapsible Sections -->
                    <div class="space-y-6">
                        <!-- Experiments and Literature Data Sections (Livewire Component) -->
                        @livewire('profile-pagination', ['person' => $person, 'stats' => $stats])

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Organization Modal -->
    <div class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-40" id="profile_organization_form_modal" style="display:none;">
        <div class="bg-white rounded-lg shadow-lg w-full max-w-md max-h-[90vh] flex flex-col">
            <div class="p-6 border-b border-gray-200">
                <button id="profile_organization_form_close_btn" type="button" class="absolute top-2 right-2 text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times"></i>
                </button>
                <h2 class="text-lg font-bold mb-4 text-blue-700">Add New Organization</h2>
            </div>
            <div class="flex-1 overflow-y-auto p-6">
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Organization Name*</label>
                        <input type="text" id="new_organization_name" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                            required />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Organization Type*</label>
                        <input type="text" id="new_organization_type" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                            placeholder="Select or enter organization type"
                            list="organization_types" required/>
                        <datalist id="organization_types">
                            <option value="Government Agency">Government Agency</option>
                            <option value="Research Institute">Research Institute</option>
                            <option value="University">University</option>
                            <option value="Non-Profit Organization">Non-Profit Organization</option>
                            <option value="Private Company">Private Company</option>
                            <option value="Zoo">Zoo</option>
                            <option value="Wildlife Sanctuary">Wildlife Sanctuary</option>
                            <option value="Veterinary Clinic">Veterinary Clinic</option>
                            <option value="Laboratory">Laboratory</option>
                            <option value="Conservation Organization">Conservation Organization</option>
                            <option value="National Park">National Park</option>
                            <option value="Game Reserve">Game Reserve</option>
                            <option value="Museum">Museum</option>
                            <option value="Hospital">Hospital</option>
                            <option value="Pharmaceutical Company">Pharmaceutical Company</option>
                            <option value="Biotechnology Company">Biotechnology Company</option>
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Country*</label>
                        <input type="text" id="new_organization_country" 
                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600"
                            placeholder="Select or enter country"
                            list="countries" required/>
                        <datalist id="countries">
                            @foreach($countries ?? [] as $country)
                                <option value="{{ $country->name }}">{{ $country->name }}</option>
                            @endforeach
                        </datalist>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">City</label>
                        <input type="text" id="new_organization_city" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Region/Province</label>
                        <input type="text" id="new_organization_region" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <textarea id="new_organization_address" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600"></textarea>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Website</label>
                        <input type="url" id="new_organization_website" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="new_organization_description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-2 focus:ring-blue-600 focus:border-blue-600"></textarea>
                    </div>
                    <p class="text-sm text-gray-500">* Required fields</p>
                </div>
            </div>
            <div class="p-6 border-t border-gray-200 bg-gray-50">
                <div class="flex justify-end space-x-3">
                    <button type="button" id="profile_organization_cancel_btn" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        Cancel
                    </button>
                    <button type="button" id="profile_organization_save_btn" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Add Organization
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Organization modal functionality
        function showOrganizationModal() {
            const organizationModal = document.getElementById('profile_organization_form_modal');
            if (organizationModal) {
                organizationModal.style.display = 'flex';
            }
        }

        function hideOrganizationModal() {
            const organizationModal = document.getElementById('profile_organization_form_modal');
            if (organizationModal) {
                organizationModal.style.display = 'none';
            }
            // Clear form fields
            document.getElementById('new_organization_name').value = '';
            document.getElementById('new_organization_type').value = '';
            document.getElementById('new_organization_country').value = '';
            document.getElementById('new_organization_city').value = '';
            document.getElementById('new_organization_region').value = '';
            document.getElementById('new_organization_address').value = '';
            document.getElementById('new_organization_website').value = '';
            document.getElementById('new_organization_description').value = '';
        }

        // Event listeners for organization modal
        document.addEventListener('DOMContentLoaded', function() {
            const organizationBtn = document.getElementById('profile_organization_form_btn');
            const organizationCloseBtn = document.getElementById('profile_organization_form_close_btn');
            const organizationCancelBtn = document.getElementById('profile_organization_cancel_btn');
            const organizationSaveBtn = document.getElementById('profile_organization_save_btn');
            const organizationModal = document.getElementById('profile_organization_form_modal');

            console.log('Organization elements found:', {
                btn: !!organizationBtn,
                closeBtn: !!organizationCloseBtn,
                cancelBtn: !!organizationCancelBtn,
                saveBtn: !!organizationSaveBtn,
                modal: !!organizationModal
            });

            if (organizationBtn) {
                organizationBtn.addEventListener('click', showOrganizationModal);
            }
            if (organizationCloseBtn) {
                organizationCloseBtn.addEventListener('click', hideOrganizationModal);
            }
            if (organizationCancelBtn) {
                organizationCancelBtn.addEventListener('click', hideOrganizationModal);
            }
            if (organizationSaveBtn) {
                organizationSaveBtn.addEventListener('click', saveNewOrganization);
            }

            // Close modal when clicking outside
            if (organizationModal) {
                organizationModal.addEventListener('click', function(e) {
                    if (e.target === organizationModal) {
                        hideOrganizationModal();
                    }
                });
            }
        });

        // Inline editing functions
        function startEdit(field, currentValue) {
            const input = document.getElementById(field + '_input');
            if (input) {
                input.value = currentValue;
                // Find the Alpine component and update its data
                const alpineComponent = input.closest('[x-data]');
                if (alpineComponent && alpineComponent._x_dataStack && alpineComponent._x_dataStack[0]) {
                    alpineComponent._x_dataStack[0].editing[field] = true;
                }
            }
        }

        function saveEdit(field) {
            const input = document.getElementById(field + '_input');
            const value = input.value;

            fetch('/profile/update-field', {
                method: 'PATCH',
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
                if (data.success) {
                    // Find the Alpine component and update its data
                    const alpineComponent = input.closest('[x-data]');
                    if (alpineComponent && alpineComponent._x_dataStack && alpineComponent._x_dataStack[0]) {
                        alpineComponent._x_dataStack[0].editing[field] = false;
                    }
                    // Update the display text
                    const displaySpan = input.parentElement.previousElementSibling;
                    displaySpan.textContent = value || 'N/A';
                    
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message,
                            confirmButtonColor: '#d33'
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to update field',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        }

        function cancelEdit(field) {
            const input = document.getElementById(field + '_input');
            const alpineComponent = input.closest('[x-data]');
            if (alpineComponent && alpineComponent._x_dataStack && alpineComponent._x_dataStack[0]) {
                alpineComponent._x_dataStack[0].editing[field] = false;
            }
        }

        // Photo upload functions
        function confirmPhotoUpdate(input) {
            const file = input.files[0];
            if (!file) return;

            console.log('File selected:', file.name, file.size);

            // Check if user already has a photo
            const photoContainer = input.closest('.bg-white\\/20');
            const existingPhoto = photoContainer.querySelector('img');
            
            if (existingPhoto) {
                // Ask for confirmation before updating
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: 'Update Profile Photo?',
                        text: "This will replace your current profile photo. Are you sure?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, update it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            uploadProfilePhoto(file);
                        } else {
                            // Clear the input if user cancels
                            input.value = '';
                        }
                    });
                } else {
                    uploadProfilePhoto(file);
                }
            } else {
                // No existing photo, upload directly
                uploadProfilePhoto(file);
            }
        }

        function uploadProfilePhoto(file) {
            const formData = new FormData();
            formData.append('photo', file);

            console.log('Starting upload for file:', file.name);

            // Show loading state
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Uploading...',
                    text: 'Please wait while we upload your photo',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });
            }

            fetch('/profile/upload-photo', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => {
                console.log('Response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('Upload response:', data);
                if (data.success) {
                    // Close loading dialog
                    Swal.close();
                    
                    // Show success message
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                    
                    // Update the photo display without reload
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    Swal.close();
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: data.message || 'Failed to upload photo',
                            confirmButtonColor: '#d33'
                        });
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.close();
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Failed to upload photo. Please try again.',
                        confirmButtonColor: '#d33'
                    });
                }
            });
        }

        // Organization functions
        function saveNewOrganization() {
            const name = document.getElementById('new_organization_name').value.trim();
            const type = document.getElementById('new_organization_type').value.trim();
            const country = document.getElementById('new_organization_country').value.trim();
            const city = document.getElementById('new_organization_city').value;
            const region = document.getElementById('new_organization_region').value;
            const address = document.getElementById('new_organization_address').value;
            const website = document.getElementById('new_organization_website').value;
            const description = document.getElementById('new_organization_description').value;

            // Client-side validation matching server requirements
            if (!name) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Organization name is required',
                        confirmButtonColor: '#d33'
                    });
                } else {
                    alert('Organization name is required');
                }
                return;
            }

            if (!type) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Organization type is required',
                        confirmButtonColor: '#d33'
                    });
                } else {
                    alert('Organization type is required');
                }
                return;
            }

            if (!country) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: 'Country is required',
                        confirmButtonColor: '#d33'
                    });
                } else {
                    alert('Country is required');
                }
                return;
            }

            // Create form data with correct field names for OrganizationsController
            const formData = new FormData();
            formData.append('organization_name', name);
            formData.append('organization_type', type);
            formData.append('organization_country', country);
            formData.append('city', city);
            formData.append('region', region);
            formData.append('address', address);
            formData.append('website', website);
            formData.append('description', description);

            // Send to server using the profile-specific route
            fetch('/profile/organizations', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    // Try to get error details from response
                    return response.json().then(data => {
                        throw new Error(`Server error: ${response.status} - ${data.message || 'Unknown error'}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Close modal
                    hideOrganizationModal();

                    // Show success message
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success!',
                            text: data.message || 'Organization added successfully!',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }

                    // Reload page to show new organization
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Failed to add organization');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                
                // Check if it's a validation error
                if (error.message.includes('422') || error.message.includes('validation')) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Validation Error!',
                            text: 'Please fill in all required fields correctly.',
                            confirmButtonColor: '#d33'
                        });
                    }
                } else {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: error.message || 'Failed to add organization. Please try again.',
                            confirmButtonColor: '#d33'
                    });
                    }
                }
            });
        }

        function clearOrganizationForm() {
            document.getElementById('new_organization_name').value = '';
            document.getElementById('new_organization_type').value = '';
            document.getElementById('new_organization_country').value = '';
            document.getElementById('new_organization_city').value = '';
            document.getElementById('new_organization_region').value = '';
            document.getElementById('new_organization_address').value = '';
            document.getElementById('new_organization_website').value = '';
            document.getElementById('new_organization_description').value = '';
        }

        function validateOrganization(input) {
            const value = input.value.trim();
            const datalist = document.getElementById('organizations_list');
            const options = Array.from(datalist.options).map(option => option.value);
            
            if (value && !options.includes(value)) {
                input.setCustomValidity('Please select an organization from the list');
                input.classList.add('border-red-500');
            } else {
                input.setCustomValidity('');
                input.classList.remove('border-red-500');
            }
        }
    </script>
</x-layout>
