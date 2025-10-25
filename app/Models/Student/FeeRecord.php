<?php

namespace App\Models\Student;

use App\Casts\DateCast;
use App\Casts\EnumCast;
use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Finance\DefaultFeeHead;
use App\Models\Finance\FeeHead;
use App\Models\Finance\Transaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeeRecord extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'student_fee_records';

    protected $casts = [
        'default_fee_head' => EnumCast::class.':'.DefaultFeeHead::class,
        'amount' => PriceCast::class,
        'paid' => PriceCast::class,
        'concession' => PriceCast::class,
        'due_date' => DateCast::class,
        'has_custom_amount' => 'boolean',
        'is_optional' => 'boolean',
        'meta' => 'array',
    ];

    public function getAmountWithConcession()
    {
        return \Price::from($this->amount->value - $this->concession->value);
    }

    public function getBalance()
    {
        $amount = $this->getAmountWithConcession();

        return \Price::from($amount->value - $this->paid->value);
    }

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class, 'student_fee_id');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->whereUuid($uuid)
            ->getOrFail(trans('student.fee.fee'));
    }

    public function getDefaultFeeHeadName()
    {
        if ($this->fee_head_id || ! $this->default_fee_head) {
            return;
        }

        return DefaultFeeHead::getDetail($this->default_fee_head)['label'] ?? '';
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('student_fee_record')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
