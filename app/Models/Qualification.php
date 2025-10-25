<?php

namespace App\Models;

use App\Casts\DateCast;
use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\VerificationStatus;
use App\Helpers\CalHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Qualification extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'qualifications';

    protected $casts = [
        'start_date' => DateCast::class,
        'end_date' => DateCast::class,
        'verified_at' => DateTimeCast::class,
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Qualification';
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'level_id');
    }

    public function getPeriodAttribute(): string
    {
        return CalHelper::getPeriod($this->start_date->value, $this->end_date->value);
    }

    public function getDurationAttribute(): string
    {
        return CalHelper::getDuration($this->start_date->value, $this->end_date->value, 'day');
    }

    public function getIsVerifiedAttribute(): bool
    {
        if ($this->getMeta('self_upload')) {
            return $this->verified_at->value ? true : false;
        }

        return true;
    }

    public function getVerificationStatusAttribute(): VerificationStatus
    {
        if (! $this->getMeta('self_upload')) {
            return VerificationStatus::VERIFIED;
        }

        if ($this->verified_at->value) {
            return VerificationStatus::VERIFIED;
        }

        if ($this->getMeta('status') == 'rejected') {
            return VerificationStatus::REJECTED;
        }

        return VerificationStatus::PENDING;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('qualification')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
