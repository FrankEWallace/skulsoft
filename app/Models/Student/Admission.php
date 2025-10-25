<?php

namespace App\Models\Student;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Academic\Batch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Admission extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'admissions';

    protected $casts = [
        'joining_date' => DateCast::class,
        'leaving_date' => DateCast::class,
        'cancelled_at' => DateTimeCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Admission';
    }

    public function registration(): BelongsTo
    {
        return $this->belongsTo(Registration::class);
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('registration', function ($q) use ($teamId) {
            $q->whereHas('contact', function ($q) use ($teamId) {
                $q->byTeam($teamId);
            });
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('admission')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
