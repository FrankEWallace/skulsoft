<?php

namespace App\Models;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    use HasFactory, HasFilter, HasMeta;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'activities';

    protected $casts = [
        'description' => 'array',
        'properties' => 'array',
        'meta' => 'array',
    ];

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
