<?php

namespace App\Models;

use App\Casts\DateTimeCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\VerificationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Account extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'accounts';

    protected $casts = [
        'verified_at' => DateTimeCast::class,
        'bank_details' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'Account';
    }

    public function accountable(): MorphTo
    {
        return $this->morphTo();
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
            ->useLogName('account')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
