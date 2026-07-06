<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-yellow-500 to-orange-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-tasks text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Core Operations</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-yellow-500 to-orange-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Essential tools for managing microbial culture registration, monitoring, and analysis 
          with comprehensive growth tracking and pathogen identification capabilities.
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

        $can_register_cultures = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'cultures')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_cultures ? '/samples/cultures/create' : null"
          :is_allowed="$can_register_cultures"
          image_path="/images/culture_create.jpeg"
          title="Registration"
          icon="fas fa-plus text-yellow-300"
          arrow_text="Register new cultures"
          badge_text="Growing"
          badge_icon="fas fa-petri-dish"
          badge_color="yellow">
          Register newly isolated microbial cultures with comprehensive protocols, growth conditions, and identification information.
        </x-box>
        <x-box 
          link="/samples/cultures/list"
          image_path="/images/cultures_list.jpeg"
          title="Cultures List"
          icon="fas fa-list text-orange-300"
          arrow_text="Browse all cultures"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="orange">
          Comprehensive view of all microbial cultures with advanced filtering, growth status tracking, and detailed culture information.
        </x-box>
        <x-box 
          link="/samples/cultures/dashboard"
          image_path="/images/culture_dashboard.png"
          title="Dashboard"
          icon="fas fa-chart-bar text-red-300"
          arrow_text="Gallery & analytics"
          badge_text="Gallery"
          badge_icon="fas fa-images"
          badge_color="red">
          Explore a curated gallery of images and records from your cultures with comprehensive analytics and insights.
        </x-box>
      </div>
    </div>
  </div>
</x-layout>
