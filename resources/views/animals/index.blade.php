<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-paw text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Animal Management</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-green-500 to-emerald-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Comprehensive tools for managing animal registration, tracking, and health monitoring 
          with species-specific protocols and detailed individual records.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          link="/animals/create"
          image_path="/images/animals_index.jpeg"
          title="Registration"
          icon="fas fa-plus text-green-300"
          arrow_text="Register new animals"
          badge_text="Multi-species"
          badge_icon="fas fa-hippo"
          badge_color="green">
          Register newly identified animals with comprehensive metadata, species identification, and individual tracking information.
        </x-box>
        <x-box 
          link="/animals/list"
          image_path="/images/animals_index_1.jpeg"
          title="Animal List"
          icon="fas fa-list text-blue-300"
          arrow_text="Browse all animals"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="blue">
          Comprehensive view of all registered animals with advanced filtering, species categorization, and detailed individual information.
        </x-box>
        <x-box 
          link="/animals/dashboard"
          image_path="/images/dashboard.png"
          title="Dashboard"
          icon="fas fa-chart-bar text-green-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="green">
          Interactive dashboard with comprehensive analytics, species distribution, and population monitoring insights for research planning.
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
          <i class="fas fa-heartbeat text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Health & Monitoring</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-purple-500 to-violet-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Specialized tools for health data documentation, veterinary care, and comprehensive monitoring workflows.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          link="/samples/animals/health/create"
          image_path="/images/signs.jpeg"
          title="Health Data"
          icon="fas fa-heartbeat text-purple-300"
          arrow_text="Veterinary documentation"
          badge_text="Clinical Data"
          badge_icon="fas fa-user-md"
          badge_color="purple">
          Document clinical signs, lesions, and health conditions observed during veterinary fieldwork and wildlife monitoring.
        </x-box>

        <x-box 
          link="/samples/animals/medication/create"
          image_path="/images/medication.jpg"
          title="Medication Data"
          icon="fas fa-prescription-bottle text-purple-300"
          arrow_text="Record & browse"
          badge_text="Manage medications"
          badge_icon="fas fa-pills"
          badge_color="purple">
          Record medication history, dosages, and treatment schedules for veterinary care and research protocols.
        </x-box>

        <x-box 
          link="/samples/animals/vaccination/create"
          image_path="/images/vaccine.jpeg"
          title="Vaccination Data"
          icon="fas fa-syringe text-purple-300"
          arrow_text="Manage vaccines"
          badge_text="Prophylaxis and prevention"
          badge_icon="fas fa-virus"
          badge_color="purple">
          Record vaccination history, schedules, and immunization protocols for disease prevention and research.
        </x-box>
      </div>
    </div>
  </div>
</x-layout> 