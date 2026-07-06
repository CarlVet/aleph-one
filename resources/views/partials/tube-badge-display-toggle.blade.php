<div id="tube-badge-display-toggle" class="hidden rounded-lg border border-gray-200 bg-gray-50/80 px-4 py-3">
    <div class="text-xs font-semibold uppercase tracking-wide text-gray-600">Selected tube badges</div>
    <div class="mt-2 flex flex-wrap items-center gap-6">
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="radio" name="tube_badge_display" value="tube" checked
                class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                onchange="window.alephHandleTubeBadgeDisplayChange?.()">
            <span>Tube code</span>
        </label>
        <label class="inline-flex items-center gap-2 text-sm text-gray-700">
            <input type="radio" name="tube_badge_display" value="alias"
                class="h-4 w-4 text-blue-600 focus:ring-blue-500"
                onchange="window.alephHandleTubeBadgeDisplayChange?.()">
            <span>Tube alias</span>
        </label>
    </div>
</div>
