<?php

namespace App\Support;

use App\Helpers\SysHelper;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

trait ServerPreRequisite
{
    /**
     * Check extension enabled or not
     */
    public function check(bool $boolean, string $message, string $help = '', bool $fatal = false): array
    {
        return ['key' => Str::uuid(), 'type' => ($boolean ? 'success' : 'error'), 'message' => ($boolean ? $message : $help)];
    }

    /**
     * Check whether pre requisites are fulfilled or not
     */
    public function getPreRequisite(): array
    {
        $server[] = $this->check((dirname($_SERVER['REQUEST_URI']) != '/' && str_replace('\\', '/', dirname($_SERVER['REQUEST_URI'])) != '/'), 'Installation directory is valid.', 'Please use root directory or point your sub directory to domain/subdomain to install.', true);
        $server[] = $this->check(SysHelper::versionComparison(phpversion(), '8.1.0', '>='), sprintf('Min PHP version 8.1.0 (%s)', 'Current Version '.phpversion()), 'Current Version '.phpversion().' < 8.1.0', true);
        $server[] = $this->check(extension_loaded('fileinfo'), 'Fileinfo PHP extension enabled.', 'Install and enable Fileinfo extension.', true);
        $server[] = $this->check(extension_loaded('openssl'), 'OpenSSL PHP extension enabled.', 'Install and enable OpenSSL extension.', true);
        $server[] = $this->check(extension_loaded('tokenizer'), 'Tokenizer PHP extension enabled.', 'Install and enable Tokenizer extension.', true);
        $server[] = $this->check(extension_loaded('mbstring'), 'Mbstring PHP extension enabled.', 'Install and enable Mbstring extension.', true);
        $server[] = $this->check(extension_loaded('zip'), 'Zip archive PHP extension enabled.', 'Install and enable Zip archive extension.', true);
        $server[] = $this->check(class_exists('PDO'), 'PDO is installed.', 'Install PDO (mandatory for Eloquent).', true);
        if (extension_loaded('curl')) {
            $server[] = $this->check(SysHelper::versionComparison(Arr::get(curl_version(), 'version'), '7.60.0', '>='), 'CURL version is up-to-date', 'Upgrade CURL version to atleast 7.60.0', true);
        } else {
            $server[] = 'Install and enable CURL v7.60.0.';
        }
        // $server[] = $this->check(ini_get('allow_url_fopen'), 'allow_url_fopen is on.', 'Turn on allow_url_fopen.', true);

        $folder[] = $this->check(is_writable('../.env'), 'File .env is writable', 'Folder .env is not writable', true);
        $folder[] = $this->check(is_writable('../storage/framework'), 'Folder /storage/framework is writable', 'Folder /storage/framework is not writable', true);
        $folder[] = $this->check(is_writable('../storage/logs'), 'Folder /storage/logs is writable', 'Folder /storage/logs is not writable', true);
        $folder[] = $this->check(is_writable('../bootstrap/cache'), 'Folder /bootstrap/cache is writable', 'Folder /bootstrap/cache is not writable', true);
        $folder[] = $this->check(is_writable('../lang'), 'Folder /lang is writable', 'Folder /lang is not writable', true);

        return [
            ['key' => 'server', 'title' => trans('setup.install.server_pre_requisite'), 'items' => $server],
            ['key' => 'folder', 'title' => trans('setup.install.folder_pre_requisite'), 'items' => $folder],
        ];
    }

    /**
     * Check database version
     */
    public function checkDbVersion(string $version): void
    {
        $mysql_required_version = '8.0.0';
        $mariadb_required_version = '10.2.7';

        if (Str::contains(strtolower($version), 'maria')) {
            $db = explode('-', $version);
            $db = $db[0] ?? '1.0.0';

            if (! SysHelper::versionComparison($db, $mariadb_required_version, '>=')) {
                throw ValidationException::withMessages(['message' => 'Please install MariaDB version >= '.$mariadb_required_version]);
            }
        } elseif (! SysHelper::versionComparison($version, $mysql_required_version, '>=')) {
            throw ValidationException::withMessages(['message' => 'Please install MySQL version >= '.$mysql_required_version]);
        }
    }
}
