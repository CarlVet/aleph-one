<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-teal-500 to-cyan-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-tasks text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Core Operations</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-teal-500 to-cyan-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Essential tools for managing pooled sample registration, tracking, and analysis 
          with comprehensive high-throughput capabilities and statistical insights.
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

        $can_register_pools = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'pools')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_pools ? '/samples/pools/create' : null"
          :is_allowed="$can_register_pools"
          image_path="/images/pools_create.jpg"
          title="Registration"
          icon="fas fa-plus text-teal-300"
          arrow_text="Register new pools"
          badge_text="Pooled"
          badge_icon="fas fa-cubes"
          badge_color="teal">
          Register newly created pooled samples with comprehensive protocols, sample combinations, and analysis parameters.
        </x-box>
        <x-box 
          link="/samples/pools/list"
          image_path="/images/pools_list.jpg"
          title="Sample List"
          icon="fas fa-list text-blue-300"
          arrow_text="Browse all pools"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="blue">
          Comprehensive view of all pooled samples with advanced filtering, combination tracking, and detailed analysis information.
        </x-box>
        <x-box 
          link="/samples/pools/dashboard"
          image_path="/images/pools_dashboard.png"
          title="Dashboard"
          icon="fas fa-chart-bar text-purple-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="purple">
          Interactive dashboard with comprehensive statistics, pooling insights, and high-throughput analysis results for research planning.
        </x-box>
      </div>
    </div>
  </div>
</x-layout> 