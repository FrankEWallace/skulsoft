<?php

namespace App\Support;

class Site
{
    public function view(string $view, array $data = [])
    {
        return view('site.'.config('config.site.theme', 'default').'.'.$view, $data);
    }
}
