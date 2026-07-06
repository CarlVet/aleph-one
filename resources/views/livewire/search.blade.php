<div>
    <form>
        <div class="mt-2">
            <h2>Search articles by title</h2>
            <x-forms.textarea 
            placeholder="{{$placeholder}}" 
            wire:model.live.debounce="search_text">
        </x-forms.textarea>
        </div>
    </form>
    <livewire:search-results :results="$results" :show="!empty($search_text)">
</div>
