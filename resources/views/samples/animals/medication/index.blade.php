<x-layout>
  <!-- General Tasks Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-prescription-bottle text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Animal Medication Management</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-blue-500 to-indigo-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Comprehensive tools for managing animal medication records, treatment schedules, dosages, and veterinary prescriptions 
          with detailed tracking and compliance monitoring.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          link="/samples/animals/medication/create"
          image_path="/images/medication.jpg"
          title="Medication Registration"
          icon="fas fa-plus text-blue-300"
          arrow_text="Register new medications"
          badge_text="Multi-species"
          badge_icon="fas fa-pills"
          badge_color="blue">
          Register new animal medication records with comprehensive dosage information, treatment schedules, and prescription details.
        </x-box>
        <x-box 
          link="/samples/animals/medication/list"
          image_path="/images/medication_list.jpeg"
          title="Medication Records"
          icon="fas fa-list text-green-300"
          arrow_text="Browse all records"
          badge_text="Advanced Search"
          badge_icon="fas fa-search"
          badge_color="green">
          Comprehensive view of all animal medication records with advanced filtering, treatment tracking, and detailed prescription information.
        </x-box>
        <x-box 
          link="/samples/animals/medication/dashboard"
          image_path="/images/dashboard.png"
          title="Medication Dashboard"
          icon="fas fa-chart-bar text-purple-300"
          arrow_text="Analytics & insights"
          badge_text="Analytics"
          badge_icon="fas fa-chart-line"
          badge_color="purple">
          Interactive dashboard with comprehensive medication analytics, treatment effectiveness, and compliance monitoring for research planning.
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
          <i class="fas fa-pills text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Treatment Management</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-purple-500 to-violet-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Specialized tools for dosage calculation, treatment scheduling, and medication compliance tracking.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <x-box 
          image_path="/images/dosage.jpeg"
          title="Dosage Management"
          icon="fas fa-calculator text-purple-300"
          arrow_text="Calculate dosages"
          badge_text="Dosage"
          badge_icon="fas fa-weight"
          badge_color="purple">
          Calculate and manage medication dosages based on animal weight, species, and treatment requirements with safety protocols.
        </x-box>

        <x-box 
          image_path="/images/schedule.jpeg"
          title="Treatment Scheduling"
          icon="fas fa-calendar-alt text-purple-300"
          arrow_text="Schedule treatments"
          badge_text="Scheduling"
          badge_icon="fas fa-clock"
          badge_color="purple">
          Schedule and track medication administration times, treatment duration, and follow-up appointments for optimal care.
        </x-box>

        <x-box 
          image_path="/images/compliance.jpeg"
          title="Compliance Tracking"
          icon="fas fa-check-circle text-purple-300"
          arrow_text="Monitor compliance"
          badge_text="Compliance"
          badge_icon="fas fa-clipboard-check"
          badge_color="purple">
          Monitor medication compliance, track treatment effectiveness, and maintain detailed records for regulatory and research purposes.
        </x-box>
      </div>
    </div>
  </div>
</x-layout> 