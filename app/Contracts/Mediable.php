<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Http\Request;

interface Mediable
{
    public function media(): MorphMany;

    public function getModelName(): string;

    public function setToken(Request $request): void;

    public function addMedia(Request $request): void;

    public function updateMedia(Request $request): void;
}
