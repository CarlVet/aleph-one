(function () {
    if (window.__profileTablesLoaded) {
        return;
    }
    window.__profileTablesLoaded = true;

    function enhanceProfileTables() {
        if (typeof window.enhanceDashboardModalTable !== 'function') {
            return;
        }

        document.querySelectorAll('[data-profile-tables] table').forEach(function (table) {
            // Skip tables that live inside a modal; those are handled on modal open.
            if (table.closest('[id$="Modal"]')) {
                return;
            }

            var container = table.closest('.overflow-x-auto') || table.parentElement;
            if (!container) {
                return;
            }

            try {
                window.enhanceDashboardModalTable(container);
            } catch (error) {
                /* no-op: never let one table break the rest of the page */
                return;
            }

            // When the scroll wrapper is itself the Alpine collapsible panel, the
            // enhancer drops its toolbar as a preceding sibling, so it stays visible
            // even while the section is collapsed. Move it inside the panel so it
            // toggles together with the table.
            if (container.hasAttribute('x-show') && container.dataset.toolbarRelocated !== '1') {
                var toolbar = container.previousElementSibling;
                if (toolbar && toolbar.querySelector('[data-modal-download-table]')) {
                    container.dataset.toolbarRelocated = '1';
                    container.insertBefore(toolbar, container.firstChild);
                }
            }
        });
    }

    function schedule() {
        // Profiles render their tables inside collapsible Alpine sections, so run a
        // couple of passes to catch tables that mount slightly after navigation.
        window.setTimeout(enhanceProfileTables, 50);
        window.setTimeout(enhanceProfileTables, 350);
    }

    document.addEventListener('DOMContentLoaded', schedule);
    document.addEventListener('livewire:initialized', schedule);
    document.addEventListener('livewire:navigated', schedule);

    // Inline edits re-render the component and strip the injected filter row /
    // toolbar, so re-apply after every Livewire commit.
    document.addEventListener('livewire:init', function () {
        if (!window.Livewire || typeof window.Livewire.hook !== 'function') {
            return;
        }

        window.Livewire.hook('commit', function (payload) {
            if (payload && typeof payload.respond === 'function') {
                payload.respond(function () {
                    schedule();
                });

                return;
            }

            schedule();
        });
    });
})();
