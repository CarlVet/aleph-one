{{-- Export buttons (CSV + Excel). The host component must expose export(string $format). --}}
<div class="inline-flex items-center gap-2">
    <button type="button" wire:click="export('csv')" wire:loading.attr="disabled" wire:target="export('csv')"
        class="group relative inline-flex items-center justify-center px-5 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-emerald-600 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100">
        <i class="fas fa-file-csv mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
        Export CSV
    </button>
    <button type="button" wire:click="export('xlsx')" wire:loading.attr="disabled" wire:target="export('xlsx')"
        class="group relative inline-flex items-center justify-center px-5 py-3 text-sm font-medium transition-all duration-300 ease-in-out transform hover:scale-105 bg-gradient-to-r from-teal-500 to-teal-600 text-white rounded-xl shadow-lg hover:shadow-xl border border-teal-600 disabled:opacity-60 disabled:cursor-not-allowed disabled:hover:scale-100">
        <i class="fas fa-file-excel mr-2 text-lg group-hover:translate-y-1 transition-transform duration-300"></i>
        Export Excel
    </button>
</div>
