<?php

namespace App\Mixins;

class CarbonMixin
{
    /**
     * Get user date format
     */
    public function userDateFormat()
    {
        return self::format('d M Y');
    }

    /**
     * Get user time format
     */
    public function userTimeFormat()
    {
        return self::format('h:i a');
    }
}
