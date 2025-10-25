<?php

namespace App\Models\Inventory;

use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Employee\Employee;
use App\Models\Incharge;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Inventory extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'inventories';

    protected $casts = [
        'meta' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function incharge(): BelongsTo
    {
        return $this->belongsTo(Incharge::class);
    }

    public function incharges(): MorphMany
    {
        return $this->morphMany(Incharge::class, 'model');
    }

    public function scopeWithCurrentIncharges(Builder $query)
    {
        $query->with([
            'incharges' => function ($q) {
                return $q->where('start_date', '<=', today()->toDateString())
                    ->where(function ($q) {
                        $q->whereNull('end_date')
                            ->orWhere('end_date', '>=', today()->toDateString());
                    });
            }, 'incharges.employee' => fn ($q) => $q->detail(),
        ]);
    }

    public function scopeWithLastIncharge(Builder $query)
    {
        $query->addSelect(['incharge_id' => Incharge::select('id')
            ->whereColumn('model_id', 'divisions.id')
            ->where('model_type', 'Division')
            ->where('effective_date', '<=', today()->toDateString())
            ->orderBy('effective_date', 'desc')
            ->limit(1),
        ])->with(['incharge', 'incharge.employee' => fn ($q) => $q->detail()]);
    }

    public function scopeFilterAccessible(Builder $query, ?string $date = null)
    {
        if (auth()->user()->hasRole('admin')) {
            return;
        }

        if (auth()->user()->can('inventory:admin-access')) {
            return;
        }

        $date = $date ?? today()->toDateString();

        $employee = Employee::auth()->first();

        $incharges = $employee ? Incharge::query()
            ->where('model_type', 'Inventory')
            ->where('employee_id', $employee->id)
            ->where('start_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', $date);
            })
            ->pluck('model_id')
            ->all() : [];

        $query->whereIn('id', $incharges);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byTeam()
            ->filterAccessible()
            ->where('uuid', $uuid)
            ->getOrFail(trans('inventory.inventory'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('inventory')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
