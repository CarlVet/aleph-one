@props([
    'link' => '',
    'is_allowed' => true,
    'image_path' => '',
    'title' => '',
    'icon' => '', // pass icon class, e.g. 'fas fa-paw text-orange-300'
    'arrow_text' => '', // text for the arrow section
    'badge_text' => '', // text for the badge
    'badge_icon' => '', // icon for the badge
    'badge_color' => 'blue' // color for the badge (blue, green, purple, orange, etc.)
])

<div class="group relative transform hover:scale-105 transition-all duration-300">
    <div class="absolute inset-0 bg-gradient-to-r from-blue-500 to-cyan-600 rounded-2xl blur opacity-20 group-hover:opacity-30 transition-opacity duration-300"></div>
    <div class="relative bg-white rounded-2xl shadow-xl overflow-hidden border border-blue-100">
        @if($link)
            <a href="{{ $link }}" class="block">
        @endif
            <div class="relative overflow-hidden">
                <img src="{{ $image_path }}" class="w-full h-48 object-cover transform group-hover:scale-110 transition-transform duration-500">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent"></div>
                <div class="absolute bottom-4 left-4 right-4">
                    <h3 class="text-xl font-bold text-white mb-2 flex items-center">
                        @if($icon)
                            <i class="{{ $icon }} mr-3"></i>
                        @endif
                        {{ $title }}
                    </h3>
                    @if($arrow_text)
                        <div class="flex items-center text-blue-200">
                            <i class="fas fa-arrow-right mr-2"></i>
                            <span class="text-sm font-medium">{{ $arrow_text }}</span>
                        </div>
                    @endif
                </div>
            </div>
            <div class="p-6">
                <div class="overflow-hidden transition-all duration-300 max-h-0 group-hover:max-h-32">
                    <p class="text-gray-600 leading-relaxed">
                        {{ $slot }}
                    </p>
                </div>
                @if($badge_text)
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center px-3 py-1 bg-{{ $badge_color }}-100 text-{{ $badge_color }}-800 text-sm font-medium rounded-full">
                            @if($badge_icon)
                                <i class="{{ $badge_icon }} mr-1"></i>
                            @endif
                            {{ $badge_text }}
                        </span>
                        @if($link)
                            <i class="fas fa-chevron-right text-{{ $badge_color }}-500 group-hover:translate-x-1 transition-transform duration-300"></i>
                        @endif
                    </div>
                @endif
            </div>
        @if($link)
            </a>
        @else
            @if($is_allowed)
                <!-- Coming Soon Overlay -->
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-black bg-opacity-25 group-hover:bg-opacity-75 transition-all duration-300">
                    <i class="fas fa-tools text-white text-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></i>
                    <span class="mt-2 text-white text-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        We're working on it
                    </span>
                </div>
            @else
            <!-- Not Allowed Overlay -->
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-black bg-opacity-25 group-hover:bg-opacity-75 transition-all duration-300">
                    <i class="fas fa-ban text-white text-3xl opacity-0 group-hover:opacity-100 transition-opacity duration-300"></i>
                    <span class="mt-2 text-white text-lg opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                        You are not allowed to access this page
                    </span>
                </div>
            @endif
        @endif
    </div>
</div>
