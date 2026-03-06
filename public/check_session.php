<?php
/**
 * Full login diagnostic script - DELETE after use!
 * Visit: https://sims.mewogstars.sc.tz/check_session.php
 */

define('LARAVEL_START', microtime(true));
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

header('Content-Type: text/plain');

$results = [];

// 1. Storage / INSTALLED flag
try {
    $appFileExists = \Storage::disk('local')->exists('.app');
    $appContent = $appFileExists ? \Storage::disk('local')->get('.app') : '(file missing)';
    $results['.app file exists'] = $appFileExists ? 'YES' : 'NO ✗';
    $results['.app content'] = trim($appContent);
    $results['.reinstall exists'] = \Storage::disk('local')->exists('.reinstall') ? 'YES ✗ (blocks login!)' : 'NO ✓';
} catch (\Exception $e) {
    $results['storage check error'] = $e->getMessage();
}

// 2. isInstalled check
try {
    $installed = \App\Helpers\SysHelper::isInstalled();
    $results['SysHelper::isInstalled()'] = $installed ? 'YES ✓' : 'NO ✗ (app not installed!)';
} catch (\Exception $e) {
    $results['isInstalled error'] = $e->getMessage();
}

// 3. Admin user check
try {
    $user = \App\Models\User::find(1);
    if ($user) {
        $results['admin user exists'] = 'YES ✓';
        $results['admin email'] = $user->email;
        $results['admin status'] = $user->status?->value ?? 'NULL ✗';
        $results['admin current_team_id (meta)'] = $user->getMeta('current_team_id') ?? 'NULL ✗';
        $results['admin is_default (meta)'] = $user->getMeta('is_default') ? 'true ✓' : 'false';
        $teamId = $user->getMeta('current_team_id');
        \App\Helpers\SysHelper::setTeam($teamId);
        $roles = $user->getRoleNames();
        $results['admin roles'] = $roles->isEmpty() ? 'NONE ✗' : $roles->implode(', ');
        \App\Helpers\SysHelper::setTeam(null);
    } else {
        $results['admin user'] = 'NOT FOUND ✗';
    }
} catch (\Exception $e) {
    $results['user check error'] = $e->getMessage();
}

// 4. Team check
try {
    $team = \Illuminate\Support\Facades\DB::table('teams')->find(1);
    $results['team id=1'] = $team ? 'EXISTS: ' . $team->name : 'NOT FOUND ✗';
} catch (\Exception $e) {
    $results['team check error'] = $e->getMessage();
}

// 5. Try actual login attempt
try {
    $user = \App\Models\User::where('email', 'admin@mewogstars.sc.tz')->first();
    if ($user) {
        $passwordOk = \Hash::check('Admin@1234', $user->password);
        $results['password check'] = $passwordOk ? 'CORRECT ✓' : 'WRONG ✗';
    }
} catch (\Exception $e) {
    $results['password check error'] = $e->getMessage();
}

// 6. ValidateRole check
try {
    $user = \App\Models\User::find(1);
    if ($user) {
        $teamId = $user->getMeta('current_team_id');
        \App\Helpers\SysHelper::setTeam($teamId);
        app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($teamId);
        $validateRole = new \App\Actions\Auth\ValidateRole();
        $validateRole->execute($user);
        $results['ValidateRole::execute()'] = 'PASSED ✓';
        \App\Helpers\SysHelper::setTeam(null);
    }
} catch (\Exception $e) {
    $results['ValidateRole::execute() error'] = $e->getMessage();
}

// 7. Storage permissions
$storagePath = storage_path('framework/sessions');
$results['sessions storage path'] = $storagePath;
$results['sessions storage writable'] = is_writable($storagePath) ? 'YES ✓' : 'NO (but using DB driver)';

// 8. CORS / domain
$results['SESSION_DOMAIN (env)'] = env('SESSION_DOMAIN', '(not set)');
$results['SESSION_SECURE_COOKIE (env)'] = env('SESSION_SECURE_COOKIE', '(not set - defaults to false)');
$results['config(session.secure)'] = config('session.secure') ? 'true' : 'false';
$results['config(session.same_site)'] = config('session.same_site');

echo "=== FULL LOGIN DIAGNOSTIC ===\n\n";
foreach ($results as $key => $value) {
    echo str_pad($key, 45) . ': ' . $value . "\n";
}
echo "\n=== END DIAGNOSTIC ===\n";
echo "\n⚠️  DELETE THIS FILE AFTER USE!\n";
