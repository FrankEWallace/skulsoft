<?php

namespace App\Actions\Config\Module;

class StoreDisciplineConfig
{
    public static function handle(): array
    {
        $input = request()->validate([
        ], [], [
        ]);

        return $input;
    }
}
