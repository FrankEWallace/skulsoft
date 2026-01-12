<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== SkulSoft Logo & Favicon Setup ===\n\n";

// Define logo file paths
$logoFiles = [
    'logo' => 'public/images/skulsoft-logo.png',
    'logo_light' => 'public/images/skulsoft-logo-light.png',
    'icon' => 'public/images/skulsoft-icon.png',
    'favicon' => 'public/images/skulsoft-favicon.png',
    'favicon_ico' => 'public/images/favicon.ico',
];

// Check if logo files exist
echo "Checking for logo files...\n\n";
$filesExist = true;

foreach ($logoFiles as $key => $path) {
    if (file_exists($path)) {
        echo "✅ Found: $path\n";
    } else {
        echo "⚠️  Missing: $path\n";
        if ($key !== 'favicon_ico') {
            $filesExist = false;
        }
    }
}

if (!$filesExist) {
    echo "\n❌ Please add your logo files first!\n";
    echo "\nQuick setup:\n";
    echo "  1. Save your logo as: public/images/skulsoft-logo.png\n";
    echo "  2. Copy it to: public/images/skulsoft-logo-light.png\n";
    echo "  3. Create square version: public/images/skulsoft-icon.png (512x512)\n";
    echo "  4. Create favicon: public/images/skulsoft-favicon.png (256x256)\n";
    echo "  5. Run this script again\n\n";
    
    echo "OR use existing files:\n";
    
    // Option to use uploaded file if exists
    if (file_exists('public/images/logo.png')) {
        echo "\nFound existing logo.png. Use it? (yes/no): ";
        $handle = fopen ("php://stdin","r");
        $line = trim(fgets($handle));
        
        if(strtolower($line) === 'yes' || strtolower($line) === 'y'){
            copy('public/images/logo.png', 'public/images/skulsoft-logo.png');
            copy('public/images/logo.png', 'public/images/skulsoft-logo-light.png');
            copy('public/images/icon.png', 'public/images/skulsoft-icon.png');
            copy('public/images/favicon.png', 'public/images/skulsoft-favicon.png');
            echo "✅ Copied existing files!\n";
            $filesExist = true;
        }
    }
    
    if (!$filesExist) {
        exit(1);
    }
}

echo "\n=== Updating Database Configuration ===\n\n";

// Update logo configuration in database
try {
    // Update logo
    $updated = DB::table('configs')
        ->where('name', 'logo')
        ->update(['value' => json_encode('/images/skulsoft-logo.png')]);
    
    if ($updated) {
        echo "✅ Updated main logo path\n";
    } else {
        // Insert if doesn't exist
        DB::table('configs')->insert([
            'name' => 'logo',
            'value' => json_encode('/images/skulsoft-logo.png'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "✅ Created main logo configuration\n";
    }
    
    // Update light logo
    $updated = DB::table('configs')
        ->where('name', 'logo_light')
        ->update(['value' => json_encode('/images/skulsoft-logo-light.png')]);
    
    if ($updated) {
        echo "✅ Updated light logo path\n";
    } else {
        DB::table('configs')->insert([
            'name' => 'logo_light',
            'value' => json_encode('/images/skulsoft-logo-light.png'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "✅ Created light logo configuration\n";
    }
    
    // Update icon
    $updated = DB::table('configs')
        ->where('name', 'icon')
        ->update(['value' => json_encode('/images/skulsoft-icon.png')]);
    
    if ($updated) {
        echo "✅ Updated app icon path\n";
    } else {
        DB::table('configs')->insert([
            'name' => 'icon',
            'value' => json_encode('/images/skulsoft-icon.png'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "✅ Created app icon configuration\n";
    }
    
    // Update favicon
    $updated = DB::table('configs')
        ->where('name', 'favicon')
        ->update(['value' => json_encode('/images/skulsoft-favicon.png')]);
    
    if ($updated) {
        echo "✅ Updated favicon path\n";
    } else {
        DB::table('configs')->insert([
            'name' => 'favicon',
            'value' => json_encode('/images/skulsoft-favicon.png'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        echo "✅ Created favicon configuration\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error updating configuration: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\n=== Clearing Caches ===\n\n";

Artisan::call('config:clear');
echo "✅ Config cache cleared\n";

Artisan::call('cache:clear');
echo "✅ Application cache cleared\n";

Artisan::call('view:clear');
echo "✅ View cache cleared\n";

echo "\n=== Setup Complete! ===\n\n";
echo "Your SkulSoft logo and favicon have been configured.\n\n";
echo "Files in use:\n";
echo "  - Logo: /images/skulsoft-logo.png\n";
echo "  - Logo (Light): /images/skulsoft-logo-light.png\n";
echo "  - App Icon: /images/skulsoft-icon.png\n";
echo "  - Favicon: /images/skulsoft-favicon.png\n\n";
echo "Refresh your browser (Ctrl+Shift+R / Cmd+Shift+R) to see changes!\n\n";
