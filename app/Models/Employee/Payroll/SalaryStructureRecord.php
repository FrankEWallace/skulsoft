<?php

namespace App\Models\Employee\Payroll;

use App\Casts\PriceCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Employee\Payroll\SalaryStructureUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SalaryStructureRecord extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'salary_structure_records';

    protected $casts = [
        'amount' => PriceCast::class,
        'unit' => SalaryStructureUnit::class,
        'meta' => 'array',
    ];

    public function structure(): BelongsTo
    {
        return $this->belongsTo(SalaryTemplate::class);
    }

    public function payHead(): BelongsTo
    {
        return $this->belongsTo(PayHead::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('salary_structure')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
