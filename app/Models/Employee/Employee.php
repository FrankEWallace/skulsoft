<?php

namespace App\Models\Employee;

use App\Casts\DateCast;
use App\Concerns\HasCustomField;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasStorage;
use App\Concerns\HasTags;
use App\Concerns\HasUuid;
use App\Enums\Employee\Type;
use App\Helpers\CalHelper;
use App\Models\Account;
use App\Models\Contact;
use App\Models\GroupMember;
use App\Models\Qualification;
use App\Models\Tag;
use App\Models\Team;
use App\Scopes\Employee\EmployeeScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Employee extends Model
{
    use EmployeeScope, HasCustomField, HasFactory, HasFilter, HasMeta, HasStorage, HasTags, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'employees';

    protected $casts = [
        'type' => Type::class,
        'joining_date' => DateCast::class,
        'leaving_date' => DateCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    protected $with = [];

    public function getIsDefaultAttribute()
    {
        return $this->getMeta('is_default') ? true : false;
    }

    public function getModelName(): string
    {
        return 'Employee';
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function groups(): MorphMany
    {
        return $this->morphMany(GroupMember::class, 'model');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function accounts(): MorphMany
    {
        return $this->morphMany(Account::class, 'accountable');
    }

    public function records(): HasMany
    {
        return $this->hasMany(Record::class);
    }

    public function qualifications(): HasMany
    {
        return $this->hasMany(Qualification::class, 'employee_id');
    }

    public function lastRecord(): BelongsTo
    {
        return $this->belongsTo(Record::class);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->summary()
            ->filterAccessible()
            ->where('employees.uuid', '=', $uuid)
            ->getOrFail(trans('employee.employee'));
    }

    public function scopeFindSummaryByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->summary()
            ->filterAccessible()
            ->where('employees.uuid', '=', $uuid)
            ->getOrFail(trans('employee.employee'));
    }

    public function scopeFindDetailByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->detail()
            ->filterAccessible()
            ->where('employees.uuid', '=', $uuid)
            ->getOrFail(trans('employee.employee'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->where('employees.team_id', $teamId);
    }

    public function getPeriodAttribute(): string
    {
        return CalHelper::getPeriod($this->joining_date->value, $this->leaving_date->value);
    }

    public function getDurationAttribute(): string
    {
        return CalHelper::getDuration($this->joining_date->value, $this->leaving_date->value, 'day');
    }

    public function getPhotoUrlAttribute(): string
    {
        $photo = $this->photo;

        $default = '/images/'.($this->gender?->value ?? 'male').'.png';

        return $this->getImageFile(visibility: 'public', path: $photo, default: $default);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('employee')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
