<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-tasks text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Core Operations</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-green-500 to-emerald-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Essential tools for managing environmental sample registration, tracking, and analysis 
          with comprehensive ecosystem monitoring capabilities.
        </p>
      </div>

      @php
        $user = Auth::user();
        $project = null;

        if ($user && $user->people) {
            $project = $user->people
                ->projects()
                ->where('projects.id', session('selected_project_id'))
                ->withPivot('role', 'date_joined', 'permission')
                ->first();
        }

        $can_register_environment_samples = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'environment_samples')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_environment_samples ? '/samples/environment/create' : null"
          :is_allowed="$can_register_environment_samples"
          image_path="/images/environment_create.jpeg"
          title="Registration"
          icon="fas fa-plus text-green-300"
          arrow_text="Register new samples"
          badge_text="Multi-matrix"
          badge_icon="fas fa-globe"
          badge_color="green">
          Register newly collected environmental samples with comprehensive metadata, location tracking, and environmental condition documentation.
        </x-box>
        <x-box 
          link="/samples/environment/list"
          image_path="/images/environment_list.jpg"
          title="Sample List"
          icon="fas fa-list text-blue-300"
          arrow_text="Browse all samples"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="blue">
          Comprehensive view of all environmental samples with advanced filtering, location mapping, and detailed environmental information.
        </x-box>
        <x-box 
          link="/samples/environment/dashboard"
          image_path="/images/environment_dashboard.jpg"
          title="Dashboard"
          icon="fas fa-chart-bar text-teal-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="teal">
          Interactive dashboard with comprehensive analytics, environmental trends, and ecosystem health insights for research planning.
        </x-box>
      </div>
    </div>
  </div>

</x-layout>
  