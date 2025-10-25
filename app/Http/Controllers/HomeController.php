<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{
    public function __invoke()
    {
        if (config('config.site.enable_site')) {
            return \Site::view('index');
        }

        return redirect()->route('app');
    }
}
