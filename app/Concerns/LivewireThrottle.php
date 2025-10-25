<?php

namespace App\Concerns;

use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;
use DanHarrin\LivewireRateLimiting\WithRateLimiting;
use Illuminate\Validation\ValidationException;

trait LivewireThrottle
{
    use WithRateLimiting;

    public function throttle($maxAttempts = 5, $decaySeconds = 120, $field = 'message')
    {
        try {
            $this->rateLimit($maxAttempts, $decaySeconds);
        } catch (TooManyRequestsException $exception) {
            throw ValidationException::withMessages([
                $field => 'You are sending request too fast. Please wait for '.$exception->secondsUntilAvailable.' seconds',
            ]);
        }
    }
}
