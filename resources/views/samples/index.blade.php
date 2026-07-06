<x-layout>
    <!-- Primary Samples Section -->
    <div class="py-4 bg-gray-50">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
            <!-- Section Header -->
            <div class="text-center mb-4">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-green-500 to-emerald-600 rounded-full shadow-lg mb-6">
                    <i class="fas fa-leaf text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Field-Collected Samples</h2>
                <div class="w-24 h-1 bg-gradient-to-r from-green-500 to-emerald-600 mx-auto mb-4"></div>
                <p class="text-gray-600 max-w-3xl mx-auto text-lg">
                    Samples directly collected from human, animal and environmental sources represent essential
                    components of One Health research and surveillance.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <x-box link="/samples/humans" image_path="/images/human_index.jpg" title="Human Samples"
                    icon="fas fa-person text-pink-300" arrow_text="Access full functionalities"
                    badge_text="HIPAA Compliant" badge_icon="fas fa-shield-alt" badge_color="pink">
                    Comprehensive management of human biological samples with advanced tracking, consent management, and
                    ethical compliance features.
                </x-box>
                <x-box link="/samples/animals" image_path="/images/GGH05846.jpg" title="Animal Samples"
                    icon="fas fa-paw text-orange-300" arrow_text="Wildlife & domestic species"
                    badge_text="Multi-species" badge_icon="fas fa-hippo" badge_color="orange">
                    Extensive collection from mammals, reptiles, amphibians, birds, and fish. Includes health monitoring
                    and species-specific protocols.
                </x-box>
                <x-box link="/samples/environment" image_path="/images/samples_environmental.jpg"
                    title="Environmental Samples" icon="fas fa-seedling text-green-300"
                    arrow_text="Ecosystem monitoring" badge_text="Multi-matrix" badge_icon="fas fa-globe"
                    badge_color="green">
                    Comprehensive environmental monitoring including plant, soil, water, and airborne samples for
                    ecosystem health assessment and research.
                </x-box>
            </div>

            <!-- Processing Button Section -->
            <div class="relative text-center mt-16 mb-8">
                <!-- Decorative background elements -->
                <div
                    class="absolute inset-0 bg-gradient-to-r from-emerald-50 via-teal-50 to-cyan-50 rounded-3xl opacity-50">
                </div>
                <div
                    class="absolute top-0 left-1/2 transform -translate-x-1/2 w-32 h-1 bg-gradient-to-r from-transparent via-emerald-400 to-transparent">
                </div>

                <div class="relative bg-white/80 backdrop-blur-sm rounded-3xl p-8 shadow-xl border border-emerald-100">
                    <!-- Enhanced header with better visual hierarchy -->
                    <div
                        class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-emerald-400 to-teal-600 rounded-2xl shadow-lg mb-6 transform hover:scale-110 transition-transform duration-300">
                        <i class="fas fa-arrow-down text-white text-xl"></i>
                    </div>

                    <h3
                        class="text-2xl font-bold text-gray-900 mb-3 bg-gradient-to-r from-emerald-600 to-teal-700 bg-clip-text text-transparent">
                        Next Step: Process Samples
                    </h3>

                    <p class="text-gray-600 mb-8 max-w-2xl mx-auto text-lg leading-relaxed">
                        Convert your field-collected samples into laboratory-ready tubes for further sample
                        registration, storage and analysis
                    </p>

                    <!-- Enhanced button with multiple effects -->
                    <a href="/samples/process"
                        class="group relative inline-flex items-center justify-center px-10 py-5 text-lg font-semibold transition-all duration-500 ease-out transform hover:scale-105 bg-gradient-to-br from-emerald-400 via-teal-500 to-cyan-600 text-white rounded-2xl shadow-2xl hover:shadow-3xl border-0 overflow-hidden">
                        <!-- Animated background overlay -->
                        <div
                            class="absolute inset-0 bg-gradient-to-r from-transparent via-white/10 to-transparent transform -skew-x-12 -translate-x-full group-hover:translate-x-full transition-transform duration-1000 ease-out">
                        </div>

                        <!-- Icon with enhanced animation -->
                        <div class="relative flex items-center space-x-4">
                            <div class="relative">
                                <i
                                    class="fas fa-cogs text-white text-xl group-hover:rotate-180 transition-transform duration-700 ease-out"></i>
                                <!-- Pulsing ring effect -->
                                <div class="absolute inset-0 rounded-full border-2 border-white/30 animate-ping"></div>
                            </div>
                            <span class="relative">Process Field Samples</span>
                            <i
                                class="fas fa-arrow-right text-white text-lg group-hover:translate-x-2 transition-transform duration-300 ease-out"></i>
                        </div>

                        <!-- Subtle glow effect -->
                        <div
                            class="absolute inset-0 rounded-2xl bg-gradient-to-r from-emerald-400/20 to-cyan-600/20 blur-xl group-hover:blur-2xl transition-all duration-500">
                        </div>
                    </a>

                    <!-- Additional decorative elements -->
                    <div class="mt-6 flex justify-center space-x-2">
                        <div class="w-2 h-2 bg-emerald-400 rounded-full animate-bounce"></div>
                        <div class="w-2 h-2 bg-teal-500 rounded-full animate-bounce" style="animation-delay: 0.1s">
                        </div>
                        <div class="w-2 h-2 bg-cyan-600 rounded-full animate-bounce" style="animation-delay: 0.2s">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Processed Samples Section -->
    <div class="py-4 bg-white">
        <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
            <!-- Section Header -->
            <div class="text-center mb-4">
                <div
                    class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-r from-purple-500 to-violet-600 rounded-full shadow-lg mb-6">
                    <i class="fas fa-flask text-white text-2xl"></i>
                </div>
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Laboratory-Derived Samples</h2>
                <div class="w-24 h-1 bg-gradient-to-r from-purple-500 to-violet-600 mx-auto mb-4"></div>
                <p class="text-gray-600 max-w-3xl mx-auto text-lg">
                    Advanced laboratory processing of primary samples, resulting in the creation of derived samples for
                    further analysis.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                <x-box link="/samples/parasites" image_path="/images/samples_parasites.jpg" title="Parasite Samples"
                    icon="fas fa-spider text-purple-300" badge_text="ID Ready" badge_icon="fas fa-search"
                    badge_color="purple">
                    Helminths and ectoparasitic arthropods identification and analysis.
                </x-box>
                <x-box link="/samples/nucleic" image_path="/images/samples_nucleic.png" title="Nucleic Acids"
                    icon="fas fa-dna text-blue-300" badge_text="Extracted" badge_icon="fas fa-vial" badge_color="blue">
                    DNA/RNA extraction and molecular biology analysis.
                </x-box>
                <x-box link="/samples/cultures" image_path="/images/culture.jpg" title="Cultures"
                    icon="fas fa-bacteria text-yellow-300" badge_text="Growing" badge_icon="fas fa-petri-dish"
                    badge_color="yellow">
                    Microbial culture and isolation protocols.
                </x-box>
                <x-box link="/samples/pools" image_path="/images/samples_pools.jpeg" title="Pools"
                    icon="fas fa-layer-group text-cyan-300" badge_text="Pooled" badge_icon="fas fa-cubes"
                    badge_color="cyan">
                    Sample pooling for high-throughput analysis.
                </x-box>
                <x-box image_path="/images/samples_proteins.jpg" title="Proteins" icon="fas fa-atom text-indigo-300"
                    badge_text="Coming Soon" badge_icon="fas fa-clock" badge_color="indigo">
                    Protein analysis and characterization.
                </x-box>
                <x-box image_path="/images/samples_metabolites.jpg" title="Metabolites"
                    icon="fas fa-flask-vial text-emerald-300" badge_text="Coming Soon" badge_icon="fas fa-clock"
                    badge_color="emerald">
                    Metabolomic profiling and analysis.
                </x-box>
                <x-box link="/samples/microplastics" image_path="/images/samples_others.jpg" title="Microplastics"
                    icon="fas fa-recycle text-sky-300" badge_text="Identification" badge_icon="fas fa-recycle"
                    badge_color="sky">
                    Micro- and nano-plastics analysis.
                </x-box>
            </div>
        </div>
    </div>

</x-layout>
