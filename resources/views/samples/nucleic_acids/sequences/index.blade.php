<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-code text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Core Operations</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-indigo-500 to-purple-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Essential tools for managing sequence data registration, analysis, and bioinformatics workflows 
          with comprehensive quality control and molecular insights.
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

        $can_register_sequences = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'nucleic_acids')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_sequences ? '/samples/nucleic/sequences/create' : null"
          :is_allowed="$can_register_sequences"
          image_path="/images/sequence_registration-2.png"
          title="Registration of Sequences"
          icon="fas fa-plus text-indigo-300"
          arrow_text="Register new sequences"
          badge_text="Sequenced"
          badge_icon="fas fa-dna"
          badge_color="indigo">
          Register newly sequenced nucleic acids.
        </x-box>

        <x-box 
          link="/samples/nucleic/sequences/list" 
          image_path="/images/sequence_list.png" 
          title="List of Sequences"
          icon="fas fa-list text-blue-300"
          arrow_text="Browse all sequences"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="blue">
          View list of sequences.
        </x-box>

        <x-box 
          link="/samples/nucleic/sequences/dashboard" 
          image_path="/images/sequence_registration.jpeg" 
          title="Dashboard of Sequences"
          icon="fas fa-chart-bar text-purple-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="purple">
          View a comprehensive dashboard of sequences.
        </x-box>
      </div>
    </div>
  </div>
</x-layout>
