<?php

namespace App\Http\Controllers\Chat;

use App\Events\Chat\MessageSent;
use App\Http\Controllers\Controller;
use App\Http\Resources\Chat\MessageResource;
use App\Models\Chat\Chat;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Chat $chat)
    {
        $messages = $chat->messages()
            ->with('user')
            ->latest('id')
            ->cursorPaginate(10);

        return MessageResource::collection($messages);
    }

    public function store(Request $request, Chat $chat)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'type' => 'required|in:text,image,file',
        ]);

        $message = $chat->messages()->create([
            'user_id' => auth()->id(),
            'content' => $validated['content'],
            // 'type' => $validated['type'],
        ]);

        $message->load('user');

        $chat->last_messaged_at = now()->toDateTimeString();
        $chat->save();

        broadcast(new MessageSent($chat, $message))
            ->toOthers();

        return MessageResource::make($message);
    }
}
