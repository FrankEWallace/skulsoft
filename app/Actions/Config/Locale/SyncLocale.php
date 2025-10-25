<?php

namespace App\Actions\Config\Locale;

use App\Concerns\LocalStorage;

class SyncLocale
{
    use LocalStorage;

    protected $storage_key = 'locales';

    public function execute(string $locale)
    {
        $baseLocale = 'en';

        $modules = [];
        foreach (\File::allFiles(base_path('/lang/en')) as $file) {
            $modules[] = basename($file, '.php');
        }

        foreach ($modules as $module) {
            $file = base_path('/lang/'.$baseLocale.'/'.$module.'.php');
            $baseWords = \File::getRequire($file);

            $file = base_path('/lang/'.$locale.'/'.$module.'.php');

            if (! \File::exists($file)) {
                \File::put($file, var_export($baseWords, true));
                \File::prepend($file, '<?php return ');
                \File::append($file, ';');
            }

            $words = \File::getRequire($file);

            $newWords = array_merge_recursive_distinct($baseWords, $words);

            $file = base_path('/lang/'.$locale.'/'.$module.'.php');
            \File::put($file, var_export($newWords, true));
            \File::prepend($file, '<?php return ');
            \File::append($file, ';');
        }
    }
}
