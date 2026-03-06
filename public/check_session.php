<?php
/**
 * Session/CSRF diagnostic script - DELETE after use!
 * Upload to public/ and visit: https://sims.mewogstars.sc.tz/check_session.php
 */

// Load Laravel environment
define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

$results = [];

// 1. Check SESSION_DRIVER
$results['SESSION_DRIVER'] = env('SESSION_DRIVER', '(not set - defaults to file)');
$results['SESSION_TABLE'] = env('SESSION_TABLE', '(not set - defaults to sessions)');
$results['SESSION_DOMAIN'] = env('SESSION_DOMAIN', '(not set)');
$results['SANCTUM_STATEFUL_DOMAINS'] = env('SANCTUM_STATEFUL_DOMAINS', '(not set)');
$results['APP_URL'] = env('APP_URL', '(not set)');

// 2. Check if user_sessions table exists
try {
    $exists = \Illuminate\Support\Facades\Schema::hasTable('user_sessions');
    $results['user_sessions table exists'] = $exists ? 'YES ✓' : 'NO ✗ (migration not run!)';
} catch (\Exception $e) {
    $results['user_sessions table check error'] = $e->getMessage();
}

// 3. Check if old sessions table exists and its columns
try {
    $exists = \Illuminate\Support\Facades\Schema::hasTable('sessions');
    $results['sessions table exists'] = $exists ? 'YES (academic sessions table)' : 'NO';
    if ($exists) {
        $columns = \Illuminate\Support\Facades\Schema::getColumnListing('sessions');
        $results['sessions table columns'] = implode(', ', $columns);
    }
} catch (\Exception $e) {
    $results['sessions table check error'] = $e->getMessage();
}

// 4. Check what the session config actually resolves to
$results['config(session.driver)'] = config('session.driver');
$results['config(session.table)'] = config('session.table');

// 5. Try writing a session
try {
    $sessionTable = config('session.table');
    if (config('session.driver') === 'database') {
        $count = \Illuminate\Support\Facades\DB::table($sessionTable)->count();
        $results['session table row count'] = $count;
        $results['session write test'] = 'Table accessible ✓';
    } else {
        $results['session write test'] = 'SKIPPED (driver is not database)';
    }
} catch (\Exception $e) {
    $results['session write test'] = 'FAILED: ' . $e->getMessage();
}

// 6. Check migrations ran
try {
    $ran = \Illuminate\Support\Facades\DB::table('migrations')
        ->where('migration', 'like', '%user_session%')
        ->first();
    $results['user_sessions migration in DB'] = $ran ? 'YES - ' . $ran->migration : 'NO ✗';
} catch (\Exception $e) {
    $results['migrations check error'] = $e->getMessage();
}

echo "=== SESSION/CSRF DIAGNOSTIC ===\n\n";
foreach ($results as $key => $value) {
    echo str_pad($key, 45) . ': ' . $value . "\n";
}
echo "\n=== END DIAGNOSTIC ===\n";
echo "\n⚠️  DELETE THIS FILE AFTER USE!\n";
