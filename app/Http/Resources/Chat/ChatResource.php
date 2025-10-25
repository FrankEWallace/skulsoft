<?php

namespace App\Http\Resources\Chat;

use Illuminate\Http\Resources\Json\JsonResource;

class ChatResource extends JsonResource
{
    public function toArray($request)
    {
        $name = null;
        $avatar = null;
        if ($this->is_group_chat) {
            $name = $this->name;
            $avatar = $this->avatar;
        } else {
            $participant = $this->participants->firstWhere('user_id', '!=', auth()->id());

            if (! $participant) {
                $participant = $this->participants->first();
            }

            $name = $participant?->user->name;
            $avatar = $participant?->user->avatar;
        }

        return [
            'uuid' => $this->uuid,
            'name' => $name,
            'avatar' => $avatar,
            'recipient' => $this->recipient,
            'unread_count' => $this->unread_count,
            'is_group_chat' => $this->is_group_chat,
            'participants' => $this->participants->map(function ($participant) {
                return [
                    'uuid' => $participant->user->uuid,
                    'name' => $participant->user->name,
                    'avatar' => $participant->user->avatar,
                ];
            }),
            'latest_message' => MessageResource::make($this->latestMessage),
        ];
    }
}
