<?php

namespace App\Exceptions;

use Exception;

class MaintenanceModeException extends Exception
{
    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
        ], 503);
    }
}
