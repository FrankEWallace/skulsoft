<?php

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use App\Services\Blog\BlogListService;
use Illuminate\Http\Request;

class BlogExportController extends Controller
{
    public function __invoke(Request $request, BlogListService $service)
    {
        $list = $service->list($request);

        return $service->export($list);
    }
}
