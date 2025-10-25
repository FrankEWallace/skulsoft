<?php

namespace App\Models\Form;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Form\Status;
use App\Models\Academic\Period;
use App\Models\Audience;
use App\Scopes\AudienceScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Form extends Model
{
    use AudienceScope, HasConfig, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'forms';

    protected $casts = [
        'due_date' => DateCast::class,
        'published_at' => DateTimeCast::class,
        'audience' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Form';
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(Period::class, 'period_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(Field::class, 'form_id')->orderBy('position', 'asc');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'form_id');
    }

    public function audiences()
    {
        return $this->morphMany(Audience::class, 'shareable');
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->published_at->value ? false : true;
    }

    public function getStatusAttribute(): Status
    {
        if (! $this->published_at->value) {
            return Status::DRAFT;
        }

        if ($this->published_at->value > now()->toDateTimeString()) {
            return Status::DRAFT;
        }

        if ($this->due_date < now()->toDateString()) {
            return Status::EXPIRED;
        }

        return Status::PUBLISHED;
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('period', function ($q) use ($teamId) {
            $q->byTeam($teamId);
        });
    }

    public function scopeByPeriod(Builder $query, $periodId = null)
    {
        $periodId = $periodId ?? auth()->user()->current_period_id;

        $query->wherePeriodId($periodId);
    }

    public function scopeFilterAccessible(Builder $query)
    {
        $query->accessible();
    }

    public function scopeWithSubmission(Builder $query)
    {
        $query->addSelect(['submitted_at' => Submission::select('submitted_at')
            ->whereColumn('form_id', 'forms.id')
            ->where('user_id', auth()->id())
            ->orderBy('submitted_at', 'asc')
            ->limit(1),
        ]);
    }

    public function scopeWithSubmissionCount(Builder $query)
    {
        $query->withCount(['submissions' => function ($query) {
            $query->where('form_submissions.user_id', auth()->id());
        }]);
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            ->byPeriod()
            ->filterAccessible()
            ->withSubmission()
            ->where('uuid', $uuid)
            ->getOrFail(trans('form.form'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('form')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
