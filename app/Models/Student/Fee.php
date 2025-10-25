<?php

namespace App\Models\Student;

use App\Casts\DateCast;
use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Finance\LateFeeFrequency;
use App\Enums\Finance\PaymentStatus;
use App\Helpers\CalHelper;
use App\Models\Finance\FeeConcession;
use App\Models\Finance\FeeInstallment;
use App\Models\Finance\Transaction;
use App\Models\Transport\Circle as TransportCircle;
use App\ValueObjects\Cal;
use App\ValueObjects\Price;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Fee extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'student_fees';

    protected $casts = [
        'additional_charge' => PriceCast::class,
        'additional_discount' => PriceCast::class,
        'total' => PriceCast::class,
        'paid' => PriceCast::class,
        'due_date' => DateCast::class,
        'fee' => 'array',
        'meta' => 'array',
    ];

    protected $with = ['installment'];

    public function getFee(string $option, mixed $default = null)
    {
        return Arr::get($this->fee, $option, $default);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function installment(): BelongsTo
    {
        return $this->belongsTo(FeeInstallment::class, 'fee_installment_id');
    }

    public function transportCircle(): BelongsTo
    {
        return $this->belongsTo(TransportCircle::class, 'transport_circle_id');
    }

    public function concession(): BelongsTo
    {
        return $this->belongsTo(FeeConcession::class, 'fee_concession_id');
    }

    public function records(): HasMany
    {
        return $this->hasMany(FeeRecord::class, 'student_fee_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(FeePayment::class, 'student_fee_id');
    }

    public function transactions()
    {
        return $this->morphMany(Transaction::class, 'transactionable');
    }

    public function getDueDate(): ?Cal
    {
        if ($this->final_due_date) {
            return \Cal::date($this->final_due_date);
        }

        if ($this->due_date?->value) {
            return $this->due_date;
        }

        return $this->installment?->due_date ?? null;
    }

    public function getLateFee(string $option, mixed $default = null): mixed
    {
        $lateFee = Arr::get($this->fee, 'late_fee', []);

        if (array_key_exists($option, $lateFee)) {
            return $lateFee[$option];
        }

        $installmentLateFee = $this->installment_late_fee ? json_decode($this->installment_late_fee, true) : $this->installment?->late_fee;

        return Arr::get($installmentLateFee, $option, $default);
    }

    public function getLateFeeDetail(?string $date = null)
    {
        if (! CalHelper::validateDate($date)) {
            $date = today()->toDateString();
        }

        $lateFeeValue = $this->getLateFee('type', 'amount') === 'amount' ? \Price::from($this->getLateFee('value', 0)) : \Percent::from($this->getLateFee('value', 0));

        return [
            'applicable' => $this->getLateFee('applicable', false),
            'type' => $this->getLateFee('type', 'amount'),
            'frequency' => LateFeeFrequency::getDetail($this->getLateFee('frequency')),
            'value' => $lateFeeValue,
            'amount' => $this->calculateLateFeeAmount($date),
            'paid' => \Price::from($this->getLateFee('paid', 0)),
        ];
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->whereUuid($uuid)
            ->getOrFail(trans('student.fee.fee'));
    }

    public function getStatus(?string $date = null): PaymentStatus
    {
        $total = $this->getTotal($date);

        $balance = $this->getBalance($date);

        if ($total->value <= 0) {
            return PaymentStatus::NA;
        }

        if ($balance->value <= 0) {
            return PaymentStatus::PAID;
        }

        if ($this->paid->value <= 0) {
            return PaymentStatus::UNPAID;
        }

        return PaymentStatus::PARTIALLY_PAID;
    }

    public function getTotal(?string $date = null): Price
    {
        $lateFeeAmount = $this->calculateLateFeeAmount($date);

        return \Price::from($this->total->value + $lateFeeAmount->value);
    }

    public function getPaid(): Price
    {
        return \Price::from($this->paid->value);
    }

    public function getBalance(?string $date = null): Price
    {
        $total = $this->getTotal($date);
        $paid = $this->getPaid();

        return \Price::from($total->value - $paid->value);
    }

    public function getOverdueDays(?string $date = null): int
    {
        $balance = $this->getBalance($date);

        if ($balance->value <= 0) {
            return 0;
        }

        if (! CalHelper::validateDate($date)) {
            $date = today()->toDateString();
        }

        $dueDate = $this->getDueDate();

        if (empty($dueDate?->value)) {
            return 0;
        }

        return $date > $dueDate->value ? abs(Carbon::parse($date)->diffInDays($dueDate->carbon())) : 0;
    }

    public function getInstallmentTotal(): Price
    {
        $installmentTotal = $this->records->sum(function ($record) {
            return $record->amount->value - $record->concession->value;
        });

        return \Price::from($installmentTotal);
    }

    public function calculateLateFeeAmount(?string $date = null): Price
    {
        if (! CalHelper::validateDate($date)) {
            $date = today()->toDateString();
        }

        $lateFeeWaiverTillDate = config('config.student.late_fee_waiver_till_date');

        if ($lateFeeWaiverTillDate && CalHelper::validateDate($lateFeeWaiverTillDate) && $date < $lateFeeWaiverTillDate) {
            return \Price::from(0);
        }

        $amount = \Price::from(0);

        if ($this->total->value <= 0) {
            return $amount;
        }

        // if fee paid once will not have any late fee applicable
        // if ($this->paid->value > 0) {
        //     return $amount;
        // }

        // if fee paid in full will not have any late fee applicable
        if ($this->paid->value >= $this->total->value) {
            return $amount;
        }

        $dueDate = $this->getDueDate();

        if (empty($dueDate?->value)) {
            return $amount;
        }

        if ($date <= $dueDate->value) {
            return $amount;
        }

        if (! $this->getLateFee('applicable', false)) {
            return $amount;
        }

        $multiplier = LateFeeFrequency::getMultiplier($this->getLateFee('frequency'), $date, $dueDate->value);

        $multiplier = abs($multiplier);

        // starts immediately then add one to the multiplier else it will start after frequency period
        if (! in_array($this->getLateFee('frequency'), [LateFeeFrequency::ONE_TIME->value, LateFeeFrequency::DAILY->value])) {
            $multiplier += 1;
        }

        return \Price::from($this->getLateFee('value', 0) * $multiplier);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('student_fee')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
