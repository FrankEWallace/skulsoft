<?php

namespace App\Models\Reception;

use App\Casts\DateCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Reception\EnquiryStatus;
use App\Models\Option;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EnquiryFollowUp extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'enquiry_follow_ups';

    protected $casts = [
        'follow_up_date' => DateCast::class,
        'next_follow_up_date' => DateCast::class,
        'status' => EnquiryStatus::class,
        'meta' => 'array',
    ];

    public function enquiry(): BelongsTo
    {
        return $this->belongsTo(Enquiry::class, 'enquiry_id');
    }

    public function stage(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'stage_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('enquiry_follow_up')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
