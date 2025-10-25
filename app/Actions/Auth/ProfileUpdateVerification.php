<?php

namespace App\Actions\Auth;

use App\Http\Resources\AuthUserResource;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class ProfileUpdateVerification
{
    public function execute(Request $request)
    {
        $user = \Auth::user();

        $pendingUpdate = $user->pending_update;

        if (! $pendingUpdate) {
            throw ValidationException::withMessages(['message' => trans('general.errors.invalid_action')]);
        }

        $this->validateExistingEmail($request, $pendingUpdate);

        $this->validateNewEmail($request, $pendingUpdate);

        $this->validateOtpValidity($request, $pendingUpdate);

        $user->email = Arr::get($pendingUpdate, 'email') ?? $user->email;
        $user->username = Arr::get($pendingUpdate, 'username') ?? $user->username;
        $user->pending_update = null;

        $user->save();

        return AuthUserResource::make($user);
    }

    private function validateExistingEmail(Request $request, $pendingUpdate)
    {
        $existingEmailVerification = Arr::get($pendingUpdate, 'existing_email_otp') ? true : false;

        if (! $existingEmailVerification) {
            return;
        }

        $request->validate([
            'existing_email_otp' => 'required',
        ], [], [
            'existing_email_otp' => trans('user.profile.props.existing_email_otp'),
        ]);

        if (Arr::get($pendingUpdate, 'existing_email_otp') != $request->existing_email_otp) {
            throw ValidationException::withMessages(['existing_email_otp' => trans('general.errors.invalid_input')]);
        }
    }

    private function validateNewEmail(Request $request, $pendingUpdate)
    {
        $newEmailVerification = Arr::get($pendingUpdate, 'new_email_otp') ? true : false;

        if (! $newEmailVerification) {
            return;
        }

        $request->validate([
            'new_email_otp' => 'required',
        ], [], [
            'new_email_otp' => trans('user.profile.props.new_email_otp'),
        ]);

        if (Arr::get($pendingUpdate, 'new_email_otp') != $request->new_email_otp) {
            throw ValidationException::withMessages(['new_email_otp' => trans('general.errors.invalid_input')]);
        }
    }

    private function validateOtpValidity(Request $request, $pendingUpdate)
    {
        $validTill = Carbon::parse(Arr::get($pendingUpdate, 'valid_till'));

        if ($validTill < now()->toDateTimeString()) {
            throw ValidationException::withMessages(['message' => trans('user.profile.verification_otp_expired')]);
        }
    }
}
