<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-syringe text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Animal Vaccination Management</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-green-500 to-emerald-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Comprehensive tools for managing animal vaccination records, immunization schedules, and disease prevention protocols 
          with detailed tracking and compliance monitoring.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          link="/samples/animals/vaccination/create"
          image_path="/images/vaccine.jpeg"
          title="Vaccination Registration"
          icon="fas fa-plus text-green-300"
          arrow_text="Register new vaccinations"
          badge_text="Multi-species"
          badge_icon="fas fa-virus"
          badge_color="green">
          Register new animal vaccination records with comprehensive vaccine information, administration details, and schedule tracking.
        </x-box>
        <x-box 
          link="/samples/animals/vaccination/list"
          image_path="/images/vaccination_list.jpeg"
          title="Vaccination Records"
          icon="fas fa-list text-blue-300"
          arrow_text="Browse all records"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="blue">
          Comprehensive view of all animal vaccination records with advanced filtering, immunization tracking, and detailed vaccine information.
        </x-box>
        <x-box 
          link="/samples/animals/vaccination/dashboard"
          image_path="/images/dashboard.png"
          title="Vaccination Dashboard"
          icon="fas fa-chart-bar text-purple-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="purple">
          Interactive dashboard with comprehensive vaccination analytics, immunization coverage, and disease prevention insights for research planning.
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
          <i class="fas fa-shield-alt text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Immunization Management</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-purple-500 to-violet-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Specialized tools for immunization scheduling, vaccine type management, and disease prevention tracking.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          image_path="/images/immunization.jpeg"
          title="Immunization Scheduling"
          icon="fas fa-calendar-check text-purple-300"
          arrow_text="Schedule immunizations"
          badge_text="Scheduling"
          badge_icon="fas fa-clock"
          badge_color="purple">
          Schedule and track vaccination appointments, booster shots, and immunization protocols for optimal disease prevention.
        </x-box>

        <x-box 
          image_path="/images/vaccine_types.jpeg"
          title="Vaccine Management"
          icon="fas fa-virus-slash text-purple-300"
          arrow_text="Manage vaccines"
          badge_text="Vaccines"
          badge_icon="fas fa-syringe"
          badge_color="purple">
          Manage different vaccine types, track vaccine efficacy, and maintain comprehensive immunization records for research and regulatory compliance.
        </x-box>

        <x-box 
          image_path="/images/prevention.jpeg"
          title="Disease Prevention"
          icon="fas fa-shield-virus text-purple-300"
          arrow_text="Prevent diseases"
          badge_text="Prevention"
          badge_icon="fas fa-heart"
          badge_color="purple">
          Monitor disease prevention effectiveness, track immunization coverage, and maintain detailed records for population health management.
        </x-box>
      </div>
    </div>
  </div>
</x-layout> 