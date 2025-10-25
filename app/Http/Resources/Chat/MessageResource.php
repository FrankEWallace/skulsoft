<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'uuid' => $this->uuid,
            $this->mergeWhen($this->relationLoaded('user'), [
                'user' => [
                    'uuid' => $this->user->uuid,
                    'name' => $this->user->name,
                    'avatar' => $this->user->avatar,
                ],
            ]),
            'chat' => ChatResource::make($this->whenLoaded('chat')),
            'content' => $this->content,
            'read_at' => $this->read_at,
            'is_sent' => $this->user_id === auth()->id(),
            'created_at' => \Cal::dateTime($this->created_at),
        ];
    }
}
