<x-layout>
    <div class="py-6">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
            <div class="mb-10 text-center">
                <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-r from-sky-500 to-cyan-600 shadow-lg">
                    <i class="fas fa-recycle text-2xl text-white"></i>
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Microplastics</h1>
                <p class="mx-auto mt-3 max-w-3xl text-lg text-gray-600">
                    Register, browse, and summarize tube-derived microplastics identifications with the same workflow used across the other laboratory-derived sample sections.
                </p>
            </div>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-3">
                <x-box link="/samples/microplastics/create" image_path="/images/MP_pic.png"
                    title="Identification" icon="fas fa-recycle text-sky-300" badge_text="Register"
                    badge_icon="fas fa-plus-circle" badge_color="sky">
                    Register new microplastics identifications from existing tubes using form, table, or CSV workflows.
                </x-box>

                <x-box link="/samples/microplastics/list" image_path="/images/MP_list.jpg"
                    title="List" icon="fas fa-list text-cyan-300" badge_text="Browse" badge_icon="fas fa-table"
                    badge_color="cyan">
                    Filter and review identified microplastics records, linked source samples, and downstream experiments.
                </x-box>

                <x-box link="/samples/microplastics/dashboard" image_path="/images/MP_dash.jpg"
                    title="Dashboard" icon="fas fa-chart-column text-blue-300" badge_text="Summarize"
                    badge_icon="fas fa-chart-pie" badge_color="blue">
                    Explore project-level counts and quick summaries for protocols, particle types, laboratories, and source sample origins.
                </x-box>
            </div>
        </div>
    </div>
</x-layout>
