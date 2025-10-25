<?php

namespace App\Models\Chat;

use App\Concerns\HasMeta;
use App\Concerns\HasStorage;
use App\Concerns\HasUuid;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory, HasMeta, HasStorage, HasUuid;

    protected $fillable = ['name', 'is_group_chat'];

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'chat_participants');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latest();
    }

    public function getAvatarAttribute(): string
    {
        $default = '/images/group.png';
        $avatar = $this->getMeta('avatar');

        if (! $avatar) {
            return url($default);
        }

        return $this->getImageFile(visibility: 'public', path: $avatar, default: $default);
    }
}
