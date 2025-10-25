<?php

namespace App\Models;

use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Helpers\SysHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Media extends Model
{
    use HasFactory, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'medias';

    protected $casts = [
        'meta' => 'array',
    ];

    public function model(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getIcon(): string
    {
        $icons = [
            'application/json' => 'fa-file-code',
            'application/msword' => 'fa-file-word',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'fa-file-powerpoint',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'fa-file-excel',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'fa-file-word',
            'application/vnd.ms-excel' => 'fa-file-excel',
            'application/vnd.ms-powerpoint' => 'fa-file-powerpoint',
            'application/xml' => 'fa-file-code',
            'application/&' => 'fa-file-pdf',
            'application/pdf' => 'fa-file-pdf',
            'image/jpeg' => 'fa-file-image',
            'image/png' => 'fa-file-image',
            'image/webp' => 'fa-file-image',
            'image/svg+xml' => 'fa-file-image',
            'image/vnd.adobe.photoshop' => 'fa-file-image',
            'image/vnd.microsoft.icon' => 'fa-file-image',
            'image/&' => 'fa-file-image',
            'text/plain' => 'fa-file-alt',
            'video/mp4' => 'fa-file-video',
            'video/ogg' => 'fa-file-video',
            'video/quicktime' => 'fa-file-video',
            'video/&' => 'fa-file-video',
            'video/x-&' => 'fa-file-video',
        ];

        return Arr::get($icons, $this->getMeta('mime'), 'fa-file-alt');
    }

    public function getSizeAttribute()
    {
        return SysHelper::fileSize($this->getMeta('size'));
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('media')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
