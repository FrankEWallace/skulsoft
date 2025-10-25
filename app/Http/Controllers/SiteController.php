<?php

namespace App\Http\Controllers;

use App\Services\SiteService;

class SiteController extends Controller
{
    public function home(SiteService $service)
    {
        return $service->getPage('Home');
    }

    public function page(string $slug, SiteService $service)
    {
        return $service->getPage($slug);
    }

    public function pageView(string $slug, SiteService $service)
    {
        return $service->getPageView($slug);
    }
}
