<?php

namespace Mint\Service\Actions;

use App\Helpers\SysHelper;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;

class UpdateApp
{
    public function execute($data = array()): void
    {
        $build = Arr::get($data, 'product.next_release_build');
        $version = Arr::get($data, 'product.next_release_version');

        $zip = new \ZipArchive;
        if (!$zip) {
            throw ValidationException::withMessages(['message' => 'Zip extension missing.']);
        }

        $updateFileName = base_path($build . '-' . $version . '.zip');

        if (!\File::exists($updateFileName)) {
            throw ValidationException::withMessages(['message' => 'Update file doesn\'t exist.']);
        }

        \File::copyDirectory(public_path('build'), public_path('build-' . date('YmdHis')));
        \File::deleteDirectory(public_path('build'));

        if ($zip->open($updateFileName) === TRUE) {
            $zip->extractTo(base_path());
            $zip->close();
        } else {
            unlink($updateFileName);
            throw ValidationException::withMessages(['message' => 'Zip file corrupted.']);
        }

        if (\File::exists(base_path('database/update/pre-update-' . $version . '.sql'))) {
            \DB::unprepared(\File::get(base_path('database/update/pre-update-' . $version . '.sql')));
        }

        \Artisan::call('migrate', ['--force' => true]);

        if (\File::exists(base_path('database/update/post-update-' . $version . '.sql'))) {
            \DB::unprepared(\File::get(base_path('database/update/post-update-' . $version . '.sql')));
        }

        \Artisan::call('sync:role', ['--force' => true]);
        \Artisan::call('sync:permission', ['--force' => true]);
        \Artisan::call('sync:template', ['--force' => true]);

        SysHelper::setApp(['VERSION' => $version]);

        // \Artisan::call('optimize:clear');

        unlink($updateFileName);
    }
}
