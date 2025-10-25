<?php

namespace App\Models\Employee\Payroll;

use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalaryTemplate extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'salary_templates';

    protected $casts = [
        'config' => 'array',
        'meta' => 'array',
    ];

    public function records(): HasMany
    {
        return $this->hasMany(SalaryTemplateRecord::class, 'salary_template_id');
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function structures(): HasMany
    {
        return $this->hasMany(SalaryStructure::class, 'salary_template_id');
    }

    public function getHasHourlyPayrollAttribute(): bool
    {
        return $this->getConfig('has_hourly_payroll') ? true : false;
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->byTeam()
            ->whereUuid($uuid)
            ->getOrFail(trans('employee.payroll.salary_template.salary_template'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('salary_template')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
