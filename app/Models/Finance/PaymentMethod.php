<?php

namespace App\Models\Finance;

use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentMethod extends Model
{
    use HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'payment_methods';

    protected $casts = [
        'is_payment_gateway' => 'boolean',
        'config' => 'array',
        'meta' => 'array',
    ];

    protected $appends = ['code'];

    public function getConfig(string $option, mixed $default = null)
    {
        return Arr::get($this->config, $option, $default);
    }

    public function getCodeAttribute(): ?string
    {
        return $this->getConfig('code');
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name);
    }

    public function scopeByTeam(Builder $query, $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('payment_method')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
