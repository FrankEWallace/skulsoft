#!/usr/bin/env php
<?php

/**
 * SkulSoft Production Preparation Script
 * 
 * This script helps prepare the application for production deployment
 * by removing demo data and ensuring security settings are correct.
 * 
 * Usage: php prepare_production.php
 */

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   SkulSoft Production Preparation Script                  ║\n";
echo "║   ⚠️  WARNING: This will remove demo users and data        ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

// Check if we're in the right directory
if (!file_exists('artisan')) {
    echo "❌ Error: Please run this script from the Laravel root directory.\n\n";
    exit(1);
}

// Load Laravel
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

echo "📋 Production Preparation Checklist\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Confirm before proceeding
echo "This script will:\n";
echo "  1. Remove all demo users (@demo.com)\n";
echo "  2. Clean up demo data\n";
echo "  3. Check environment configuration\n";
echo "  4. Verify security settings\n";
echo "\n";
echo "⚠️  Make sure you have a database backup before proceeding!\n\n";
echo "Do you want to continue? (yes/no): ";

$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes') {
    echo "\n❌ Operation cancelled.\n\n";
    exit(0);
}

echo "\n🚀 Starting production preparation...\n\n";

// Step 1: Remove demo users
echo "1️⃣  Removing demo users...\n";
try {
    $demoUsers = DB::table('users')
        ->where('email', 'like', '%@demo.com%')
        ->get();
    
    $count = $demoUsers->count();
    
    if ($count > 0) {
        echo "   Found {$count} demo users:\n";
        foreach ($demoUsers as $user) {
            echo "   - {$user->email} ({$user->name})\n";
        }
        
        // Delete demo users
        DB::table('users')->where('email', 'like', '%@demo.com%')->delete();
        echo "   ✅ Removed {$count} demo users\n\n";
    } else {
        echo "   ✅ No demo users found\n\n";
    }
} catch (Exception $e) {
    echo "   ❌ Error removing demo users: " . $e->getMessage() . "\n\n";
}

// Step 2: Check default admin password
echo "2️⃣  Checking admin account...\n";
try {
    $admin = DB::table('users')->where('email', 'admin@skulsoft.com')->first();
    
    if ($admin) {
        echo "   ⚠️  Default admin account exists: admin@skulsoft.com\n";
        echo "   🔒 Please change the password after deployment!\n\n";
    } else {
        echo "   ✅ No default admin account found\n\n";
    }
} catch (Exception $e) {
    echo "   ⚠️  Could not check admin account: " . $e->getMessage() . "\n\n";
}

// Step 3: Check environment settings
echo "3️⃣  Checking environment configuration...\n";
$issues = [];

if (env('APP_DEBUG') === true) {
    $issues[] = "APP_DEBUG is set to true (should be false in production)";
}

if (env('APP_ENV') !== 'production') {
    $issues[] = "APP_ENV is set to '" . env('APP_ENV') . "' (should be 'production')";
}

if (empty(env('APP_KEY'))) {
    $issues[] = "APP_KEY is not set";
}

if (env('DB_PASSWORD') === 'root' || env('DB_PASSWORD') === '') {
    $issues[] = "Weak or empty database password";
}

if (count($issues) > 0) {
    echo "   ⚠️  Environment issues found:\n";
    foreach ($issues as $issue) {
        echo "      - {$issue}\n";
    }
    echo "\n";
} else {
    echo "   ✅ Environment configuration looks good\n\n";
}

// Step 4: Check file permissions
echo "4️⃣  Checking file permissions...\n";
$permissionIssues = [];

if (file_exists('.env')) {
    $perms = substr(sprintf('%o', fileperms('.env')), -4);
    if ($perms !== '0600') {
        $permissionIssues[] = ".env has permissions {$perms} (should be 0600)";
    }
}

if (count($permissionIssues) > 0) {
    echo "   ⚠️  Permission issues found:\n";
    foreach ($permissionIssues as $issue) {
        echo "      - {$issue}\n";
    }
    echo "\n";
} else {
    echo "   ✅ File permissions look good\n\n";
}

// Step 5: Clear all caches
echo "5️⃣  Clearing application caches...\n";
try {
    Artisan::call('optimize:clear');
    echo "   ✅ All caches cleared\n\n";
} catch (Exception $e) {
    echo "   ⚠️  Error clearing caches: " . $e->getMessage() . "\n\n";
}

// Step 6: Run security checks
echo "6️⃣  Running security checks...\n";
echo "   Checking for sensitive files...\n";

$sensitiveFiles = [
    '.env.example',
    'phpinfo.php',
    'info.php',
    'test.php',
    'debug.php',
];

$foundSensitive = [];
foreach ($sensitiveFiles as $file) {
    if (file_exists($file)) {
        $foundSensitive[] = $file;
    }
}

if (count($foundSensitive) > 0) {
    echo "   ⚠️  Sensitive files found (consider removing):\n";
    foreach ($foundSensitive as $file) {
        echo "      - {$file}\n";
    }
    echo "\n";
} else {
    echo "   ✅ No sensitive test files found\n\n";
}

// Summary
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "📊 Summary\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

$totalIssues = count($issues) + count($permissionIssues) + count($foundSensitive);

if ($totalIssues === 0) {
    echo "✅ All checks passed! Application is ready for production.\n\n";
} else {
    echo "⚠️  Found {$totalIssues} issue(s) that need attention.\n\n";
}

echo "📝 Next Steps:\n";
echo "   1. Review and update .env file for production\n";
echo "   2. Set APP_DEBUG=false\n";
echo "   3. Set APP_ENV=production\n";
echo "   4. Change default admin password\n";
echo "   5. Configure SSL certificate\n";
echo "   6. Set up automated backups\n";
echo "   7. Review SECURITY_AUDIT_CHECKLIST.md\n";
echo "   8. Run: php artisan config:cache\n";
echo "   9. Run: php artisan route:cache\n";
echo "   10. Run: php artisan view:cache\n";
echo "\n";

echo "📚 Documentation:\n";
echo "   - PRODUCTION_DEPLOYMENT_GUIDE.md\n";
echo "   - SECURITY_AUDIT_CHECKLIST.md\n";
echo "   - CPANEL_DEPLOYMENT_GUIDE.md\n";
echo "\n";

echo "✅ Production preparation complete!\n\n";
