<?php

namespace App\Models\Form;

use App\Casts\DateTimeCast;
use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Submission extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMedia, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'form_submissions';

    protected $casts = [
        'submitted_at' => DateTimeCast::class,
        'config' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'FormSubmission';
    }

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class, 'form_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function model()
    {
        return $this->morphTo();
    }

    public function records(): HasMany
    {
        return $this->hasMany(SubmissionRecord::class);
    }

    public function scopeFindByUuidOrFail(Builder $query, Form $form, string $uuid, $field = 'message')
    {
        return $query
            ->where('form_id', $form->id)
            ->where('uuid', $uuid)
            ->getOrFail(trans('form.submission.submission'), $field);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('form_submission')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
