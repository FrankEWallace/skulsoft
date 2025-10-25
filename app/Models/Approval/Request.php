<?php

namespace App\Models\Approval;

use App\Casts\DateCast;
use App\Casts\PriceCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Approval\Category;
use App\Enums\Approval\Status;
use App\Models\Activity;
use App\Models\Comment;
use App\Models\Employee\Employee;
use App\Models\Finance\Ledger;
use App\Models\Option;
use App\Models\RequestRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Request extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'approval_requests';

    protected $casts = [
        'amount' => PriceCast::class,
        'date' => DateCast::class,
        'due_date' => DateCast::class,
        'payment' => 'array',
        'contact' => 'array',
        'items' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'ApprovalRequest';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function priority(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'priority_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'group_id');
    }

    public function nature(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'nature_id');
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Ledger::class, 'vendor_id');
    }

    public function requestRecords(): MorphMany
    {
        return $this->morphMany(RequestRecord::class, 'model');
    }

    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function activities(): MorphMany
    {
        return $this->morphMany(Activity::class, 'subject');
    }

    public function getCategoryDetailAttribute()
    {
        $category = Category::getDetail($this->type->category);

        return $category;
    }

    public function getStatusDetailAttribute()
    {
        $status = Status::getDetail($this->status);

        return $status;
    }

    public function getIsEditableAttribute()
    {
        if (! auth()->user()->can('approval-request:edit')) {
            return false;
        }

        if ($this->request_user_id == auth()->id() && ! in_array($this->status, [Status::REQUESTED->value, Status::RETURNED->value])) {
            return false;
        }

        if ($this->request_user_id != auth()->id() && ! in_array($this->status, [Status::REQUESTED->value, Status::HOLD->value, Status::RETURNED->value])) {
            return false;
        }

        if ($this->request_user_id == auth()->id()) {
            if ($this->status == Status::REQUESTED->value) {
                if ($this->requestRecords->filter(function ($record) {
                    return $record->status != Status::REQUESTED->value;
                })->count()) {
                    return false;
                }

                return true;
            } else if ($this->status == Status::RETURNED->value && $this->getMeta('return_to_requester')) {
                return true;
            }

            return false;
        }

        $recordRequest = $this->requestRecords->filter(function ($record) {
            return $record->user_id == auth()->id();
        })->first();

        if ($recordRequest && in_array($recordRequest->status, [Status::REQUESTED->value, Status::HOLD->value, Status::RETURNED->value])) {
            return true;
        }

        return false;
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereHas('type', function (Builder $query) use ($teamId) {
            $query->where('team_id', $teamId);
        });
    }

    public function scopeFilterAccessible(Builder $query)
    {
        if (auth()->user()->is_default) {
            return;
        }

        if (auth()->user()->hasRole('admin')) {
            return;
        }

        $userId = Employee::query()
            ->auth()
            ->first()
            ?->user_id;

        if (! $userId) {
            $query->whereNull('request_user_id');

            return;
        }

        $query->where(function ($q) use ($userId) {
            $q->where('request_user_id', $userId)
                ->orWhereHas('requestRecords', function (Builder $q) use ($userId) {
                    $q->where('user_id', $userId);
                });
        });
    }

    public function scopeFindByUuidOrFail(Builder $query, string $uuid, $field = 'message')
    {
        return $query
            // ->byTeam() other team members can see other team requests if they are in the approval levels
            ->filterAccessible()
            ->where('uuid', $uuid)
            ->getOrFail(trans('approval.request.request'), $field);
    }

    public function scopeFindDetailByUuidOrFail(Builder $query, string $uuid)
    {
        return $query
            ->with('requestRecords', 'type.levels')
            // ->byTeam() other team members can see other team requests if they are in the approval levels
            ->filterAccessible()
            ->where('uuid', $uuid)
            ->getOrFail(trans('approval.request.request'), 'message');
    }

    public function getAllowedActions(): array
    {
        $approvalType = $this->type;
        $approvalLevels = $approvalType->levels;

        $employee = Employee::query()
            ->auth()
            ->first();

        if (! $employee) {
            return [];
        }

        $approvalLevel = $approvalLevels->firstWhere('employee_id', $employee->id);

        if (! $approvalLevel) {
            return [];
        }

        $allowedActions = Arr::get($approvalLevel, 'config.actions', []);

        return $allowedActions;
    }

    public function getAllowedStatuses(array $allowedActions): array
    {
        $statuses = collect(Status::getOptions());

        $statuses = $statuses->reject(function ($status) {
            return $status['value'] == Status::REQUESTED->value;
        });

        if (! in_array('hold', $allowedActions)) {
            $statuses = $statuses->reject(function ($status) {
                return $status['value'] == Status::HOLD->value;
            });
        }

        if (! in_array('cancel', $allowedActions)) {
            $statuses = $statuses->reject(function ($status) {
                return $status['value'] == Status::CANCELLED->value;
            });
        }

        if (! in_array('return', $allowedActions)) {
            $statuses = $statuses->reject(function ($status) {
                return $status['value'] == Status::RETURNED->value;
            });
        }

        if (! in_array('reject', $allowedActions)) {
            $statuses = $statuses->reject(function ($status) {
                return $status['value'] == Status::REJECTED->value;
            });
        }

        return $statuses->values()->toArray();
    }

    public function isActionable(): bool
    {
        if ($this->status == Status::CANCELLED->value) {
            return false;
        }

        $firstRecord = $this->requestRecords->first();
        $lastRecord = $this->requestRecords->last();

        if (in_array($firstRecord->status, [Status::REQUESTED->value, Status::HOLD->value]) && $firstRecord->user_id == auth()->id() && $firstRecord->received_at->value) {
            return true;
        }

        $lastApprovedRecord = $this->requestRecords->where('status', Status::APPROVED->value)->last();

        if (! $lastApprovedRecord) {
            return false;
        }

        if (in_array($lastRecord->status, [Status::APPROVED->value, Status::REJECTED->value, Status::CANCELLED->value])) {
            return false;
        }

        $nextRecord = $this->requestRecords->where('id', '>', $lastApprovedRecord->id)->first();

        if ($nextRecord && $nextRecord->user_id == auth()->id()) {
            return true;
        }

        return false;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('approval_request')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
