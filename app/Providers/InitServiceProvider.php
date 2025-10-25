<?php

namespace App\Providers;

use App\Mixins\ArrMixin;
use App\Mixins\CollectionMixin;
use App\Mixins\QueryMixin;
use App\Mixins\RequestMixin;
use App\Mixins\ResponseMixin;
use App\Mixins\StrMixin;
use App\Mixins\ViewMixin;
use App\Support\Site;
use App\ValueObjects\Cal;
use App\ValueObjects\Country;
use App\ValueObjects\Currency;
use App\ValueObjects\Percent;
use App\ValueObjects\Price;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class InitServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('site', function () {
            return new Site;
        });

        $this->app->bind('price', function () {
            return new Price;
        });

        $this->app->bind('currency', function () {
            return new Currency;
        });

        $this->app->bind('cal', function () {
            return new Cal;
        });

        $this->app->bind('percent', function () {
            return new Percent;
        });

        $this->app->bind('country', function () {
            return new Country;
        });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        Str::mixin(new StrMixin);
        Arr::mixin(new ArrMixin);
        Response::mixin(new ResponseMixin);
        Request::mixin(new RequestMixin);
        View::mixin(new ViewMixin);
        Collection::mixin(new CollectionMixin);
        Builder::mixin(new QueryMixin);
        // Carbon::mixin(new CarbonMixin());
    }
}
