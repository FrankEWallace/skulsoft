<?php

namespace App\Models;

use App\Casts\DateCast;
use App\Casts\EnumCast;
use App\Concerns\HasCustomField;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasStorage;
use App\Concerns\HasUuid;
use App\Enums\BloodGroup;
use App\Enums\CustomFieldForm;
use App\Enums\Gender;
use App\Enums\Locality;
use App\Enums\MaritalStatus;
use App\Models\Employee\Employee;
use App\Models\Student\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Contact extends Model
{
    use HasCustomField, HasFactory, HasFilter, HasMeta, HasStorage, HasUuid, LogsActivity;

    protected $guarded = [];

    protected $primaryKey = 'id';

    protected $table = 'contacts';

    protected $casts = [
        'gender' => Gender::class,
        'locality' => EnumCast::class.':'.Locality::class,
        'blood_group' => EnumCast::class.':'.BloodGroup::class,
        'marital_status' => EnumCast::class.':'.MaritalStatus::class,
        'birth_date' => DateCast::class,
        'anniversary_date' => DateCast::class,
        'alternate_records' => 'array',
        'emergency_contact_records' => 'array',
        'address' => 'array',
        'meta' => 'array',
    ];

    public function customFieldFormName(): string
    {
        return CustomFieldForm::STUDENT->value;
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(Guardian::class, 'primary_contact_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(Guardian::class);
    }

    public function caste(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'caste_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'category_id');
    }

    public function religion(): BelongsTo
    {
        return $this->belongsTo(Option::class, 'religion_id');
    }

    public function accounts(): MorphMany
    {
        return $this->morphMany(Account::class, 'accountable');
    }

    public function dialogues(): MorphMany
    {
        return $this->morphMany(Dialogue::class, 'model');
    }

    public function documents(): MorphMany
    {
        return $this->morphMany(Document::class, 'documentable');
    }

    public function qualifications(): MorphMany
    {
        return $this->morphMany(Qualification::class, 'model');
    }

    public function experiences(): MorphMany
    {
        return $this->morphMany(Experience::class, 'model');
    }

    public function getSourceAttribute()
    {
        return $this->getMeta('source');
    }

    public function scopeWithGuardian(Builder $query)
    {
        $query->addSelect(['guardian_id' => Guardian::select('id')
            ->whereColumn('primary_contact_id', 'contacts.id')
            ->orderBy('position', 'asc')
            ->limit(1),
        ])->with('guardian', 'guardian.contact');
    }

    public function scopeByTeam(Builder $query, ?int $teamId = null)
    {
        $teamId = $teamId ?? auth()->user()?->current_team_id;

        $query->whereTeamId($teamId);
    }

    public function scopeFindByUuidOrFail(Builder $query, ?string $uuid = null)
    {
        return $query
            ->byTeam()
            ->whereUuid($uuid)
            ->getOrFail(trans('contact.contact'));
    }

    public function scopeSearchByName(Builder $query, $name = null)
    {
        $columns = ['first_name', 'middle_name', 'third_name', 'last_name'];
        $names = Str::toWordArray($name);
        $query->where(function ($q) use ($columns, $names) {
            foreach ($names as $name) {
                foreach ($columns as $column) {
                    $q->orWhere($column, 'like', "%{$name}%");
                }
            }
        });
    }

    public function getNameAttribute()
    {
        return ucwords(preg_replace('/\s+/', ' ', $this->first_name.' '.$this->middle_name.' '.$this->third_name.' '.$this->last_name));
    }

    public function getNameWithNumberAttribute()
    {
        return ucwords(preg_replace('/\s+/', ' ', $this->first_name.' '.$this->middle_name.' '.$this->third_name.' '.$this->last_name)).' '.$this->contact_number;
    }

    public function getPhotoUrlAttribute(): string
    {
        $photo = $this->photo;

        $default = '/images/'.($this->gender?->value ?? 'male').'.png';

        return $this->getImageFile(visibility: 'public', path: $photo, default: $default);
    }

    public function getPresentAddressAttribute()
    {
        return [
            'address_line1' => Arr::get($this->address, 'present.address_line1'),
            'address_line2' => Arr::get($this->address, 'present.address_line2'),
            'city' => Arr::get($this->address, 'present.city'),
            'state' => Arr::get($this->address, 'present.state'),
            'zipcode' => Arr::get($this->address, 'present.zipcode'),
            'country' => Arr::get($this->address, 'present.country'),
        ];
    }

    public function getSameAsPresentAddressAttribute()
    {
        return (bool) Arr::get($this->address, 'permanent.same_as_present_address');
    }

    public function getPermanentAddressAttribute()
    {
        return [
            'same_as_present_address' => $this->same_as_present_address,
            'address_line1' => Arr::get($this->address, 'permanent.address_line1'),
            'address_line2' => Arr::get($this->address, 'permanent.address_line2'),
            'city' => Arr::get($this->address, 'permanent.city'),
            'state' => Arr::get($this->address, 'permanent.state'),
            'zipcode' => Arr::get($this->address, 'permanent.zipcode'),
            'country' => Arr::get($this->address, 'permanent.country'),
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('contact')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
