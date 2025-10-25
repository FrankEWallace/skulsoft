<?php

namespace App\Models;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GroupMember extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'group_members';

    protected $attributes = [];

    protected $casts = [
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'GroupMember';
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function modelGroup(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'model_group_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('group_member')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
