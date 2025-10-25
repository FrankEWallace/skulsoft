<?php

namespace App\Models\Employee\Payroll;

use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Record extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'payroll_records';

    protected $casts = [
        'amount' => PriceCast::class,
        'calculated' => PriceCast::class,
        'meta' => 'array',
    ];

    public function payroll(): BelongsTo
    {
        return $this->belongsTo(Payroll::class);
    }

    public function payHead(): BelongsTo
    {
        return $this->belongsTo(PayHead::class);
    }

    public function getAsTotalAttribute(): bool
    {
        return (bool) $this->getMeta('as_total', false);
    }

    public function getVisibilityAttribute(): bool
    {
        if (! $this->as_total) {
            return true;
        }

        $showPayrollAsTotalComponent = config('config.employee.show_payroll_as_total_component');

        if ($showPayrollAsTotalComponent) {
            return true;
        }

        return false;
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payroll')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
