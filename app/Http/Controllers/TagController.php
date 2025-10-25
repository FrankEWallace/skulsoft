<?php

namespace App\Http\Controllers;

use App\Services\TagListService;
use Illuminate\Http\Request;

class TagController extends Controller
{
    public function index(Request $request, TagListService $service)
    {
        return $service->paginate($request);
    }
}
