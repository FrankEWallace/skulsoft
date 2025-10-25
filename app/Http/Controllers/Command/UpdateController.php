<?php

namespace App\Http\Controllers\Command;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __invoke(Request $request)
    {
        $file = $request->query('file');
        if (! $file) {
            return 'File is required.';
        }

        $zip = new \ZipArchive;
        if (! $zip) {
            return 'Zip extension missing.';
        }

        $updateFileName = base_path($file.'.zip');

        if (! \File::exists($updateFileName)) {
            return 'Update file doesn\'t exist.';
        }

        \File::copyDirectory(public_path('build'), public_path('build-'.date('YmdHis')));
        \File::deleteDirectory(public_path('build'));

        if ($zip->open($updateFileName) === true) {
            $zip->extractTo(base_path());
            $zip->close();
        } else {
            unlink($updateFileName);

            return 'Zip file corrupted.';
        }

        if (\File::exists(base_path('database/update/pre-update-'.$file.'.sql'))) {
            \DB::unprepared(\File::get(base_path('database/update/pre-update-'.$file.'.sql')));
        }

        \Artisan::call('migrate', ['--force' => true]);

        if (\File::exists(base_path('database/update/post-update-'.$file.'.sql'))) {
            \DB::unprepared(\File::get(base_path('database/update/post-update-'.$file.'.sql')));
        }

        \Artisan::call('sync:role', ['--force' => true]);
        \Artisan::call('sync:permission', ['--force' => true]);
        \Artisan::call('sync:template', ['--force' => true]);

        unlink($updateFileName);

        return view('index', ['message' => 'Update complete.']);
    }
}
