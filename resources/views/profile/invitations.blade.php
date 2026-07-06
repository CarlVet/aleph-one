<x-layout>
    <x-slot:heading>
        Project Invitations
    </x-slot:heading>
    <div class="max-w-2xl mx-auto mt-10 bg-white p-8 rounded shadow">
        <h2 class="text-xl font-bold mb-4">Pending Project Invitations</h2>
        @if ($invitations->isEmpty())
            <div class="text-gray-600">No pending invitations.</div>
        @else
            @foreach ($invitations as $invitation)
                <div class="mb-6 border rounded p-4 bg-gray-50">
                    <div class="mb-2">
                        <strong>Project:</strong> {{ $invitation->project->title ?? $invitation->project->code }}<br>
                        <strong>Role:</strong> {{ $invitation->role }}<br>
                        <strong>Permission:</strong> {{ $invitation->permission }}<br>
                        <strong>Invited:</strong> {{ $invitation->created_at->diffForHumans() }}
                    </div>
                    <form method="POST" action="{{ route('register.invitation.handle', $invitation->id) }}" class="flex flex-col gap-2">
                        @csrf
                        <label for="token_{{ $invitation->id }}" class="text-sm">Enter invitation token to accept:</label>
                        <input type="text" name="token" id="token_{{ $invitation->id }}" class="border rounded px-2 py-1" required>
                        <div class="flex gap-2 mt-2">
                            <button type="submit" name="action" value="accept" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Accept</button>
                            <button type="submit" name="action" value="reject" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700">Reject</button>
                        </div>
                    </form>
                </div>
            @endforeach
        @endif
    </div>
</x-layout> 