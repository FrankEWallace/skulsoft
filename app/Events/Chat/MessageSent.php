<?php

namespace App\Events\Chat;

use App\Http\Resources\Chat\MessageResource;
use App\Models\Chat\Chat;
use App\Models\Chat\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chat;

    public $message;

    public function __construct(Chat $chat, Message $message)
    {
        $this->chat = $chat;
        $this->message = $message;
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'message.sent';
    }

    public function broadcastOn()
    {
        return new PrivateChannel('chats.'.$this->chat->uuid);
    }

    public function broadcastWith()
    {
        return [
            // 'message' => (new MessageResource($this->message->load('user')))->resolve(),
            'message' => MessageResource::make($this->message->load('user')),
        ];
    }
}
