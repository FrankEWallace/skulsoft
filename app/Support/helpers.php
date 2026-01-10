<?php

/**
 * SkulSoft Helper Functions
 * 
 * Global helper functions for the application
 */

if (! function_exists('get_team_setting')) {
    /**
     * Get a team-specific setting
     */
    function get_team_setting(string $key, mixed $default = null): mixed
    {
        return app(\App\Services\SettingService::class)->getTeamSetting($key, $default);
    }
}

if (! function_exists('current_team')) {
    /**
     * Get the current team
     */
    function current_team(): ?\App\Models\Team
    {
        return auth()->user()?->currentTeam;
    }
}

if (! function_exists('current_period')) {
    /**
     * Get the current academic period
     */
    function current_period(): ?\App\Models\Academic\Period
    {
        return auth()->user()?->currentPeriod;
    }
}

if (! function_exists('format_currency')) {
    /**
     * Format amount as currency
     */
    function format_currency(float $amount, string $currency = null): string
    {
        $currency = $currency ?? config('app.currency', 'USD');
        return number_format($amount, 2) . ' ' . $currency;
    }
}

if (! function_exists('academic_year')) {
    /**
     * Get current academic year
     */
    function academic_year(): string
    {
        return current_period()?->code ?? date('Y');
    }
}
