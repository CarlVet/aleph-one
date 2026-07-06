<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-tasks text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Core Operations</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-cyan-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Essential tools for managing tube registration, movement tracking, and position 
          management with comprehensive analytics capabilities.
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

        $can_register_tubes = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'tube_positions')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_tubes ? '/bank/tubes/create' : null"
          :is_allowed="$can_register_tubes"
          image_path="/images/storage_tubes_create.jpg"
          title="Registration"
          icon="fas fa-plus text-blue-300"
          arrow_text="Register tubes/Process samples"
          badge_text="Tracked"
          badge_icon="fas fa-vial"
          badge_color="blue">
          Register tubes and process samples with comprehensive tracking information and movement documentation.
        </x-box>
        <x-box 
          link="/bank/tubes/list"
          image_path="/images/storage_tubes_show.jpg"
          title="Tube Positions"
          icon="fas fa-list text-indigo-300"
          arrow_text="Browse all positions"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="indigo">
          View the history of tube positions and movements.
        </x-box>
        <x-box
          link="/bank/tubes/dashboard"
          image_path="/images/storage_tubes_show.jpg"
          title="Dashboard"
          icon="fas fa-chart-pie text-cyan-300"
          arrow_text="View analytics"
          badge_text="Map & Charts"
          badge_icon="fas fa-map-marked-alt"
          badge_color="cyan">
          Explore tube storage distribution, laboratory coverage, and movement timelines.
        </x-box>
      </div>
    </div>
  </div>
</x-layout>