#!/usr/bin/env php
<?php

/**
 * SkulSoft Database Migration Script
 * This script installs the database without triggering middleware
 */

define('LARAVEL_START', microtime(true));

// Register the Composer autoloader
require __DIR__.'/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__.'/bootstrap/app.php';

// Don't run middleware for this command
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🚀 Starting SkulSoft Database Migration...\n\n";

try {
    // Test database connection
    echo "📡 Testing database connection...\n";
    $pdo = DB::connection()->getPdo();
    echo "✅ Database connection successful!\n\n";
    
    // Run migrations
    echo "🔄 Running database migrations...\n";
    Artisan::call('migrate', ['--force' => true]);
    echo Artisan::output();
    
    // Run seeders
    echo "\n🌱 Running database seeders...\n";
    Artisan::call('db:seed', ['--force' => true]);
    echo Artisan::output();
    
    echo "\n✅ Database installation completed successfully!\n";
    echo "🎉 SkulSoft is now ready to use!\n\n";
    
    echo "Next steps:\n";
    echo "1. Start the development server: php artisan serve\n";
    echo "2. Visit: http://localhost:8000\n";
    echo "3. Login with admin credentials\n\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "\n📋 Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
