#!/usr/bin/env php
<?php

/**
 * SkulSoft Laravel Framework Restructuring Script
 * 
 * This script helps reorganize the project to follow Laravel 11 best practices
 * 
 * Usage: php restructure.php [--dry-run] [--phase=1]
 */

$dryRun = in_array('--dry-run', $argv);
$phase = null;

foreach ($argv as $arg) {
    if (strpos($arg, '--phase=') === 0) {
        $phase = (int) substr($arg, 8);
    }
}

echo "🚀 SkulSoft Laravel 11 Restructuring Script\n";
echo "==========================================\n\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No changes will be made\n\n";
}

class Restructure
{
    private bool $dryRun;
    private array $log = [];
    
    public function __construct(bool $dryRun = false)
    {
        $this->dryRun = $dryRun;
    }
    
    public function phase1(): void
    {
        echo "📦 Phase 1: Clean Up Root Directory\n";
        echo "====================================\n\n";
        
        // Remove temporary files
        $this->removeFile('fix_admin_school.php', 'Temporary admin fix script');
        $this->removeFile('fix_admin_school.sql', 'Temporary SQL file');
        $this->removeFile('create_database.php', 'Database creation script (use migrations)');
        $this->removeFile('install_database.php', 'Installation script');
        
        // Create routes/features directory
        $this->createDirectory('routes/features', 'Feature-based route organization');
        
        echo "\n✅ Phase 1 Complete\n\n";
    }
    
    public function phase2(): void
    {
        echo "🗺️  Phase 2: Reorganize Route Files\n";
        echo "====================================\n\n";
        
        // Move feature routes
        $routesToMove = [
            'chat.php' => 'Communication routes',
            'export.php' => 'Export functionality',
            'gateway.php' => 'Payment gateway routes',
            'integration.php' => 'Third-party integrations',
            'report.php' => 'Reporting routes',
        ];
        
        foreach ($routesToMove as $file => $description) {
            $this->moveFile(
                "routes/{$file}",
                "routes/features/{$file}",
                $description
            );
        }
        
        // Create route organization file
        $this->createWebRoutesStructure();
        
        echo "\n✅ Phase 2 Complete\n\n";
    }
    
    public function phase3(): void
    {
        echo "🏗️  Phase 3: Create Domain Structure\n";
        echo "====================================\n\n";
        
        $domains = [
            'Academic' => 'Academic management domain',
            'Finance' => 'Finance and fee management',
            'Student' => 'Student management',
            'Employee' => 'Employee/Staff management',
            'Communication' => 'Communication features',
            'Library' => 'Library management',
            'Transport' => 'Transport management',
            'Hostel' => 'Hostel management',
            'Exam' => 'Examination management',
            'Inventory' => 'Inventory management',
        ];
        
        foreach ($domains as $domain => $description) {
            $this->createDomainStructure($domain, $description);
        }
        
        // Move billdesk to Finance/Gateways
        $this->createDirectory('app/Domain/Finance/Gateways', 'Payment gateway services');
        $this->moveDirectory(
            'billdesk',
            'app/Domain/Finance/Gateways/BillDesk',
            'BillDesk payment gateway'
        );
        
        echo "\n✅ Phase 3 Complete\n\n";
    }
    
    public function phase4(): void
    {
        echo "⚡ Phase 4: Organize Livewire Components\n";
        echo "========================================\n\n";
        
        echo "ℹ️  Livewire components should be organized by feature.\n";
        echo "   Use: php artisan livewire:move OldComponent Feature/NewComponent\n\n";
        
        // Create suggested directories
        $livewireDirs = [
            'Academic',
            'Finance',
            'Student',
            'Employee',
            'Communication',
            'Shared',
        ];
        
        foreach ($livewireDirs as $dir) {
            $this->createDirectory("app/Livewire/{$dir}", "{$dir} Livewire components");
        }
        
        echo "\n✅ Phase 4 Complete\n\n";
    }
    
    public function phase5(): void
    {
        echo "📝 Phase 5: Update Configuration\n";
        echo "=================================\n\n";
        
        // Update composer.json autoload
        $this->updateComposerAutoload();
        
        // Create helpers file
        $this->createHelpersFile();
        
        echo "\n✅ Phase 5 Complete\n\n";
    }
    
    private function createDomainStructure(string $domain, string $description): void
    {
        echo "Creating domain: {$domain}\n";
        
        $directories = [
            "app/Domain/{$domain}/Models",
            "app/Domain/{$domain}/Services",
            "app/Domain/{$domain}/Actions",
            "app/Domain/{$domain}/Policies",
            "app/Domain/{$domain}/QueryFilters",
            "app/Domain/{$domain}/Events",
            "app/Domain/{$domain}/Listeners",
        ];
        
        foreach ($directories as $dir) {
            $this->createDirectory($dir, $description);
        }
        
        // Create .gitkeep files
        foreach ($directories as $dir) {
            $gitkeepPath = $dir . '/.gitkeep';
            if (!$this->dryRun) {
                @file_put_contents($gitkeepPath, '');
            }
        }
    }
    
    private function createDirectory(string $path, string $description): void
    {
        echo "  📁 Creating: {$path}\n";
        echo "      → {$description}\n";
        
        if (!$this->dryRun && !is_dir($path)) {
            mkdir($path, 0755, true);
        }
        
        $this->log[] = "Created directory: {$path}";
    }
    
    private function moveFile(string $from, string $to, string $description): void
    {
        echo "  📄 Moving: {$from} → {$to}\n";
        echo "      → {$description}\n";
        
        if (!$this->dryRun && file_exists($from)) {
            $dir = dirname($to);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            rename($from, $to);
        }
        
        $this->log[] = "Moved file: {$from} → {$to}";
    }
    
    private function moveDirectory(string $from, string $to, string $description): void
    {
        echo "  📂 Moving: {$from} → {$to}\n";
        echo "      → {$description}\n";
        
        if (!$this->dryRun && is_dir($from)) {
            $dir = dirname($to);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            rename($from, $to);
        }
        
        $this->log[] = "Moved directory: {$from} → {$to}";
    }
    
    private function removeFile(string $path, string $description): void
    {
        if (!file_exists($path)) {
            echo "  ⏭️  Skipping: {$path} (not found)\n";
            return;
        }
        
        echo "  🗑️  Removing: {$path}\n";
        echo "      → {$description}\n";
        
        if (!$this->dryRun) {
            unlink($path);
        }
        
        $this->log[] = "Removed file: {$path}";
    }
    
    private function createWebRoutesStructure(): void
    {
        $content = <<<'PHP'
<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application.
| These routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group.
|
*/

// Guest routes
require __DIR__.'/guest.php';

// Authentication routes
require __DIR__.'/auth.php';

// Site/Public routes
require __DIR__.'/site.php';

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {
    
    // Main application routes
    require __DIR__.'/app.php';
    
    // Module routes
    require __DIR__.'/module.php';
    
    // Feature-based routes
    Route::prefix('chat')->group(base_path('routes/features/chat.php'));
    Route::prefix('export')->group(base_path('routes/features/export.php'));
    Route::prefix('reports')->group(base_path('routes/features/report.php'));
    
    // Payment gateway routes
    Route::prefix('gateway')->group(base_path('routes/features/gateway.php'));
    
    // Integration routes
    Route::prefix('integration')->group(base_path('routes/features/integration.php'));
});

PHP;

        echo "  📝 Creating consolidated web.php\n";
        
        if (!$this->dryRun) {
            file_put_contents('routes/web.php.new', $content);
            echo "      → Saved as routes/web.php.new (review before replacing)\n";
        }
    }
    
    private function updateComposerAutoload(): void
    {
        echo "  📦 Updating composer.json autoload section\n";
        
        $composerPath = 'composer.json';
        if (!file_exists($composerPath)) {
            echo "      ⚠️  composer.json not found\n";
            return;
        }
        
        $composer = json_decode(file_get_contents($composerPath), true);
        
        // Add Domain namespace
        $composer['autoload']['psr-4']['App\\Domain\\'] = 'app/Domain/';
        
        // Add helpers file
        if (!isset($composer['autoload']['files'])) {
            $composer['autoload']['files'] = [];
        }
        
        if (!in_array('app/Support/helpers.php', $composer['autoload']['files'])) {
            $composer['autoload']['files'][] = 'app/Support/helpers.php';
        }
        
        if (!$this->dryRun) {
            file_put_contents(
                $composerPath . '.new',
                json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            echo "      → Saved as composer.json.new (review before replacing)\n";
        }
    }
    
    private function createHelpersFile(): void
    {
        $content = <<<'PHP'
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

PHP;

        echo "  📝 Creating app/Support/helpers.php\n";
        
        if (!$this->dryRun) {
            $dir = 'app/Support';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            file_put_contents($dir . '/helpers.php', $content);
        }
    }
    
    public function showLog(): void
    {
        echo "\n📋 Change Log\n";
        echo "=============\n";
        foreach ($this->log as $entry) {
            echo "  • {$entry}\n";
        }
        echo "\n";
    }
}

// Run the restructuring
$restructure = new Restructure($dryRun);

if ($phase) {
    echo "Running Phase {$phase} only...\n\n";
    $method = "phase{$phase}";
    if (method_exists($restructure, $method)) {
        $restructure->$method();
    } else {
        echo "❌ Invalid phase number: {$phase}\n";
        exit(1);
    }
} else {
    echo "Running all phases...\n\n";
    $restructure->phase1();
    $restructure->phase2();
    $restructure->phase3();
    $restructure->phase4();
    $restructure->phase5();
}

$restructure->showLog();

echo "✨ Restructuring Complete!\n\n";

echo "📌 Next Steps:\n";
echo "==============\n";
echo "1. Review all changes (especially .new files)\n";
echo "2. Run: composer dump-autoload\n";
echo "3. Run: php artisan optimize:clear\n";
echo "4. Test the application thoroughly\n";
echo "5. Commit changes to git\n\n";

echo "📖 For detailed guide, see: LARAVEL_RESTRUCTURING_GUIDE.md\n\n";
