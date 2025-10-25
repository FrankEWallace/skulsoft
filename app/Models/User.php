<?php

namespace App\Models;

use App\Actions\Auth\ValidateIp;
use App\Actions\Auth\ValidateRole;
use App\Casts\EnumCast;
use App\Concerns\Auth\TwoFactorSecurity;
use App\Concerns\HasFilter;
use App\Concerns\HasMeta;
use App\Concerns\HasStorage;
use App\Concerns\HasUuid;
use App\Enums\UserStatus;
use App\Events\Auth\UserLogin;
use App\Helpers\SysHelper;
use App\Models\Chat\Chat;
use App\Models\Chat\Message;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasFilter, HasMeta, HasRoles, HasStorage, HasUuid, LogsActivity, Notifiable, TwoFactorSecurity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
        'pending_update',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'status' => EnumCast::class.':'.UserStatus::class,
        'meta' => 'array',
        'pending_update' => 'array',
        'preference' => 'array',
    ];

    public function chats()
    {
        return $this->belongsToMany(Chat::class, 'chat_participants');
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function getIsDefaultAttribute()
    {
        return $this->getMeta('is_default') ? true : false;
    }

    public function getTimezoneAttribute()
    {
        $timezone = Arr::get($this->preference, 'system.timezone', config('config.system.timezone', config('app.timezone')));

        if (empty($timezone)) {
            $timezone = config('config.system.timezone', config('app.timezone'));
        }

        return $timezone;
    }

    public function getUserSidebarAttribute()
    {
        return Arr::get($this->preference, 'layout.sidebar', config('config.system.enable_mini_sidebar') ? 'mini' : 'pinned');
    }

    public function getUserDisplayAttribute()
    {
        return Arr::get($this->preference, 'layout.display', config('config.system.enable_dark_theme') ? 'dark' : 'light');
    }

    public function getUserPreferenceAttribute()
    {
        return [
            'system' => [
                'locale' => Arr::get($this->preference, 'system.locale', config('config.system.locale')),
                'timezone' => $this->timezone,
                'date_format' => Arr::get($this->preference, 'system.date_format', config('config.system.date_format')),
                'time_format' => Arr::get($this->preference, 'system.time_format', config('config.system.time_format')),
            ],
            'layout' => [
                'sidebar' => $this->user_sidebar,
                'display' => $this->user_display,
            ],
            'academic' => [
                'period' => Arr::get($this->preference, 'academic.period_id', config('config.academic.default_period_id')),
            ],
        ];
    }

    public function getUserRoleAttribute()
    {
        if (empty(\Auth::user())) {
            return [];
        }

        // if (\Auth::user()->hasRole('admin')) {
        //     return ['*'];
        // }

        return $this->roles()->pluck('name')->all();
    }

    public function getUserPermissionAttribute()
    {
        if (empty(\Auth::user())) {
            return [];
        }

        if (\Auth::user()->hasRole('admin')) {
            return ['*'];
        }

        return $this->getAllPermissions()->pluck('name')->all();
    }

    public function getAvatarAttribute(): string
    {
        $default = '/images/male.png';
        $avatar = $this->getMeta('avatar');

        if (! $avatar) {
            return url($default);
        }

        return $this->getImageFile(visibility: 'public', path: $avatar, default: $default);
    }

    public function getPendingUpdate($option)
    {
        return Arr::get($this->pending_update, $option);
    }

    public function getAllowedTeamIds(): array
    {
        return \DB::table('model_has_roles')->whereModelId($this->id)->whereModelType('User')->get()->pluck('team_id')->all();
    }

    public function validateCurrentTeamId()
    {
        $allowedTeamIds = $this->getAllowedTeamIds();

        $currentTeamId = $this->getMeta('current_team_id');
        $meta = $this->meta;

        if (
            ($currentTeamId && ! in_array($currentTeamId, $allowedTeamIds)) ||
            ! $currentTeamId
        ) {
            $this->updateMeta(['current_team_id' => Arr::first($allowedTeamIds)]);
        }
    }

    public function setCurrentTeamId()
    {
        $currentTeamId = $this->getMeta('current_team_id');

        if (! $currentTeamId) {
            $allowedTeamIds = $this->getAllowedTeamIds();
            $this->updateMeta(['current_team_id' => Arr::first($allowedTeamIds)]);
        }

        session(['team_id' => $currentTeamId]);

        SysHelper::setTeam($currentTeamId);

        cache()->forget('query_config_list_all');
    }

    public function getCurrentTeamIdAttribute()
    {
        $currentTeamId = $this->getMeta('current_team_id');

        if (! config('config.teams_set', false)) {
            return $currentTeamId;
        }

        $allowedTeamIds = config('config.teams', []);

        if ($this->is_default && $currentTeamId) {
            return $currentTeamId;
        }

        if (in_array($currentTeamId, $allowedTeamIds)) {
            return $currentTeamId;
        }

        return Arr::first($allowedTeamIds);
    }

    public function getCurrentPeriodIdAttribute()
    {
        $currentPeriodId = $this->getPreference('academic.period_id');

        $allowedPeriodIds = config('config.academic.periods', []);

        if (in_array($currentPeriodId, $allowedPeriodIds)) {
            return $currentPeriodId;
        }

        return config('config.academic.default_period_id', Arr::first($allowedPeriodIds));
    }

    public function getScopeAttribute()
    {
        return $this->getMeta('scope', 'current_team');
    }

    public function getScopeDetailAttribute()
    {
        return $this->getMeta('scope_detail', []);
    }

    public function getScopeTeamsAttribute()
    {
        return $this->getMeta('scope_detail.teams', []);
    }

    // Constrains

    public function validateStatus($authEvent = true): void
    {
        if ($this->is_default) {
            $this->dispatchAuthEvent($authEvent);

            return;
        }

        if (! $this->can('login:action')) {
            $this->logout();
            throw ValidationException::withMessages(['email' => __('auth.login.errors.permission_disabled')]);
        }

        if ($this->status != UserStatus::ACTIVATED) {
            $this->logout();
            throw ValidationException::withMessages(['email' => __('auth.login.errors.invalid_status.'.$this->status->value)]);
        }

        if (config('config.system.enable_maintenance_mode')) {
            $this->logout();
            throw ValidationException::withMessages(['email' => config('config.system.maintenance_mode_message', trans('general.errors.under_maintenance'))]);
        }

        (new ValidateRole)->execute($this);

        $this->dispatchAuthEvent($authEvent);
    }

    public function validateIp(string $ip): void
    {
        (new ValidateIp)->execute($this, $ip, ['logout' => true]);
    }

    private function dispatchAuthEvent(bool $authEvent)
    {
        if (! $authEvent) {
            return;
        }

        event(new UserLogin($this));
    }

    public function logout(): void
    {
        activity('user')->log('logged_out');

        session()->forget('impersonate');

        \Auth::guard('web')->logout();

        session()->forget('team_id');
    }

    public function getPreference(string $option)
    {
        return Arr::get($this->preference, $option);
    }

    public function isEditable()
    {
        if ($this->getMeta('is_default')) {
            return false;
        }

        if (! \Auth::user()->is_default && $this->hasRole('admin')) {
            return false;
        }

        if ($this->id == \Auth::id()) {
            return false;
        }

        return true;
    }

    public function scopeIsNotAdmin(Builder $query): void
    {
        $query->where(function ($q) {
            $q->where('meta->is_default', null)->orWhere('meta->is_default', false);
        });
    }

    public function getIsStudentOrGuardianAttribute(): bool
    {
        if ($this->hasAnyRole(['student', 'guardian'])) {
            return true;
        }

        return false;
    }

    public function isTeamMember(): bool
    {
        if ($this->is_default) {
            return false;
        }

        if (\Auth::user()->is_default) {
            return true;
        }

        if ($this->roles()->firstWhere('model_has_roles.team_id', 1)) {
            return true;
        }

        return false;
    }

    public function updateSelectionHistory(array $data): array
    {
        $teamId = Arr::get($data, 'team_id');
        $periodId = Arr::get($data, 'period_id');

        $selectionHistory = collect($this->meta['selection_history'] ?? []);
        $existingTeam = $selectionHistory->where('team_id', $teamId)->first();

        if ($existingTeam) {
            $selectionHistory->transform(function ($item) use ($teamId, $periodId) {
                if ($item['team_id'] == $teamId) {
                    $item['period_id'] = $periodId;
                }

                return $item;
            });
        } else {
            $selectionHistory->push([
                'team_id' => $teamId,
                'period_id' => $periodId,
            ]);
        }

        return $selectionHistory->toArray();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('user')
            ->logAll()
            ->logExcept(['updated_at'])
            ->logOnlyDirty();
    }
}
