<?php

namespace App\Support;

trait TestSymlink
{
    public function createSymlink(): bool
    {
        $testTarget = storage_path('app/public/symlink_test');
        $testLink = public_path('symlink_test');

        try {
            // Clean up any existing test directories
            if (file_exists($testTarget)) {
                is_dir($testTarget) ? rmdir($testTarget) : unlink($testTarget);
            }
            if (file_exists($testLink)) {
                is_dir($testLink) ? rmdir($testLink) : unlink($testLink);
            }

            // Create test directory
            mkdir($testTarget, 0755, true);

            // Try to create symlink
            if (@symlink($testTarget, $testLink)) {
                // Clean up
                rmdir($testTarget);
                unlink($testLink);

                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Symlink test failed: '.$e->getMessage());
        }

        // Clean up in case of failure
        if (file_exists($testTarget)) {
            is_dir($testTarget) ? rmdir($testTarget) : unlink($testTarget);
        }
        if (file_exists($testLink)) {
            is_dir($testLink) ? rmdir($testLink) : unlink($testLink);
        }

        return false;
    }
}
