<?php

namespace App\Models\Resource;

use App\Casts\DateCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SyllabusUnit extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'syllabus_units';

    protected $attributes = [];

    protected $casts = [
        'start_date' => DateCast::class,
        'end_date' => DateCast::class,
        'completion_date' => DateCast::class,
        'meta' => 'array',
    ];

    public function syllabus(): BelongsTo
    {
        return $this->belongsTo(Syllabus::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('syllabus_unit')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
