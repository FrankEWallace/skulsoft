<?php

namespace App\Models;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Guardian extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'guardians';

    protected $casts = [
        'meta' => 'array',
    ];

    protected $with = [];

    public function primary(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'primary_contact_id');
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class, 'contact_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('contact', function ($q) use ($teamId) {
            $q->whereTeamId($teamId);
        });
    }

    public function scopeFilterByPrimaryContact(Builder $query, int $primaryContactId)
    {
        $query->where('primary_contact_id', $primaryContactId);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->whereHas('primary', function ($q) {
                $q->where('team_id', auth()->user()->current_team_id);
            })
            ->whereUuid($uuid)
            ->getOrFail(trans('guardian.guardian'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('guardian')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
