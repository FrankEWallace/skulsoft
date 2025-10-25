<?php

namespace App\Actions\Config;

use App\Enums\UserScope;
use App\Helpers\SysHelper;
use App\Http\Resources\Academic\PeriodResource;
use App\Http\Resources\OrganizationResource;
use App\Http\Resources\TeamResource;
use App\Models\Academic\Period;
use App\Models\Organization;
use App\Models\Team;
use App\Support\QrCodeAttendance;
use App\Support\SocialLoginProvider;
use Closure;
use Illuminate\Support\Arr;

class GetGeneralConfig
{
    use QrCodeAttendance, SocialLoginProvider;

    public function handle($config, Closure $next)
    {
        $currentVersion = SysHelper::getApp('VERSION');
        $config['auth']['social_login_providers'] = $this->getActiveProviders();
        $config['system']['url'] = config('app.url');
        $config['system']['version'] = $currentVersion;
        $config['system']['ac'] = SysHelper::getApp() ? true : false;
        $config['system']['post_max_size'] = SysHelper::getPostMaxSize();
        $config['system']['mode'] = (config('app.mode') == 'test') ? 0 : 1;
        $config['system']['paginations'] = Arr::getList('per_page_lengths');

        $config['system']['currency'] = collect(Arr::getVar('currencies'))->firstWhere('name', Arr::get($config, 'system.currency'));
        $config['system']['currencies'] = collect(Arr::getVar('currencies'))->whereIn('name', explode(',', Arr::get($config, 'system.currencies')))->values();

        $config['authenticated'] = false;
        $config['is_impersonating'] = session()->has('impersonate') ? true : false;
        $config['layout']['display'] = config('config.system.enable_dark_theme') ? 'dark' : 'light';

        $mobileCompatibility = false;
        if (SysHelper::versionComparison($currentVersion, config('app.mobile_compatibility'), '>=')) {
            $mobileCompatibility = true;
        }

        $config['system']['mobile_compatibility'] = $mobileCompatibility;

        if (\Auth::check()) {
            $config['authenticated'] = true;
            $config['layout']['display'] = \Auth::user()->user_display;

            $config['system']['qr_code_attendance_enabled'] = $this->isQrCodeAttendanceEnabled();
        }

        // \App::setLocale($config['system']['locale'] ?: 'en');

        if (! empty(\Auth::user())) {
            $config['teams'] = TeamResource::collection(Team::allowedTeams()->get());
            $config['periods'] = PeriodResource::collection(Period::query()
                ->select('periods.*', 'sessions.name as session_name')
                ->byTeam()
                ->leftJoin('sessions', 'sessions.id', '=', 'periods.session_id')
                ->orderBy('periods.start_date', 'desc')
                ->get());

            $config['organizations'] = OrganizationResource::collection(Organization::query()->get());
            $config['user_scopes'] = UserScope::getOptions();
        } else {
            unset($config['module']);
        }

        return $next($config);
    }
}
