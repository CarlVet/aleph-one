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
          Essential tools for managing literature data registration, systematic reviews, 
          and meta-analysis with comprehensive analytics capabilities.
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

        $can_register_literature = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'literature')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_literature ? '/meta/create' : null"
          :is_allowed="$can_register_literature"
          image_path="/images/meta_create.jpg"
          title="Register Literature Data"
          icon="fas fa-plus text-purple-300"
          arrow_text="Register study data"
          badge_text="Analysis"
          badge_icon="fas fa-chart-line"
          badge_color="purple">
          Register literature data for your meta-analysis. Input study details, methodologies, and results to build a structured database for analysis.
        </x-box>
        <x-box 
          link="/meta/list/animal"
          image_path="/images/meta_gallery.png"
          title="List of Literature Data"
          icon="fas fa-list text-indigo-300"
          arrow_text="Browse all data"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="indigo">
          Analyze study distribution, methodologies, and key findings with a user-friendly dashboard and comprehensive filtering.
        </x-box>
        <x-box 
          link="/meta/gallery"
          image_path="/images/meta_show.jpg"
          title="Dashboard of Literature Data"
          icon="fas fa-images text-blue-300"
          arrow_text="Visual insights"
          badge_text="Overview"
          badge_icon="fas fa-book"
          badge_color="blue">
          Explore a curated dashboard of literature data included in the meta-analysis with comprehensive visual insights and research documentation.
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
          Advanced tools for critical appraisal and quality assessment workflows.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-4 mx-auto">
        <x-box 
          image_path="/images/meta_appraisal.jpg"
          title="Critical Appraisal"
          icon="fas fa-clipboard-check text-indigo-300"
          arrow_text="Quality assessment"
          badge_text="Assessment"
          badge_icon="fas fa-clipboard-check"
          badge_color="indigo">
          Assess the risk of bias of the studies using standardized critical appraisal tools and quality assessment protocols.
        </x-box>
      </div>
    </div>
  </div>
</x-layout>