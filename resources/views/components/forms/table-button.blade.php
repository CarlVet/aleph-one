<!-- Circular button with table icon -->
<button type="button"
{{ $attributes->merge(['class' => 'mt-2 mb-2 bg-blue-500 text-white hover:bg-blue-600 hover:text-white rounded-full px-3 py-2 text-sm font-medium border border-black']) }}>
<i class="fas fa-table"></i> {{ $slot }}
</button>