<x-layout>
    <div class="container mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-4">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Chat Users</h2>
            <div class="space-y-2">
                @foreach($users as $user)
                <div
                    class="p-2 hover:bg-gray-100 cursor-pointer user-chat rounded-lg"
                    data-user-id="{{ $user->id }}"
                    data-user-name="{{ trim(($user->people->first_name ?? '').' '.($user->people->last_name ?? '')) }}"
                    data-photo-path="{{ $user->people->pic_path ?? '' }}"
                >
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full bg-gray-300 flex items-center justify-center overflow-hidden">
                            <x-people-logo :person="$user->people" width="30" />
                        </div>
                        <div class="ml-3 flex-1">
                            <p class="text-sm font-medium text-gray-900">{{ $user->people->first_name . ' ' . $user->people->last_name }}</p>
                            <p class="text-xs text-gray-500">{{ $user->people->projects->first()->pivot->role }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</x-layout> 