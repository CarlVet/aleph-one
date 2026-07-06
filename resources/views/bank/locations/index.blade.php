<x-layout>
    <x-slot:heading>
      Storage
    </x-slot:heading> 
  
    <!-- Storage Section -->
    <div>
      <h2 class="text-2xl font-semibold mb-4 text-center">Storage Units</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
  
        <x-box link="/storage/tubes" image_path="/images/storage_tubes.jpeg" title="Tubes">
          Organize and keep track of your tubes. Register, update, and manage tube storage details in your database.
        </x-box>
  
        <x-box link="/storage/boxes" image_path="/images/storage_boxes.jpeg" title="Boxes">
          Efficiently manage storage boxes for your experiments. Keep a structured record of box contents and their organization.
        </x-box>
  
        <x-box link="/storage/locations" image_path="/images/storage_locations.jpeg" title="Locations">
          Manage storage locations with ease. Map and visualize where samples and materials are stored for quick accessibility.
        </x-box>
  
      </div>
  
    </div>
  
  </x-layout>