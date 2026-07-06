<div>
    <form wire:submit="changePerson()">
        <div class="mt-2">
            <x-forms.select-input type="text" name="person" wire:model.fill="person">
                @foreach($people as $item)
                    <option value="{{$item->first_name}}">
                        {{$item->first_name}}
                    </option>
                @endforeach
            </x-forms.select-input>
            <x-forms.textarea wire:model="greeting"></x-forms.textarea>
        </div>
        <div>
            @error('greeting')
                {{ $message }}
            @enderror
        </div>
        <div class="mt-2">
            <button type="submit" class="text-white font-medium rounded-md px-4 py-2 bg-blue-600">
                Greet
            </button>
        </div>
    </form>
    @if ($person_message !== '')
        <div>
            {{ $person_message }}!
        </div>
    @endif
</div>
