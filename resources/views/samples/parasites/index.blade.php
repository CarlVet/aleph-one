<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-red-500 to-pink-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-tasks text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Core Operations</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-red-500 to-pink-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Essential tools for managing parasite identification, tracking, and analysis 
          with comprehensive research and documentation capabilities.
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

        $can_register_parasite_samples = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'parasite_samples')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_parasite_samples ? '/samples/parasites/create' : null"
          :is_allowed="$can_register_parasite_samples"
          image_path="/images/parasite_id.png"
          title="Identification"
          icon="fas fa-search text-red-300"
          arrow_text="Register new parasites"
          badge_text="ID Ready"
          badge_icon="fas fa-search"
          badge_color="red">
          Register newly identified parasites with comprehensive taxonomic information, morphological characteristics, and host associations.
        </x-box>
        <x-box 
          link="/samples/parasites/list"
          image_path="/images/parasite_index.jpeg"
          title="Sample List"
          icon="fas fa-list text-orange-300"
          arrow_text="Browse all parasites"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="orange">
          Comprehensive view of all parasite samples with advanced filtering, taxonomic classification, and detailed identification information.
        </x-box>
        <x-box 
          link="/samples/parasites/dashboard"
          image_path="/images/parasite_dashboard.jpeg"
          title="Dashboard"
          icon="fas fa-chart-bar text-purple-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="purple">
          Interactive dashboard with comprehensive analytics, parasite distribution, and research insights for epidemiological studies.
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
          Advanced tools for parasite dissection and specialized analysis workflows.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_parasite_samples ? '/samples/parasites/dissection/create' : null"
          :is_allowed="$can_register_parasite_samples"
          image_path="/images/parasite_process.jpeg"
          title="Dissection"
          icon="fas fa-cut text-red-300"
          arrow_text="Laboratory workflows"
          badge_text="Processing"
          badge_icon="fas fa-cogs"
          badge_color="red">
          Dissect parasites for further testing and analysis with standardized protocols and detailed documentation procedures.
        </x-box>
      </div>
    </div>
  </div>
</x-layout>
