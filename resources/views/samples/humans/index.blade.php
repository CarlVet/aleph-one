<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-pink-500 to-rose-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-tasks text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Core Tasks</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-pink-500 to-rose-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Essential tools for managing human sample registration, tracking, and analysis 
          with full compliance and ethical oversight.
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

        $can_register_human_samples = $user && $project
            ? \App\Support\ProjectPermission::canWrite($user, (int) session('selected_project_id'), 'human_samples')
            : false;
      @endphp

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          :link="$can_register_human_samples ? '/samples/humans/create' : null"
          :is_allowed="$can_register_human_samples"
          image_path="/images/human_samples.jpg"
          title="Registration"
          icon="fas fa-plus text-pink-300"
          arrow_text="Register new samples"
          badge_text="HIPAA Compliant"
          badge_icon="fas fa-shield-alt"
          badge_color="pink">
          Register newly collected human samples with comprehensive metadata, consent tracking, and ethical compliance documentation.
        </x-box>
        <x-box 
          link="/samples/humans/list"
          image_path="/images/human_samples_list.jpeg"
          title="Sample List"
          icon="fas fa-list text-blue-300"
          arrow_text="Browse all samples"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="blue">
          Comprehensive view of all human samples with advanced filtering, search capabilities, and detailed sample information.
        </x-box>
        <x-box 
          link="/samples/humans/dashboard"
          image_path="/images/human_dashboard.jpeg"
          title="Dashboard"
          icon="fas fa-chart-bar text-green-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="green">
          Interactive dashboard with comprehensive analytics, sample statistics, and visual insights for research planning and reporting.
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
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Specialized Tasks</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-purple-500 to-violet-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Advanced tools for health data documentation, sample processing, and specialized analysis workflows.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          image_path="/images/human_health.jpeg"
          title="Health Data"
          icon="fas fa-heartbeat text-purple-300"
          arrow_text="Clinical documentation"
          badge_text="Clinical Data"
          badge_icon="fas fa-user-md"
          badge_color="purple">
          Document clinical signs, symptoms, and health conditions observed during sample collection with comprehensive medical documentation.
        </x-box>

        <x-box 
          image_path="/images/medication.jpg"
          title="Medication Data"
          icon="fas fa-prescription-bottle text-purple-300"
          arrow_text="Record & browse"
          badge_text="Manage medications"
          badge_icon="fas fa-pills"
          badge_color="purple">
          Record medication history.
        </x-box>

        <x-box 
          image_path="/images/vaccine.jpeg"
          title="Vaccination Data"
          icon="fas fa-syringe text-purple-300"
          arrow_text="Manage vaccines"
          badge_text="Prophylaxis and prevention"
          badge_icon="fas fa-virus"
          badge_color="purple">
          Record vaccination history.
        </x-box>
      </div>
    </div>
  </div>
</x-layout>
