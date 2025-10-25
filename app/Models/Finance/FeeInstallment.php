<?php

namespace App\Models\Finance;

use App\Casts\DateCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\Transport\Fee as TransportFee;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeeInstallment extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'fee_installments';

    protected $attributes = [];

    protected $casts = [
        'due_date' => DateCast::class,
        'late_fee' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class, 'fee_structure_id');
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(FeeGroup::class, 'fee_group_id');
    }

    public function transportFee(): BelongsTo
    {
        return $this->belongsTo(TransportFee::class, 'transport_fee_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(FeeInstallmentRecord::class, 'fee_installment_id');
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->whereUuid($uuid)
            ->getOrFail(trans('finance.fee_structure.installment'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('fee_installment')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
