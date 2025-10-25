<?php

namespace App\Models\Finance;

use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeeInstallmentRecord extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'fee_installment_records';

    protected $attributes = [];

    protected $casts = [
        'amount' => PriceCast::class,
        'meta' => 'array',
    ];

    protected $appends = ['applicable_to'];

    public function installment(): BelongsTo
    {
        return $this->belongsTo(FeeInstallment::class, 'fee_installment_id');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }

    public function getApplicableToAttribute(): string
    {
        return $this->getMeta('applicable_to', 'all');
    }

    public function getApplicableToGenderAttribute(): string
    {
        return $this->getMeta('applicable_to_gender', 'all');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('fee_installment_record')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
