<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\Projects;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class MessagesController extends Controller
{
    private function onlineKey(int $projectId, int $userId): string
    {
        return "chat:online:{$projectId}:{$userId}";
    }

    private function typingKey(int $projectId, int $senderId, int $receiverId): string
    {
        return "chat:typing:{$projectId}:{$senderId}:{$receiverId}";
    }

    public function index()
    {

        $user = Auth::user();
        $projectId = session('selected_project_id');
        $project = Projects::findOrFail($projectId);

        // Get all users in the same project through their people relationship
        $users = User::whereHas('people.projects', function ($query) use ($project) {
            $query->where('projects.id', $project->id);
        })->with(['people' => function ($query) use ($project) {
            $query->with(['projects' => function ($query) use ($project) {
                $query->where('projects.id', $project->id)
                    ->withPivot('role');
            }]);
        }])->where('id', '!=', $user->id)->get();

        return view('chat.index', compact('users'));
    }

    public function getMessages($userId)
    {
        $user = Auth::user();
        $projectId = session('selected_project_id');

        $messages = Message::where(function ($query) use ($user, $userId, $projectId) {
            $query->where('sender_id', $user->id)
                ->where('receiver_id', $userId)
                ->where('projects_id', $projectId);
        })->orWhere(function ($query) use ($user, $userId, $projectId) {
            $query->where('sender_id', $userId)
                ->where('receiver_id', $user->id)
                ->where('projects_id', $projectId);
        })
            ->with(['sender.people' => function ($query) {
                $query->select('id', 'first_name', 'last_name');
            }])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                $message->sender_name = $message->sender->people->first_name.' '.$message->sender->people->last_name;

                return $message;
            });

        // Mark messages as read
        Message::where('sender_id', $userId)
            ->where('receiver_id', $user->id)
            ->where('projects_id', $projectId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json($messages);
    }

    public function sendMessage(Request $request)
    {
        $projectId = session('selected_project_id');

        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'content' => 'required|string',
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'projects_id' => $projectId, // Assuming project ID 1 for now
            'content' => $request->content,
        ]);

        // Load sender information
        $message->load(['sender.people' => function ($query) {
            $query->select('id', 'first_name', 'last_name');
        }]);
        $message->sender_name = $message->sender->people->first_name.' '.$message->sender->people->last_name;

        return response()->json($message);
    }

    public function getUnreadCount()
    {
        $projectId = session('selected_project_id');

        $unreadCount = Message::where('receiver_id', Auth::id())
            ->where('projects_id', $projectId)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $unreadCount]);
    }

    public function getUserUnreadCount($userId)
    {
        $projectId = session('selected_project_id');

        $unreadCount = Message::where('sender_id', $userId)
            ->where('receiver_id', Auth::id())
            ->where('projects_id', $projectId)
            ->where('is_read', false)
            ->count();

        return response()->json(['count' => $unreadCount]);
    }

    public function heartbeat()
    {
        $projectId = (int) session('selected_project_id');
        $userId = (int) Auth::id();
        Cache::put($this->onlineKey($projectId, $userId), true, now()->addSeconds(70));

        return response()->json(['ok' => true]);
    }

    public function onlineStatuses(Request $request)
    {
        $projectId = (int) session('selected_project_id');
        $userIdsRaw = $request->query('user_ids', '');
        $userIds = [];

        if (is_string($userIdsRaw) && $userIdsRaw !== '') {
            $userIds = array_values(array_unique(array_filter(array_map('intval', explode(',', $userIdsRaw)))));
        } elseif (is_array($userIdsRaw)) {
            $userIds = array_values(array_unique(array_filter(array_map('intval', $userIdsRaw))));
        }

        $statuses = [];
        foreach ($userIds as $id) {
            $statuses[(string) $id] = Cache::has($this->onlineKey($projectId, (int) $id));
        }

        return response()->json(['statuses' => $statuses]);
    }

    public function typingStart(Request $request)
    {
        $projectId = (int) session('selected_project_id');
        $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
        ]);

        $senderId = (int) Auth::id();
        $receiverId = (int) $request->receiver_id;
        Cache::put($this->typingKey($projectId, $senderId, $receiverId), true, now()->addSeconds(8));

        return response()->json(['ok' => true]);
    }

    public function typingStop(Request $request)
    {
        $projectId = (int) session('selected_project_id');
        $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
        ]);

        $senderId = (int) Auth::id();
        $receiverId = (int) $request->receiver_id;
        Cache::forget($this->typingKey($projectId, $senderId, $receiverId));

        return response()->json(['ok' => true]);
    }

    public function typingStatus($userId)
    {
        $projectId = (int) session('selected_project_id');
        $senderId = (int) $userId;
        $receiverId = (int) Auth::id();
        $isTyping = Cache::has($this->typingKey($projectId, $senderId, $receiverId));

        return response()->json(['typing' => $isTyping]);
    }
}
