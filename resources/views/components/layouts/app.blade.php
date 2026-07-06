<x-layout>
    <header>
        <livewire:search 
    model="Studies" 
    search_field="title" 
    placeholder="Search studies by title..." 
/>
    </header>
    {{$slot}}
</x-layout>