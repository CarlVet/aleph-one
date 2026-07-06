<x-layout>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <!-- Header Section -->
            <div class="bg-gradient-to-r from-indigo-900 to-indigo-800 rounded-t-xl shadow-lg">
                <div class="px-6 py-8">
                    <div class="flex justify-between items-start">
                        <div class="flex-1">
                            <div class="flex items-center space-x-3 mb-4">
                                <div class="bg-white/20 p-3 rounded-lg">
                                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h1 class="text-3xl font-bold text-white">My Projects</h1>
                                </div>
                            </div>

                            <!-- Status Badge -->
                            <div class="flex items-center space-x-4">
                                <span class="text-indigo-100 text-sm">
                                    {{ ($activeProjects->count() + $completedProjects->count()) }} projects • {{ auth()->user()->people->first_name ?? 'User' }}
                                </span>
                            </div>
                        </div>

                        <div class="flex space-x-3">
                            <a href="{{ route('projects.create') }}"
                                class="group relative inline-flex items-center justify-center px-6 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-white/20 hover:bg-white/30 text-white rounded-xl shadow-lg hover:shadow-xl border border-white/30">
                                <i class="fas fa-plus-circle mr-2 text-lg group-hover:rotate-90 transition-transform duration-300"></i>
                                Create New Project
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="bg-white shadow-lg rounded-b-xl">
                <div class="p-8">
                    <!-- Filters -->
                    <div class="bg-gray-50 rounded-xl p-6 mb-8">
                        <div class="flex items-center justify-between gap-4 flex-wrap">
                            <div class="flex items-center gap-3">
                                <div class="bg-indigo-100 p-2 rounded-lg">
                                    <i class="fas fa-filter text-indigo-700"></i>
                                </div>
                                <div>
                                    <h2 class="text-lg font-semibold text-gray-900">Filters</h2>
                                    <div class="text-xs text-gray-500 mt-1">Filters apply to both active and completed projects.</div>
                                </div>
                            </div>
                            <a href="{{ route('profile.projects') }}"
                               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 text-sm font-semibold rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                <i class="fas fa-times"></i>
                                Clear
                            </a>
                        </div>

                        <form method="GET" action="{{ route('profile.projects') }}" class="mt-5 grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Search (code/title)</label>
                                <input type="text" name="search" value="{{ $filters['search'] ?? '' }}"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300 bg-white"
                                       placeholder="e.g. PA1, malaria...">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Type</label>
                                <input type="text" name="type" value="{{ $filters['type'] ?? '' }}"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300 bg-white"
                                       placeholder="PhD project, MSc...">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Your role</label>
                                <input type="text" name="role" value="{{ $filters['role'] ?? '' }}"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300 bg-white"
                                       placeholder="Supervisor, PI...">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Funding (source/reference)</label>
                                <input type="text" name="funding" value="{{ $filters['funding'] ?? '' }}"
                                       class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-200 focus:border-indigo-300 bg-white"
                                       placeholder="Wellcome, grant...">
                            </div>

                            <div class="md:col-span-4 flex justify-end">
                                <button type="submit"
                                        class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-lg shadow-sm transition-colors duration-200">
                                    <i class="fas fa-search"></i>
                                    Apply
                                </button>
                            </div>
                        </form>
                    </div>

                    <form id="projectSelectionForm" action="{{ route('project.select') }}" method="POST">
                        @csrf
                        
                        <!-- Guest Mode Option -->
                        <div class="bg-gradient-to-r from-purple-50 to-blue-50 border border-purple-200 rounded-xl p-6 mb-8">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-4">
                                    <input type="radio" 
                                        name="selected_project" 
                                        value="guest"
                                        id="project_guest"
                                        class="h-5 w-5 text-purple-600 focus:ring-purple-500 border-gray-300"
                                        {{ !session('selected_project_id') ? 'checked' : '' }}
                                        onchange="submitProjectSelection(this)"
                                    >
                                    <div class="flex items-center space-x-3">
                                        <div class="bg-purple-100 p-3 rounded-lg">
                                            <i class="fas fa-eye text-2xl text-purple-600"></i>
                                        </div>
                                        <div>
                                            <label for="project_guest" class="text-xl font-semibold text-purple-800">
                                                Guest Mode
                                            </label>
                                            <p class="text-sm text-purple-600 mt-1">
                                                Explore public data from other projects
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                <div class="text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                        <i class="fas fa-unlock mr-1"></i>Public Content Only
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Active Projects Section -->
                        @if($activeProjects->count() > 0)
                            <div class="mb-8">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="bg-green-100 p-2 rounded-lg">
                                        <i class="fas fa-play-circle text-xl text-green-600"></i>
                                    </div>
                                    <h2 class="text-2xl font-bold text-gray-900">Active Projects ({{ $activeProjects->count() }})</h2>
                                </div>
                                
                                <div class="border border-gray-200 rounded-xl shadow-xl bg-white">
                                    <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-green-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Intended End Date</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Funding</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Your Role</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collaborators</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($activeProjects as $project)
                                                <tr class="hover:bg-green-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01]">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        <div class="flex items-center">
                                                            <input type="radio" 
                                                                name="selected_project" 
                                                                value="{{ $project->id }}"
                                                                id="project_{{ $project->id }}"
                                                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
                                                                {{ session('selected_project_id') == $project->id ? 'checked' : '' }}
                                                                onchange="submitProjectSelection(this)"
                                                            >
                                                            <a href="{{ route('projects.profile', $project->code) }}" class="ml-3 font-semibold text-blue-600 hover:text-blue-800">
                                                                {{ $project->code }}
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500">
                                                        <button type="button" 
                                                            onclick="showFullTitle('{{ $project->code }}', '{{ addslashes($project->title) }}')"
                                                            class="max-w-[180px] truncate hover:text-green-600 focus:outline-none transition-colors duration-200">
                                                            {{ $project->title }}
                                                        </button>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $project->date_started ? $project->date_started : 'N/A' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $project->date_end_intended ? $project->date_end_intended : 'Ongoing' }}</td>
                                                    <td class="px-6 py-4 text-sm text-gray-500">
                                                        @if(($project->fundings ?? collect())->count() > 0)
                                                            <div class="flex flex-wrap gap-2">
                                                                @foreach($project->fundings as $f)
                                                                    <a href="{{ route('fundings.profile', $f) }}"
                                                                       class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors">
                                                                        {{ $f->source ?? 'Funding' }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="text-gray-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                                {{ $project->pivot->role }}
                                                            </span>
                                                            @if($project->pivot->permission === 'admin')
                                                                <a href="{{ route('projects.edit', ['project' => $project->id, 'section' => 'general']) }}" 
                                                                    class="text-green-600 hover:text-green-800 transition-colors duration-200">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500">
                                                        <div class="space-y-1">
                                                            @foreach($project->people as $collaborator)
                                                                @if($collaborator->id !== $person->id)
                                                                    <div class="flex items-center text-xs">
                                                                        <x-people-logo :person="$collaborator" width="30" />
                                                                        <a href="{{ route('profile.show', $collaborator->id) }}" class="ml-2 hover:text-green-600 transition-colors duration-200">
                                                                            {{ $collaborator->first_name }} {{ $collaborator->last_name }}
                                                                        </a>
                                                                        <span class="ml-4 whitespace-nowrap px-2 py-1 text-xs rounded-full {{ $collaborator->pivot->role === $project->pivot->role ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                                                            {{ $collaborator->pivot->role }}
                                                                        </span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        @if($project->pivot->permission === 'admin')
                                                            <button type="button"
                                                                    onclick="markProjectComplete({{ $project->id }}, '{{ $project->code }}', '{{ $project->date_started ?? '' }}')"
                                                                    class="inline-flex items-center gap-2 px-3 py-2 bg-gray-800 hover:bg-gray-900 text-white text-xs font-semibold rounded-lg transition-colors duration-200">
                                                                <i class="fas fa-check"></i>
                                                                Mark complete
                                                            </button>
                                                        @else
                                                            <span class="text-gray-400">—</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    {{ $activeProjects->links() }}
                                </div>
                            </div>
                        @endif

                        <!-- Completed Projects Section -->
                        @if($completedProjects->count() > 0)
                            <div class="mb-8">
                                <div class="flex items-center space-x-3 mb-4">
                                    <div class="bg-gray-100 p-2 rounded-lg">
                                        <i class="fas fa-check-circle text-xl text-gray-600"></i>
                                    </div>
                                    <h2 class="text-2xl font-bold text-gray-900">Completed Projects ({{ $completedProjects->count() }})</h2>
                                </div>
                                
                                <div class="border border-gray-200 rounded-xl shadow-xl bg-white">
                                    <div class="overflow-x-auto">
                                    <table class="min-w-full divide-y divide-gray-200">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Title</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Start Date</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Official End Date</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Funding</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Your Role</th>
                                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Collaborators</th>
                                            </tr>
                                        </thead>
                                        <tbody class="bg-white divide-y divide-gray-200">
                                            @foreach($completedProjects as $project)
                                                <tr class="hover:bg-gray-50 transition-all duration-200 ease-in-out transform hover:scale-[1.01] opacity-75">
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                        <div class="flex items-center">
                                                            <input type="radio" 
                                                                name="selected_project" 
                                                                value="{{ $project->id }}"
                                                                id="project_{{ $project->id }}"
                                                                class="h-4 w-4 text-gray-600 focus:ring-gray-500 border-gray-300"
                                                                {{ session('selected_project_id') == $project->id ? 'checked' : '' }}
                                                                onchange="submitProjectSelection(this)"
                                                            >
                                                            <a href="{{ route('projects.profile', $project->code) }}" class="ml-3 font-semibold text-blue-600 hover:text-blue-800">
                                                                {{ $project->code }}
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500">
                                                        <button type="button" 
                                                            onclick="showFullTitle('{{ $project->code }}', '{{ addslashes($project->title) }}')"
                                                            class="max-w-[180px] truncate hover:text-gray-600 focus:outline-none transition-colors duration-200">
                                                            {{ $project->title }}
                                                        </button>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $project->date_started ? $project->date_started : 'N/A' }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $project->date_end ? $project->date_end : 'N/A' }}</td>
                                                    <td class="px-6 py-4 text-sm text-gray-500">
                                                        @if(($project->fundings ?? collect())->count() > 0)
                                                            <div class="flex flex-wrap gap-2">
                                                                @foreach($project->fundings as $f)
                                                                    <a href="{{ route('fundings.profile', $f) }}"
                                                                       class="px-2 py-1 text-xs font-semibold rounded-full bg-purple-100 text-purple-800 hover:bg-purple-200 transition-colors">
                                                                        {{ $f->source ?? 'Funding' }}
                                                                    </a>
                                                                @endforeach
                                                            </div>
                                                        @else
                                                            <span class="text-gray-400">—</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        <div class="flex items-center space-x-2">
                                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                                {{ $project->pivot->role }}
                                                            </span>
                                                            @if($project->pivot->permission === 'admin')
                                                                <a href="{{ route('projects.edit', ['project' => $project->id, 'section' => 'general']) }}" 
                                                                    class="text-gray-600 hover:text-gray-800 transition-colors duration-200">
                                                                    <i class="fas fa-edit"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="px-6 py-4 text-sm text-gray-500">
                                                        <div class="space-y-1">
                                                            @foreach($project->people as $collaborator)
                                                                @if($collaborator->id !== $person->id)
                                                                    <div class="flex items-center text-xs">
                                                                        <x-people-logo :person="$collaborator" width="30" />
                                                                        <a href="{{ route('profile.show', $collaborator->id) }}" class="ml-2 hover:text-gray-600 transition-colors duration-200">
                                                                            {{ $collaborator->first_name }} {{ $collaborator->last_name }}
                                                                        </a>
                                                                        <span class="ml-4 whitespace-nowrap px-2 py-1 text-xs rounded-full {{ $collaborator->pivot->role === $project->pivot->role ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800' }}">
                                                                            {{ $collaborator->pivot->role }}
                                                                        </span>
                                                                    </div>
                                                                @endif
                                                            @endforeach
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                    </div>
                                </div>
                                <div class="mt-4">
                                    {{ $completedProjects->links() }}
                                </div>
                            </div>
                        @endif

                        <!-- No Projects Message -->
                        @if($activeProjects->count() == 0 && $completedProjects->count() == 0)
                            <div class="text-center py-12">
                                <div class="bg-gray-50 rounded-xl p-8">
                                    <div class="bg-gray-100 p-4 rounded-full w-16 h-16 mx-auto mb-4 flex items-center justify-center">
                                        <i class="fas fa-folder-open text-2xl text-gray-400"></i>
                                    </div>
                                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Projects Found</h3>
                                    <p class="text-gray-500 mb-6">You haven't been added to any projects yet.</p>
                                    <a href="{{ route('projects.create') }}" 
                                        class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                        <i class="fas fa-plus mr-2"></i>
                                        Create Your First Project
                                    </a>
                                </div>
                            </div>
                        @endif
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layout>

<div id="titleModal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden" onclick="closeModal()">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full" onclick="event.stopPropagation()">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle"></h3>
                <button type="button" onclick="closeModal()" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
            <p class="text-gray-500 whitespace-pre-wrap" id="modalContent"></p>
        </div>
    </div>
</div>

<!-- Project Form Modal -->
<div id="project_form_modal" class="fixed inset-0 bg-gray-500 bg-opacity-75 hidden" onclick="closeProjectModal()">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full" onclick="event.stopPropagation()">
            <div class="flex justify-between items-start mb-4">
                <h3 class="text-lg font-medium text-gray-900">Create New Project</h3>
                <button type="button" onclick="closeProjectModal()" class="text-gray-400 hover:text-gray-500">
                    <span class="sr-only">Close</span>
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <form id="project_form" class="space-y-6">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Left Column -->
                    <div class="space-y-6">
                        <div>
                            <label for="code" class="block text-sm font-medium text-gray-700">Project Code</label>
                            <input type="text" name="code" id="code" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="e.g., PA1">
                        </div>

                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700">Project Type</label>
                            <select name="type" id="type" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                <option value="">Select type</option>
                                <option value="PhD project">PhD project</option>
                                <option value="MSc project">MSc project</option>
                                <option value="Research assignment">Research assignment</option>
                                <option value="Publication-related project">Publication-related project</option>
                            </select>
                        </div>

                        <div>
                            <label for="date_started" class="block text-sm font-medium text-gray-700">Start Date</label>
                            <input type="date" name="date_started" id="date_started" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Project Title</label>
                            <textarea name="title" id="title" rows="3" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="Enter project title"></textarea>
                        </div>

                        <div>
                            <label for="ethics_ref" class="block text-sm font-medium text-gray-700">Ethics Reference</label>
                            <input type="text" name="ethics_ref" id="ethics_ref"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"
                                placeholder="e.g., ETH12345">
                        </div>

                        <div>
                            <label for="date_end_intended" class="block text-sm font-medium text-gray-700">Intended End Date</label>
                            <input type="date" name="date_end_intended" id="date_end_intended"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-3 mt-6">
                    <button type="button" onclick="closeProjectModal()"
                        class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Create Project
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@if (session('success'))
    <div id="successMessage" class="hidden">{{ session('success') }}</div>
@endif

@if (session('error'))
    <div id="errorMessage" class="hidden">{{ session('error') }}</div>
@endif

<script>
function showFullTitle(code, title) {
    document.getElementById('modalTitle').textContent = `Project ${code} - Full Title`;
    document.getElementById('modalContent').textContent = title;
    document.getElementById('titleModal').classList.remove('hidden');
}

function closeModal() {
    document.getElementById('titleModal').classList.add('hidden');
}

function showProjectModal() {
    document.getElementById('project_form_modal').classList.remove('hidden');
}

function closeProjectModal() {
    document.getElementById('project_form_modal').classList.add('hidden');
}

document.getElementById('create_project_btn').addEventListener('click', showProjectModal);

document.getElementById('project_form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    fetch('/projects', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(Object.fromEntries(formData))
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Project created successfully!',
            }).then(() => {
                window.location.reload();
            });
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'An error occurred while creating the project.',
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'An error occurred while creating the project.',
        });
    });
});

function submitProjectSelection(radio) {
    const form = document.getElementById('projectSelectionForm');
    const formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: data.message,
                position: 'center',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                width: 'auto',
                padding: '2em',
                customClass: {
                    popup: 'animate__animated animate__fadeInDown'
                }
            }).then(() => {
                // Only reload after the alert is closed
                window.location.reload();
            });
        }
    })
    .catch(error => {
        Swal.fire({
            icon: 'error',
            title: 'An error occurred while selecting the project',
            position: 'center',
            showConfirmButton: false,
            timer: 2000,
            timerProgressBar: true,
            width: 'auto',
            padding: '2em'
        });
    });
}

function markProjectComplete(projectId, projectCode, startDate) {
    const today = new Date().toISOString().slice(0, 10);

    Swal.fire({
        title: `Mark project ${projectCode} complete`,
        html: `
            <div class="text-left">
                <label class="block text-sm font-medium text-gray-700 mb-2">Official end date</label>
                <input id="official_end_date" type="date" class="swal2-input" value="${today}">
                <div class="text-xs text-gray-500 mt-2">This will move the project to the “Completed Projects” section.</div>
            </div>
        `,
        focusConfirm: false,
        showCancelButton: true,
        confirmButtonText: 'Mark complete',
        cancelButtonText: 'Cancel',
        preConfirm: () => {
            const dateEnd = document.getElementById('official_end_date')?.value;
            if (!dateEnd) {
                Swal.showValidationMessage('Please choose an official end date.');
                return false;
            }
            if (startDate && dateEnd < startDate) {
                Swal.showValidationMessage('Official end date cannot be before the start date.');
                return false;
            }
            return { date_end: dateEnd };
        }
    }).then((result) => {
        if (!result.isConfirmed) return;

        fetch(`/projects/${projectId}/complete`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(result.value)
        })
        .then(r => r.json().then(data => ({ ok: r.ok, data })))
        .then(({ ok, data }) => {
            if (!ok || !data.success) {
                Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Failed to mark project complete.' });
                return;
            }
            Swal.fire({ icon: 'success', title: 'Success', text: data.message || 'Project marked complete.', timer: 1500, showConfirmButton: false })
                .then(() => window.location.reload());
        })
        .catch(() => {
            Swal.fire({ icon: 'error', title: 'Error', text: 'Failed to mark project complete.' });
        });
    });
}

// Handle flash messages
document.addEventListener('DOMContentLoaded', function() {
    @if(session('success'))
        Swal.fire({
            toast: true,
            icon: 'success',
            title: "{{ session('success') }}",
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    @endif

    @if(session('error'))
        Swal.fire({
            toast: true,
            icon: 'error',
            title: "{{ session('error') }}",
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });
    @endif
});
</script> 