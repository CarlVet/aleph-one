<x-layout>
    <div class="bg-white">
        <!-- HERO SECTION -->
        <section
            class="relative bg-white py-20 px-6 sm:px-8 lg:px-10 overflow-hidden border-b border-gray-100">
            <div class="relative z-10 max-w-4xl mx-auto text-center">
                <p class="text-sm font-semibold uppercase tracking-widest mb-4" style="color: #0097A7;" data-aos="fade-up" data-aos-delay="100">
                    Hello, {{ Auth::user()->people->first_name ?? 'Guest' }}!
                </p>
                <h1 class="text-5xl sm:text-6xl font-bold leading-tight tracking-tight" style="color: #0F1729;" data-aos="fade-down">
                    Welcome to <span style="color: #0097A7;">Aleph∞One</span>
                </h1>
                <p class="text-lg text-gray-500 mt-5 max-w-2xl mx-auto" data-aos="fade-down">
                    A cutting-edge software to streamline One Health epidemiological investigations
                </p>
                <div class="mt-10 flex justify-center gap-6" data-aos="zoom-in-up" data-aos-delay="300">
                    <a href="/my-projects"
                        class="group relative inline-flex items-center justify-center px-8 py-4 text-lg font-medium transition-all duration-300 ease-in-out transform hover:scale-105 text-white rounded-xl shadow-sm hover:shadow-md"
                        style="background-color: #0097A7;">
                        <i
                            class="fas fa-rocket mr-2 text-lg group-hover:translate-x-1 transition-transform duration-300"></i>
                        Get Started → Choose a project
                    </a>
                </div>
            </div>
        </section>

        <!-- METRICS DASHBOARD -->
        <section class="py-16 px-6 sm:px-8 lg:px-10 bg-gradient-to-r from-gray-50 to-white">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-4xl font-semibold tracking-tight text-gray-900 text-center mb-4" data-aos="fade-up">
                    System Overview
                </h2>
                <p class="text-xl text-gray-600 text-center mb-12 max-w-3xl mx-auto" data-aos="fade-up"
                    data-aos-delay="100">
                    Comprehensive metrics showing the scale and diversity of data managed in Aleph∞One
                </p>

                <!-- Main Metrics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-12">
                    @foreach ([['title' => 'Total Samples', 'count' => $metrics['samples']['total'], 'icon' => 'fas fa-flask', 'color' => 'from-blue-500 to-blue-600', 'delay' => 0], ['title' => 'Experiments', 'count' => $metrics['experiments']['total'], 'icon' => 'fas fa-microscope', 'color' => 'from-green-500 to-green-600', 'delay' => 100], ['title' => 'Projects', 'count' => $metrics['projects']['total'], 'icon' => 'fas fa-project-diagram', 'color' => 'from-purple-500 to-purple-600', 'delay' => 200], ['title' => 'Pathogens', 'count' => $metrics['pathogens']['total'], 'icon' => 'fas fa-virus', 'color' => 'from-orange-500 to-orange-600', 'delay' => 300], ['title' => 'Protocols', 'count' => $metrics['protocols']['total'], 'icon' => 'fas fa-clipboard-list', 'color' => 'from-red-500 to-red-600', 'delay' => 400]] as $metric)
                        <div class="bg-white rounded-xl shadow-lg p-6 text-center transform hover:scale-105 transition-all duration-300 border border-gray-100"
                            data-aos="fade-up" data-aos-delay="{{ $metric['delay'] }}">
                            <div
                                class="w-16 h-16 bg-gradient-to-r {{ $metric['color'] }} rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                                <i class="{{ $metric['icon'] }} text-white text-2xl"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900 mb-2 counter"
                                data-target="{{ $metric['count'] }}">{{ number_format($metric['count']) }}</h3>
                            <p class="text-gray-600 font-medium">{{ $metric['title'] }}</p>
                        </div>
                    @endforeach
                </div>

                <!-- Detailed Metrics -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    <!-- Samples Breakdown -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100" data-aos="fade-right">
                        <h3 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-flask text-blue-600 mr-3"></i>
                            Sample Types
                        </h3>
                        <div class="space-y-4">
                            @foreach ($metrics['samples']['by_type'] as $type => $count)
                                @php
                                    $labels = [
                                        'animal_samples' => 'Animal Samples',
                                        'human_samples' => 'Human Samples',
                                        'environment_samples' => 'Environment Samples',
                                        'parasite_samples' => 'Parasite Samples',
                                        'nucleic_acids' => 'Nucleic Acids',
                                        'cultures' => 'Cultures',
                                        'pools' => 'Pools',
                                    ];
                                    $colors = [
                                        'animal_samples' => 'bg-blue-100 text-blue-800',
                                        'human_samples' => 'bg-green-100 text-green-800',
                                        'environment_samples' => 'bg-yellow-100 text-yellow-800',
                                        'parasite_samples' => 'bg-purple-100 text-purple-800',
                                        'nucleic_acids' => 'bg-red-100 text-red-800',
                                        'cultures' => 'bg-indigo-100 text-indigo-800',
                                        'pools' => 'bg-pink-100 text-pink-800',
                                    ];
                                    $links = [
                                        'animal_samples' => '/samples/animals/list',
                                        'human_samples' => '/samples/humans/list',
                                        'environment_samples' => '/samples/environment/list',
                                        'parasite_samples' => '/samples/parasites/list',
                                        'nucleic_acids' => '/samples/nucleic/list',
                                        'cultures' => '/samples/cultures/list',
                                        'pools' => '/samples/pools/list',
                                    ];
                                @endphp
                                <div
                                    class="flex justify-between items-center p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                    <span class="font-medium text-gray-700"><a href="{{ $links[$type] }}" class="text-blue-600 hover:text-blue-800 transition-colors duration-200">{{ $labels[$type] }}</a></span>
                                    <span
                                        class="px-3 py-1 rounded-full text-sm font-semibold {{ $colors[$type] }} shadow-sm">
                                        {{ number_format($count) }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Experiments Breakdown by Pathogen -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100" data-aos="fade-left">
                        <h3 class="text-2xl font-semibold text-gray-900 mb-6 flex items-center">
                            <i class="fas fa-microscope text-green-600 mr-3"></i>
                            Experiments by Pathogen Domain
                        </h3>
                        <div class="space-y-2">
                            @if (count($metrics['experiments']['by_pathogen']) > 0)
                                @foreach ($metrics['experiments']['by_pathogen'] as $domain => $domainData)
                                    <div class="border border-gray-200 rounded-lg overflow-hidden">
                                        <!-- Domain Level -->
                                        <div class="domain-header bg-gray-50 p-3 cursor-pointer hover:bg-gray-100 transition-colors duration-200"
                                            onclick="toggleDomain('{{ $domain }}')">
                                            <div class="flex justify-between items-center">
                                                <div class="flex items-center">
                                                    <i class="fas fa-chevron-right text-gray-500 mr-2 domain-icon"
                                                        id="icon-{{ $domain }}"></i>
                                                    <span
                                                        class="font-semibold text-gray-800">{{ $domain }}</span>
                                                    <span class="text-xs text-gray-500 ml-2">Domain</span>
                                                </div>
                                                <span
                                                    class="px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800 shadow-sm">
                                                    {{ number_format($domainData['count']) }}
                                                </span>
                                            </div>
                                        </div>

                                        <!-- Family Level (Hidden by default) -->
                                        <div class="domain-content hidden" id="content-{{ $domain }}">
                                            <div class="space-y-1">
                                                @foreach ($domainData['families'] as $family => $familyData)
                                                    <div class="border-t border-gray-100">
                                                        <!-- Family Level -->
                                                        <div class="family-header bg-white p-3 cursor-pointer hover:bg-gray-50 transition-colors duration-200 ml-4"
                                                            onclick="toggleFamily('{{ $domain }}-{{ $family }}')">
                                                            <div class="flex justify-between items-center">
                                                                <div class="flex items-center">
                                                                    <i class="fas fa-chevron-right text-gray-400 mr-2 family-icon"
                                                                        id="icon-{{ $domain }}-{{ $family }}"></i>
                                                                    <span
                                                                        class="font-medium text-gray-700">{{ $family }}</span>
                                                                    <span
                                                                        class="text-xs text-gray-500 ml-2">Family</span>
                                                                </div>
                                                                <span
                                                                    class="px-2 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800 shadow-sm">
                                                                    {{ number_format($familyData['count']) }}
                                                                </span>
                                                            </div>
                                                        </div>

                                                        <!-- Species Level (Hidden by default) -->
                                                        <div class="family-content hidden"
                                                            id="content-{{ $domain }}-{{ $family }}">
                                                            <div class="space-y-1">
                                                                @foreach ($familyData['species'] as $species => $count)
                                                                    <div class="bg-gray-50 p-2 ml-8">
                                                                        <div class="flex justify-between items-center">
                                                                            <div class="flex items-center">
                                                                                <span
                                                                                    class="text-sm text-gray-600">{{ $species }}</span>
                                                                                <span
                                                                                    class="text-xs text-gray-500 ml-2">Species</span>
                                                                            </div>
                                                                            <span
                                                                                class="px-2 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-800 shadow-sm">
                                                                                {{ number_format($count) }}
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="text-center py-8 text-gray-500">
                                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                                    <p>No pathogen-specific experiments found</p>
                                </div>
                            @endif
                        </div>
                        <div class="mt-4 p-3 bg-blue-50 rounded-lg">
                            <p class="text-xs text-blue-700">
                                <i class="fas fa-info-circle mr-1"></i>
                                Click on domains to expand families, click on families to expand species
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Additional Metrics -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-8">
                    <!-- Project Status -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100" data-aos="fade-up"
                        data-aos-delay="100">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-project-diagram text-purple-600 mr-2"></i>
                            Project Status
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">Active</span>
                                <span class="font-semibold text-green-600">{{ $metrics['projects']['active'] }}</span>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">Completed</span>
                                <span
                                    class="font-semibold text-blue-600">{{ $metrics['projects']['completed'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Pathogen Details -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100" data-aos="fade-up"
                        data-aos-delay="200">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-virus text-orange-600 mr-2"></i>
                            Pathogen Details
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">With Protocols</span>
                                <span
                                    class="font-semibold text-green-600">{{ $metrics['pathogens']['with_protocols'] }}</span>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">Without Protocols</span>
                                <span
                                    class="font-semibold text-red-600">{{ $metrics['pathogens']['without_protocols'] }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Protocol Details -->
                    <div class="bg-white rounded-xl shadow-lg p-6 border border-gray-100" data-aos="fade-up"
                        data-aos-delay="300">
                        <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-clipboard-list text-red-600 mr-2"></i>
                            Protocol Details
                        </h4>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">With Techniques</span>
                                <span
                                    class="font-semibold text-green-600">{{ $metrics['protocols']['with_techniques'] }}</span>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">With Pathogens</span>
                                <span
                                    class="font-semibold text-blue-600">{{ $metrics['protocols']['with_pathogens'] }}</span>
                            </div>
                            <div class="flex justify-between items-center p-2 bg-gray-50 rounded-lg">
                                <span class="text-gray-600">With Studies</span>
                                <span
                                    class="font-semibold text-purple-600">{{ $metrics['protocols']['with_studies'] }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- APP DESCRIPTION -->
        <section class="py-20 px-6 sm:px-8 lg:px-10 bg-white">
            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-12">
                <div class="lg:col-span-2 space-y-6" data-aos="fade-right">
                    <h2 class="text-4xl font-semibold tracking-tight text-gray-900">About Aleph∞One</h2>
                    <p class="text-xl text-gray-600 leading-8">
                        Aleph∞One is a cutting-edge software designed to streamline One Health epidemiological
                        surveillance.
                        From sample registration to data visualization and pathogen tracking, Aleph∞One simplifies your
                        research pipeline.
                    </p>
                    <p class="text-base text-gray-700 leading-7">
                        Make your work as seamless as using an iPhone — intuitive, powerful, and
                        surprisingly delightful.
                        Whether you're in the field, the lab or in front of a computer, Aleph∞One is built to follow
                        your
                        workflow.
                    </p>
                </div>
                <div class="space-y-6" data-aos="fade-left">
                    <dl class="space-y-6">
                        @foreach ([['User-friendly', 'Effortless data registration'], ['Real-time', 'Dynamic project management'], ['Multifaceted', 'Comprehensive & versatile tools'], ['Traceability', 'Secure end-to-end data tracking']] as [$title, $desc])
                            <div>
                                <dd class="text-3xl font-bold text-gray-900">{{ $title }}</dd>
                                <dt class="text-base text-gray-600">{{ $desc }}</dt>
                            </div>
                        @endforeach
                    </dl>
                </div>
            </div>
        </section>

        <!-- DATA MANAGEMENT UNITS -->
        <section class="py-20 px-6 sm:px-8 lg:px-10 bg-gradient-to-b from-gray-50 to-white">
            <div class="max-w-7xl mx-auto">
                <h2 class="text-4xl font-semibold tracking-tight text-gray-900 text-center mb-4" data-aos="fade-up">
                    Integrated Data Management
                </h2>
                <p class="text-xl text-gray-600 text-center mb-12 max-w-3xl mx-auto" data-aos="fade-up"
                    data-aos-delay="100">
                    A comprehensive system connecting samples, experiments, storage, and literature data for seamless
                    research management
                </p>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-16 items-center">
                    <!-- Left Column: Units Overview -->
                    <div class="space-y-6" data-aos="fade-right">
                        <a href="/samples"
                            class="group block bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="flex-shrink-0 w-16 h-16 bg-blue-50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-flask text-blue-600 text-2xl"></i>
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-semibold text-gray-900 group-hover:text-blue-600 transition-colors">
                                        Samples</h3>
                                    <p class="text-gray-600 mt-1">Collection & Processing</p>
                                </div>
                            </div>
                        </a>

                        <a href="/bank"
                            class="group block bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="flex-shrink-0 w-16 h-16 bg-green-50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-archive text-green-600 text-2xl"></i>
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-semibold text-gray-900 group-hover:text-green-600 transition-colors">
                                        Storage</h3>
                                    <p class="text-gray-600 mt-1">Organization & Tracking</p>
                                </div>
                            </div>
                        </a>

                        <a href="/experiments"
                            class="group block bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="flex-shrink-0 w-16 h-16 bg-purple-50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-microscope text-purple-600 text-2xl"></i>
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-semibold text-gray-900 group-hover:text-purple-600 transition-colors">
                                        Experiments</h3>
                                    <p class="text-gray-600 mt-1">Analysis & Results</p>
                                </div>
                            </div>
                        </a>

                        <a href="/meta"
                            class="group block bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100">
                            <div class="flex items-center space-x-4">
                                <div
                                    class="flex-shrink-0 w-16 h-16 bg-orange-50 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-book-open text-orange-600 text-2xl"></i>
                                </div>
                                <div>
                                    <h3
                                        class="text-xl font-semibold text-gray-900 group-hover:text-orange-600 transition-colors">
                                        Literature</h3>
                                    <p class="text-gray-600 mt-1">Meta-analysis & Research</p>
                                </div>
                            </div>
                        </a>
                    </div>

                    <!-- Right Column: Interactive Data Flow Diagram -->
                    <div class="bg-white p-8 rounded-xl shadow-lg border border-gray-100" data-aos="fade-left">
                        <h3 class="text-2xl font-semibold text-gray-900 mb-8 text-center">Data Flow & Connections</h3>

                        <!-- Interactive Flow Diagram -->
                        <div class="relative">
                            <!-- Main Flow Container -->
                            <div class="grid grid-cols-3 grid-rows-3 gap-8 h-[28rem]">
                                <!-- Samples Node -->
                                <div class="col-start-1 row-start-2">
                                    <div class="flow-node bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-300 rounded-xl p-4 text-center cursor-pointer transform hover:scale-105 transition-all duration-300"
                                        data-node="samples">
                                        <div
                                            class="w-12 h-12 bg-blue-500 rounded-full flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-flask text-white text-lg"></i>
                                        </div>
                                        <h4 class="font-semibold text-blue-800 text-sm">Samples</h4>
                                        <p class="text-blue-600 text-xs">Collection & Processing</p>
                                    </div>
                                </div>

                                <!-- Storage Node -->
                                <div class="col-start-2 row-start-1">
                                    <div class="flow-node bg-gradient-to-br from-green-50 to-green-100 border-2 border-green-300 rounded-xl p-4 text-center cursor-pointer transform hover:scale-105 transition-all duration-300"
                                        data-node="storage">
                                        <div
                                            class="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-archive text-white text-lg"></i>
                                        </div>
                                        <h4 class="font-semibold text-green-800 text-sm">Storage</h4>
                                        <p class="text-green-600 text-xs">Organization & Tracking</p>
                                    </div>
                                </div>

                                <!-- Experiments Node -->
                                <div class="col-start-3 row-start-2">
                                    <div class="flow-node bg-gradient-to-br from-purple-50 to-purple-100 border-2 border-purple-300 rounded-xl p-4 text-center cursor-pointer transform hover:scale-105 transition-all duration-300"
                                        data-node="experiments">
                                        <div
                                            class="w-12 h-12 bg-purple-500 rounded-full flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-microscope text-white text-lg"></i>
                                        </div>
                                        <h4 class="font-semibold text-purple-800 text-sm">Experiments</h4>
                                        <p class="text-purple-600 text-xs">Analysis & Results</p>
                                    </div>
                                </div>

                                <!-- Literature Node -->
                                <div class="col-start-2 row-start-3">
                                    <div class="flow-node bg-gradient-to-br from-orange-50 to-orange-100 border-2 border-orange-300 rounded-xl p-4 text-center cursor-pointer transform hover:scale-105 transition-all duration-300"
                                        data-node="literature">
                                        <div
                                            class="w-12 h-12 bg-orange-500 rounded-full flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-book-open text-white text-lg"></i>
                                        </div>
                                        <h4 class="font-semibold text-orange-800 text-sm">Literature</h4>
                                        <p class="text-orange-600 text-xs">Meta-analysis & Research</p>
                                    </div>
                                </div>

                                <!-- Center Hub -->
                                <div class="col-start-2 row-start-2">
                                    <div class="flow-node bg-gradient-to-br from-indigo-50 to-indigo-100 border-2 border-indigo-300 rounded-xl p-4 text-center cursor-pointer transform hover:scale-105 transition-all duration-300"
                                        data-node="hub">
                                        <div
                                            class="w-12 h-12 bg-indigo-500 rounded-full flex items-center justify-center mx-auto mb-2">
                                            <i class="fas fa-database text-white text-lg"></i>
                                        </div>
                                        <h4 class="font-semibold text-indigo-800 text-sm">Data Hub</h4>
                                        <p class="text-indigo-600 text-xs">Central Integration</p>
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- Flow Description -->
                        <div id="flow-description" class="mt-6 p-4 bg-gray-50 rounded-lg text-center">
                            <p class="text-gray-600 text-sm">Click on any node to see its role in the data flow</p>
                        </div>

                    </div>
                </div>
            </div>
        </section>

        <!-- IMAGE MOSAIC
        <section class="mt-12">
            <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
                <h2 id="mosaic-title" class="text-4xl font-semibold tracking-tight text-gray-900 text-center mb-6"
                    data-aos="zoom-in">Field Activities</h2>

                <div class="swiper-container bg-white p-8 rounded-xl shadow-lg border border-gray-100"
                    style="overflow: hidden;" data-aos="fade-up">
                    <div class="swiper-wrapper">
                        @foreach ([[['/images/team_karoo.jpeg', 2, 2], ['/images/home_img_2.jpeg', 1, 1], ['/images/home_img_3.jpeg', 1, 1]], [['/images/home_lab_1.jpeg', 2, 2], ['/images/home_lab_2.jpeg', 1, 1], ['/images/home_lab_3.jpeg', 1, 1]]] as $slide)
                            <div class="swiper-slide">
                                <div class="grid grid-cols-3 grid-rows-2 gap-4">
                                    @foreach ($slide as [$src, $col, $row])
                                        <img src="{{ $src }}"
                                            class="col-span-{{ $col }} row-span-{{ $row }} object-cover rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300">
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="swiper-pagination"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                </div>
            </div>
        </section>
         -->
        

        <!-- MISSION AND VISION -->
        <section class="mt-4 bg-gradient-to-b from-gray-50 to-white py-20" id="mission">
            <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10">
                <div class="text-center max-w-2xl mx-auto mb-12" data-aos="fade-up">
                    <h2 class="text-4xl font-semibold tracking-tight text-gray-900">Our Mission & Vision</h2>
                    <p class="mt-4 text-lg text-gray-600">
                        We envision a future where data drives sustainable, science-backed conservation efforts. Our
                        mission is to build tools
                        that empower professionals in the field of One Health and epidemiology.
                    </p>
                </div>
                <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-10">
                    @foreach ([['Empowering Surveillance', 'Precision data collection for One Eealth.'], ['Unified Data Management', 'One platform. All your data.'], ['Global Collaboration', 'For teams solving global challenges.'], ['Innovative Solutions', 'Built for 21st century veterinary science.'], ['Sustainability First', 'Healthier ecosystems for all.'], ['Accessible for All', 'Designed with inclusivity in mind.']] as [$title, $desc])
                        <div class="bg-white shadow-lg p-6 rounded-xl hover:shadow-xl transition-all duration-300 transform hover:-translate-y-1 border border-gray-100"
                            data-aos="fade-up">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $title }}</h3>
                            <p class="mt-2 text-gray-600">{{ $desc }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <!-- LOGO CLOUD -->
        <section class="mt-10 bg-white py-16">
            <div class="max-w-7xl mx-auto px-6 sm:px-8 lg:px-10 text-center" data-aos="fade-in">
                <h2 class="text-lg font-semibold text-gray-900">Trusted by Leading Veterinary & Epidemiology Teams</h2>
                <div class="mt-10 bg-white p-8 rounded-xl shadow-lg border border-gray-100">
                    <div class="grid grid-cols-2 sm:grid-cols-5 lg:grid-cols-5 gap-8 items-center justify-center">
                        @foreach (['/images/logo_up.png', '/images/logo_dvtd.jpeg', '/images/logo_sanparks2.png', '/images/Institute_of_Tropical_Medicine_Antwerp.jpg', '/images/logo_izste.jpg'] as $logo)
                            <img src="{{ $logo }}"
                                class="max-h-20 w-auto mx-auto hover:scale-110 transition-transform duration-300">
                        @endforeach
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Swiper & AOS Scripts -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const titles = ["Field Activities", "Lab Expertise"];
            const titleElement = document.getElementById("mosaic-title");

            const swiper = new Swiper(".swiper-container", {
                loop: true,
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true
                },
                navigation: {
                    nextEl: ".swiper-button-next",
                    prevEl: ".swiper-button-prev"
                },
                on: {
                    slideChange: function() {
                        titleElement.textContent = titles[this.realIndex];
                    }
                }
            });

            AOS.init({
                duration: 800,
                easing: 'ease-out-cubic'
            });

            // Counter animation for metrics
            const counters = document.querySelectorAll('.counter');
            const animateCounters = () => {
                counters.forEach(counter => {
                    const target = parseInt(counter.getAttribute('data-target'));
                    if (target > 0) {
                        const duration = 2000; // 2 seconds
                        const increment = target / (duration / 16); // 60fps
                        let current = 0;

                        const updateCounter = () => {
                            current += increment;
                            if (current < target) {
                                counter.textContent = Math.floor(current).toLocaleString();
                                requestAnimationFrame(updateCounter);
                            } else {
                                counter.textContent = target.toLocaleString();
                            }
                        };

                        updateCounter();
                    }
                });
            };

            // Trigger counter animation immediately
            animateCounters();

            // Also trigger when metrics section comes into view as backup
            const metricsSection = document.querySelector('.bg-gradient-to-r.from-gray-50.to-white');
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        setTimeout(animateCounters, 100); // Small delay to ensure DOM is ready
                        observer.unobserve(entry.target);
                    }
                });
            }, {
                threshold: 0.1
            });

            if (metricsSection) {
                observer.observe(metricsSection);
            }

            // Connection diagram interaction
            const tooltip = document.getElementById('connection-tooltip');
            const tooltipContent = document.getElementById('tooltip-content');
            const connectionLines = document.querySelectorAll('.connection-line');

            connectionLines.forEach(line => {
                line.addEventListener('mouseenter', (e) => {
                    const description = e.target.getAttribute('data-description');
                    tooltipContent.textContent = description;
                    tooltip.classList.remove('hidden');

                    // Position tooltip near the mouse
                    const rect = e.target.getBoundingClientRect();
                    tooltip.style.left = `${rect.left + window.scrollX + rect.width/2}px`;
                    tooltip.style.top = `${rect.top + window.scrollY - 40}px`;

                    // Highlight the connection
                    e.target.style.strokeWidth = '5';
                    e.target.style.filter = 'drop-shadow(0 0 8px rgba(232, 151, 45, 0.5))';
                });

                line.addEventListener('mouseleave', (e) => {
                    tooltip.classList.add('hidden');
                    e.target.style.strokeWidth = '3';
                    e.target.style.filter = 'none';
                });

                line.addEventListener('mousemove', (e) => {
                    const rect = e.target.getBoundingClientRect();
                    tooltip.style.left = `${rect.left + window.scrollX + rect.width/2}px`;
                    tooltip.style.top = `${rect.top + window.scrollY - 40}px`;
                });
            });

            // Interactive Flow Diagram
            const flowNodes = document.querySelectorAll('.flow-node');
            const flowDescription = document.getElementById('flow-description');

            const nodeDescriptions = {
                'samples': {
                    title: 'Sample Collection & Processing',
                    description: 'Comprehensive sample management including animal, human, environmental, and parasite samples. Features metadata tracking, collection protocols, and sample processing workflows.',
                    features: ['Multi-type sample support', 'Metadata tracking', 'Collection protocols',
                        'Processing workflows'
                    ]
                },
                'storage': {
                    title: 'Storage & Organization',
                    description: 'Advanced storage management system with precise location tracking, inventory control, and sample organization using boxes and tubes.',
                    features: ['Location tracking', 'Inventory control', 'Box & tube management',
                        'Sample organization'
                    ]
                },
                'experiments': {
                    title: 'Experimental Analysis',
                    description: 'Complete experiment tracking with pathogen testing, protocol management, and result analysis. Supports various experimental techniques and statistical analysis.',
                    features: ['Pathogen testing', 'Protocol management', 'Result analysis',
                        'Statistical tools']
                },
                'literature': {
                    title: 'Literature Integration',
                    description: 'Meta-analysis and literature review tools that connect your experimental data with published research for comprehensive analysis.',
                    features: ['Meta-analysis', 'Literature review', 'Research integration',
                        'Publication tracking'
                    ]
                },
                'hub': {
                    title: 'Central Data Hub',
                    description: 'The core integration point that connects all data management units, providing seamless data flow and cross-referencing capabilities.',
                    features: ['Data integration', 'Cross-referencing', 'Seamless flow', 'Centralized access']
                }
            };

            flowNodes.forEach(node => {
                node.addEventListener('click', () => {
                    const nodeType = node.getAttribute('data-node');
                    const nodeInfo = nodeDescriptions[nodeType];

                    if (nodeInfo) {
                        flowDescription.innerHTML = `
                            <h4 class="font-semibold text-gray-900 mb-2">${nodeInfo.title}</h4>
                            <p class="text-gray-600 text-sm mb-3">${nodeInfo.description}</p>
                            <div class="grid grid-cols-2 gap-2">
                                ${nodeInfo.features.map(feature => `
                                        <div class="flex items-center text-xs text-gray-500">
                                            <i class="fas fa-check text-green-500 mr-1"></i>
                                            ${feature}
                                        </div>
                                    `).join('')}
                            </div>
                        `;
                    }

                    // Add visual feedback
                    flowNodes.forEach(n => n.classList.remove('ring-4', 'ring-blue-300'));
                    node.classList.add('ring-4', 'ring-blue-300');
                });
            });

            // Hierarchical pathogen data expand/collapse functions
            window.toggleDomain = function(domain) {
                const content = document.getElementById('content-' + domain);
                const icon = document.getElementById('icon-' + domain);

                if (content.classList.contains('hidden')) {
                    content.classList.remove('hidden');
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-down');
                } else {
                    content.classList.add('hidden');
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-right');
                }
            };

            window.toggleFamily = function(familyId) {
                const content = document.getElementById('content-' + familyId);
                const icon = document.getElementById('icon-' + familyId);

                if (content.classList.contains('hidden')) {
                    content.classList.remove('hidden');
                    icon.classList.remove('fa-chevron-right');
                    icon.classList.add('fa-chevron-down');
                } else {
                    content.classList.add('hidden');
                    icon.classList.remove('fa-chevron-down');
                    icon.classList.add('fa-chevron-right');
                }
            };
        });
    </script>

    <!-- AOS Stylesheet -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <!-- AOS Script -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>

    <link rel="stylesheet" href="{{ asset('css/custom.css') }}">
</x-layout>
