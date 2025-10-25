<?php

namespace App\Models\Student;

use App\Casts\EnumCast;
use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Finance\DefaultFeeHead;
use App\Models\Finance\FeeHead;
use App\Models\Finance\Transaction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class FeePayment extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'student_fee_payments';

    protected $casts = [
        'amount' => PriceCast::class,
        'concession_amount' => PriceCast::class,
        'default_fee_head' => EnumCast::class.':'.DefaultFeeHead::class,
        'meta' => 'array',
    ];

    public function fee(): BelongsTo
    {
        return $this->belongsTo(Fee::class, 'student_fee_id');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(FeeHead::class, 'fee_head_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'transaction_id');
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
            ->useLogName('student_fee_payment')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
