<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-tasks text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Core Operations</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-indigo-500 to-purple-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Essential tools for managing box registration, position tracking, and content 
          organization with comprehensive analytics capabilities.
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

        $can_register_box_positions = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'box_positions')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_box_positions ? '/bank/boxes/create' : null"
          :is_allowed="$can_register_box_positions"
          image_path="/images/storage_locations.jpeg"
          title="Registration"
          icon="fas fa-plus text-indigo-300"
          arrow_text="Register box positions"
          badge_text="Organized"
          badge_icon="fas fa-box"
          badge_color="indigo">
          Register tubes and process samples with comprehensive tracking information and position documentation.
        </x-box>
        <x-box 
          link="/bank/boxes/list"
          image_path="/images/storage_boxes.jpeg"
          title="Box Positions"
          icon="fas fa-list text-blue-300"
          arrow_text="Browse all positions"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="blue">
          Analyze study distribution, methodologies, and key findings with a user-friendly dashboard and comprehensive filtering.
        </x-box>
      </div>
    </div>
  </div>
</x-layout>