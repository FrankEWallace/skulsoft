<?php

namespace App\Models\Academic;

use App\Concerns\HasConfig;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasUuid;
use App\Enums\Academic\CertificateFor;
use App\Enums\Academic\CertificateType;
use App\Enums\CustomFieldType;
use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CertificateTemplate extends Model
{
    use HasConfig, HasFactory, HasFilter, HasMeta, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'certificate_templates';

    protected $attributes = [];

    protected $casts = [
        'type' => CertificateType::class,
        'for' => CertificateFor::class,
        'custom_fields' => 'array',
        'config' => 'array',
        'meta' => 'array',
    ];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function getSlugAttribute(): string
    {
        return Str::slug($this->name, '-');
    }

    public function getDetailedCustomFieldsAttribute(): array
    {
        $customFields = [];
        foreach ($this->custom_fields as $key => $field) {
            $field['placeholder'] = strtoupper(Str::snake(Arr::get($field, 'label')));
            $field['name'] = Arr::get($field, 'name');
            $field['is_required'] = (bool) Arr::get($field, 'is_required');
            $field['show_label'] = (bool) Arr::get($field, 'show_label');

            if (in_array(Arr::get($field, 'type'), [CustomFieldType::SELECT_INPUT->value, CustomFieldType::MULTI_SELECT_INPUT->value, CustomFieldType::CHECKBOX_INPUT->value, CustomFieldType::RADIO_INPUT->value])) {
                $field['option_array'] = array_map('trim', explode(',', Arr::get($field, 'options')));
                $field['option_array'] = array_map(fn ($option) => ['label' => $option, 'value' => $option], $field['option_array']);
            }

            $field['value'] = in_array(Arr::get($field, 'type'), [CustomFieldType::CHECKBOX_INPUT->value, CustomFieldType::MULTI_SELECT_INPUT->value]) ? [] : '';

            $customFields[] = $field;
        }

        return $customFields;
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byTeam()
            ->where('uuid', $uuid)
            ->getOrFail(trans('academic.certificate.template.template'));
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->where('team_id', $teamId);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('certificate_template')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
