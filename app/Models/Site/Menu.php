<?php

namespace App\Models\Site;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasParent;
use App\Concerns\HasUuid;
use App\Enums\Site\MenuPlacement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Menu extends Model
{
    use HasFactory, HasFilter, HasMeta, HasParent, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'site_menus';

    protected $casts = [
        'placement' => MenuPlacement::class,
        'is_default' => 'boolean',
        'meta' => 'array',
    ];

    public function page(): BelongsTo
    {
        return $this->belongsTo(Page::class);
    }

    public function getUrlAttribute(): string
    {
        return $this->is_external ? $this->url : route('site.page', $this->slug);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('site_menu')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
