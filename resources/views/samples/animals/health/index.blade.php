<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-red-500 to-pink-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-heartbeat text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Animal Health Management</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-red-500 to-pink-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Comprehensive tools for documenting animal health status, clinical signs, lesions, and veterinary assessments 
          with detailed health monitoring and treatment tracking.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          link="/samples/animals/health/create"
          image_path="/images/signs.jpeg"
          title="Health Registration"
          icon="fas fa-plus text-red-300"
          arrow_text="Register new health records"
          badge_text="Multi-species"
          badge_icon="fas fa-heartbeat"
          badge_color="red">
          Register new animal health assessments with comprehensive clinical documentation, lesion identification, and health status tracking.
        </x-box>
        <x-box 
          link="/samples/animals/health/list"
          image_path="/images/animal_health.webp"
          title="Health Records"
          icon="fas fa-list text-blue-300"
          arrow_text="Browse all records"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="blue">
          Comprehensive view of all animal health records with advanced filtering, species categorization, and detailed health information.
        </x-box>
        <x-box 
          link="/samples/animals/health/dashboard"
          image_path="/images/dashboard.png"
          title="Health Dashboard"
          icon="fas fa-chart-bar text-green-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="green">
          Interactive dashboard with comprehensive health analytics, species health trends, and clinical monitoring insights for research planning.
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
          <i class="fas fa-stethoscope text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Clinical Documentation</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-purple-500 to-violet-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Specialized tools for clinical signs documentation, lesion assessment, and veterinary examination workflows.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          image_path="/images/signs.jpeg"
          title="Clinical Signs"
          icon="fas fa-notes-medical text-purple-300"
          arrow_text="Document symptoms"
          badge_text="Clinical Data"
          badge_icon="fas fa-user-md"
          badge_color="purple">
          Document clinical signs, symptoms, and behavioral observations during veterinary examinations and health assessments.
        </x-box>

        <x-box 
          image_path="/images/lesions.jpeg"
          title="Lesion Assessment"
          icon="fas fa-search-plus text-purple-300"
          arrow_text="Record lesions"
          badge_text="Pathology"
          badge_icon="fas fa-microscope"
          badge_color="purple">
          Record and categorize lesions, injuries, and pathological findings with detailed descriptions and photographic documentation.
        </x-box>

        <x-box 
          image_path="/images/health_monitoring.jpeg"
          title="Health Monitoring"
          icon="fas fa-chart-line text-purple-300"
          arrow_text="Track health trends"
          badge_text="Monitoring"
          badge_icon="fas fa-eye"
          badge_color="purple">
          Monitor health trends, track recovery progress, and maintain comprehensive health histories for research and veterinary care.
        </x-box>
      </div>
    </div>
  </div>
</x-layout> 