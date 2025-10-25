<?php

namespace App\Providers;

use App\Concerns\ModelRelation;
use App\Contracts\Finance\PaymentGateway;
use App\Services\Finance\PaymentGateway\Billdesk;
use App\Services\Finance\PaymentGateway\Billplz;
use App\Services\Finance\PaymentGateway\Ccavenue;
use App\Services\Finance\PaymentGateway\Paypal;
use App\Services\Finance\PaymentGateway\Paystack;
use App\Services\Finance\PaymentGateway\Payzone;
use App\Services\Finance\PaymentGateway\Razorpay;
use App\Services\Finance\PaymentGateway\Stripe;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Activity;

class AppServiceProvider extends ServiceProvider
{
    use ModelRelation;

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        if ($this->app->environment('local')) {
            //
        }

        $this->app->bind(PaymentGateway::class, function ($app) {
            if (request('gateway') === 'razorpay') {
                return $app->make(Razorpay::class);
            } elseif (request('gateway') === 'paystack') {
                return $app->make(Paystack::class);
            } elseif (request('gateway') === 'stripe') {
                return $app->make(Stripe::class);
            } elseif (request('gateway') === 'payzone') {
                return $app->make(Payzone::class);
            } elseif (request('gateway') === 'paypal') {
                return $app->make(Paypal::class);
            } elseif (request('gateway') === 'ccavenue') {
                return $app->make(Ccavenue::class);
            } elseif (request('gateway') === 'billdesk') {
                return $app->make(Billdesk::class);
            } elseif (request('gateway') === 'billplz') {
                return $app->make(Billplz::class);
            } else {
                throw ValidationException::withMessages(['message' => trans('general.errors.invalid_input')]);
            }
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Activity::saving(function (Activity $activity) {
            $activity->properties = $activity->properties->put('ip', \Request::ip());
            $activity->properties = $activity->properties->put('user_agent', \Request::header('User-Agent'));

            if (session()->has('impersonate')) {
                $activity->properties = $activity->properties->put('impersonate', session('impersonate'));
            }
        });

        if (config('database.type') == 'mariadb') {
            Schema::defaultStringLength(191);
        }

        if (app()->environment('local')) {
            Mail::alwaysTo('hello@scriptmint.com');
        }

        JsonResource::withoutWrapping();
        Validator::includeUnvalidatedArrayKeys();

        Relation::morphMap($this->relations());
    }
}
