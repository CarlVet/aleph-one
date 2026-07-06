<x-layout>
  <!-- Storage Section -->
  <div class="py-4 bg-gray-50">
    <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
      <!-- Section Header -->
      <div class="text-center mb-4">
        <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-full shadow-lg mb-6">
          <i class="fas fa-tasks text-white text-2xl"></i>
        </div>
        <h2 class="text-3xl font-bold text-gray-900 mb-4">Storage Units</h2>
        <div class="w-24 h-1 bg-gradient-to-r from-indigo-500 to-purple-600 mx-auto mb-4"></div>
        <p class="text-gray-600 max-w-3xl mx-auto text-lg">
          Essential tools for managing sample storage infrastructure with comprehensive 
          tracking, organization, and inventory management capabilities.
        </p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-4xl mx-auto">
        <x-box 
          link="/bank/tubes"
          image_path="/images/storage_tubes.jpeg"
          title="Tubes"
          icon="fas fa-vial text-blue-300"
          arrow_text="Individual samples"
          badge_text="Tracked"
          badge_icon="fas fa-vial"
          badge_color="blue">
          Organize and keep track of your tubes. Register, update, and manage tube storage details in your database.
        </x-box>
        <x-box 
          link="/bank/boxes"
          image_path="/images/storage_boxes.jpg"
          title="Boxes"
          icon="fas fa-box text-indigo-300"
          arrow_text="Storage containers"
          badge_text="Organized"
          badge_icon="fas fa-box"
          badge_color="indigo">
          Efficiently manage storage boxes for your experiments. Keep a structured record of box contents and their organization.
        </x-box>
      </div>
    </div>
  </div>
</x-layout>