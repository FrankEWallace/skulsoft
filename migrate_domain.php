#!/usr/bin/env php
<?php

/**
 * SkulSoft Domain Migration Script
 * 
 * This script helps migrate code from flat structure to domain-based structure
 * 
 * Usage: php migrate_domain.php <domain> [--dry-run]
 * Example: php migrate_domain.php Academic --dry-run
 */

if (!isset($argv[1])) {
    echo "❌ Error: Domain name required\n";
    echo "Usage: php migrate_domain.php <domain> [--dry-run]\n";
    echo "Available domains: Academic, Finance, Student, Employee, Communication, Library, Transport, Hostel, Exam, Inventory\n";
    exit(1);
}

$domain = ucfirst($argv[1]);
$dryRun = in_array('--dry-run', $argv);

$availableDomains = ['Academic', 'Finance', 'Student', 'Employee', 'Communication', 'Library', 'Transport', 'Hostel', 'Exam', 'Inventory'];

if (!in_array($domain, $availableDomains)) {
    echo "❌ Error: Invalid domain '{$domain}'\n";
    echo "Available domains: " . implode(', ', $availableDomains) . "\n";
    exit(1);
}

echo "🚀 SkulSoft Domain Migration Script\n";
echo "====================================\n\n";
echo "📦 Migrating: {$domain} Domain\n";

if ($dryRun) {
    echo "🔍 DRY RUN MODE - No changes will be made\n";
}

echo "\n";

class DomainMigration
{
    private string $domain;
    private bool $dryRun;
    private array $log = [];
    private array $movedFiles = [];
    private array $namespaceChanges = [];
    
    public function __construct(string $domain, bool $dryRun = false)
    {
        $this->domain = $domain;
        $this->dryRun = $dryRun;
    }
    
    public function migrate(): void
    {
        echo "Step 1: Analyzing current structure...\n";
        echo "=====================================\n\n";
        
        $this->analyzeCurrentStructure();
        
        echo "\nStep 2: Moving Model files...\n";
        echo "============================\n\n";
        
        $this->migrateModels();
        
        echo "\nStep 3: Updating namespaces...\n";
        echo "==============================\n\n";
        
        $this->updateNamespaces();
        
        echo "\nStep 4: Finding and updating imports...\n";
        echo "=======================================\n\n";
        
        $this->updateImports();
        
        echo "\nStep 5: Verification...\n";
        echo "======================\n\n";
        
        $this->verify();
    }
    
    private function analyzeCurrentStructure(): void
    {
        $modelPath = "app/Models/{$this->domain}";
        
        if (!is_dir($modelPath)) {
            echo "  ⚠️  No {$this->domain} models found in app/Models/{$this->domain}/\n";
            echo "      This is normal if models are already in other locations.\n";
            return;
        }
        
        $files = glob($modelPath . '/*.php');
        
        echo "  📁 Found " . count($files) . " model file(s) in app/Models/{$this->domain}/\n\n";
        
        foreach ($files as $file) {
            $filename = basename($file);
            echo "      • {$filename}\n";
        }
    }
    
    private function migrateModels(): void
    {
        $sourcePath = "app/Models/{$this->domain}";
        $targetPath = "app/Domain/{$this->domain}/Models";
        
        if (!is_dir($sourcePath)) {
            echo "  ℹ️  No models to migrate from app/Models/{$this->domain}/\n";
            return;
        }
        
        $files = glob($sourcePath . '/*.php');
        
        foreach ($files as $file) {
            $filename = basename($file);
            $targetFile = $targetPath . '/' . $filename;
            
            echo "  📄 Moving: {$filename}\n";
            echo "      From: {$file}\n";
            echo "      To:   {$targetFile}\n";
            
            if (!$this->dryRun) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
                
                // Read file content
                $content = file_get_contents($file);
                
                // Update namespace
                $oldNamespace = "App\\Models\\{$this->domain}";
                $newNamespace = "App\\Domain\\{$this->domain}\\Models";
                $content = str_replace("namespace {$oldNamespace};", "namespace {$newNamespace};", $content);
                
                // Write to new location
                file_put_contents($targetFile, $content);
                
                // Delete old file
                unlink($file);
                
                $this->movedFiles[] = [
                    'from' => $file,
                    'to' => $targetFile,
                    'class' => pathinfo($filename, PATHINFO_FILENAME)
                ];
                
                $this->namespaceChanges[$oldNamespace] = $newNamespace;
            }
            
            $this->log[] = "Moved: {$filename}";
        }
        
        // Remove empty directory
        if (!$this->dryRun && is_dir($sourcePath) && count(scandir($sourcePath)) === 2) {
            rmdir($sourcePath);
            echo "\n  🗑️  Removed empty directory: {$sourcePath}\n";
        }
    }
    
    private function updateNamespaces(): void
    {
        if (empty($this->namespaceChanges)) {
            echo "  ℹ️  No namespace changes to apply\n";
            return;
        }
        
        foreach ($this->namespaceChanges as $old => $new) {
            echo "  ✏️  {$old} → {$new}\n";
        }
    }
    
    private function updateImports(): void
    {
        if (empty($this->movedFiles)) {
            echo "  ℹ️  No files moved, no imports to update\n";
            return;
        }
        
        echo "  🔍 Scanning codebase for import updates...\n\n";
        
        $directories = [
            'app/Http/Controllers',
            'app/Services',
            'app/Actions',
            'app/Livewire',
            'app/Policies',
            'app/Observers',
        ];
        
        $filesToUpdate = [];
        
        foreach ($directories as $dir) {
            if (!is_dir($dir)) continue;
            
            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir),
                RecursiveIteratorIterator::SELF_FIRST
            );
            
            foreach ($files as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $content = file_get_contents($file->getPathname());
                    $needsUpdate = false;
                    
                    foreach ($this->movedFiles as $movedFile) {
                        $oldImport = "use App\\Models\\{$this->domain}\\{$movedFile['class']};";
                        $newImport = "use App\\Domain\\{$this->domain}\\Models\\{$movedFile['class']};";
                        
                        if (strpos($content, $oldImport) !== false) {
                            $needsUpdate = true;
                            
                            if (!$this->dryRun) {
                                $content = str_replace($oldImport, $newImport, $content);
                            }
                        }
                    }
                    
                    if ($needsUpdate) {
                        $relativePath = str_replace(getcwd() . '/', '', $file->getPathname());
                        $filesToUpdate[] = $relativePath;
                        
                        if (!$this->dryRun) {
                            file_put_contents($file->getPathname(), $content);
                        }
                    }
                }
            }
        }
        
        if (empty($filesToUpdate)) {
            echo "  ✅ No import updates needed\n";
        } else {
            echo "  📝 Updated imports in " . count($filesToUpdate) . " file(s):\n\n";
            foreach ($filesToUpdate as $file) {
                echo "      • {$file}\n";
            }
        }
    }
    
    private function verify(): void
    {
        echo "  🔍 Running verification checks...\n\n";
        
        // Check if old directory is empty
        $oldPath = "app/Models/{$this->domain}";
        if (is_dir($oldPath)) {
            echo "  ⚠️  Warning: Old directory still exists: {$oldPath}\n";
        } else {
            echo "  ✅ Old directory removed successfully\n";
        }
        
        // Check if new directory has files
        $newPath = "app/Domain/{$this->domain}/Models";
        if (is_dir($newPath)) {
            $files = glob($newPath . '/*.php');
            echo "  ✅ New domain location has " . count($files) . " file(s)\n";
        }
        
        // Suggest running composer dump-autoload
        echo "\n  💡 Next steps:\n";
        echo "      1. Run: composer dump-autoload\n";
        echo "      2. Run: php artisan optimize:clear\n";
        echo "      3. Test the application\n";
    }
    
    public function showLog(): void
    {
        echo "\n📋 Migration Log\n";
        echo "================\n";
        
        if (empty($this->log)) {
            echo "  No changes made\n";
            return;
        }
        
        foreach ($this->log as $entry) {
            echo "  • {$entry}\n";
        }
        echo "\n";
    }
}

// Run migration
$migration = new DomainMigration($domain, $dryRun);
$migration->migrate();
$migration->showLog();

echo "✨ Migration Complete!\n\n";

if (!$dryRun) {
    echo "📌 Don't forget to:\n";
    echo "   1. Run: composer dump-autoload\n";
    echo "   2. Run: php artisan optimize:clear\n";
    echo "   3. Test the {$domain} domain functionality\n";
    echo "   4. Commit changes: git add . && git commit -m \"Migrate {$domain} domain\"\n\n";
}
