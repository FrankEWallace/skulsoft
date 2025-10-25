<?php

namespace App\Models\Site;

use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasStorage;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Page extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMedia, HasMeta, HasStorage, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'site_pages';

    protected $casts = [
        'assets' => 'array',
        'config' => 'array',
        'seo' => 'array',
        'analytics' => 'array',
        'meta' => 'array',
    ];

    public function getModelName(): string
    {
        return 'SitePage';
    }

    protected function getCoverImageAttribute(): string
    {
        $cover = Arr::get($this->assets, 'cover');

        return $this->getImageFile(visibility: 'public', path: $cover, default: '/images/site/cover.webp');
    }

    protected function getOgImageAttribute(): string
    {
        $og = Arr::get($this->assets, 'og');

        return $this->getImageFile(visibility: 'public', path: $og, default: '/images/site/og.webp');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('site_page')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
