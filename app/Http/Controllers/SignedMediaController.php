<?php

namespace App\Http\Controllers;

use App\Models\Media;
use Illuminate\Http\Request;

class SignedMediaController extends Controller
{
    public function __invoke(Request $request, Media $media, string $conversion = '') {}
}
