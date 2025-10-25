<?php

namespace App\Models;

use App\Casts\DateTimeCast;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewLog extends Model
{
    use HasFactory, HasMeta, HasUuid;

    protected $guarded = [];

    protected $casts = [
        'viewed_at' => DateTimeCast::class,
    ];

    public function viewable()
    {
        return $this->morphTo();
    }
}
