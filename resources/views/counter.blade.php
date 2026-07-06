<x-layout>
    @livewire(App\Http\Livewire\Counter::class)

    <script>
        document.querySelectorAll('[wire\\:snapshot]').forEach(el => {
            let snapshot = JSON.parse(el.getAttribute('wire:snapshot'))

            el.addEventListener('click', e => {
                if (! e.target.hasAttribute('wire:click')) return

                fetch('/livewire', {
                    method: 'POST',
                    headers: {'Content-type' : 'application/json'},
                    body: JSON.stringify({
                        foo:'bar'
                    })
                })
            })
        })
    </script>
</x-layout>
