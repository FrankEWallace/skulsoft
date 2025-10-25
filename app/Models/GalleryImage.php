<?php

namespace App\Models;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasStorage;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class GalleryImage extends Model
{
    use HasFactory, HasFilter, HasMeta, HasStorage, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'gallery_images';

    protected $casts = [
        'is_cover' => 'boolean',
        'meta' => 'array',
    ];

    public function getUrlAttribute(): string
    {
        $path = $this->path;

        $default = '/images/item/cover.jpeg';

        return $this->getImageFile(visibility: 'public', path: $path, default: $default);
    }

    public function getThumbnailUrlAttribute(): string
    {
        $path = $this->path;

        $path = Str::of($path)->replaceLast('.', '-thumb.');

        $default = '/images/item/thumbnail.jpeg';

        return $this->getImageFile(visibility: 'public', path: $path, default: $default);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('gallery_image')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
