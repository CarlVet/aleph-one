<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-purple-500 to-violet-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-tasks text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Core Operations</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-purple-500 to-violet-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Essential tools for managing experiment registration, tracking, and analysis 
          with comprehensive research and statistical capabilities.
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

        $can_register_experiments = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'experiments')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_experiments ? '/experiments/create' : null"
          :is_allowed="$can_register_experiments"
          image_path="/images/pathogens_register2.jpeg"
          title="Registration"
          icon="fas fa-plus text-purple-300"
          arrow_text="Register new experiments"
          badge_text="Research"
          badge_icon="fas fa-flask"
          badge_color="purple">
          Register newly designed experiments with comprehensive protocols, parameters, and research objectives documentation.
        </x-box>
        <x-box 
          link="/experiments/list"
          image_path="/images/pathogen.jpg"
          title="Experiment List"
          icon="fas fa-list text-blue-300"
          arrow_text="Browse all experiments"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="blue">
          Discover pathogen occurrence and distribution through an intuitive and interactive dashboard with comprehensive filtering.
        </x-box>
        <x-box 
          link="/experiments/dashboard"
          image_path="/images/experiments_dashboard.png"
          title="Dashboard"
          icon="fas fa-chart-bar text-green-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="green">
          Explore a curated gallery of images and records from your experiments with comprehensive analytics and research insights.
        </x-box>
      </div>
    </div>
  </div>

  <!-- Specific Tasks Section -->
  <div class="py-4 bg-white">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-microscope text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Specialized Operations</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-indigo-500 to-purple-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Advanced tools for protocol management and statistical analysis workflows.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
        <x-box 
          image_path="/images/protocol.png"
          title="Protocol Management"
          icon="fas fa-clipboard-list text-indigo-300"
          arrow_text="Laboratory procedures"
          badge_text="Protocols"
          badge_icon="fas fa-clipboard-list"
          badge_color="indigo">
          Consult and register laboratory protocols with standardized procedures and comprehensive documentation.
        </x-box>
        <x-box 
          image_path="/images/statistics.webp"
          title="Inferential Statistics"
          icon="fas fa-chart-line text-blue-300"
          arrow_text="Advanced analysis"
          badge_text="Statistics"
          badge_icon="fas fa-chart-line"
          badge_color="blue">
          Discover insights into pathogen prevalence with confidence intervals, explore risk factors, and delve into association and regression analyses.
        </x-box>
      </div>
    </div>
  </div>
</x-layout>