<?php

namespace App\Actions\Auth;

use App\Actions\SendMailTemplate;
use App\Actions\SendSMS;
use App\Helpers\IpHelper;
use App\Helpers\SysHelper;
use App\Http\Resources\AuthUserResource;
use App\Models\Config\Template;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Auth\ThrottlesLogins;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OtpLogin
{
    use ThrottlesLogins;

    public function maxAttempts(): int
    {
        return config('config.auth.login_throttle_max_attempts', 5);
    }

    public function decayMinutes(): int
    {
        return config('config.auth.login_throttle_lock_timeout', 2);
    }

    public function username(): string
    {
        return 'email';
    }

    private function getCacheKey(Request $request, User $user): string
    {
        return 'otp_'.$request->method.'_'.$user->id.'|'.IpHelper::getClientIp();
    }

    /**
     * Request for OTP
     */
    public function request(Request $request): void
    {
        $this->validateMethod($request);

        if ($request->has('otp')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $methodName = 'send'.title_case($request->method).'Otp';
        $this->$methodName($request);
    }

    /**
     * Validate OTP
     */
    public function confirm(Request $request): array
    {
        if (method_exists($this, 'hasTooManyLoginAttempts') &&
            $this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);

            $this->sendLockoutResponse($request);

            return [];
        }

        $user = $this->getUser($request, true);

        if ($request->otp != cache($this->getCacheKey($request, $user))) {
            $this->incrementLoginAttempts($request);
            throw ValidationException::withMessages(['otp' => trans('auth.login.errors.failed')]);
        }

        $this->clearLoginAttempts($request);

        cache()->forget($this->getCacheKey($request, $user));

        \Auth::login($user);

        $user->setCurrentTeamId();

        $user->validateStatus();

        $user->setTwoFactor();

        $user->validateIp($request->ip());

        $request->merge([
            'refresh_period' => true,
        ]);

        activity('user')->log('logged_in');

        return [
            'message' => __('auth.login.logged_in'),
            'user' => AuthUserResource::make($user),
            'two_factor_security' => false,
        ];
    }

    private function validateMethod(Request $request): void
    {
        $method = $request->method;

        if (! in_array($method, ['sms', 'email'])) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        if (! config('config.auth.enable_'.$method.'_otp_login')) {
            throw ValidationException::withMessages(['message' => trans('general.errors.feature_not_available')]);
        }
    }

    private function sendEmailOtp(Request $request): void
    {
        $user = $this->getUser($request);

        $otp = rand(100000, 999999);

        (new SendMailTemplate)->execute(
            email: $user->email,
            code: 'send-email-otp',
            variables: [
                'name' => $user->name,
                'code' => $otp,
                'token_lifetime' => config('config.auth.otp_login_lifetime', 10),
            ]
        );

        cache([$this->getCacheKey($request, $user) => $otp], config('config.auth.otp_login_lifetime', 10) * 60);
    }

    private function sendSmsOtp(Request $request): void
    {
        $user = $this->getUser($request);

        $otp = rand(100000, 999999);

        $smsTemplate = Template::query()
            ->where('code', 'send-sms-otp')
            ->firstOrFail();

        $params = [
            'template_id' => $smsTemplate->getMeta('template_id'),
            'recipients' => [
                [
                    'mobile' => $request->phone,
                    'message' => $smsTemplate->content,
                    'variables' => [
                        'name' => $user->name,
                        'otp' => $otp,
                        'token_lifetime' => config('config.auth.otp_login_lifetime', 10),
                    ],
                ],
            ],
        ];

        (new SendSMS)->execute($params);

        cache([$this->getCacheKey($request, $user) => $otp], config('config.auth.otp_login_lifetime', 10) * 60);
    }

    private function getUser(Request $request, $setSession = false): User
    {
        $query = User::query();

        if ($request->method === 'email') {
            $query->whereEmail($request->email);
        } elseif ($request->method === 'sms') {
            $userId = $this->getUserIdFromPhone($request);
            $query->whereId($userId);
        }

        $user = $query->first();

        if (! $user) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('user.user')])]);
        }

        if ($user->current_team_id && $setSession) {
            session(['team_id' => $user->current_team_id]);
            SysHelper::setTeam($user->current_team_id);
        }

        return $user;
    }

    private function getUserIdFromPhone(Request $request)
    {
        $contacts = Contact::query()
            ->where('contact_number', $request->phone)
            ->get();

        if ($contacts->count() > 1) {
            throw ValidationException::withMessages(['message' => trans('auth.login.multiple_user_found')]);
        }

        $userId = $contacts->first()?->user_id;

        if (! $userId) {
            throw ValidationException::withMessages(['message' => trans('global.could_not_find', ['attribute' => trans('user.user')])]);
        }

        return $userId;
    }
}
