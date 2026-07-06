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
          Essential tools for managing nucleic acid extraction, analysis, and molecular biology workflows 
          with comprehensive quality control and bioinformatics capabilities.
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

        $can_register_nucleic_acids = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'nucleic_acids')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_nucleic_acids ? '/samples/nucleic/create' : null"
          :is_allowed="$can_register_nucleic_acids"
          image_path="/images/nucleic_create.jpg"
          title="Extraction"
          icon="fas fa-vial text-blue-300"
          arrow_text="DNA/RNA extraction"
          badge_text="Extracted"
          badge_icon="fas fa-vial"
          badge_color="blue">
          Register nucleic acid extractions with comprehensive protocols, quality metrics, and sample tracking information.
        </x-box>
        <x-box 
          link="/samples/nucleic/list"
          image_path="/images/nucleic_index_2.jpg"
          title="Sample List"
          icon="fas fa-list text-indigo-300"
          arrow_text="Browse all samples"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="indigo">
          Comprehensive view of all nucleic acid samples with advanced filtering, quality metrics, and detailed molecular information.
        </x-box>
        <x-box 
          link="/samples/nucleic/dashboard"
          image_path="/images/nucleic_index.jpg"
          title="Dashboard"
          icon="fas fa-chart-bar text-teal-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="teal">
          Interactive dashboard with comprehensive analytics, quality metrics, and molecular biology insights for research planning.
        </x-box>
      </div>
    </div>
  </div>

  <!-- Specific Tasks Section -->
  <div class="py-4 bg-white">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-purple-500 to-violet-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-microscope text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Specialized Operations</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-purple-500 to-violet-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Advanced tools for sequence analysis, quality assessment, and specialized molecular biology workflows.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          link="/samples/nucleic/sequences"
          image_path="/images/sequence_icon.jpg"
          title="Sequences"
          icon="fas fa-code text-indigo-300"
          arrow_text="Bioinformatics analysis"
          badge_text="Bioinformatics"
          badge_icon="fas fa-code"
          badge_color="indigo">
          Explore and analyze sequence data with advanced bioinformatics tools and comprehensive sequence management capabilities.
        </x-box>
        <x-box 
          image_path="/images/nucleic_quality.png"
          title="Quality Assessment"
          icon="fas fa-check-circle text-teal-300"
          arrow_text="QC protocols"
          badge_text="Quality Control"
          badge_icon="fas fa-clipboard"
          badge_color="teal">
          Comprehensive quality control protocols and assessment tools for nucleic acid samples and molecular biology workflows.
        </x-box>
      </div>
    </div>
  </div>
</x-layout>
