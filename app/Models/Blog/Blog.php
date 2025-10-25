<?php

namespace App\Models\Blog;

use App\Casts\DateTimeCast;
use App\Casts\EnumCast;
use App\Concerns\HasFilter;
use App\Concerns\HasMedia;
use App\Concerns\HasMeta;
use App\Concerns\HasStorage;
use App\Concerns\HasTags;
use App\Concerns\HasUuid;
use App\Enums\Blog\Status;
use App\Enums\Blog\Visibility;
use App\Models\Option;
use App\Models\Tag;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Blog extends Model
{
    use HasFactory, HasFilter, HasMedia, HasMeta, HasStorage, HasTags, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'blogs';

    protected $attributes = [
        'status' => Status::DRAFT,
        'visibility' => Visibility::PUBLIC,
    ];

    protected $casts = [
        'published_at' => DateTimeCast::class,
        'pinned_at' => DateTimeCast::class,
        'archived_at' => DateTimeCast::class,
        'status' => EnumCast::class.':'.Status::class,
        'visibility' => EnumCast::class.':'.Visibility::class,
        'assets' => 'array',
        'seo' => 'array',
        'author' => 'array',
        'analytic' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    protected $with = [];

    public function getModelName(): string
    {
        return 'Blog';
    }

    public function getScoutKey()
    {
        return $this->uuid;
    }

    public function getScoutKeyName()
    {
        return 'uuid';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'category_id');
    }

    public function tags()
    {
        return $this->morphToMany(Tag::class, 'taggable');
    }

    public function scopeFindIfExists(Builder $query, string $uuid, $field = 'message'): self
    {
        return $query->whereUuid($uuid)
            ->getOrFail(trans('blog.blog'), $field);
    }

    public function scopeSearchByKeyword(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('sub_title', 'like', "%{$search}%");
            // $q->whereHas('tags', function($q) use ($search) {
            //     $q->where('name', 'like', "%{$search}%");
            // });
            // ->orWhereHas('category', function($q) use ($search) {
            //     $q->whereType(OptionType::BLOG_CATEGORY->value)
            //     ->where('name', 'like', "%{$search}%");
            // });
        });
    }

    protected function getCoverImageAttribute(): string
    {
        $cover = Arr::get($this->assets, 'cover');

        return $this->getImageFile(visibility: 'public', path: $cover, default: '/images/blog/cover.webp');
    }

    protected function getOgImageAttribute(): string
    {
        $og = Arr::get($this->assets, 'og');

        return $this->getImageFile(visibility: 'public', path: $og, default: '/images/blog/og.webp');
    }

    public function getIsPublishedAttribute(): bool
    {
        if (empty($this->published_at->value)) {
            return false;
        }

        if (Carbon::parse($this->published_at->value)->isFuture()) {
            return false;
        }

        return true;
    }

    public function getSeo(string $option, mixed $default = null)
    {
        return Arr::get($this->seo, $option, $default);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('blog')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
